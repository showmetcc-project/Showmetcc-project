<?php

require_once('../assets/config/conexao.php');
/*usuario */

$nome = $_POST["nome"];
$apelido = $_POST["apelido"];
$sobrenome = $_POST["sobrenome"];
$email = $_POST["email"];
$senha = $_POST["senha"];

$sql = "INSERT INTO usuario (nome_user, apelido_user, sobrenome, email_user, senha_user) VALUES (:nome, :apelido, :sobrenome, :email, :senha)";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':nome', $nome);
$stmt->bindParam(':apelido', $apelido);
$stmt->bindParam(':sobrenome', $sobrenome);
$stmt->bindParam(':email', $email);
$stmt->bindParam(':senha', $senha);

if ($stmt->execute()) {
    header("Location: ../../login.html?sucesso=1");
    exit;
} else {
    header("Location: ../../cadastro.html?erro=1");
    exit;
}

/**********/


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

/*
if ($stmt->execute()) {
    echo "Usuário cadastrado com sucesso!";
} else {
    echo "Erro ao cadastrar usuário.";
} */