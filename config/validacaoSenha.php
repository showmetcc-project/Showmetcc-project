<?php

/**
 * Política única de força de senha usada pelo cadastro, pela alteração no
 * perfil e pela redefinição via e-mail.
 */
function validarRequisitosSenha(string $senha): ?string
{
    if (strlen($senha) < 6 || !preg_match('/[A-Za-z]/', $senha) || !preg_match('/\d/', $senha)) {
        return 'A senha deve ter no mínimo 6 caracteres e conter letras e números.';
    }

    return null;
}
