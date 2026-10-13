<?php

require_once dirname(__DIR__, 2) . '/config/sessao.php';

function exigirLogin(): int
{
    iniciarSessaoSegura();

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
