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

    if ($conteudo === false || trim($conteudo) === '') {
        responder(['erro' => 'Nenhum dado foi enviado'], 400);
    }

    $dados = json_decode($conteudo, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dados)) {
        responder([
            'erro' => 'Corpo JSON inválido',
            'detalhes' => json_last_error_msg()
        ], 400);
    }

    return $dados;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/conexao.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = null;

/*
|--------------------------------------------------------------------------
| ID DA URL
|--------------------------------------------------------------------------
*/

if (array_key_exists('id', $_GET)) {

    $idInformado = $_GET['id'];

    if (
        !is_string($idInformado) ||
        !ctype_digit($idInformado) ||
        (int) $idInformado < 1
    ) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    $id = (int) $idInformado;
}

/*
|--------------------------------------------------------------------------
| POST - CADASTRO
| NÃO PRECISA DE LOGIN
|--------------------------------------------------------------------------
*/

if ($metodo === 'POST') {

    if ($id !== null) {
        responder(['erro' => 'O cadastro não recebe ID na URL'], 400);
    }

    $dados = lerJson();

    $nome = trim((string) ($dados['nome'] ?? ''));
    $sobrenome = trim((string) ($dados['sobrenome'] ?? ''));
    $email = trim((string) ($dados['email'] ?? ''));
    $senha = (string) ($dados['senha'] ?? '');

    if ($nome === '' || $sobrenome === '') {
        responder([
            'erro' => 'Nome e sobrenome são obrigatórios'
        ], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder([
            'erro' => 'E-mail inválido'
        ], 400);
    }

    if (strlen($senha) < 6) {
        responder([
            'erro' => 'A senha deve ter pelo menos 6 caracteres'
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFICA SE O E-MAIL JÁ EXISTE
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        'SELECT id_user
         FROM usuario
         WHERE email_user = ?
         LIMIT 1'
    );

    if (!$stmt) {
        responder([
            'erro' => 'Erro ao preparar consulta no banco'
        ], 500);
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        responder([
            'erro' => 'Este e-mail já está cadastrado'
        ], 409);
    }

    $stmt->close();

    /*
    |--------------------------------------------------------------------------
    | CRIPTOGRAFA A SENHA
    |--------------------------------------------------------------------------
    */

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    /*
    |--------------------------------------------------------------------------
    | INSERE USUÁRIO
    |--------------------------------------------------------------------------
    */

    $stmt = $conn->prepare(
        "INSERT INTO usuario
        (nome_user, sobrenome, email_user, senha_user, tipo_usuario)
        VALUES (?, ?, ?, ?, 'comum')"
    );

    if (!$stmt) {
        responder([
            'erro' => 'Erro ao preparar cadastro no banco'
        ], 500);
    }

    $stmt->bind_param(
        'ssss',
        $nome,
        $sobrenome,
        $email,
        $senhaHash
    );

    if (!$stmt->execute()) {

        $erroBanco = $stmt->error;

        $stmt->close();

        responder([
            'erro' => 'Não foi possível cadastrar o usuário',
            'detalhes' => $erroBanco
        ], 500);
    }

    $novoId = $conn->insert_id;

    $stmt->close();

    responder([
        'mensagem' => 'Usuário cadastrado com sucesso',
        'usuario' => [
            'id_user' => $novoId,
            'nome_user' => $nome,
            'sobrenome' => $sobrenome,
            'email_user' => $email,
            'tipo_usuario' => 'comum'
        ]
    ], 201);
}

/*
|--------------------------------------------------------------------------
| A PARTIR DAQUI, AS OPERAÇÕES PRECISAM DE LOGIN
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/middleware/verifica_login.php';

/*
|--------------------------------------------------------------------------
| GET - CONSULTAR USUÁRIO
|--------------------------------------------------------------------------
*/

if ($metodo === 'GET') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';

    if ($idLogado !== $id && !$ehAdmin) {
        responder([
            'erro' => 'Você só pode consultar o próprio perfil'
        ], 403);
    }

    $stmt = $conn->prepare(
        'SELECT id_user, nome_user, sobrenome, email_user,
                tipo_usuario, data_cadastro
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $usuario = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$usuario) {
        responder([
            'erro' => 'Usuário não encontrado'
        ], 404);
    }

    responder([
        'usuario' => $usuario
    ]);
}

/*
|--------------------------------------------------------------------------
| PUT - EDITAR PERFIL
|--------------------------------------------------------------------------
*/

if ($metodo === 'PUT') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    if ($idLogado !== $id) {
        responder([
            'erro' => 'Você só pode editar o próprio perfil'
        ], 403);
    }

    $dados = lerJson();

    $camposEditaveis = [
        'nome',
        'sobrenome',
        'email',
        'senha'
    ];

    $camposRecebidos = array_intersect(
        $camposEditaveis,
        array_keys($dados)
    );

    if ($camposRecebidos === []) {
        responder([
            'erro' => 'Informe ao menos um campo editável'
        ], 400);
    }

    $stmt = $conn->prepare(
        'SELECT nome_user, sobrenome, email_user
         FROM usuario
         WHERE id_user = ?
         LIMIT 1'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $usuarioAtual = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    if (!$usuarioAtual) {
        responder([
            'erro' => 'Usuário não encontrado'
        ], 404);
    }

    $nome = array_key_exists('nome', $dados)
        ? trim((string) $dados['nome'])
        : $usuarioAtual['nome_user'];

    $sobrenome = array_key_exists('sobrenome', $dados)
        ? trim((string) $dados['sobrenome'])
        : $usuarioAtual['sobrenome'];

    $email = array_key_exists('email', $dados)
        ? trim((string) $dados['email'])
        : $usuarioAtual['email_user'];

    $alterarSenha = array_key_exists('senha', $dados);

    if ($nome === '' || $sobrenome === '') {
        responder([
            'erro' => 'Nome e sobrenome são obrigatórios'
        ], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder([
            'erro' => 'E-mail inválido'
        ], 400);
    }

    $stmt = $conn->prepare(
        'SELECT id_user
         FROM usuario
         WHERE email_user = ?
         AND id_user <> ?
         LIMIT 1'
    );

    $stmt->bind_param('si', $email, $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();

        responder([
            'erro' => 'Este e-mail já está cadastrado'
        ], 409);
    }

    $stmt->close();

    if ($alterarSenha) {

        $senha = (string) $dados['senha'];

        if (strlen($senha) < 6) {
            responder([
                'erro' => 'A senha deve ter pelo menos 6 caracteres'
            ], 400);
        }

        $senhaHash = password_hash(
            $senha,
            PASSWORD_DEFAULT
        );

        $stmt = $conn->prepare(
            'UPDATE usuario
             SET nome_user = ?,
                 sobrenome = ?,
                 email_user = ?,
                 senha_user = ?
             WHERE id_user = ?'
        );

        $stmt->bind_param(
            'ssssi',
            $nome,
            $sobrenome,
            $email,
            $senhaHash,
            $id
        );

    } else {

        $stmt = $conn->prepare(
            'UPDATE usuario
             SET nome_user = ?,
                 sobrenome = ?,
                 email_user = ?
             WHERE id_user = ?'
        );

        $stmt->bind_param(
            'sssi',
            $nome,
            $sobrenome,
            $email,
            $id
        );
    }

    if (!$stmt->execute()) {

        $erroBanco = $stmt->error;

        $stmt->close();

        responder([
            'erro' => 'Não foi possível atualizar o perfil',
            'detalhes' => $erroBanco
        ], 500);
    }

    $stmt->close();

    $_SESSION['nome_user'] = $nome;

    responder([
        'mensagem' => 'Perfil atualizado com sucesso'
    ]);
}

/*
|--------------------------------------------------------------------------
| DELETE - EXCLUIR CONTA
|--------------------------------------------------------------------------
*/

if ($metodo === 'DELETE') {

    if ($id === null) {
        responder([
            'erro' => 'Informe o ID do usuário na URL'
        ], 400);
    }

    $idLogado = exigirLogin();

    if ($idLogado !== $id) {
        responder([
            'erro' => 'Você só pode apagar a própria conta'
        ], 403);
    }

    $stmt = $conn->prepare(
        'DELETE FROM usuario
         WHERE id_user = ?'
    );

    $stmt->bind_param('i', $id);
    $stmt->execute();

    $removido = $stmt->affected_rows;

    $stmt->close();

    if ($removido === 0) {
        responder([
            'erro' => 'Usuário não encontrado'
        ], 404);
    }

    session_unset();
    session_destroy();

    responder([
        'mensagem' => 'Conta removida com sucesso'
    ]);
}

/*
|--------------------------------------------------------------------------
| MÉTODO NÃO PERMITIDO
|--------------------------------------------------------------------------
*/

responder([
    'erro' => 'Método não permitido'
], 405);
