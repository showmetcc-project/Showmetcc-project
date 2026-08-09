<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "../config/conexao.php";

$id_evento = $_GET["id_evento"] ?? null;

if (!$id_evento) {

    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Evento não informado."
    ]);

    exit;
}

$sql = "
    SELECT
        a.id_avaliacao,
        a.id_user,
        a.id_evento,
        a.nota,
        a.comentario,
        a.data_avaliacao,
        u.nome_completo
    FROM avaliacao a

    INNER JOIN usuario u
        ON u.id_user = a.id_user

    WHERE a.id_evento = ?

    ORDER BY a.data_avaliacao DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id_evento);

$stmt->execute();

$resultado = $stmt->get_result();

$avaliacoes = [];

while ($avaliacao = $resultado->fetch_assoc()) {

    $avaliacao["nota"] =
        intval($avaliacao["nota"]);

    $avaliacoes[] = $avaliacao;
}


/*
|--------------------------------------------------------------------------
| Média das avaliações
|--------------------------------------------------------------------------
*/

$sqlMedia = "
    SELECT
        COUNT(*) AS total,
        COALESCE(AVG(nota), 0) AS media
    FROM avaliacao
    WHERE id_evento = ?
";

$stmtMedia = $conn->prepare($sqlMedia);

$stmtMedia->bind_param("i", $id_evento);

$stmtMedia->execute();

$resultadoMedia =
    $stmtMedia->get_result();

$dadosMedia =
    $resultadoMedia->fetch_assoc();


echo json_encode([

    "sucesso" => true,

    "media" => round(
        floatval($dadosMedia["media"]),
        1
    ),

    "total" =>
        intval($dadosMedia["total"]),

    "avaliacoes" =>
        $avaliacoes

], JSON_UNESCAPED_UNICODE);


$stmt->close();
$stmtMedia->close();

$conn->close();

?>