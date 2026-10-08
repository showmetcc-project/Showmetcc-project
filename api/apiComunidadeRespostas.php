<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();
$idUsuario = exigirUsuarioComum();

if ($metodo === 'POST') {
    if ($id !== null) {
        responder(['erro' => 'Não informe ID para criar uma resposta'], 400);
    }

    $dados = lerJson();
    $idPost = filter_var(
        $dados['id_post'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $texto = trim((string) ($dados['texto'] ?? ''));

    if ($idPost === false || $texto === '') {
        responder(['erro' => 'Publicação e texto são obrigatórios'], 400);
    }

    if (tamanhoTextoApi($texto) > 3000) {
        responder(['erro' => 'texto deve ter no máximo 3000 caracteres'], 400);
    }

    $stmt = $conn->prepare(
        "SELECT 1 FROM comunidade_post
         WHERE id_post = ? AND status_post = 'ativo'
         LIMIT 1"
    );
    $stmt->bind_param('i', $idPost);
    executarStatementApi($stmt);
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        $stmt->close();
        responder(['erro' => 'Publicação não encontrada'], 404);
    }
    $stmt->close();

    $stmt = $conn->prepare(
        'INSERT INTO comunidade_resposta (id_post, id_usuario, texto) VALUES (?, ?, ?)'
    );
    $stmt->bind_param('iis', $idPost, $idUsuario, $texto);
    executarStatementApi($stmt);
    $idResposta = $conn->insert_id;
    $stmt->close();

    responder([
        'mensagem' => 'Resposta publicada com sucesso',
        'id_resposta' => (int) $idResposta,
    ], 201);
}

if ($metodo === 'PUT') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID da resposta na URL'], 400);
    }

    $dados = lerJson();
    $texto = trim((string) ($dados['texto'] ?? ''));

    if ($texto === '') {
        responder(['erro' => 'texto é obrigatório'], 400);
    }

    if (tamanhoTextoApi($texto) > 3000) {
        responder(['erro' => 'texto deve ter no máximo 3000 caracteres'], 400);
    }

    $stmt = $conn->prepare('SELECT id_usuario FROM comunidade_resposta WHERE id_resposta = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $resposta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$resposta) {
        responder(['erro' => 'Resposta não encontrada'], 404);
    }

    if ((int) $resposta['id_usuario'] !== $idUsuario) {
        responder(['erro' => 'Você só pode editar sua própria resposta'], 403);
    }

    $stmt = $conn->prepare(
        'UPDATE comunidade_resposta SET texto = ? WHERE id_resposta = ? AND id_usuario = ?'
    );
    $stmt->bind_param('sii', $texto, $id, $idUsuario);
    executarStatementApi($stmt);
    $stmt->close();

    responder(['mensagem' => 'Resposta atualizada com sucesso']);
}

if ($metodo === 'DELETE') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID da resposta na URL'], 400);
    }

    $stmt = $conn->prepare('SELECT id_usuario FROM comunidade_resposta WHERE id_resposta = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $resposta = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$resposta) {
        responder(['erro' => 'Resposta não encontrada'], 404);
    }

    if ((int) $resposta['id_usuario'] !== $idUsuario) {
        responder(['erro' => 'Você só pode excluir sua própria resposta'], 403);
    }

    $stmt = $conn->prepare(
        'DELETE FROM comunidade_resposta WHERE id_resposta = ? AND id_usuario = ?'
    );
    $stmt->bind_param('ii', $id, $idUsuario);
    executarStatementApi($stmt);
    $stmt->close();

    responder(['mensagem' => 'Resposta excluída com sucesso']);
}

responder(['erro' => 'Método não permitido'], 405);
