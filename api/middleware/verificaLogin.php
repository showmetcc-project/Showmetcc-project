<?php

function exigirLogin(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['id_user'])) {
        responder(['erro' => 'Autenticação necessária'], 401);
    }

    return (int) $_SESSION['id_user'];
}

function exigirUsuarioComum(): int
{
    $idUsuario = exigirLogin();

    if (($_SESSION['tipo_usuario'] ?? 'comum') === 'admin') {
        responder(['erro' => 'Esta ação está disponível apenas para usuários comuns'], 403);
    }

    return $idUsuario;
}
