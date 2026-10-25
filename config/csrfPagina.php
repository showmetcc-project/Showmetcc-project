<?php

require_once __DIR__ . '/sessao.php';

/**
 * CSRF para formulários PHP tradicionais (recuperação e redefinição de senha).
 * A API usa as funções equivalentes de api/middleware/apiCommon.php.
 */
function obterTokenCsrfPagina(): string
{
    iniciarSessaoSegura();

    if (empty($_SESSION['csrf_token_pagina']) || !is_string($_SESSION['csrf_token_pagina'])) {
        $_SESSION['csrf_token_pagina'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token_pagina'];
}

function validarTokenCsrfPagina(?string $tokenEnviado): bool
{
    iniciarSessaoSegura();
    $tokenSessao = $_SESSION['csrf_token_pagina'] ?? '';

    return is_string($tokenSessao) &&
        $tokenSessao !== '' &&
        is_string($tokenEnviado) &&
        $tokenEnviado !== '' &&
        hash_equals($tokenSessao, $tokenEnviado);
}
