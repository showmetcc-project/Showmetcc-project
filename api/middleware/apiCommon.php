<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function responder($dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function lerJson(bool $detalharErro = false): array
{
    $conteudo = file_get_contents('php://input');

    if ($conteudo === false || trim($conteudo) === '') {
        if ($detalharErro) {
            responder(['erro' => 'Nenhum dado foi enviado'], 400);
        }

        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    $dados = json_decode($conteudo, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        if ($detalharErro) {
            responder([
                'erro' => 'Corpo JSON inválido',
                'detalhes' => json_last_error_msg()
            ], 400);
        }

        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    return $dados;
}

function obterIdApi(): ?int
{
    if (!array_key_exists('id', $_GET)) {
        return null;
    }

    $idInformado = $_GET['id'];

    if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    return (int) $idInformado;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
