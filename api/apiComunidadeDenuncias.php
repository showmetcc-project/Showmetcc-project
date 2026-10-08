<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/verificaAdmin.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

if ($metodo === 'GET') {
    exigirAdmin();

    if ($id !== null) {
        responder(['erro' => 'Não informe ID para listar as denúncias'], 400);
    }

    if (array_key_exists('status', $_GET) && !is_string($_GET['status'])) {
        responder(['erro' => 'status deve ser um texto'], 400);
    }
    $status = trim((string) ($_GET['status'] ?? 'todas'));
    $statusPermitidos = ['todas', 'pendente', 'mantido', 'removido'];

    if (!in_array($status, $statusPermitidos, true)) {
        responder(['erro' => 'status deve ser todas, pendente, mantido ou removido'], 400);
    }

    if (array_key_exists('busca', $_GET) && !is_string($_GET['busca'])) {
        responder(['erro' => 'busca deve ser um texto'], 400);
    }
    $busca = trim((string) ($_GET['busca'] ?? ''));
    if (tamanhoTextoApi($busca) > 100) {
        responder(['erro' => 'busca deve ter no máximo 100 caracteres'], 400);
    }

    $dias = 0;
    if (array_key_exists('dias', $_GET)) {
        $diasInformados = $_GET['dias'];
        if (!is_string($diasInformados) || !ctype_digit($diasInformados)
            || (int) $diasInformados > 3650) {
            responder(['erro' => 'dias deve ser um inteiro entre 0 e 3650'], 400);
        }
        $dias = (int) $diasInformados;
    }

    $limite = null;
    $offset = 0;
    if (array_key_exists('limite', $_GET)) {
        $limiteInformado = $_GET['limite'];
        if (!is_string($limiteInformado) || !ctype_digit($limiteInformado)
            || (int) $limiteInformado < 1 || (int) $limiteInformado > 50) {
            responder(['erro' => 'limite deve ser um inteiro entre 1 e 50'], 400);
        }
        $limite = (int) $limiteInformado;
    }

    if (array_key_exists('offset', $_GET)) {
        $offsetInformado = $_GET['offset'];
        if ($limite === null || !is_string($offsetInformado) || !ctype_digit($offsetInformado)) {
            responder(['erro' => 'offset deve ser um inteiro maior ou igual a zero e exige limite'], 400);
        }
        $offset = (int) $offsetInformado;
    }

    $baseDenuncias = "SELECT * FROM (
                SELECT d.id_denuncia, d.id_post, NULL AS id_midia,
                       'post' AS tipo_alvo, d.id_usuario AS id_denunciante,
                       d.motivo, d.status_denuncia, d.data_criacao AS data_denuncia,
                       d.data_moderacao, d.id_admin_moderacao,
                       p.id_evento, p.id_usuario AS id_autor,
                       p.categoria AS categoria_post, p.texto AS texto_post,
                       p.status_post, p.data_criacao AS data_post,
                       NULL AS caminho_midia, NULL AS legenda_midia,
                       NULL AS status_midia, NULL AS data_midia,
                       autor.nome_user AS autor_nome, autor.sobrenome AS autor_sobrenome,
                       autor.email_user AS autor_email, autor.foto_perfil AS autor_foto,
                       denunciante.nome_user AS denunciante_nome,
                       denunciante.sobrenome AS denunciante_sobrenome,
                       denunciante.email_user AS denunciante_email,
                       e.nome_evento AS comunidade_nome, e.imagem_evento AS comunidade_imagem
                FROM comunidade_denuncia d
                INNER JOIN comunidade_post p ON p.id_post = d.id_post
                INNER JOIN usuario autor ON autor.id_user = p.id_usuario
                INNER JOIN usuario denunciante ON denunciante.id_user = d.id_usuario
                INNER JOIN evento e ON e.id_evento = p.id_evento
                WHERE d.id_post IS NOT NULL

                UNION ALL

                SELECT d.id_denuncia, NULL AS id_post, d.id_midia,
                       'midia' AS tipo_alvo, d.id_usuario AS id_denunciante,
                       d.motivo, d.status_denuncia, d.data_criacao AS data_denuncia,
                       d.data_moderacao, d.id_admin_moderacao,
                       m.id_evento, m.id_usuario AS id_autor,
                       NULL AS categoria_post, NULL AS texto_post,
                       NULL AS status_post, NULL AS data_post,
                       m.caminho_arquivo AS caminho_midia, m.legenda AS legenda_midia,
                       m.status_midia, m.data_criacao AS data_midia,
                       autor.nome_user AS autor_nome, autor.sobrenome AS autor_sobrenome,
                       autor.email_user AS autor_email, autor.foto_perfil AS autor_foto,
                       denunciante.nome_user AS denunciante_nome,
                       denunciante.sobrenome AS denunciante_sobrenome,
                       denunciante.email_user AS denunciante_email,
                       e.nome_evento AS comunidade_nome, e.imagem_evento AS comunidade_imagem
                FROM comunidade_denuncia d
                INNER JOIN comunidade_midia m ON m.id_midia = d.id_midia
                INNER JOIN usuario autor ON autor.id_user = m.id_usuario
                INNER JOIN usuario denunciante ON denunciante.id_user = d.id_usuario
                INNER JOIN evento e ON e.id_evento = m.id_evento
                WHERE d.id_midia IS NOT NULL
            ) AS lista_denuncias";
    $filtrosDenuncias = " WHERE (? = 'todas' OR status_denuncia = ?)
                           AND (? = 0 OR data_denuncia >= DATE_SUB(NOW(), INTERVAL ? DAY))
                           AND (? = '' OR CONCAT_WS(' ',
                                texto_post, categoria_post, legenda_midia, tipo_alvo,
                                motivo, comunidade_nome, autor_nome, autor_sobrenome,
                                autor_email, denunciante_nome, denunciante_sobrenome,
                                denunciante_email
                           ) LIKE CONCAT('%', ?, '%'))";
    $sql = $baseDenuncias . $filtrosDenuncias
        . ' ORDER BY data_denuncia DESC, id_denuncia DESC';

    if ($limite !== null) {
        $sql .= ' LIMIT ? OFFSET ?';
    }

    $stmt = $conn->prepare($sql);

    if ($limite === null) {
        $stmt->bind_param('ssiiss', $status, $status, $dias, $dias, $busca, $busca);
    } else {
        $stmt->bind_param(
            'ssiissii',
            $status,
            $status,
            $dias,
            $dias,
            $busca,
            $busca,
            $limite,
            $offset
        );
    }

    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $denuncias = [];

    while ($denuncia = $resultado->fetch_assoc()) {
        $denuncias[] = normalizarDenunciaComunidadeApi($denuncia);
    }

    $stmt->close();

    $sqlContadores =
        "SELECT COUNT(*) AS todas,
                SUM(status_denuncia = 'pendente') AS pendente,
                SUM(status_denuncia = 'mantido') AS mantido,
                SUM(status_denuncia = 'removido') AS removido
         FROM ($baseDenuncias) AS contagem_denuncias
         WHERE (? = 0 OR data_denuncia >= DATE_SUB(NOW(), INTERVAL ? DAY))
           AND (? = '' OR CONCAT_WS(' ',
                texto_post, categoria_post, legenda_midia, tipo_alvo,
                motivo, comunidade_nome, autor_nome, autor_sobrenome,
                autor_email, denunciante_nome, denunciante_sobrenome,
                denunciante_email
           ) LIKE CONCAT('%', ?, '%'))";
    $stmt = $conn->prepare($sqlContadores);
    $stmt->bind_param('iiss', $dias, $dias, $busca, $busca);
    executarStatementApi($stmt);
    $contadores = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    foreach (['todas', 'pendente', 'mantido', 'removido'] as $campoContador) {
        $contadores[$campoContador] = (int) ($contadores[$campoContador] ?? 0);
    }

    $totalStatus = $contadores[$status === 'todas' ? 'todas' : $status];
    responder([
        'denuncias' => $denuncias,
        'contadores' => $contadores,
        'total' => $totalStatus,
        'limite' => $limite,
        'offset' => $offset,
        'tem_mais' => $limite !== null && $offset + count($denuncias) < $totalStatus
    ]);
}

if ($metodo === 'POST') {
    if ($id !== null) {
        responder(['erro' => 'Não informe ID para criar uma denúncia'], 400);
    }

    $idUsuario = exigirUsuarioComum();
    $dados = lerJson();
    $idPost = filter_var($dados['id_post'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $idMidia = filter_var($dados['id_midia'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $motivo = trim((string) ($dados['motivo'] ?? ''));
    $temPost = $idPost !== false && $idPost !== null;
    $temMidia = $idMidia !== false && $idMidia !== null;

    if ($temPost === $temMidia || $motivo === '') {
        responder(['erro' => 'Informe motivo e exatamente um alvo: id_post ou id_midia'], 400);
    }

    if (tamanhoTextoApi($motivo) > 100) {
        responder(['erro' => 'motivo deve ter no máximo 100 caracteres'], 400);
    }

    $motivosPermitidos = [
        'Conteúdo inapropriado',
        'Spam ou publicidade',
        'Informação falsa',
        'Discurso de ódio',
    ];
    $prefixoOutroMotivo = 'Outro motivo: ';
    $ehOutroMotivo = str_starts_with($motivo, $prefixoOutroMotivo)
        && trim(substr($motivo, strlen($prefixoOutroMotivo))) !== '';

    if (!in_array($motivo, $motivosPermitidos, true) && !$ehOutroMotivo) {
        responder(['erro' => 'Selecione um motivo de denúncia válido'], 400);
    }

    if ($temPost) {
        $stmt = $conn->prepare(
            "SELECT id_usuario
             FROM comunidade_post
             WHERE id_post = ? AND status_post = 'ativo'
             LIMIT 1"
        );
        $idAlvo = $idPost;
        $colunaAlvo = 'id_post';
    } else {
        $stmt = $conn->prepare(
            "SELECT id_usuario
             FROM comunidade_midia
             WHERE id_midia = ? AND status_midia = 'ativo'
             LIMIT 1"
        );
        $idAlvo = $idMidia;
        $colunaAlvo = 'id_midia';
    }

    $stmt->bind_param('i', $idAlvo);
    executarStatementApi($stmt);
    $alvo = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$alvo) {
        responder(['erro' => 'Conteúdo não encontrado'], 404);
    }

    if ((int) $alvo['id_usuario'] === $idUsuario) {
        responder(['erro' => 'Você não pode denunciar seu próprio conteúdo'], 400);
    }

    $stmt = $conn->prepare(
        "SELECT id_denuncia
         FROM comunidade_denuncia
         WHERE $colunaAlvo = ? AND id_usuario = ?
         LIMIT 1"
    );
    $stmt->bind_param('ii', $idAlvo, $idUsuario);
    executarStatementApi($stmt);
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        responder(['erro' => 'Você já denunciou este conteúdo'], 409);
    }
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO comunidade_denuncia (id_post, id_midia, id_usuario, motivo)
         VALUES (?, ?, ?, ?)'
    );
    $valorPost = $temPost ? $idPost : null;
    $valorMidia = $temMidia ? $idMidia : null;
    $stmt->bind_param('iiis', $valorPost, $valorMidia, $idUsuario, $motivo);

    try {
        executarStatementApi($stmt);
    } catch (mysqli_sql_exception $erro) {
        $stmt->close();
        if ((int) $erro->getCode() === 1062) {
            responder(['erro' => 'Você já denunciou este conteúdo'], 409);
        }
        throw $erro;
    }

    $idDenuncia = $conn->insert_id;
    $stmt->close();
    responder(['mensagem' => 'Denúncia registrada', 'id_denuncia' => (int) $idDenuncia], 201);
}

if ($metodo === 'PUT') {
    $idAdmin = exigirAdmin();

    if ($id === null) {
        responder(['erro' => 'Informe o ID da denúncia na URL'], 400);
    }

    $dados = lerJson();
    $acao = trim((string) ($dados['acao'] ?? ''));

    if (!in_array($acao, ['manter', 'remover'], true)) {
        responder(['erro' => 'acao deve ser manter ou remover'], 400);
    }

    $conn->begin_transaction();
    $caminhoMidiaRemovida = null;
    $denunciantes = [];

    try {
        $stmt = $conn->prepare(
            'SELECT id_post, id_midia, status_denuncia
             FROM comunidade_denuncia
             WHERE id_denuncia = ?
             LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $denuncia = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$denuncia) {
            $conn->rollback();
            responder(['erro' => 'Denúncia não encontrada'], 404);
        }

        if ($denuncia['status_denuncia'] !== 'pendente') {
            $conn->rollback();
            responder(['erro' => 'Esta denúncia já foi analisada'], 409);
        }

        $tipoAlvo = $denuncia['id_post'] !== null ? 'post' : 'midia';
        $idAlvo = (int) ($denuncia['id_post'] ?? $denuncia['id_midia']);

        if ($tipoAlvo === 'post') {
            $stmt = $conn->prepare(
                'SELECT p.id_usuario, p.status_post AS status_conteudo,
                        p.texto AS descricao_conteudo, e.nome_evento,
                        u.nome_user, u.sobrenome, u.email_user
                 FROM comunidade_post p
                 INNER JOIN evento e ON e.id_evento = p.id_evento
                 INNER JOIN usuario u ON u.id_user = p.id_usuario
                 WHERE p.id_post = ? LIMIT 1 FOR UPDATE'
            );
        } else {
            $stmt = $conn->prepare(
                'SELECT m.id_usuario, m.status_midia AS status_conteudo,
                        COALESCE(m.legenda, \'Foto da galeria\') AS descricao_conteudo,
                        m.caminho_arquivo, e.nome_evento,
                        u.nome_user, u.sobrenome, u.email_user
                 FROM comunidade_midia m
                 INNER JOIN evento e ON e.id_evento = m.id_evento
                 INNER JOIN usuario u ON u.id_user = m.id_usuario
                 WHERE m.id_midia = ? LIMIT 1 FOR UPDATE'
            );
        }

        $stmt->bind_param('i', $idAlvo);
        executarStatementApi($stmt);
        $conteudo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$conteudo) {
            $conn->rollback();
            responder(['erro' => 'Conteúdo denunciado não encontrado'], 404);
        }

        if ($acao === 'manter' && $conteudo['status_conteudo'] === 'removido') {
            $conn->rollback();
            responder(['erro' => 'O conteúdo já foi removido'], 409);
        }

        $colunaAlvo = $tipoAlvo === 'post' ? 'id_post' : 'id_midia';
        $stmt = $conn->prepare(
            "SELECT u.nome_user, u.sobrenome, u.email_user
             FROM comunidade_denuncia d
             INNER JOIN usuario u ON u.id_user = d.id_usuario
             WHERE d.$colunaAlvo = ? AND d.status_denuncia = 'pendente'"
        );
        $stmt->bind_param('i', $idAlvo);
        executarStatementApi($stmt);
        $resultadoDenunciantes = $stmt->get_result();
        while ($denunciante = $resultadoDenunciantes->fetch_assoc()) {
            $denunciantes[] = $denunciante;
        }
        $stmt->close();

        if ($acao === 'remover') {
            if ($tipoAlvo === 'post') {
                $stmt = $conn->prepare("UPDATE comunidade_post SET status_post = 'removido' WHERE id_post = ?");
            } else {
                $stmt = $conn->prepare("UPDATE comunidade_midia SET status_midia = 'removido' WHERE id_midia = ?");
                $caminhoMidiaRemovida = $conteudo['caminho_arquivo'];
            }
            $stmt->bind_param('i', $idAlvo);
            executarStatementApi($stmt);
            $stmt->close();
        }

        $novoStatus = $acao === 'remover' ? 'removido' : 'mantido';
        $stmt = $conn->prepare(
            "UPDATE comunidade_denuncia
             SET status_denuncia = ?, data_moderacao = NOW(), id_admin_moderacao = ?
             WHERE $colunaAlvo = ? AND status_denuncia = 'pendente'"
        );
        $stmt->bind_param('sii', $novoStatus, $idAdmin, $idAlvo);
        executarStatementApi($stmt);
        $quantidadeAtualizada = $stmt->affected_rows;
        $stmt->close();
        $conn->commit();

        if ($caminhoMidiaRemovida !== null) {
            removerArquivoUpload($caminhoMidiaRemovida);
        }

        $emailDisponivel = true;
        try {
            require_once dirname(__DIR__) . '/config/emailHelper.php';
        } catch (Throwable $erroEmail) {
            $emailDisponivel = false;
            error_log('Falha ao carregar o envio de e-mail da moderação: ' . $erroEmail->getMessage());
        }

        foreach ($emailDisponivel ? $denunciantes : [] as $denunciante) {
            try {
                enviarEmailModeracaoDenunciaShowMe(
                    (string) $denunciante['email_user'],
                    trim((string) $denunciante['nome_user'] . ' ' . (string) $denunciante['sobrenome']),
                    (string) $conteudo['nome_evento'],
                    $tipoAlvo,
                    $acao,
                    'denunciante'
                );
            } catch (Throwable $erroEmail) {
                error_log('Falha ao notificar denunciante: ' . $erroEmail->getMessage());
            }
        }

        if ($acao === 'remover' && $emailDisponivel) {
            try {
                enviarEmailModeracaoDenunciaShowMe(
                    (string) $conteudo['email_user'],
                    trim((string) $conteudo['nome_user'] . ' ' . (string) $conteudo['sobrenome']),
                    (string) $conteudo['nome_evento'],
                    $tipoAlvo,
                    $acao,
                    'autor'
                );
            } catch (Throwable $erroEmail) {
                error_log('Falha ao notificar autor do conteúdo removido: ' . $erroEmail->getMessage());
            }
        }

        responder([
            'mensagem' => $acao === 'remover'
                ? 'Conteúdo removido com sucesso'
                : 'Conteúdo mantido com sucesso',
            'moderacao' => [
                'id_denuncia' => $id,
                'tipo_alvo' => $tipoAlvo,
                'id_alvo' => $idAlvo,
                'status_denuncia' => $novoStatus,
                'status_conteudo' => $acao === 'remover' ? 'removido' : 'ativo',
                'denuncias_atualizadas' => $quantidadeAtualizada,
            ],
        ]);
    } catch (Throwable $erro) {
        $conn->rollback();
        responderErroInfraestrutura('Falha ao moderar denúncia', $erro);
    }
}

responder(['erro' => 'Método não permitido'], 405);
