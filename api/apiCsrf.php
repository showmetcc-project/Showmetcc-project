<?php

require_once __DIR__ . '/middleware/apiCommon.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder(['erro' => 'Método não permitido'], 405);
}

responder(['csrf_token' => obterTokenCsrf()]);
