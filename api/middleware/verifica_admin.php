<?php

require_once __DIR__ . '/verifica_login.php';

function exigirAdmin(): int
{
    $idUsuario = exigirLogin();

    if (($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
        responder(['erro' => 'Acesso restrito a administradores'], 403);
    }

    return $idUsuario;
}
