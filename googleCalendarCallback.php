<?php

require_once __DIR__ . '/config/verificaLogin.php';
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/api/middleware/googleCalendarHelper.php';

$retorno = retornoGoogleCalendarSeguro($_SESSION['google_calendar_retorno'] ?? null);
$estadoEsperado = (string) ($_SESSION['google_calendar_estado'] ?? '');
unset($_SESSION['google_calendar_retorno'], $_SESSION['google_calendar_estado']);

if (isset($_GET['error'])) {
    header('Location: ' . adicionarParametroRetorno($retorno, 'calendar', 'cancelado'));
    exit;
}

$estadoRecebido = (string) ($_GET['state'] ?? '');
$codigo = (string) ($_GET['code'] ?? '');

if ($estadoEsperado === '' || !hash_equals($estadoEsperado, $estadoRecebido) || $codigo === '') {
    header('Location: ' . adicionarParametroRetorno($retorno, 'calendar', 'estado_invalido'));
    exit;
}

try {
    $cliente = criarClienteGoogleCalendar();
    $token = $cliente->fetchAccessTokenWithAuthCode($codigo);

    if (isset($token['error']) || empty($token['access_token'])) {
        throw new RuntimeException('O Google não retornou um access token válido');
    }

    $refreshToken = (string) ($token['refresh_token'] ?? '');

    if ($refreshToken === '') {
        $existente = buscarTokenGoogleCalendar($conn, (int) $_SESSION['id_user']);
        $refreshToken = (string) ($existente['refresh_token'] ?? '');
    }

    if ($refreshToken === '') {
        throw new RuntimeException('O Google não retornou o refresh token');
    }

    salvarTokenGoogleCalendar(
        $conn,
        (int) $_SESSION['id_user'],
        (string) $token['access_token'],
        $refreshToken,
        time() + (int) ($token['expires_in'] ?? 3600)
    );

    header('Location: ' . adicionarParametroRetorno($retorno, 'calendar', 'conectado'));
    exit;
} catch (Throwable $erro) {
    error_log('Falha no callback do Google Agenda: ' . $erro->getMessage());
    header('Location: ' . adicionarParametroRetorno($retorno, 'calendar', 'erro'));
    exit;
}
