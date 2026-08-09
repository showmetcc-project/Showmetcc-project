<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/conexao.php";

$id_evento = $_GET["id_evento"] ?? null;

if (!$id_evento) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID do evento não informado."
    ]);

    exit;
}

$sql = "
    SELECT
        id_evento,
        nome,
        cidade,
        uf,
        latitude,
        longitude
    FROM evento
    WHERE id_evento = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id_evento);

$stmt->execute();

$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {

    http_response_code(404);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Evento não encontrado."
    ]);

    exit;
}

$evento = $resultado->fetch_assoc();

$evento["latitude"] = floatval($evento["latitude"]);
$evento["longitude"] = floatval($evento["longitude"]);

echo json_encode([
    "sucesso" => true,
    "evento" => $evento
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();

?>