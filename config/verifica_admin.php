<?php

require_once __DIR__ . '/verifica_login.php';

if (($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: loginAdmin.php');
    exit;
}
