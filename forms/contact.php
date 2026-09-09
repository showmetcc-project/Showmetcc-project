<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

header('Content-Type: application/json; charset=UTF-8');

function responderContato(array $dados, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE);
    exit();
}

function campoContato(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    responderContato(['erro' => 'Método não permitido'], 405);
}

$nome = campoContato('nome');
$email = campoContato('email');
$mensagem = campoContato('mensagem');

if ($nome === '' || mb_strlen($nome) < 2 || mb_strlen($nome) > 100) {
    responderContato(['erro' => 'Informe um nome entre 2 e 100 caracteres'], 422);
}

if (mb_strlen($email) > 254 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    responderContato(['erro' => 'Informe um e-mail válido'], 422);
}

if ($mensagem === '' || mb_strlen($mensagem) < 10 || mb_strlen($mensagem) > 5000) {
    responderContato(['erro' => 'A mensagem deve ter entre 10 e 5000 caracteres'], 422);
}

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$arquivoConfiguracao = dirname(__DIR__) . '/config/email.php';

if (!is_file($autoload)) {
    responderContato(['erro' => 'Dependências de e-mail não instaladas'], 503);
}

if (!is_file($arquivoConfiguracao)) {
    responderContato(['erro' => 'Configuração de e-mail não encontrada'], 503);
}

require_once $autoload;
$configuracao = require $arquivoConfiguracao;

if (!is_array($configuracao)) {
    responderContato(['erro' => 'Configuração de e-mail inválida'], 503);
}

$host = trim((string) ($configuracao['host'] ?? ''));
$porta = filter_var(
    $configuracao['porta'] ?? null,
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 65535]]
);
$usuario = trim((string) ($configuracao['usuario'] ?? ''));
$senha = (string) ($configuracao['senha'] ?? '');
$criptografia = strtolower(trim((string) ($configuracao['criptografia'] ?? '')));
$remetenteEmail = trim((string) ($configuracao['remetente_email'] ?? ''));
$remetenteNome = trim((string) ($configuracao['remetente_nome'] ?? 'ShowMe'));
$destinatarioEmail = trim((string) ($configuracao['destinatario_email'] ?? ''));
$destinatarioNome = trim((string) ($configuracao['destinatario_nome'] ?? 'ShowMe'));

if ($destinatarioEmail === '') {
    $destinatarioEmail = $remetenteEmail;
}

if (
    $host === ''
    || $porta === false
    || $usuario === ''
    || $senha === ''
    || filter_var($remetenteEmail, FILTER_VALIDATE_EMAIL) === false
    || filter_var($destinatarioEmail, FILTER_VALIDATE_EMAIL) === false
    || !in_array($criptografia, ['', 'tls', 'ssl'], true)
) {
    responderContato(['erro' => 'O envio de e-mail ainda não foi configurado'], 503);
}

$nomeCabecalho = preg_replace('/[\r\n]+/', ' ', $nome) ?: 'Visitante ShowMe';
$nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$emailSeguro = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$mensagemSegura = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

try {
    $mailer = new PHPMailer(true);
    $mailer->isSMTP();
    $mailer->Host = $host;
    $mailer->Port = (int) $porta;
    $mailer->SMTPAuth = true;
    $mailer->Username = $usuario;
    $mailer->Password = $senha;
    $mailer->Timeout = 15;
    $mailer->CharSet = PHPMailer::CHARSET_UTF8;

    if ($criptografia === 'tls') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    } elseif ($criptografia === 'ssl') {
        $mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mailer->SMTPSecure = '';
        $mailer->SMTPAutoTLS = false;
    }

    $mailer->setFrom($remetenteEmail, $remetenteNome !== '' ? $remetenteNome : 'ShowMe');
    $mailer->addAddress($destinatarioEmail, $destinatarioNome);
    $mailer->addReplyTo($email, $nomeCabecalho);
    $mailer->isHTML(true);
    $mailer->Subject = 'Nova mensagem de contato - ShowMe';
    $mailer->Body = "<h2>Nova mensagem pelo site ShowMe</h2>
        <p><strong>Nome:</strong> {$nomeSeguro}</p>
        <p><strong>E-mail:</strong> {$emailSeguro}</p>
        <p><strong>Mensagem:</strong><br>{$mensagemSegura}</p>";
    $mailer->AltBody = "Nova mensagem pelo site ShowMe\n\n"
        . "Nome: {$nome}\n"
        . "E-mail: {$email}\n\n"
        . "Mensagem:\n{$mensagem}";
    $mailer->send();

    responderContato(['sucesso' => true]);
} catch (Exception $erro) {
    error_log('Falha no envio do formulário de contato: ' . $erro->getMessage());
    responderContato(['erro' => 'Não foi possível enviar a mensagem. Tente novamente mais tarde.'], 500);
}
