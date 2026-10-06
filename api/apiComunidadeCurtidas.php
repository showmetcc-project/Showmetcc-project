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
    responder(['erro' => 'Não informe ID na URL'], 400);
}
$dados = lerJson();
$idPost = filter_var($dados['id_post'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($idPost === false) {
    responder(['erro' => 'id_post deve ser um inteiro positivo'], 400);
}
$conn->begin_transaction();
try {
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
        $conn->rollback();
        responder(['erro' => 'Publicação não encontrada'], 404);
    }
    $stmt->close();
    $stmt = $conn->prepare('DELETE FROM comunidade_curtida WHERE id_post = ? AND id_usuario = ?');
    $stmt->bind_param('ii', $idPost, $idUsuario);
    executarStatementApi($stmt);
    $curtido = $stmt->affected_rows === 0;
    $stmt->close();
    if ($curtido) {
        $stmt = $conn->prepare('INSERT INTO comunidade_curtida (id_post, id_usuario) VALUES (?, ?)');
        $stmt->bind_param('ii', $idPost, $idUsuario);
        executarStatementApi($stmt);
        $stmt->close();
    }
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM comunidade_curtida WHERE id_post = ?');
    $stmt->bind_param('i', $idPost);
    executarStatementApi($stmt);
    $total = (int) $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->commit();
} catch (Throwable $erro) {
    $conn->rollback();
    throw $erro;
}
responder(['curtido' => $curtido, 'total_curtidas' => $total]);
