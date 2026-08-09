<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "PUT") {

    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Método não permitido."
    ]);

    exit;
}

$dados = json_decode(
    file_get_contents("php://input"),
    true
);

$id_avaliacao =
    $dados["id_avaliacao"] ?? null;

$id_user =
    $dados["id_user"] ?? null;

$nota =
    $dados["nota"] ?? null;

$comentario =
    trim($dados["comentario"] ?? "");


if (!$id_avaliacao || !$id_user || !$nota) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Dados incompletos."
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
    UPDATE avaliacao

    SET
        nota = ?,
        comentario = ?

    WHERE
        id_avaliacao = ?
        AND id_user = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "isii",
    $nota,
    $comentario,
    $id_avaliacao,
    $id_user
);

$stmt->execute();


if ($stmt->affected_rows > 0) {

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Avaliação atualizada."
    ]);

} else {

    http_response_code(404);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Avaliação não encontrada ou não pertence a este usuário."
    ]);
}


$stmt->close();
$conn->close();

?>