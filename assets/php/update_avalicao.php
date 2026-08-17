<?php

session_start();

require_once __DIR__ . '/conexao.php';


// Usuário logado
$id_user = $_SESSION['id_user']
    ?? ($_SESSION['usuario']['id_user'] ?? null);

if (!$id_user) {
    die('Usuário não autenticado.');
}


// Dados recebidos
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

$nota = filter_input(
    INPUT_POST,
    'nota',
    FILTER_VALIDATE_INT
);

$comentario = trim(
    $_POST['comentario'] ?? ''
);


// Validação
if (!$id_avaliacao || !$id_evento) {
    die('Dados da avaliação inválidos.');
}

if (!$nota || $nota < 1 || $nota > 5) {
    die('A nota deve estar entre 1 e 5.');
}

if ($comentario === '') {
    die('O comentário não pode ficar vazio.');
}


// Atualiza somente se a avaliação
// pertencer ao usuário logado
$sql = "UPDATE avaliacao
        SET nota = ?,
            comentario = ?
        WHERE id_avaliacao = ?
        AND id_user = ?
        AND id_evento = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    'isiii',
    $nota,
    $comentario,
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
        "Erro ao atualizar avaliação: " .
        htmlspecialchars($erro)
    );
}