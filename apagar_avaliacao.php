<?php

session_start();

require_once __DIR__ . '/conexao.php';


// Usuário logado
$id_user = $_SESSION['id_user']
    ?? ($_SESSION['usuario']['id_user'] ?? null);

if (!$id_user) {
    die('Usuário não autenticado.');
}


// Dados
$id_avaliacao = filter_input(
    INPUT_POST,
    'id_avaliacao',
    FILTER_VALIDATE_INT
);

$id_evento = filter_input(
    INPUT_POST,
    'id_evento',
    FILTER_VALIDATE_INT
);


if (!$id_avaliacao || !$id_evento) {
    die('Dados inválidos.');
}


// Apaga somente a avaliação
// pertencente ao usuário logado
$sql = "DELETE FROM avaliacao
        WHERE id_avaliacao = ?
        AND id_user = ?
        AND id_evento = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    'iii',
    $id_avaliacao,
    $id_user,
    $id_evento
);

if ($stmt->execute()) {

    $stmt->close();

    header(
        "Location: ../detalhesEvento.php?id_evento=" .
        $id_evento .
        "#avaliacoes"
    );

    exit;

} else {

    $erro = $stmt->error;

    $stmt->close();

    die(
        "Erro ao apagar avaliação: " .
        htmlspecialchars($erro)
    );
}