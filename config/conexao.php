<?php

require_once __DIR__ . '/erroInfraestrutura.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "showme";

try {
    $conn = new mysqli(
        $host,
        $usuario,
        $senha,
        $banco
    );

    if ($conn->connect_errno !== 0) {
        throw new RuntimeException($conn->connect_error);
    }

    if (!$conn->set_charset('utf8mb4')) {
        throw new RuntimeException($conn->error);
    }
} catch (Throwable $erro) {
    responderErroInfraestrutura('Falha ao conectar/configurar o banco de dados', $erro);
}
