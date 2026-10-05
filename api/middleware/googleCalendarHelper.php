<?php

use Google\Client as GoogleClient;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

const GOOGLE_CALENDAR_ESCOPO_LEITURA = 'https://www.googleapis.com/auth/calendar.readonly';
const GOOGLE_CALENDAR_ESCOPO_EVENTOS = 'https://www.googleapis.com/auth/calendar.events';

function configuracaoGoogleCalendar(): array
{
    static $configuracao = null;

    if (is_array($configuracao)) {
        return $configuracao;
    }

    $arquivoReal = dirname(__DIR__, 2) . '/config/googleCalendar.php';
    $arquivoExemplo = dirname(__DIR__, 2) . '/config/googleCalendar.example.php';
    $arquivo = is_file($arquivoReal) ? $arquivoReal : $arquivoExemplo;
    $carregada = require $arquivo;

    if (!is_array($carregada)) {
        throw new RuntimeException('Configuração do Google Agenda inválida');
    }

    $configuracao = $carregada + [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => '',
        'timezone' => 'America/Sao_Paulo',
    ];

    return $configuracao;
}

function googleCalendarConfigurado(): bool
{
    $configuracao = configuracaoGoogleCalendar();

    return trim((string) $configuracao['client_id']) !== ''
        && trim((string) $configuracao['client_secret']) !== ''
        && filter_var($configuracao['redirect_uri'], FILTER_VALIDATE_URL) !== false;
}

function criarClienteGoogleCalendar(): GoogleClient
{
    if (!googleCalendarConfigurado()) {
        throw new RuntimeException('Google Agenda ainda não foi configurado');
    }

    $configuracao = configuracaoGoogleCalendar();
    $cliente = new GoogleClient();
    $cliente->setClientId($configuracao['client_id']);
    $cliente->setClientSecret($configuracao['client_secret']);
    $cliente->setRedirectUri($configuracao['redirect_uri']);
    $cliente->setScopes([
        GOOGLE_CALENDAR_ESCOPO_LEITURA,
        GOOGLE_CALENDAR_ESCOPO_EVENTOS,
    ]);
    $cliente->setAccessType('offline');
    $cliente->setPrompt('consent');
    $cliente->setIncludeGrantedScopes(true);

    return $cliente;
}

function buscarTokenGoogleCalendar(mysqli $conn, int $idUsuario): ?array
{
    $stmt = $conn->prepare(
        'SELECT access_token, refresh_token, expira_em, data_conexao
         FROM google_calendar_token
         WHERE id_usuario = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $token = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $token;
}

function salvarTokenGoogleCalendar(
    mysqli $conn,
    int $idUsuario,
    string $accessToken,
    string $refreshToken,
    int $expiraEmUnix
): void {
    $expiraEm = (new DateTimeImmutable('@' . $expiraEmUnix))
        ->setTimezone(new DateTimeZone('UTC'))
        ->format('Y-m-d H:i:s');

    $stmt = $conn->prepare(
        'INSERT INTO google_calendar_token
            (id_usuario, access_token, refresh_token, expira_em)
         VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            access_token = VALUES(access_token),
            refresh_token = VALUES(refresh_token),
            expira_em = VALUES(expira_em),
            data_conexao = CURRENT_TIMESTAMP'
    );
    $stmt->bind_param('isss', $idUsuario, $accessToken, $refreshToken, $expiraEm);
    $stmt->execute();
    $stmt->close();
}

function clienteGoogleCalendarAutorizado(mysqli $conn, int $idUsuario): ?GoogleClient
{
    $tokenSalvo = buscarTokenGoogleCalendar($conn, $idUsuario);

    if ($tokenSalvo === null) {
        return null;
    }

    $cliente = criarClienteGoogleCalendar();
    $expiraEm = new DateTimeImmutable($tokenSalvo['expira_em'], new DateTimeZone('UTC'));
    $agora = new DateTimeImmutable('now', new DateTimeZone('UTC'));

    $cliente->setAccessToken([
        'access_token' => $tokenSalvo['access_token'],
        'refresh_token' => $tokenSalvo['refresh_token'],
        'expires_in' => max(1, $expiraEm->getTimestamp() - $agora->getTimestamp()),
        'created' => $agora->getTimestamp(),
    ]);

    if ($expiraEm <= $agora->modify('+60 seconds')) {
        $novoToken = $cliente->fetchAccessTokenWithRefreshToken($tokenSalvo['refresh_token']);

        if (isset($novoToken['error']) || empty($novoToken['access_token'])) {
            throw new RuntimeException('Não foi possível renovar a autorização do Google Agenda');
        }

        $refreshToken = $novoToken['refresh_token'] ?? $tokenSalvo['refresh_token'];
        $expiraEmUnix = time() + (int) ($novoToken['expires_in'] ?? 3600);
        salvarTokenGoogleCalendar(
            $conn,
            $idUsuario,
            (string) $novoToken['access_token'],
            (string) $refreshToken,
            $expiraEmUnix
        );

        $novoToken['refresh_token'] = $refreshToken;
        $cliente->setAccessToken($novoToken);
    }

    return $cliente;
}

function removerTokenGoogleCalendar(mysqli $conn, int $idUsuario): void
{
    $stmt = $conn->prepare('DELETE FROM google_calendar_token WHERE id_usuario = ?');
    $stmt->bind_param('i', $idUsuario);
    $stmt->execute();
    $stmt->close();
}

function retornoGoogleCalendarSeguro(?string $retorno): string
{
    $retorno = trim((string) $retorno);

    if (
        $retorno === ''
        || str_contains($retorno, '://')
        || str_starts_with($retorno, '//')
        || preg_match('/[\r\n]/', $retorno)
        || !preg_match('/^[a-zA-Z][a-zA-Z0-9]*\.php(?:\?[a-zA-Z0-9_=&%.-]*)?$/', $retorno)
    ) {
        return 'perfilUsuario.php';
    }

    return $retorno;
}

function adicionarParametroRetorno(string $url, string $nome, string $valor): string
{
    return $url . (str_contains($url, '?') ? '&' : '?')
        . rawurlencode($nome) . '=' . rawurlencode($valor);
}
