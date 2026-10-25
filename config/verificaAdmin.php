<?php

require_once __DIR__ . '/sessao.php';
iniciarSessaoSegura();

if (!isset($_SESSION['id_user']) || ($_SESSION['tipo_usuario'] ?? '') !== 'admin') {
    header('Location: loginAdmin.php');
    exit();
}
