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

function normalizarEvento(array $evento): array
{
    $evento['id_evento'] = (int) $evento['id_evento'];
    $evento['gratuidade'] = (bool) $evento['gratuidade'];
    return $evento;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/assets/config/conexao.php';
require_once __DIR__ . '/middleware/verifica_login.php';
require_once __DIR__ . '/middleware/verifica_admin.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = null;

if (array_key_exists('id', $_GET)) {
    $idInformado = $_GET['id'];

    if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
        responder(['erro' => 'O ID deve ser um inteiro positivo'], 400);
    }

    $id = (int) $idInformado;
}

$camposEvento = 'id_evento, num_evento, nome_evento, local_evento, rua_evento,
                 cidade_evento, uf, descricao_evento, data_evento, gratuidade,
                 categoria_evento, link_oficial, imagem_evento, status_evento';

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $evento = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$evento) {
                responder(['erro' => 'Evento não encontrado'], 404);
            }

            responder(['evento' => normalizarEvento($evento)]);
        }

        $resultado = $conn->query("SELECT $camposEvento FROM evento ORDER BY data_evento ASC");
        $eventos = [];

        while ($evento = $resultado->fetch_assoc()) {
            $eventos[] = normalizarEvento($evento);
        }

        responder(['eventos' => $eventos]);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $dados = lerJson();
        $nome = trim((string) ($dados['nome_evento'] ?? ''));
        $foto = isset($dados['foto']) ? trim((string) $dados['foto']) : null;
        $horario = isset($dados['horario_evento']) ? trim((string) $dados['horario_evento']) : null;
        $data = isset($dados['data_evento']) ? trim((string) $dados['data_evento']) : null;
        $local = isset($dados['local_evento']) ? trim((string) $dados['local_evento']) : null;
        $gratuidade = !empty($dados['gratuidade']) ? 1 : 0;
        $descricao = isset($dados['descricao_evento']) ? trim((string) $dados['descricao_evento']) : null;
        $descricaoArtista = isset($dados['descricao_artista']) ? trim((string) $dados['descricao_artista']) : null;

        if ($nome === '') {
            responder(['erro' => 'Nome do evento é obrigatório'], 400);
        }

        if ($data !== null && $data !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            responder(['erro' => 'data_evento deve usar o formato YYYY-MM-DD'], 400);
        }

        if ($horario !== null && $horario !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)) {
            responder(['erro' => 'horario_evento deve usar HH:MM ou HH:MM:SS'], 400);
        }

        $stmt = $conn->prepare(
            'INSERT INTO solicitacao
             (id_user, nome_evento, foto, horario_evento, data_evento, local_evento,
              gratuidade, descricao_evento, descricao_artista)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'isssssiss',
            $idUsuario,
            $nome,
            $foto,
            $horario,
            $data,
            $local,
            $gratuidade,
            $descricao,
            $descricaoArtista
        );
        $stmt->execute();
        $idSolicitacao = $conn->insert_id;
        $stmt->close();

        responder([
            'mensagem' => 'Solicitação de evento criada com sucesso',
            'solicitacao' => [
                'id_solicitacao' => $idSolicitacao,
                'status_solicitacao' => 'pendente'
            ]
        ], 201);

    case 'PUT':
        if ($id === null) {
            responder(['erro' => 'Informe o ID da solicitação na URL'], 400);
        }

        exigirAdmin();
        $dados = lerJson();
        $novoStatus = (string) ($dados['status_solicitacao'] ?? '');

        if (!in_array($novoStatus, ['aprovado', 'recusado'], true)) {
            responder(['erro' => 'status_solicitacao deve ser aprovado ou recusado'], 400);
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                'SELECT nome_evento, foto, data_evento, local_evento, gratuidade,
                        descricao_evento, status_solicitacao
                 FROM solicitacao WHERE id_solicitacao = ? LIMIT 1 FOR UPDATE'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $solicitacao = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$solicitacao) {
                $conn->rollback();
                responder(['erro' => 'Solicitação não encontrada'], 404);
            }

            if ($solicitacao['status_solicitacao'] !== 'pendente') {
                $conn->rollback();
                responder(['erro' => 'Esta solicitação já foi analisada'], 409);
            }

            $idEvento = null;

            if ($novoStatus === 'aprovado') {
                $gratuidade = (int) $solicitacao['gratuidade'];
                $stmt = $conn->prepare(
                    'INSERT INTO evento
                     (num_evento, nome_evento, local_evento, descricao_evento,
                      data_evento, gratuidade, imagem_evento)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'issssis',
                    $id,
                    $solicitacao['nome_evento'],
                    $solicitacao['local_evento'],
                    $solicitacao['descricao_evento'],
                    $solicitacao['data_evento'],
                    $gratuidade,
                    $solicitacao['foto']
                );
                $stmt->execute();
                $idEvento = $conn->insert_id;
                $stmt->close();
            }

            $stmt = $conn->prepare(
                'UPDATE solicitacao SET status_solicitacao = ? WHERE id_solicitacao = ?'
            );
            $stmt->bind_param('si', $novoStatus, $id);
            $stmt->execute();
            $stmt->close();
            $conn->commit();

            responder([
                'mensagem' => 'Solicitação atualizada com sucesso',
                'solicitacao' => [
                    'id_solicitacao' => $id,
                    'status_solicitacao' => $novoStatus,
                    'id_evento' => $idEvento
                ]
            ]);
        } catch (Throwable $erro) {
            $conn->rollback();
            responder(['erro' => 'Não foi possível atualizar a solicitação'], 500);
        }

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
