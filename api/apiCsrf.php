<?php

require_once __DIR__ . '/../config/sessao.php';

iniciarSessaoSegura();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode([
    'csrf_token' => $_SESSION['csrf_token']
], JSON_UNESCAPED_UNICODE);