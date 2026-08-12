<?php

header("Content-Type: application/json; charset=UTF-8");

require_once "php/conexao.php";

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

$arquivosSalvos = [];

if (isset($_FILES["arquivos"])) {

    $pasta = "../uploads/avaliacoes/";

    if (!is_dir($pasta)) {
        mkdir($pasta, 0777, true);
    }

    $quantidade = count($_FILES["arquivos"]["name"]);

    for ($i = 0; $i < $quantidade; $i++) {

        if ($_FILES["arquivos"]["error"][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $nomeOriginal = $_FILES["arquivos"]["name"][$i];
        $temporario = $_FILES["arquivos"]["tmp_name"][$i];

        $extensao = strtolower(
            pathinfo($nomeOriginal, PATHINFO_EXTENSION)
        );

        $extensoesPermitidas = [
            "jpg",
            "jpeg",
            "png",
            "webp",
            "mp4",
            "webm"
        ];

        if (!in_array($extensao, $extensoesPermitidas)) {
            continue;
        }

        $novoNome =
            uniqid("avaliacao_", true)
            . "."
            . $extensao;

        $destino = $pasta . $novoNome;

        if (move_uploaded_file($temporario, $destino)) {

            $arquivosSalvos[] = $novoNome;

        
        }
    }
}


echo json_encode([
    "sucesso" => true,
    "mensagem" => "Avaliação enviada com sucesso.",
    "id_avaliacao" => $id_avaliacao,
    "arquivos" => $arquivosSalvos
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();

?>