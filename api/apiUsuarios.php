<?php

require_once __DIR__ . '/middleware/apiCommon.php';

require_once dirname(__DIR__) . '/config/conexao.php';
require_once dirname(__DIR__) . '/config/midiasPerfil.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

/*
|--------------------------------------------------------------------------
| POST COM ID - PERSONALIZAR FOTO E BANNER
|--------------------------------------------------------------------------
*/

if ($metodo === 'POST' && $id !== null) {
    require_once __DIR__ . '/middleware/verificaLogin.php';

    $idLogado = exigirLogin();
    if ($idLogado !== $id) {
        responder(['erro' => 'Você só pode personalizar o próprio perfil'], 403);
    }

    $tipoConteudo = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
    if (!str_starts_with($tipoConteudo, 'multipart/form-data')) {
        responder(['erro' => 'Envie a personalização como multipart/form-data'], 400);
    }

    if (!isset($_POST['acao']) || !is_string($_POST['acao']) || $_POST['acao'] !== 'personalizar_midia') {
        responder(['erro' => 'Ação de personalização inválida'], 400);
    }

    try {
        validarTamanhoTotalUpload(22 * 1024 * 1024);
        $arquivosPerfil = normalizarArquivosUpload($_FILES['foto_perfil'] ?? null);
        $arquivosBanner = normalizarArquivosUpload($_FILES['foto_banner'] ?? null);
    } catch (UploadInvalidoException $erro) {
        responder(['erro' => $erro->getMessage()], 400);
    }

    if (count($arquivosPerfil) > 1 || count($arquivosBanner) > 1) {
        responder(['erro' => 'Envie no máximo uma foto de perfil e um banner'], 400);
    }

    $perfilPronto = isset($_POST['foto_perfil_pronta']) && is_string($_POST['foto_perfil_pronta'])
        ? trim($_POST['foto_perfil_pronta'])
        : '';
    $bannerPronto = isset($_POST['foto_banner_pronta']) && is_string($_POST['foto_banner_pronta'])
        ? trim($_POST['foto_banner_pronta'])
        : '';

    if ($arquivosPerfil !== [] && $perfilPronto !== '') {
        responder(['erro' => 'Escolha entre upload ou imagem pronta para a foto de perfil'], 400);
    }

    if ($arquivosBanner !== [] && $bannerPronto !== '') {
        responder(['erro' => 'Escolha entre upload ou imagem pronta para o banner'], 400);
    }

    if ($perfilPronto !== '' && !midiaPerfilProntaPermitida('foto_perfil', $perfilPronto)) {
        responder(['erro' => 'A foto de perfil pronta selecionada não é permitida'], 400);
    }

    if ($bannerPronto !== '' && !midiaPerfilProntaPermitida('foto_banner', $bannerPronto)) {
        responder(['erro' => 'O banner pronto selecionado não é permitido'], 400);
    }

    if ($arquivosPerfil === [] && $arquivosBanner === [] && $perfilPronto === '' && $bannerPronto === '') {
        responder(['erro' => 'Selecione uma foto de perfil ou um banner'], 400);
    }

    $stmt = $conn->prepare(
        'SELECT foto_perfil, foto_banner
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $midiaAtual = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$midiaAtual) {
        responder(['erro' => 'Usuário não encontrado'], 404);
    }

    $novaFotoPerfil = $midiaAtual['foto_perfil'];
    $novoBanner = $midiaAtual['foto_banner'];
    $novosUploads = [];
    $transacaoIniciada = false;

    try {
        if ($arquivosPerfil !== []) {
            $salva = salvarImagemRedimensionadaUpload($arquivosPerfil[0], 'perfis', 500, 500);
            $novaFotoPerfil = $salva['caminho_arquivo'];
            $novosUploads[] = $novaFotoPerfil;
        } elseif ($perfilPronto !== '') {
            $novaFotoPerfil = $perfilPronto;
        }

        if ($arquivosBanner !== []) {
            $salva = salvarImagemRedimensionadaUpload($arquivosBanner[0], 'banners', 1600, 400);
            $novoBanner = $salva['caminho_arquivo'];
            $novosUploads[] = $novoBanner;
        } elseif ($bannerPronto !== '') {
            $novoBanner = $bannerPronto;
        }

        $conn->begin_transaction();
        $transacaoIniciada = true;

        $stmt = $conn->prepare(
            'UPDATE usuario
             SET foto_perfil = ?, foto_banner = ?
             WHERE id_user = ?'
        );
        $stmt->bind_param('ssi', $novaFotoPerfil, $novoBanner, $id);
        executarStatementApi($stmt);
        $stmt->close();

        $conn->commit();
        $transacaoIniciada = false;
    } catch (UploadInvalidoException $erro) {
        if ($transacaoIniciada) {
            $conn->rollback();
        }
        foreach ($novosUploads as $caminho) {
            removerArquivoUpload($caminho);
        }
        responder(['erro' => $erro->getMessage()], 400);
    } catch (Throwable $erro) {
        if ($transacaoIniciada) {
            $conn->rollback();
        }
        foreach ($novosUploads as $caminho) {
            removerArquivoUpload($caminho);
        }
        throw $erro;
    }

    $anteriores = [];
    if ($midiaAtual['foto_perfil'] && $midiaAtual['foto_perfil'] !== $novaFotoPerfil) {
        $anteriores[] = $midiaAtual['foto_perfil'];
    }
    if ($midiaAtual['foto_banner'] && $midiaAtual['foto_banner'] !== $novoBanner) {
        $anteriores[] = $midiaAtual['foto_banner'];
    }
    removerArquivosUploadSemReferencia($conn, $anteriores);

    $stmt = $conn->prepare(
        'SELECT id_user, nome_user, sobrenome, email_user, tipo_usuario,
                foto_perfil, foto_banner, data_cadastro
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $usuarioAtualizado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    responder([
        'mensagem' => 'Foto e banner atualizados com sucesso',
        'usuario' => normalizarUsuarioApi($usuarioAtualizado),
    ]);
}

/*
|--------------------------------------------------------------------------
| POST - CADASTRO
| NÃO PRECISA DE LOGIN
|--------------------------------------------------------------------------
*/

if ($metodo === 'POST') {

    if ($id !== null) {
        responder(['erro' => 'O cadastro não recebe ID na URL'], 400);
    }

    $dados = lerJson(true);

    $nome = trim((string) ($dados['nome'] ?? ''));
    $sobrenome = trim((string) ($dados['sobrenome'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');

    if ($nome === '' || $sobrenome === '') {
        responder([
            'erro' => 'Nome e sobrenome são obrigatórios'
        ], 400);
    }

    $erroLimite = validarLimitesTextoApi([
        'nome' => $nome,
        'sobrenome' => $sobrenome,
        'email' => $email,
    ], [
        'nome' => 100,
        'sobrenome' => 100,
        'email' => 100,
    ]);

    if ($erroLimite !== null) {
        responder(['erro' => $erroLimite], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder([
            'erro' => 'E-mail inválido'
        ], 400);
    }

    if (strlen($senha) < 6) {
        responder([
            'erro' => 'A senha deve ter pelo menos 6 caracteres'
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICA SE O E-MAIL JÁ EXISTE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT id_user
         FROM usuario
         WHERE email_user = ?
         LIMIT 1'
    );

    if (!$stmt) {
        responderErroInfraestrutura(
            'Falha ao preparar consulta de e-mail',
            new RuntimeException($conn->error)
        );
    }

    $stmt->bind_param('s', $email);
    executarStatementApi($stmt);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        responder([
            'erro' => 'Este e-mail já está cadastrado'
        ], 409);
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | CRIPTOGRAFA A SENHA
    |--------------------------------------------------------------------------
    */

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    /*
    |--------------------------------------------------------------------------
    | INSERE USUÁRIO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO usuario
        (nome_user, sobrenome, email_user, senha_user, tipo_usuario)
        VALUES (?, ?, ?, ?, 'comum')"
    );

    if (!$stmt) {
        responderErroInfraestrutura(
            'Falha ao preparar cadastro de usuário',
            new RuntimeException($conn->error)
        );
    }

    $stmt->bind_param(
        'ssss',
        $nome,
        $sobrenome,
        $email,
        $senhaHash
    );

    executarStatementApi($stmt);

    $novoId = $conn->insert_id;

    $stmt->close();

    $usuarioCriado = normalizarUsuarioApi([
        'id_user' => $novoId,
        'nome_user' => $nome,
        'sobrenome' => $sobrenome,
        'email_user' => $email,
        'tipo_usuario' => 'comum',
        'foto_perfil' => null,
        'foto_banner' => null
    ]);

    responder([
        'mensagem' => 'Usuário cadastrado com sucesso',
        'usuario' => $usuarioCriado
    ], 201);
}

/*
|--------------------------------------------------------------------------
| A PARTIR DAQUI, AS OPERAÇÕES PRECISAM DE LOGIN
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/middleware/verificaLogin.php';

/*
|--------------------------------------------------------------------------
| GET - CONSULTAR USUÁRIO
|--------------------------------------------------------------------------
*/

if ($metodo === 'GET') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';

    if ($idLogado !== $id && !$ehAdmin) {
        responder([
            'erro' => 'Você só pode consultar o próprio perfil'
        ], 403);
    }

    $stmt = $conn->prepare(
        'SELECT u.id_user, u.nome_user, u.sobrenome, u.email_user,
                u.tipo_usuario, u.foto_perfil, u.foto_banner, u.data_cadastro,
                (SELECT COUNT(*) FROM rota r WHERE r.id_user = u.id_user) AS viagens_planejadas,
                (SELECT COUNT(*)
                   FROM evento e
                   INNER JOIN solicitacao s
                           ON s.id_solicitacao = e.id_solicitacao_origem
                  WHERE s.id_user = u.id_user) AS eventos_cadastrados,
                ((SELECT COUNT(*)
                    FROM comunidade_post cp
                   WHERE cp.id_usuario = u.id_user)
                 +
                 (SELECT COUNT(*)
                    FROM comunidade_midia cm
                   WHERE cm.id_usuario = u.id_user)) AS publicacoes_comunidade
         FROM usuario u
         WHERE u.id_user = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);

    $usuario = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$usuario) {
        responder([
            'erro' => 'Usuário não encontrado'
        ], 404);
    }

    $estatisticas = [
        'viagens_planejadas' => (int) $usuario['viagens_planejadas'],
        'eventos_cadastrados' => (int) $usuario['eventos_cadastrados'],
        'publicacoes_comunidade' => (int) $usuario['publicacoes_comunidade'],
    ];

    unset(
        $usuario['viagens_planejadas'],
        $usuario['eventos_cadastrados'],
        $usuario['publicacoes_comunidade']
    );

    responder([
        'usuario' => normalizarUsuarioApi($usuario),
        'estatisticas' => $estatisticas,
    ]);
}

/*
|--------------------------------------------------------------------------
| PUT - EDITAR PERFIL
|--------------------------------------------------------------------------
*/

if ($metodo === 'PUT') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    if ($idLogado !== $id) {
        responder([
            'erro' => 'Você só pode editar o próprio perfil'
        ], 403);
    }

    $dados = lerJson(true);

    $camposEditaveis = [
        'nome',
        'sobrenome',
        'email',
        'senha'
    ];

    $camposRecebidos = array_intersect(
        $camposEditaveis,
        array_keys($dados)
    );

    if ($camposRecebidos === []) {
        responder([
            'erro' => 'Informe ao menos um campo editável'
        ], 400);
    }

    $stmt = $conn->prepare(
        'SELECT nome_user, sobrenome, email_user
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);

    $usuarioAtual = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$usuarioAtual) {
        responder([
            'erro' => 'Usuário não encontrado'
        ], 404);
    }

    $nome = array_key_exists('nome', $dados)
        ? trim((string) $dados['nome'])
        : $usuarioAtual['nome_user'];

    $sobrenome = array_key_exists('sobrenome', $dados)
        ? trim((string) $dados['sobrenome'])
        : $usuarioAtual['sobrenome'];

    $email = array_key_exists('email', $dados)
        ? trim((string) $dados['email'])
        : $usuarioAtual['email_user'];

    $alterarSenha = array_key_exists('senha', $dados);

    if ($nome === '' || $sobrenome === '') {
        responder([
            'erro' => 'Nome e sobrenome são obrigatórios'
        ], 400);
    }

    $erroLimite = validarLimitesTextoApi([
        'nome' => $nome,
        'sobrenome' => $sobrenome,
        'email' => $email,
    ], [
        'nome' => 100,
        'sobrenome' => 100,
        'email' => 100,
    ]);

    if ($erroLimite !== null) {
        responder(['erro' => $erroLimite], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder([
            'erro' => 'E-mail inválido'
        ], 400);
    }

    $stmt = $conn->prepare(
        'SELECT id_user
         FROM usuario
         WHERE email_user = ?
         AND id_user <> ?
         LIMIT 1'
    );

    $stmt->bind_param('si', $email, $id);
    executarStatementApi($stmt);
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        responder([
            'erro' => 'Este e-mail já está cadastrado'
        ], 409);
    }

    $stmt->close();

    if ($alterarSenha) {

        $senha = (string) $dados['senha'];

        if (strlen($senha) < 6) {
            responder([
                'erro' => 'A senha deve ter pelo menos 6 caracteres'
            ], 400);
        }

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            'UPDATE usuario
             SET nome_user = ?,
                 sobrenome = ?,
                 email_user = ?,
                 senha_user = ?
             WHERE id_user = ?'
        );

        $stmt->bind_param(
            'ssssi',
            $nome,
            $sobrenome,
            $email,
            $senhaHash,
            $id
        );

    } else {

        $stmt = $conn->prepare(
            'UPDATE usuario
             SET nome_user = ?,
                 sobrenome = ?,
                 email_user = ?
             WHERE id_user = ?'
        );

        $stmt->bind_param(
            'sssi',
            $nome,
            $sobrenome,
            $email,
            $id
        );
    }

    executarStatementApi($stmt);

    $stmt->close();

    $_SESSION['nome_user'] = $nome;

    $stmt = $conn->prepare(
        'SELECT id_user, nome_user, sobrenome, email_user,
                tipo_usuario, foto_perfil, foto_banner, data_cadastro
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $usuarioAtualizado = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    responder([
        'mensagem' => 'Perfil atualizado com sucesso',
        'usuario' => normalizarUsuarioApi($usuarioAtualizado)
    ]);
}

/*
|--------------------------------------------------------------------------
| DELETE - EXCLUIR CONTA
|--------------------------------------------------------------------------
*/

if ($metodo === 'DELETE') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    if ($idLogado !== $id) {
        responder([
            'erro' => 'Você só pode apagar a própria conta'
        ], 403);
    }

    $caminhosUploads = [];
    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'SELECT foto_perfil, foto_banner
             FROM usuario
             WHERE id_user = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $midiaPerfil = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($midiaPerfil) {
            $caminhosUploads[] = $midiaPerfil['foto_perfil'];
            $caminhosUploads[] = $midiaPerfil['foto_banner'];
        }

        $stmt = $conn->prepare(
            'SELECT caminho_arquivo
             FROM comunidade_midia
             WHERE id_usuario = ?'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $resultado = $stmt->get_result();

        while ($midia = $resultado->fetch_assoc()) {
            $caminhosUploads[] = $midia['caminho_arquivo'];
        }
        $stmt->close();

        $stmt = $conn->prepare(
            'SELECT foto
             FROM solicitacao
             WHERE id_user = ? AND foto IS NOT NULL'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $resultado = $stmt->get_result();

        while ($solicitacao = $resultado->fetch_assoc()) {
            $caminhosUploads[] = $solicitacao['foto'];
        }
        $stmt->close();

        $stmt = $conn->prepare(
            'DELETE FROM usuario
             WHERE id_user = ?'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $removido = $stmt->affected_rows;
        $stmt->close();

        if ($removido === 0) {
            $conn->rollback();
            responder([
                'erro' => 'Usuário não encontrado'
            ], 404);
        }

        $conn->commit();
    } catch (Throwable $erro) {
        $conn->rollback();
        responderErroInfraestrutura('Falha ao excluir conta', $erro);
    }

    removerArquivosUploadSemReferencia($conn, $caminhosUploads);

    session_unset();
    session_destroy();

    responder([
        'mensagem' => 'Conta removida com sucesso'
    ]);
}

/*
|--------------------------------------------------------------------------
| MÉTODO NÃO PERMITIDO
|--------------------------------------------------------------------------
*/

responder([
    'erro' => 'Método não permitido'
], 405);
