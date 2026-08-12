<?php

header("Content-Type: application/json; charset=UTF-8");

require_once('../config/conexao.php');

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Método não permitido."
    ]);

    exit;
}

$id_user = $_POST["id_user"] ?? null;
$id_evento = $_POST["id_evento"] ?? null;
$nota = $_POST["nota"] ?? null;
$comentario = trim($_POST["comentario"] ?? "");

if (!$id_user || !$id_evento || !$nota) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Usuário, evento e nota são obrigatórios."
    ]);

    exit;
}

$nota = intval($nota);

if ($nota < 1 || $nota > 5) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "A nota deve estar entre 1 e 5."
    ]);

    exit;
}



$sql = "
    SELECT id_avaliacao
    FROM avaliacao
    WHERE id_user = ?
    AND id_evento = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $id_user, $id_evento);
$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows > 0) {
    http_response_code(409);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Você já avaliou este evento."
    ]);

    exit;
}



$sql = "
    INSERT INTO avaliacao
    (
        id_user,
        id_evento,
        nota,
        comentario,
        data_avaliacao
    )
    VALUES (?, ?, ?, ?, NOW())
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "iiis",
    $id_user,
    $id_evento,
    $nota,
    $comentario
);

if (!$stmt->execute()) {
    http_response_code(500);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Erro ao cadastrar avaliação."
    ]);

    exit;
}

$id_avaliacao = $conn->insert_id;


/*

| Upload de fotos

*/
