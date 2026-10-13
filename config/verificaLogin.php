<?php

require_once __DIR__ . '/sessao.php';
iniciarSessaoSegura();

if (!isset($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
