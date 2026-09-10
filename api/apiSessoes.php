<?php

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function responder($dados, $status = 200)
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function lerJson(): array
{
    $conteudo = file_get_contents('php://input');
    $dados = json_decode($conteudo, true);

    if (!is_array($dados)) {
        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    return $dados;
}

function responderLogin(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['id_user'] = (int) $usuario['id_user'];
    $_SESSION['nome_user'] = $usuario['nome_user'];
    $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

    unset($usuario['senha_user'], $usuario['google_id']);
    responder(['mensagem' => 'Login realizado com sucesso', 'usuario' => $usuario], 201);
}

function carregarConfiguracaoGoogle(): array
{
    $arquivoConfiguracao = dirname(__DIR__) . '/config/google.php';

    if (!is_file($arquivoConfiguracao)) {
        responder(['erro' => 'Login com Google ainda não foi configurado'], 503);
    }

    $configuracao = require $arquivoConfiguracao;
    $clientId = is_array($configuracao) ? trim((string) ($configuracao['client_id'] ?? '')) : '';

    if ($clientId === '') {
        responder(['erro' => 'Login com Google ainda não foi configurado'], 503);
    }

    return ['client_id' => $clientId];
}

function limitarTexto(string $texto, int $limite): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($texto, 0, $limite, 'UTF-8');
    }

    return substr($texto, 0, $limite);
}

function decodificarParteJwt(string $parte): ?array
{
    $parte .= str_repeat('=', (4 - strlen($parte) % 4) % 4);
    $conteudo = base64_decode(strtr($parte, '-_', '+/'), true);

    if ($conteudo === false) {
        return null;
    }

    $dados = json_decode($conteudo, true);
    return is_array($dados) ? $dados : null;
}

function responderFalhaTokenGoogle(string $token, string $clientId): void
{
    $partes = explode('.', $token);

    if (count($partes) !== 3) {
        responder(['erro' => 'O Google retornou um token em formato inválido'], 401);
    }

    $cabecalho = decodificarParteJwt($partes[0]);
    $payloadNaoVerificado = decodificarParteJwt($partes[1]);

    if ($cabecalho === null || $payloadNaoVerificado === null) {
        responder(['erro' => 'O Google retornou um token em formato inválido'], 401);
    }

    // Estes dados servem somente para explicar uma rejeição. O login só acontece
    // quando Google\Client::verifyIdToken() valida a assinatura e todas as claims.
    $audiencias = $payloadNaoVerificado['aud'] ?? [];
    $audiencias = is_array($audiencias) ? $audiencias : [$audiencias];

    if (!in_array($clientId, $audiencias, true)) {
        error_log('Token Google rejeitado: audiência diferente do client_id configurado.');
        responder([
            'erro' => 'O token foi emitido para outro Client ID do Google. Atualize a página e confira config/google.php',
        ], 401);
    }

    $agora = time();
    $expiracao = filter_var($payloadNaoVerificado['exp'] ?? null, FILTER_VALIDATE_INT);
    $emitidoEm = filter_var($payloadNaoVerificado['iat'] ?? null, FILTER_VALIDATE_INT);

    if ($expiracao !== false && $expiracao <= $agora) {
        error_log('Token Google rejeitado: expirado.');
        responder(['erro' => 'O token do Google expirou. Tente entrar novamente'], 401);
    }

    if ($emitidoEm !== false && $emitidoEm > $agora + 60) {
        error_log('Token Google rejeitado: relógio local atrasado em relação à emissão.');
        responder(['erro' => 'O relógio do servidor está dessincronizado'], 503);
    }

    $emissor = (string) ($payloadNaoVerificado['iss'] ?? '');

    if (!in_array($emissor, ['accounts.google.com', 'https://accounts.google.com'], true)) {
        error_log('Token Google rejeitado: emissor inesperado.');
        responder(['erro' => 'O token recebido não foi emitido pelo Google'], 401);
    }

    if (($cabecalho['alg'] ?? '') !== 'RS256') {
        error_log('Token Google rejeitado: algoritmo inesperado.');
        responder(['erro' => 'O token do Google usa um algoritmo não aceito'], 401);
    }

    error_log('Token Google rejeitado: assinatura ou chave pública não pôde ser validada.');
    responder(['erro' => 'Não foi possível validar a assinatura do token do Google. Tente novamente'], 401);
}

function autenticarComGoogle(mysqli $conn, string $token): void
{
    if ($token === '') {
        responder(['erro' => 'O token do Google é obrigatório'], 400);
    }

    $configuracao = carregarConfiguracaoGoogle();
    $autoload = dirname(__DIR__) . '/vendor/autoload.php';

    if (!is_file($autoload)) {
        responder(['erro' => 'Dependências do projeto não foram instaladas'], 503);
    }

    require_once $autoload;

    try {
        $clienteGoogle = new Google\Client(['client_id' => $configuracao['client_id']]);
        $payload = $clienteGoogle->verifyIdToken($token);
    } catch (Throwable $erro) {
        error_log(
            'Exceção ao validar token Google (' . get_class($erro) . '): ' . $erro->getMessage()
        );
        responderFalhaTokenGoogle($token, $configuracao['client_id']);
    }

    if (!is_array($payload)) {
        responderFalhaTokenGoogle($token, $configuracao['client_id']);
    }

    $googleId = trim((string) ($payload['sub'] ?? ''));
    $email = trim((string) ($payload['email'] ?? ''));
    $emailVerificado = filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if ($googleId === '' || strlen($googleId) > 255 || !$emailVerificado ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        responder(['erro' => 'O Google não retornou uma identidade válida com e-mail verificado'], 401);
    }

    $nome = limitarTexto(trim((string) ($payload['given_name'] ?? '')), 100);
    $sobrenome = limitarTexto(trim((string) ($payload['family_name'] ?? '')), 100);

    if ($nome === '') {
        $nome = limitarTexto((string) strstr($email, '@', true), 100);
    }

    if ($nome === '') {
        $nome = 'Usuário';
    }

    try {
        $conn->begin_transaction();

        $stmt = $conn->prepare(
            'SELECT id_user, nome_user, sobrenome, email_user, tipo_usuario, google_id
             FROM usuario WHERE google_id = ? LIMIT 1 FOR UPDATE'
        );
        $stmt->bind_param('s', $googleId);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            $stmt = $conn->prepare(
                'SELECT id_user, nome_user, sobrenome, email_user, tipo_usuario, google_id
                 FROM usuario WHERE email_user = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario) {
                if ($usuario['google_id'] !== null && $usuario['google_id'] !== '' &&
                    !hash_equals((string) $usuario['google_id'], $googleId)) {
                    $conn->rollback();
                    responder(['erro' => 'Este e-mail já está vinculado a outra conta Google'], 409);
                }

                $idUsuario = (int) $usuario['id_user'];
                $stmt = $conn->prepare('UPDATE usuario SET google_id = ? WHERE id_user = ?');
                $stmt->bind_param('si', $googleId, $idUsuario);
                $stmt->execute();
                $stmt->close();
                $usuario['google_id'] = $googleId;
            } else {
                $stmt = $conn->prepare(
                    "INSERT INTO usuario
                        (nome_user, sobrenome, email_user, senha_user, google_id, tipo_usuario)
                     VALUES (?, ?, ?, NULL, ?, 'comum')"
                );
                $stmt->bind_param('ssss', $nome, $sobrenome, $email, $googleId);
                $stmt->execute();
                $idUsuario = $stmt->insert_id;
                $stmt->close();

                $usuario = [
                    'id_user' => $idUsuario,
                    'nome_user' => $nome,
                    'sobrenome' => $sobrenome,
                    'email_user' => $email,
                    'tipo_usuario' => 'comum',
                    'google_id' => $googleId,
                ];
            }
        }

        $conn->commit();
    } catch (Throwable $erro) {
        try {
            $conn->rollback();
        } catch (Throwable $erroRollback) {
            // A conexão pode já ter encerrado a transação.
        }

        error_log('Falha no login Google: ' . $erro->getMessage());
        responder(['erro' => 'Não foi possível concluir o login com Google'], 500);
    }

    responderLogin($usuario);
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/conexao.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = null;

if (array_key_exists('id', $_GET)) {
    $idInformado = $_GET['id'];

    if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    $id = (int) $idInformado;
}

if ($id !== null) {
    responder(['erro' => 'O recurso de sessões não recebe ID na URL'], 400);
}

switch ($metodo) {
    case 'GET':
        if (!isset($_SESSION['id_user'])) {
            responder(['erro' => 'Nenhuma sessão ativa'], 401);
        }

        $idUsuario = (int) $_SESSION['id_user'];
        $stmt = $conn->prepare(
            'SELECT id_user, nome_user, sobrenome, email_user, tipo_usuario
             FROM usuario WHERE id_user = ? LIMIT 1'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            session_unset();
            session_destroy();
            responder(['erro' => 'Usuário da sessão não encontrado'], 401);
        }

        responder(['usuario' => $usuario]);

    case 'POST':
        $dados = lerJson();

        if (array_key_exists('google_token', $dados)) {
            autenticarComGoogle($conn, trim((string) $dados['google_token']));
        }

        $email = trim((string) ($dados['email'] ?? ''));
        $senha = (string) ($dados['senha'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
            responder(['erro' => 'E-mail e senha são obrigatórios'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT id_user, nome_user, sobrenome, email_user, senha_user, tipo_usuario
             FROM usuario WHERE email_user = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $usuario = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$usuario) {
            responder(['erro' => 'E-mail ou senha incorretos'], 401);
        }

        if ($usuario['senha_user'] === null) {
            responder(['erro' => 'Esta conta usa login via Google'], 401);
        }

        if (!password_verify($senha, (string) $usuario['senha_user'])) {
            responder(['erro' => 'E-mail ou senha incorretos'], 401);
        }

        responderLogin($usuario);

    case 'DELETE':
        if (!isset($_SESSION['id_user'])) {
            responder(['erro' => 'Nenhuma sessão ativa'], 401);
        }

        session_unset();
        session_destroy();
        responder(['mensagem' => 'Logout realizado com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
