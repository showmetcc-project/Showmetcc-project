<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();
$idUsuario = exigirLogin();
$categoriasPermitidas = ['Duvida', 'Dica', 'Transporte', 'Hospedagem', 'Companhia', 'Relato'];

if ($metodo === 'GET') {
    if ($id !== null) {
        responder(['erro' => 'Use evento_id para listar as publicações'], 400);
    }

    if (isset($_GET['resumo'])) {
        if ($_GET['resumo'] !== '1') {
            responder(['erro' => 'resumo deve ser 1'], 400);
        }

        $stmt = $conn->prepare(
            "SELECT e.id_evento, e.nome_evento, e.imagem_evento, e.data_evento,
                    (SELECT COUNT(*)
                     FROM comunidade_post cp
                     WHERE cp.id_evento = e.id_evento AND cp.status_post = 'ativo') AS total_posts,
                    (EXISTS(SELECT 1 FROM favoritos f WHERE f.id_evento = e.id_evento AND f.id_user = ?)
                     OR EXISTS(SELECT 1 FROM rota r WHERE r.id_evento = e.id_evento AND r.id_user = ?)) AS evento_do_usuario
             FROM evento e
             WHERE e.status_evento = 'ativo'
             ORDER BY evento_do_usuario DESC, e.data_evento DESC, e.nome_evento ASC"
        );
        $stmt->bind_param('ii', $idUsuario, $idUsuario);
        executarStatementApi($stmt);
        $resultado = $stmt->get_result();
        $seusEventos = [];
        $todasComunidades = [];
        while ($evento = $resultado->fetch_assoc()) {
            $evento = normalizarResumoComunidadeApi($evento);
            if ($evento['evento_do_usuario']) {
                $seusEventos[] = $evento;
            } else {
                $todasComunidades[] = $evento;
            }
        }
        $stmt->close();
        responder(['seus_eventos' => $seusEventos, 'todas_comunidades' => $todasComunidades]);
    }

    $idEvento = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idEvento === false || $idEvento === null) {
        responder(['erro' => 'evento_id deve ser um inteiro positivo'], 400);
    }
    $categoria = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : '';
    if ($categoria !== '' && !in_array($categoria, $categoriasPermitidas, true)) {
        responder(['erro' => 'Categoria inválida'], 400);
    }

    $sql = "SELECT p.id_post, p.id_evento, p.id_usuario, p.categoria, p.texto, p.data_criacao,
                   u.nome_user, u.sobrenome, u.foto_perfil,
                   COUNT(DISTINCT c.id_usuario) AS total_curtidas,
                   MAX(CASE WHEN c.id_usuario = ? THEN 1 ELSE 0 END) AS curtido_usuario
            FROM comunidade_post p
            INNER JOIN usuario u ON u.id_user = p.id_usuario
            LEFT JOIN comunidade_curtida c ON c.id_post = p.id_post
            WHERE p.id_evento = ? AND p.status_post = 'ativo'";
    if ($categoria !== '') {
        $sql .= ' AND p.categoria = ?';
    }
    $sql .= ' GROUP BY p.id_post, u.id_user ORDER BY p.data_criacao DESC, p.id_post DESC';
    $stmt = $conn->prepare($sql);
    if ($categoria !== '') {
        $stmt->bind_param('iis', $idUsuario, $idEvento, $categoria);
    } else {
        $stmt->bind_param('ii', $idUsuario, $idEvento);
    }
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $posts = [];
    $indices = [];
    while ($post = $resultado->fetch_assoc()) {
        $post = normalizarPostComunidadeApi($post);
        $post['respostas'] = [];
        $indices[$post['id_post']] = count($posts);
        $posts[] = $post;
    }
    $stmt->close();

    if ($posts) {
        $stmt = $conn->prepare(
            'SELECT r.id_resposta, r.id_post, r.id_usuario, r.texto, r.data_criacao,
                    u.nome_user, u.sobrenome, u.foto_perfil
             FROM comunidade_resposta r
             INNER JOIN usuario u ON u.id_user = r.id_usuario
             INNER JOIN comunidade_post p ON p.id_post = r.id_post
             WHERE p.id_evento = ? AND p.status_post = \'ativo\'
             ORDER BY r.data_criacao ASC, r.id_resposta ASC'
        );
        $stmt->bind_param('i', $idEvento);
        executarStatementApi($stmt);
        $resultado = $stmt->get_result();
        while ($resposta = $resultado->fetch_assoc()) {
            $resposta = normalizarRespostaComunidadeApi($resposta);
            if (isset($indices[$resposta['id_post']])) {
                $posts[$indices[$resposta['id_post']]]['respostas'][] = $resposta;
            }
        }
        $stmt->close();
    }
    responder(['posts' => $posts]);
}

if ($metodo === 'POST') {
    $idUsuario = exigirUsuarioComum();

    if ($id !== null) {
        responder(['erro' => 'Não informe ID para criar uma publicação'], 400);
    }
    $dados = lerJson();
    $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $categoria = trim((string) ($dados['categoria'] ?? ''));
    $texto = trim((string) ($dados['texto'] ?? ''));
    if ($idEvento === false || !in_array($categoria, $categoriasPermitidas, true) || $texto === '') {
        responder(['erro' => 'Evento, categoria e texto são obrigatórios'], 400);
    }
    if (tamanhoTextoApi($texto) > 5000) {
        responder(['erro' => 'texto deve ter no máximo 5000 caracteres'], 400);
    }
    $stmt = $conn->prepare("SELECT 1 FROM evento WHERE id_evento = ? AND status_evento = 'ativo' LIMIT 1");
    $stmt->bind_param('i', $idEvento);
    executarStatementApi($stmt);
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        responder(['erro' => 'Evento não encontrado'], 404);
    }
    $stmt->close();
    $stmt = $conn->prepare('INSERT INTO comunidade_post (id_evento, id_usuario, categoria, texto) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('iiss', $idEvento, $idUsuario, $categoria, $texto);
    executarStatementApi($stmt);
    $idPost = $conn->insert_id;
    $stmt->close();
    responder(['mensagem' => 'Publicação criada com sucesso', 'id_post' => (int) $idPost], 201);
}

if ($metodo === 'PUT') {
    $idUsuario = exigirUsuarioComum();

    if ($id === null) {
        responder(['erro' => 'Informe o ID da publicação na URL'], 400);
    }

    $dados = lerJson();
    $stmt = $conn->prepare(
        "SELECT id_usuario, categoria, texto
         FROM comunidade_post
         WHERE id_post = ? AND status_post = 'ativo'
         LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        responder(['erro' => 'Publicação não encontrada'], 404);
    }

    if ((int) $post['id_usuario'] !== $idUsuario) {
        responder(['erro' => 'Você só pode editar sua própria publicação'], 403);
    }

    $categoria = trim((string) ($dados['categoria'] ?? $post['categoria']));
    $texto = trim((string) ($dados['texto'] ?? $post['texto']));

    if (!in_array($categoria, $categoriasPermitidas, true) || $texto === '') {
        responder(['erro' => 'Categoria válida e texto são obrigatórios'], 400);
    }

    if (tamanhoTextoApi($texto) > 5000) {
        responder(['erro' => 'texto deve ter no máximo 5000 caracteres'], 400);
    }

    $stmt = $conn->prepare(
        "UPDATE comunidade_post
         SET categoria = ?, texto = ?
         WHERE id_post = ? AND id_usuario = ? AND status_post = 'ativo'"
    );
    $stmt->bind_param('ssii', $categoria, $texto, $id, $idUsuario);
    executarStatementApi($stmt);
    $stmt->close();

    responder([
        'mensagem' => 'Publicação atualizada com sucesso',
        'post' => normalizarPostComunidadeApi([
            'id_post' => $id,
            'id_usuario' => $idUsuario,
            'categoria' => $categoria,
            'texto' => $texto,
        ]),
    ]);
}

if ($metodo === 'DELETE') {
    $idUsuario = exigirUsuarioComum();

    if ($id === null) {
        responder(['erro' => 'Informe o ID da publicação na URL'], 400);
    }
    $stmt = $conn->prepare(
        "SELECT id_usuario FROM comunidade_post
         WHERE id_post = ? AND status_post = 'ativo'
         LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$post) {
        responder(['erro' => 'Publicação não encontrada'], 404);
    }
    if ((int) $post['id_usuario'] !== $idUsuario) {
        responder(['erro' => 'Você só pode excluir sua própria publicação'], 403);
    }
    $stmt = $conn->prepare('DELETE FROM comunidade_post WHERE id_post = ?');
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $stmt->close();
    responder(['mensagem' => 'Publicação excluída com sucesso']);
}

responder(['erro' => 'Método não permitido'], 405);
