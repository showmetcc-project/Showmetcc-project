<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "DELETE") {

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


if (!$id_avaliacao || !$id_user) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Dados incompletos."
    ]);

    exit;
}


$sql = "
    DELETE FROM avaliacao

    WHERE
        id_avaliacao = ?
        AND id_user = ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $id_avaliacao,
    $id_user
);

$stmt->execute();


if ($stmt->affected_rows > 0) {

    echo json_encode([
        "sucesso" => true,
        "mensagem" => "Avaliação excluída com sucesso."
    ]);

} else {

    http_response_code(404);

    echo json_encode([
        "sucesso" => false,
        "mensagem" =>
            "Avaliação não encontrada ou você não possui permissão para excluí-la."
    ]);
}


$stmt->close();
$conn->close();

?>