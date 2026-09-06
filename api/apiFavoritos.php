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
    $dados = json_decode(file_get_contents('php://input'), true);

    if (!is_array($dados)) {
        responder(['erro' => 'Corpo JSON inválido'], 400);
    }

    return $dados;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/assets/config/conexao.php';
require_once __DIR__ . '/middleware/verifica_login.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = null;

if (array_key_exists('id', $_GET)) {
    $idInformado = $_GET['id'];

    if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    $id = (int) $idInformado;
}

$idUsuario = exigirLogin();

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            responder(['erro' => 'A listagem de favoritos não recebe ID'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT f.id_favorito, e.id_evento, e.nome_evento, e.local_evento,
                    e.cidade_evento, e.uf, e.data_evento, e.gratuidade, e.imagem_evento
             FROM favoritos f
             INNER JOIN evento e ON e.id_evento = f.id_evento
             WHERE f.id_user = ?
             ORDER BY e.data_evento ASC'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $favoritos = [];

        while ($favorito = $resultado->fetch_assoc()) {
            $favorito['id_favorito'] = (int) $favorito['id_favorito'];
            $favorito['id_evento'] = (int) $favorito['id_evento'];
            $favorito['gratuidade'] = (bool) $favorito['gratuidade'];
            $favoritos[] = $favorito;
        }
        $stmt->close();

        responder(['favoritos' => $favoritos]);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $dados = lerJson();
        $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT);

        if (!$idEvento) {
            responder(['erro' => 'id_evento é obrigatório'], 400);
        }

        $stmt = $conn->prepare('SELECT id_evento FROM evento WHERE id_evento = ? LIMIT 1');
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            responder(['erro' => 'Evento não encontrado'], 404);
        }
        $stmt->close();

        $stmt = $conn->prepare(
            'SELECT id_favorito FROM favoritos WHERE id_user = ? AND id_evento = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $idUsuario, $idEvento);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            responder(['erro' => 'Evento já está nos favoritos'], 409);
        }
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO favoritos (id_user, id_evento) VALUES (?, ?)');
        $stmt->bind_param('ii', $idUsuario, $idEvento);
        $stmt->execute();
        $idFavorito = $conn->insert_id;
        $stmt->close();

        responder([
            'mensagem' => 'Favorito adicionado com sucesso',
            'favorito' => ['id_favorito' => $idFavorito, 'id_evento' => $idEvento]
        ], 201);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do favorito na URL'], 400);
        }

        $stmt = $conn->prepare(
            'DELETE FROM favoritos WHERE id_favorito = ? AND id_user = ?'
        );
        $stmt->bind_param('ii', $id, $idUsuario);
        $stmt->execute();
        $removido = $stmt->affected_rows;
        $stmt->close();

        if ($removido === 0) {
            responder(['erro' => 'Favorito não encontrado'], 404);
        }

        responder(['mensagem' => 'Favorito removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
