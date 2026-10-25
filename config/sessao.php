<?php

/**
 * Inicia a sessão com as mesmas proteções em páginas, APIs e callbacks OAuth.
 * O cookie `secure` é habilitado apenas em HTTPS para manter o XAMPP local (HTTP)
 * funcional; em produção HTTPS ele é sempre enviado somente por conexão segura.
 */
function iniciarSessaoSegura(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $httpsAtivo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443);

    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $httpsAtivo,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
// Inicia a sessão automaticamente ao incluir este arquivo.

