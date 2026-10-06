<?php

require_once __DIR__ . '/verificaLogin.php';

if (($_SESSION['tipo_usuario'] ?? 'comum') === 'admin') {
    header('Location: admin.php');
    exit;
}
