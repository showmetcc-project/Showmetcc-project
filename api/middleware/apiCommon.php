<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function responder($dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function lerJson(bool $detalharErro = false): array
{
    $conteudo = file_get_contents('php://input');

    if ($conteudo === false || trim($conteudo) === '') {
        if ($detalharErro) {
            responder(['erro' => 'Nenhum dado foi enviado'], 400);
        }

        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    $dados = json_decode($conteudo, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        if ($detalharErro) {
            responder([
                'erro' => 'Corpo JSON inválido',
                'detalhes' => json_last_error_msg()
            ], 400);
        }

        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    return $dados;
}

function obterIdApi(): ?int
{
    if (!array_key_exists('id', $_GET)) {
        return null;
    }

    $idInformado = $_GET['id'];

    if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    return (int) $idInformado;
}

require_once dirname(__DIR__, 2) . '/config/sessao.php';

iniciarSessaoSegura();

function obterTokenCsrf(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function renovarTokenCsrf(): string
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

function validarTokenCsrf(): void
{
    $tokenEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $tokenSessao = $_SESSION['csrf_token'] ?? '';

    if (!is_string($tokenEnviado) || !is_string($tokenSessao)
        || $tokenEnviado === '' || $tokenSessao === ''
        || !hash_equals($tokenSessao, $tokenEnviado)) {
        responder(['erro' => 'Token CSRF inválido ou ausente.'], 403);
    }
}

// Limites simples por sessão para mitigar spam e tentativas excessivas.
const SHOWME_LIMITE_LOGIN = 5;
const SHOWME_JANELA_LOGIN = 900;
const SHOWME_LIMITE_DENUNCIAS = 3;
const SHOWME_JANELA_DENUNCIAS = 600;

function obterRegistrosLimiteSessao(string $chave, int $janelaSegundos): array
{
    $registros = $_SESSION['limites_sessao'][$chave] ?? [];

    if (!is_array($registros)) {
        $registros = [];
    }

    $limiteTemporal = time() - $janelaSegundos;
    $registros = array_values(array_filter(
        $registros,
        static fn($registro) => is_int($registro) && $registro > $limiteTemporal
    ));

    if ($registros === []) {
        unset($_SESSION['limites_sessao'][$chave]);
    } else {
        $_SESSION['limites_sessao'][$chave] = $registros;
    }

    return $registros;
}

function verificarLimiteSessao(
    string $chave,
    int $limite,
    int $janelaSegundos,
    string $mensagem
): void {
    $registros = obterRegistrosLimiteSessao($chave, $janelaSegundos);

    if (count($registros) < $limite) {
        return;
    }

    $segundos = max(1, min($registros) + $janelaSegundos - time());
    header('Retry-After: ' . $segundos);
    responder([
        'erro' => $mensagem,
        'tentar_novamente_em' => $segundos,
    ], 429);
}

function registrarTentativaSessao(string $chave, int $janelaSegundos): void
{
    $registros = obterRegistrosLimiteSessao($chave, $janelaSegundos);
    $registros[] = time();
    $_SESSION['limites_sessao'][$chave] = $registros;
}

if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'], true)) {
    validarTokenCsrf();
}
