<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';

$idUsuario = exigirLogin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(['erro' => 'Método não permitido'], 405);
}
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
$tabela = $temPost ? 'comunidade_post' : 'comunidade_midia';
$campoId = $temPost ? 'id_post' : 'id_midia';
$idAlvo = $temPost ? $idPost : $idMidia;
$stmt = $conn->prepare("SELECT id_usuario FROM $tabela WHERE $campoId = ? LIMIT 1");
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
$stmt = $conn->prepare('INSERT INTO comunidade_denuncia (id_post, id_midia, id_usuario, motivo) VALUES (?, ?, ?, ?)');
$valorPost = $temPost ? $idPost : null;
$valorMidia = $temMidia ? $idMidia : null;
$stmt->bind_param('iiis', $valorPost, $valorMidia, $idUsuario, $motivo);
executarStatementApi($stmt);
$idDenuncia = $conn->insert_id;
$stmt->close();
responder(['mensagem' => 'Denúncia registrada', 'id_denuncia' => (int) $idDenuncia], 201);
