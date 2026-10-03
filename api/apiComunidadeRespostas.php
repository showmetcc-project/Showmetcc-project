<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';

$idUsuario = exigirLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['erro' => 'Método não permitido'], 405);
}
if (obterIdApi() !== null) {
    responder(['erro' => 'Não informe ID para criar uma resposta'], 400);
}
$dados = lerJson();
$idPost = filter_var($dados['id_post'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$texto = trim((string) ($dados['texto'] ?? ''));
if ($idPost === false || $texto === '') {
    responder(['erro' => 'Publicação e texto são obrigatórios'], 400);
}
if (tamanhoTextoApi($texto) > 3000) {
    responder(['erro' => 'texto deve ter no máximo 3000 caracteres'], 400);
}
$stmt = $conn->prepare('SELECT 1 FROM comunidade_post WHERE id_post = ? LIMIT 1');
$stmt->bind_param('i', $idPost);
executarStatementApi($stmt);
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    responder(['erro' => 'Publicação não encontrada'], 404);
}
$stmt->close();
$stmt = $conn->prepare('INSERT INTO comunidade_resposta (id_post, id_usuario, texto) VALUES (?, ?, ?)');
$stmt->bind_param('iis', $idPost, $idUsuario, $texto);
executarStatementApi($stmt);
$idResposta = $conn->insert_id;
$stmt->close();
responder(['mensagem' => 'Resposta publicada com sucesso', 'id_resposta' => (int) $idResposta], 201);
