<?php

header("Content-Type: application/json; charset=UTF-8");

require_once('../config/conexao.php');

if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "Método não permitido."
    ]);

    exit;
}

$id_evento = $_GET["id_evento"] ?? null;

if (!$id_evento) {
    http_response_code(400);

    echo json_encode([
        "sucesso" => false,
        "mensagem" => "ID do evento não informado."
    ]);

    exit;
}

$id_evento = intval($id_evento);

$sql = "
    SELECT 
        a.id_avaliacao,
        a.nota,
        a.comentario,
        a.data_avaliacao,
        u.nome_user,
        u.sobrenome
    FROM avaliacao a
    INNER JOIN usuario u 
        ON a.id_user = u.id_user
    WHERE a.id_evento = ?
    ORDER BY a.data_avaliacao DESC
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id_evento);

$stmt->execute();

$resultado = $stmt->get_result();

$avaliacoes = [];

while ($avaliacao = $resultado->fetch_assoc()) {

    $avaliacoes[] = [
        "id_avaliacao" => $avaliacao["id_avaliacao"],
        "nome" => $avaliacao["nome_user"] . " " . $avaliacao["sobrenome"],
        "comentario" => $avaliacao["comentario"],
        "nota" => intval($avaliacao["nota"]),
        "data" => date(
            "d/m/Y",
            strtotime($avaliacao["data_avaliacao"])
        )
    ];
}

echo json_encode([
    "sucesso" => true,
    "avaliacoes" => $avaliacoes
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();

?>