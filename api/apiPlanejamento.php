<?php

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function responder($dados, int $status = 200): void
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

function tamanhoTexto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function validarPlanejamento(array $dados): array
{
    $meioTransporte = trim((string) ($dados['meio_transporte'] ?? ''));
    $distanciaInformada = $dados['distancia_km'] ?? null;
    $tempoInformado = $dados['tempo_estimado'] ?? null;

    if ($meioTransporte === '' || tamanhoTexto($meioTransporte) > 30) {
        responder(['erro' => 'meio_transporte é obrigatório e deve ter até 30 caracteres'], 400);
    }

    if (!is_numeric($distanciaInformada)) {
        responder(['erro' => 'distancia_km deve ser um número positivo'], 400);
    }

    $distancia = round((float) $distanciaInformada, 2);

    if ($distancia <= 0 || $distancia > 99999999.99) {
        responder(['erro' => 'distancia_km deve ser maior que zero'], 400);
    }

    $tempo = filter_var(
        $tempoInformado,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($tempo === false) {
        responder(['erro' => 'tempo_estimado deve ser um inteiro positivo em minutos'], 400);
    }

    return [
        'meio_transporte' => $meioTransporte,
        'distancia_km' => $distancia,
        'tempo_estimado' => (int) $tempo,
    ];
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once dirname(__DIR__) . '/config/conexao.php';
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
            responder(['erro' => 'A listagem de planejamentos não recebe ID'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT r.id_rota, r.id_evento, r.meio_transporte, r.distancia_km,
                    r.tempo_estimado, e.nome_evento, e.local_evento,
                    e.cidade_evento, e.uf, e.data_evento, e.imagem_evento,
                    e.gratuidade, e.status_evento
             FROM rota r
             INNER JOIN evento e ON e.id_evento = r.id_evento
             WHERE r.id_user = ?
             ORDER BY e.data_evento ASC, r.id_rota DESC'
        );
        $stmt->bind_param('i', $idUsuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $planejamentos = [];

        while ($planejamento = $resultado->fetch_assoc()) {
            $planejamento['id_rota'] = (int) $planejamento['id_rota'];
            $planejamento['id_evento'] = (int) $planejamento['id_evento'];
            $planejamento['distancia_km'] = (float) $planejamento['distancia_km'];
            $planejamento['tempo_estimado'] = (int) $planejamento['tempo_estimado'];
            $planejamento['gratuidade'] = (bool) $planejamento['gratuidade'];
            $planejamentos[] = $planejamento;
        }

        $stmt->close();
        responder(['planejamentos' => $planejamentos]);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $dados = lerJson();
        $idEvento = filter_var(
            $dados['id_evento'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($idEvento === false) {
            responder(['erro' => 'id_evento deve ser um inteiro positivo'], 400);
        }

        $planejamento = validarPlanejamento($dados);

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
            'SELECT id_rota FROM rota WHERE id_user = ? AND id_evento = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $idUsuario, $idEvento);
        $stmt->execute();
        $existente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existente) {
            responder([
                'erro' => 'Este evento já possui um planejamento finalizado',
                'id_rota' => (int) $existente['id_rota'],
            ], 409);
        }

        $stmt = $conn->prepare(
            'INSERT INTO rota
                (id_user, id_evento, meio_transporte, distancia_km, tempo_estimado)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iisdi',
            $idUsuario,
            $idEvento,
            $planejamento['meio_transporte'],
            $planejamento['distancia_km'],
            $planejamento['tempo_estimado']
        );

        try {
            $stmt->execute();
        } catch (Throwable $erro) {
            $stmt->close();

            if ((int) $erro->getCode() === 1062) {
                responder(['erro' => 'Este evento já possui um planejamento finalizado'], 409);
            }

            error_log('Falha ao criar planejamento: ' . $erro->getMessage());
            responder(['erro' => 'Não foi possível criar o planejamento'], 500);
        }

        $idRota = $stmt->insert_id;
        $stmt->close();

        responder([
            'mensagem' => 'Planejamento finalizado com sucesso',
            'planejamento' => [
                'id_rota' => $idRota,
                'id_evento' => (int) $idEvento,
                'meio_transporte' => $planejamento['meio_transporte'],
                'distancia_km' => $planejamento['distancia_km'],
                'tempo_estimado' => $planejamento['tempo_estimado'],
            ],
        ], 201);

    case 'PUT':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do planejamento na URL'], 400);
        }

        $dados = lerJson();
        $camposEditaveis = ['meio_transporte', 'distancia_km', 'tempo_estimado'];
        $possuiCampoEditavel = false;

        foreach ($camposEditaveis as $campo) {
            if (array_key_exists($campo, $dados)) {
                $possuiCampoEditavel = true;
                break;
            }
        }

        if (!$possuiCampoEditavel) {
            responder(['erro' => 'Informe ao menos um campo editável'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT meio_transporte, distancia_km, tempo_estimado
             FROM rota WHERE id_rota = ? AND id_user = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $id, $idUsuario);
        $stmt->execute();
        $atual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$atual) {
            responder(['erro' => 'Planejamento não encontrado'], 404);
        }

        $planejamento = validarPlanejamento([
            'meio_transporte' => $dados['meio_transporte'] ?? $atual['meio_transporte'],
            'distancia_km' => $dados['distancia_km'] ?? $atual['distancia_km'],
            'tempo_estimado' => $dados['tempo_estimado'] ?? $atual['tempo_estimado'],
        ]);

        $stmt = $conn->prepare(
            'UPDATE rota
             SET meio_transporte = ?, distancia_km = ?, tempo_estimado = ?
             WHERE id_rota = ? AND id_user = ?'
        );
        $stmt->bind_param(
            'sdiii',
            $planejamento['meio_transporte'],
            $planejamento['distancia_km'],
            $planejamento['tempo_estimado'],
            $id,
            $idUsuario
        );
        $stmt->execute();
        $stmt->close();

        responder([
            'mensagem' => 'Planejamento atualizado com sucesso',
            'planejamento' => ['id_rota' => $id] + $planejamento,
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do planejamento na URL'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM rota WHERE id_rota = ? AND id_user = ?');
        $stmt->bind_param('ii', $id, $idUsuario);
        $stmt->execute();
        $removido = $stmt->affected_rows;
        $stmt->close();

        if ($removido === 0) {
            responder(['erro' => 'Planejamento não encontrado'], 404);
        }

        responder(['mensagem' => 'Planejamento removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
