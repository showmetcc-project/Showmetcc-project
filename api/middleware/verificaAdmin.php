<?php

require_once __DIR__ . '/verificaLogin.php';

function exigirAdmin(): int
{
    $idUsuario = exigirLogin();

    if (($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
        responder(['erro' => 'Acesso restrito a administradores'], 403);
    }

    return $idUsuario;
}
