<?php

require_once __DIR__ . '/middleware/apiCommon.php';

require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

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
        'tipo_usuario' => 'comum'
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

require_once __DIR__ . '/middleware/verifica_login.php';

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
        'SELECT id_user, nome_user, sobrenome, email_user,
                tipo_usuario, data_cadastro
         FROM usuario
         WHERE id_user = ?
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

    responder([
        'usuario' => normalizarUsuarioApi($usuario)
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
                tipo_usuario, data_cadastro
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
            'SELECT am.caminho_arquivo
             FROM avaliacao_midia am
             INNER JOIN avaliacao a ON a.id_avaliacao = am.id_avaliacao
             WHERE a.id_user = ?'
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
