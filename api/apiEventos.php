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

function atualizarTextoOpcional(array $dados, string $campo, $valorAtual): ?string
{
    if (!array_key_exists($campo, $dados)) {
        return $valorAtual;
    }

    $valor = trim((string) $dados[$campo]);
    return $valor === '' ? null : $valor;
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
            responder(['erro' => 'Informe o ID na URL'], 400);
        }

        exigirAdmin();
        $dados = lerJson();
        $acao = (string) ($dados['acao'] ?? (
            array_key_exists('status_solicitacao', $dados) ? 'moderar' : ''
        ));

        if ($acao === 'moderar') {
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
        }

        if ($acao !== 'editar') {
            responder(['erro' => 'acao deve ser moderar ou editar'], 400);
        }

        $camposEditaveis = [
            'num_evento', 'nome_evento', 'local_evento', 'rua_evento', 'cidade_evento',
            'uf', 'descricao_evento', 'data_evento', 'gratuidade', 'categoria_evento',
            'link_oficial', 'imagem_evento', 'status_evento'
        ];

        if (array_intersect($camposEditaveis, array_keys($dados)) === []) {
            responder(['erro' => 'Informe ao menos um campo editável do evento'], 400);
        }

        $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $eventoAtual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$eventoAtual) {
            responder(['erro' => 'Evento não encontrado'], 404);
        }

        $numEvento = $eventoAtual['num_evento'] === null ? null : (int) $eventoAtual['num_evento'];
        if (array_key_exists('num_evento', $dados)) {
            if ($dados['num_evento'] === null || $dados['num_evento'] === '') {
                $numEvento = null;
            } else {
                $numEventoValidado = filter_var(
                    $dados['num_evento'],
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );

                if ($numEventoValidado === false) {
                    responder(['erro' => 'num_evento deve ser um inteiro positivo'], 400);
                }

                $numEvento = (int) $numEventoValidado;
            }
        }

        $nome = array_key_exists('nome_evento', $dados)
            ? trim((string) $dados['nome_evento'])
            : $eventoAtual['nome_evento'];

        if ($nome === '') {
            responder(['erro' => 'Nome do evento é obrigatório'], 400);
        }

        $local = atualizarTextoOpcional($dados, 'local_evento', $eventoAtual['local_evento']);
        $rua = atualizarTextoOpcional($dados, 'rua_evento', $eventoAtual['rua_evento']);
        $cidade = atualizarTextoOpcional($dados, 'cidade_evento', $eventoAtual['cidade_evento']);
        $uf = atualizarTextoOpcional($dados, 'uf', $eventoAtual['uf']);
        $descricao = atualizarTextoOpcional($dados, 'descricao_evento', $eventoAtual['descricao_evento']);
        $data = atualizarTextoOpcional($dados, 'data_evento', $eventoAtual['data_evento']);
        $categoria = atualizarTextoOpcional($dados, 'categoria_evento', $eventoAtual['categoria_evento']);
        $linkOficial = atualizarTextoOpcional($dados, 'link_oficial', $eventoAtual['link_oficial']);
        $imagem = atualizarTextoOpcional($dados, 'imagem_evento', $eventoAtual['imagem_evento']);

        if ($uf !== null) {
            $uf = strtoupper($uf);
            if (!preg_match('/^[A-Z]{2}$/', $uf)) {
                responder(['erro' => 'uf deve conter duas letras'], 400);
            }
        }

        if ($data !== null) {
            $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
            if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                responder(['erro' => 'data_evento deve usar uma data válida no formato YYYY-MM-DD'], 400);
            }
        }

        $gratuidade = (int) $eventoAtual['gratuidade'];
        if (array_key_exists('gratuidade', $dados)) {
            $gratuidadeValidada = filter_var(
                $dados['gratuidade'],
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );

            if ($gratuidadeValidada === null) {
                responder(['erro' => 'gratuidade deve ser true ou false'], 400);
            }

            $gratuidade = $gratuidadeValidada ? 1 : 0;
        }

        $statusEvento = array_key_exists('status_evento', $dados)
            ? (string) $dados['status_evento']
            : $eventoAtual['status_evento'];

        if (!in_array($statusEvento, ['ativo', 'cancelado'], true)) {
            responder(['erro' => 'status_evento deve ser ativo ou cancelado'], 400);
        }

        $stmt = $conn->prepare(
            'UPDATE evento
             SET num_evento = ?, nome_evento = ?, local_evento = ?, rua_evento = ?,
                 cidade_evento = ?, uf = ?, descricao_evento = ?, data_evento = ?,
                 gratuidade = ?, categoria_evento = ?, link_oficial = ?, imagem_evento = ?,
                 status_evento = ?
             WHERE id_evento = ?'
        );
        $tipos = 'i' . str_repeat('s', 7) . 'i' . str_repeat('s', 4) . 'i';
        $stmt->bind_param(
            $tipos,
            $numEvento,
            $nome,
            $local,
            $rua,
            $cidade,
            $uf,
            $descricao,
            $data,
            $gratuidade,
            $categoria,
            $linkOficial,
            $imagem,
            $statusEvento,
            $id
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $evento = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        responder([
            'mensagem' => 'Evento atualizado com sucesso',
            'evento' => normalizarEvento($evento)
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do evento na URL'], 400);
        }

        exigirAdmin();

        $stmt = $conn->prepare('DELETE FROM evento WHERE id_evento = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $removido = $stmt->affected_rows;
        $stmt->close();

        if ($removido === 0) {
            responder(['erro' => 'Evento não encontrado'], 404);
        }

        responder(['mensagem' => 'Evento removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
