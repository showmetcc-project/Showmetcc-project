<?php

function responderErroInfraestrutura(string $contexto, ?Throwable $erro = null): never
{
    $detalhe = $erro === null ? '' : ': ' . $erro->getMessage();
    error_log("$contexto$detalhe");

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
    }

    http_response_code(500);
    echo json_encode(
        ['erro' => 'Erro interno do servidor'],
        JSON_UNESCAPED_UNICODE
    );
    exit();
}
