<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!isset($_SESSION['id_user']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: loginAdmin.php');
    exit;
}
