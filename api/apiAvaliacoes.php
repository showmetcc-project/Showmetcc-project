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

function validarAvaliacao(array $dados): array
{
    $nota = filter_var($dados['nota'] ?? null, FILTER_VALIDATE_INT);
    $comentario = trim((string) ($dados['comentario'] ?? ''));

    if (!$nota || $nota < 1 || $nota > 5) {
        responder(['erro' => 'A nota deve estar entre 1 e 5'], 400);
    }

    if ($comentario === '') {
        responder(['erro' => 'O comentário é obrigatório'], 400);
    }

    return [(int) $nota, $comentario];
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

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            responder(['erro' => 'Use evento_id para listar avaliações'], 400);
        }

        $idEvento = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT);

        if (!$idEvento) {
            responder(['erro' => 'evento_id é obrigatório'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT a.id_avaliacao, a.id_evento, a.id_user, a.nota, a.comentario,
                    a.data_avaliacao, u.nome_user, u.sobrenome
             FROM avaliacao a
             INNER JOIN usuario u ON u.id_user = a.id_user
             WHERE a.id_evento = ?
             ORDER BY a.data_avaliacao DESC, a.id_avaliacao DESC'
        );
        $stmt->bind_param('i', $idEvento);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $avaliacoes = [];

        while ($avaliacao = $resultado->fetch_assoc()) {
            $avaliacao['id_avaliacao'] = (int) $avaliacao['id_avaliacao'];
            $avaliacao['id_evento'] = (int) $avaliacao['id_evento'];
            $avaliacao['id_user'] = (int) $avaliacao['id_user'];
            $avaliacao['nota'] = (int) $avaliacao['nota'];
            $avaliacoes[] = $avaliacao;
        }
        $stmt->close();

        responder(['avaliacoes' => $avaliacoes]);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $dados = lerJson();
        $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT);
        [$nota, $comentario] = validarAvaliacao($dados);

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
            'SELECT id_avaliacao FROM avaliacao WHERE id_user = ? AND id_evento = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $idUsuario, $idEvento);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            responder(['erro' => 'Você já avaliou este evento'], 409);
        }
        $stmt->close();

        $stmt = $conn->prepare(
            'INSERT INTO avaliacao (id_user, id_evento, nota, comentario) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('iiis', $idUsuario, $idEvento, $nota, $comentario);
        $stmt->execute();
        $idAvaliacao = $conn->insert_id;
        $stmt->close();

        responder([
            'mensagem' => 'Avaliação criada com sucesso',
            'avaliacao' => [
                'id_avaliacao' => $idAvaliacao,
                'id_evento' => $idEvento,
                'nota' => $nota,
                'comentario' => $comentario
            ]
        ], 201);

    case 'PUT':
        if ($id === null) {
            responder(['erro' => 'Informe o ID da avaliação na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $dados = lerJson();
        [$nota, $comentario] = validarAvaliacao($dados);

        $stmt = $conn->prepare(
            'SELECT id_user, id_evento FROM avaliacao WHERE id_avaliacao = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $avaliacao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$avaliacao) {
            responder(['erro' => 'Avaliação não encontrada'], 404);
        }

        $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
        if ((int) $avaliacao['id_user'] !== $idUsuario && !$ehAdmin) {
            responder(['erro' => 'Você não pode editar esta avaliação'], 403);
        }

        $stmt = $conn->prepare(
            'UPDATE avaliacao SET nota = ?, comentario = ? WHERE id_avaliacao = ?'
        );
        $stmt->bind_param('isi', $nota, $comentario, $id);
        $stmt->execute();
        $stmt->close();

        responder([
            'mensagem' => 'Avaliação atualizada com sucesso',
            'avaliacao' => ['id_avaliacao' => $id, 'nota' => $nota, 'comentario' => $comentario]
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID da avaliação na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $stmt = $conn->prepare(
            'SELECT id_user FROM avaliacao WHERE id_avaliacao = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $avaliacao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$avaliacao) {
            responder(['erro' => 'Avaliação não encontrada'], 404);
        }

        $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
        if ((int) $avaliacao['id_user'] !== $idUsuario && !$ehAdmin) {
            responder(['erro' => 'Você não pode apagar esta avaliação'], 403);
        }

        $stmt = $conn->prepare('DELETE FROM avaliacao WHERE id_avaliacao = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        responder(['mensagem' => 'Avaliação removida com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
