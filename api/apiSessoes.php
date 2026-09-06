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

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/assets/config/conexao.php';

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

        if (!$usuario || !password_verify($senha, (string) $usuario['senha_user'])) {
            responder(['erro' => 'E-mail ou senha incorretos'], 401);
        }

        session_regenerate_id(true);
        $_SESSION['id_user'] = (int) $usuario['id_user'];
        $_SESSION['nome_user'] = $usuario['nome_user'];
        $_SESSION['tipo_usuario'] = $usuario['tipo_usuario'];

        unset($usuario['senha_user']);
        responder(['mensagem' => 'Login realizado com sucesso', 'usuario' => $usuario], 201);

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
