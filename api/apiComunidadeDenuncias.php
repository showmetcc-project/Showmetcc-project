<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/verificaAdmin.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

if ($metodo === 'GET') {
    exigirAdmin();

    if ($id !== null) {
        responder(['erro' => 'Não informe ID para listar as denúncias'], 400);
    }

    $status = trim((string) ($_GET['status'] ?? 'todas'));
    $statusPermitidos = ['todas', 'pendente', 'mantido', 'removido'];

    if (!in_array($status, $statusPermitidos, true)) {
        responder(['erro' => 'status deve ser todas, pendente, mantido ou removido'], 400);
    }

    $sql = "SELECT d.id_denuncia, d.id_post, d.id_usuario AS id_denunciante,
                   d.motivo, d.status_denuncia, d.data_criacao AS data_denuncia,
                   d.data_moderacao, d.id_admin_moderacao,
                   p.id_evento, p.id_usuario AS id_autor, p.categoria AS categoria_post,
                   p.texto AS texto_post, p.status_post, p.data_criacao AS data_post,
                   autor.nome_user AS autor_nome, autor.sobrenome AS autor_sobrenome,
                   autor.email_user AS autor_email, autor.foto_perfil AS autor_foto,
                   denunciante.nome_user AS denunciante_nome,
                   denunciante.sobrenome AS denunciante_sobrenome,
                   e.nome_evento AS comunidade_nome, e.imagem_evento AS comunidade_imagem
            FROM comunidade_denuncia d
            INNER JOIN comunidade_post p ON p.id_post = d.id_post
            INNER JOIN usuario autor ON autor.id_user = p.id_usuario
            INNER JOIN usuario denunciante ON denunciante.id_user = d.id_usuario
            INNER JOIN evento e ON e.id_evento = p.id_evento
            WHERE d.id_post IS NOT NULL";

    if ($status !== 'todas') {
        $sql .= ' AND d.status_denuncia = ?';
    }

    $sql .= " ORDER BY FIELD(d.status_denuncia, 'pendente', 'mantido', 'removido'),
                      d.data_criacao DESC, d.id_denuncia DESC";
    $stmt = $conn->prepare($sql);

    if ($status !== 'todas') {
        $stmt->bind_param('s', $status);
    }

    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $denuncias = [];

    while ($denuncia = $resultado->fetch_assoc()) {
        $denuncias[] = normalizarDenunciaComunidadeApi($denuncia);
    }

    $stmt->close();
    responder(['denuncias' => $denuncias]);
}

if ($metodo === 'POST') {
    if ($id !== null) {
        responder(['erro' => 'Não informe ID para criar uma denúncia'], 400);
    }

    $idUsuario = exigirLogin();
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
    } else {
        $stmt = $conn->prepare('SELECT id_usuario FROM comunidade_midia WHERE id_midia = ? LIMIT 1');
        $idAlvo = $idMidia;
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
        'INSERT INTO comunidade_denuncia (id_post, id_midia, id_usuario, motivo)
         VALUES (?, ?, ?, ?)'
    );
    $valorPost = $temPost ? $idPost : null;
    $valorMidia = $temMidia ? $idMidia : null;
    $stmt->bind_param('iiis', $valorPost, $valorMidia, $idUsuario, $motivo);
    executarStatementApi($stmt);
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

    try {
        $stmt = $conn->prepare(
            'SELECT d.id_post, d.status_denuncia, p.status_post
             FROM comunidade_denuncia d
             INNER JOIN comunidade_post p ON p.id_post = d.id_post
             WHERE d.id_denuncia = ? AND d.id_post IS NOT NULL
             LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $denuncia = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$denuncia) {
            $conn->rollback();
            responder(['erro' => 'Denúncia de publicação não encontrada'], 404);
        }

        if ($denuncia['status_denuncia'] !== 'pendente') {
            $conn->rollback();
            responder(['erro' => 'Esta denúncia já foi analisada'], 409);
        }

        $idPost = (int) $denuncia['id_post'];
        $novoStatus = $acao === 'remover' ? 'removido' : 'mantido';

        if ($acao === 'manter' && $denuncia['status_post'] === 'removido') {
            $conn->rollback();
            responder(['erro' => 'A publicação já foi removida'], 409);
        }

        if ($acao === 'remover') {
            $stmt = $conn->prepare(
                "UPDATE comunidade_post SET status_post = 'removido' WHERE id_post = ?"
            );
            $stmt->bind_param('i', $idPost);
            executarStatementApi($stmt);
            $stmt->close();
        }

        $stmt = $conn->prepare(
            "UPDATE comunidade_denuncia
             SET status_denuncia = ?, data_moderacao = NOW(), id_admin_moderacao = ?
             WHERE id_post = ? AND status_denuncia = 'pendente'"
        );
        $stmt->bind_param('sii', $novoStatus, $idAdmin, $idPost);
        executarStatementApi($stmt);
        $quantidadeAtualizada = $stmt->affected_rows;
        $stmt->close();
        $conn->commit();

        responder([
            'mensagem' => $acao === 'remover'
                ? 'Publicação removida com sucesso'
                : 'Publicação mantida com sucesso',
            'moderacao' => [
                'id_denuncia' => $id,
                'id_post' => $idPost,
                'status_denuncia' => $novoStatus,
                'status_post' => $acao === 'remover' ? 'removido' : 'ativo',
                'denuncias_atualizadas' => $quantidadeAtualizada
            ]
        ]);
    } catch (Throwable $erro) {
        $conn->rollback();
        responderErroInfraestrutura('Falha ao moderar denúncia', $erro);
    }
}

responder(['erro' => 'Método não permitido'], 405);
