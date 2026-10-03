<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();
$idUsuario = exigirLogin();

if ($metodo === 'GET') {
    if ($id !== null) {
        responder(['erro' => 'Use evento_id para listar a galeria'], 400);
    }
    $idEvento = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($idEvento === false || $idEvento === null) {
        responder(['erro' => 'evento_id deve ser um inteiro positivo'], 400);
    }
    $stmt = $conn->prepare(
        'SELECT m.id_midia, m.id_evento, m.id_usuario, m.caminho_arquivo, m.legenda,
                m.permitir_download, m.data_criacao, u.nome_user, u.sobrenome, u.foto_perfil
         FROM comunidade_midia m
         INNER JOIN usuario u ON u.id_user = m.id_usuario
         WHERE m.id_evento = ?
         ORDER BY m.data_criacao DESC, m.id_midia DESC'
    );
    $stmt->bind_param('i', $idEvento);
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $midias = [];
    while ($midia = $resultado->fetch_assoc()) {
        $midias[] = normalizarMidiaComunidadeApi($midia);
    }
    $stmt->close();
    responder(['midias' => $midias]);
}

if ($metodo === 'POST') {
    if ($id !== null) {
        responder(['erro' => 'Não informe ID para publicar uma foto'], 400);
    }
    $idEvento = filter_var($_POST['id_evento'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $legenda = trim((string) ($_POST['legenda'] ?? ''));
    $permitirDownload = filter_var($_POST['permitir_download'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    if ($idEvento === false || !isset($_FILES['midia'])) {
        responder(['erro' => 'Evento e imagem são obrigatórios'], 400);
    }
    if (tamanhoTextoApi($legenda) > 255) {
        responder(['erro' => 'legenda deve ter no máximo 255 caracteres'], 400);
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
    try {
        $arquivo = salvarArquivoUpload($_FILES['midia'], ['foto'], 'comunidade');
    } catch (UploadInvalidoException $erro) {
        responder(['erro' => $erro->getMessage()], 400);
    }
    try {
        $stmt = $conn->prepare(
            'INSERT INTO comunidade_midia (id_evento, id_usuario, caminho_arquivo, legenda, permitir_download)
             VALUES (?, ?, ?, ?, ?)'
        );
        $legendaBanco = $legenda === '' ? null : $legenda;
        $stmt->bind_param('iissi', $idEvento, $idUsuario, $arquivo['caminho_arquivo'], $legendaBanco, $permitirDownload);
        executarStatementApi($stmt);
        $idMidia = $conn->insert_id;
        $stmt->close();
    } catch (Throwable $erro) {
        removerArquivoUpload($arquivo['caminho_arquivo']);
        throw $erro;
    }
    responder([
        'mensagem' => 'Foto publicada com sucesso',
        'midia' => normalizarMidiaComunidadeApi([
            'id_midia' => $idMidia,
            'id_evento' => $idEvento,
            'id_usuario' => $idUsuario,
            'caminho_arquivo' => $arquivo['caminho_arquivo'],
            'legenda' => $legendaBanco,
            'permitir_download' => $permitirDownload,
            'data_criacao' => (new DateTimeImmutable('now', new DateTimeZone(API_FUSO_HORARIO)))
                ->format('Y-m-d H:i:s')
        ])
    ], 201);
}

if ($metodo === 'DELETE') {
    if ($id === null) {
        responder(['erro' => 'Informe o ID da mídia na URL'], 400);
    }
    $stmt = $conn->prepare('SELECT id_usuario, caminho_arquivo FROM comunidade_midia WHERE id_midia = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $midia = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$midia) {
        responder(['erro' => 'Foto não encontrada'], 404);
    }
    if ((int) $midia['id_usuario'] !== $idUsuario) {
        responder(['erro' => 'Você só pode excluir sua própria foto'], 403);
    }
    $stmt = $conn->prepare('DELETE FROM comunidade_midia WHERE id_midia = ?');
    $stmt->bind_param('i', $id);
    executarStatementApi($stmt);
    $stmt->close();
    removerArquivosUploadSemReferencia($conn, [$midia['caminho_arquivo']]);
    responder(['mensagem' => 'Foto excluída com sucesso']);
}

responder(['erro' => 'Método não permitido'], 405);
