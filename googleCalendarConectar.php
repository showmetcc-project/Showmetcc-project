<?php

require_once __DIR__ . '/config/verificaLogin.php';
require_once __DIR__ . '/api/middleware/googleCalendarHelper.php';

if (!googleCalendarConfigurado()) {
    header('Location: perfilUsuario.php?calendar=configuracao');
    exit;
}

$retorno = retornoGoogleCalendarSeguro($_GET['retorno'] ?? null);
$_SESSION['google_calendar_retorno'] = $retorno;
$_SESSION['google_calendar_estado'] = bin2hex(random_bytes(32));

$cliente = criarClienteGoogleCalendar();
$cliente->setState($_SESSION['google_calendar_estado']);

header('Location: ' . $cliente->createAuthUrl());
exit;
