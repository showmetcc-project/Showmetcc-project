<?php

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

require_once dirname(__DIR__) . '/config/emailHelper.php';

try {
    $configuracao = configuracaoEmailShowMe();
    $mailer = criarMailerShowMe();
} catch (Throwable $erro) {
    error_log('Falha ao carregar configuração do formulário de contato: ' . $erro->getMessage());
    responderContato(['erro' => 'O envio de e-mail ainda não foi configurado'], 503);
}

$destinatarioEmail = trim((string) ($configuracao['destinatario_email'] ?? ''));
$destinatarioNome = trim((string) ($configuracao['destinatario_nome'] ?? 'ShowMe'));
if ($destinatarioEmail === '') {
    $destinatarioEmail = trim((string) $configuracao['remetente_email']);
}

if (filter_var($destinatarioEmail, FILTER_VALIDATE_EMAIL) === false) {
    responderContato(['erro' => 'O destinatário do formulário não foi configurado corretamente'], 503);
}

$nomeCabecalho = preg_replace('/[\r\n]+/', ' ', $nome) ?: 'Visitante ShowMe';
$nomeSeguro = htmlspecialchars($nome, ENT_QUOTES, 'UTF-8');
$emailSeguro = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$mensagemSegura = nl2br(htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'));

try {
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
} catch (Throwable $erro) {
    error_log('Falha no envio do formulário de contato: ' . $erro->getMessage());
    responderContato(['erro' => 'Não foi possível enviar a mensagem. Tente novamente mais tarde.'], 500);
}
