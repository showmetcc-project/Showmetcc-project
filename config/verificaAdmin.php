<?php

require_once __DIR__ . '/verificaLogin.php';

if (($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: loginAdmin.php');
    exit;
}
