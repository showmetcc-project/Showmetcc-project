<?php

require_once __DIR__ . '/middleware/apiCommon.php';

function validarPlanejamento(array $dados): array
{
    $meioTransporte = trim((string) ($dados['meio_transporte'] ?? ''));
    $distanciaInformada = $dados['distancia_km'] ?? null;
    $tempoInformado = $dados['tempo_estimado'] ?? null;

    if ($meioTransporte === '' || tamanhoTextoApi($meioTransporte) > 30) {
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

    $origem = trim((string) ($dados['origem'] ?? ''));
    if (tamanhoTextoApi($origem) > 255) {
        responder(['erro' => 'origem deve ter até 255 caracteres'], 400);
    }

    $valores = [];
    foreach (['orcamento_total', 'custo_ingresso', 'custo_transporte', 'custo_hospedagem'] as $campo) {
        $informado = $dados[$campo] ?? 0;
        if (!is_numeric($informado)) {
            responder(['erro' => "$campo deve ser um número não negativo"], 400);
        }
        $valor = round((float) $informado, 2);
        if ($valor < 0 || $valor > 99999999.99) {
            responder(['erro' => "$campo deve ser um número não negativo válido"], 400);
        }
        $valores[$campo] = $valor;
    }

    $hospedagem = filter_var(
        $dados['hospedagem_necessaria'] ?? false,
        FILTER_VALIDATE_BOOLEAN,
        FILTER_NULL_ON_FAILURE
    );
    if ($hospedagem === null) {
        responder(['erro' => 'hospedagem_necessaria deve ser true ou false'], 400);
    }

    $nomeHospedagem = trim((string) ($dados['nome_hospedagem'] ?? ''));
    if (tamanhoTextoApi($nomeHospedagem) > 100) {
        responder(['erro' => 'nome_hospedagem deve ter até 100 caracteres'], 400);
    }
    if (!$hospedagem) {
        $nomeHospedagem = '';
        $valores['custo_hospedagem'] = 0.0;
    }

    return [
        'meio_transporte' => $meioTransporte,
        'distancia_km' => $distancia,
        'tempo_estimado' => (int) $tempo,
        'origem' => $origem === '' ? null : $origem,
        'orcamento_total' => $valores['orcamento_total'],
        'custo_ingresso' => $valores['custo_ingresso'],
        'custo_transporte' => $valores['custo_transporte'],
        'hospedagem_necessaria' => $hospedagem ? 1 : 0,
        'nome_hospedagem' => $nomeHospedagem === '' ? null : $nomeHospedagem,
        'custo_hospedagem' => $valores['custo_hospedagem'],
    ];
}

require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

$idUsuario = exigirUsuarioComum();

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            responder(['erro' => 'A listagem de planejamentos não recebe ID'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT r.id_rota, r.id_evento, r.meio_transporte, r.distancia_km,
                    r.tempo_estimado, r.origem, r.orcamento_total, r.custo_ingresso,
                    r.custo_transporte, r.hospedagem_necessaria, r.nome_hospedagem,
                    r.custo_hospedagem,
                    (r.custo_ingresso + r.custo_transporte + r.custo_hospedagem) AS investimento_total,
                    e.nome_evento, e.cep_evento, e.endereco_evento,
                    e.numero_endereco, e.cidade_evento, e.uf, e.data_evento, e.horario_evento,
                    e.imagem_evento, e.gratuidade, e.valor_ingresso_minimo,
                    e.valor_ingresso_maximo, e.status_evento
             FROM rota r
             INNER JOIN evento e ON e.id_evento = r.id_evento
             WHERE r.id_user = ?
             ORDER BY e.data_evento ASC, r.id_rota DESC'
        );
        $stmt->bind_param('i', $idUsuario);
        executarStatementApi($stmt);
        $resultado = $stmt->get_result();
        $planejamentos = [];

        while ($planejamento = $resultado->fetch_assoc()) {
            $planejamentos[] = normalizarPlanejamentoApi($planejamento);
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
        executarStatementApi($stmt);
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
        executarStatementApi($stmt);
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
                (id_user, id_evento, meio_transporte, distancia_km, tempo_estimado,
                 origem, orcamento_total, custo_ingresso, custo_transporte,
                 hospedagem_necessaria, nome_hospedagem, custo_hospedagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param(
            'iisdisdddisd',
            $idUsuario,
            $idEvento,
            $planejamento['meio_transporte'],
            $planejamento['distancia_km'],
            $planejamento['tempo_estimado'],
            $planejamento['origem'],
            $planejamento['orcamento_total'],
            $planejamento['custo_ingresso'],
            $planejamento['custo_transporte'],
            $planejamento['hospedagem_necessaria'],
            $planejamento['nome_hospedagem'],
            $planejamento['custo_hospedagem']
        );

        try {
            executarStatementApi($stmt);
        } catch (Throwable $erro) {
            $stmt->close();

            if ((int) $erro->getCode() === 1062) {
                responder(['erro' => 'Este evento já possui um planejamento finalizado'], 409);
            }

            responderErroInfraestrutura('Falha ao criar planejamento', $erro);
        }

        $idRota = $stmt->insert_id;
        $stmt->close();

        $planejamentoCriado = normalizarPlanejamentoApi([
            'id_rota' => $idRota,
            'id_evento' => $idEvento,
        ] + $planejamento);

        responder([
            'mensagem' => 'Planejamento finalizado com sucesso',
            'planejamento' => $planejamentoCriado,
        ], 201);

    case 'PUT':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do planejamento na URL'], 400);
        }

        $dados = lerJson();
        $camposEditaveis = [
            'meio_transporte', 'distancia_km', 'tempo_estimado', 'origem',
            'orcamento_total', 'custo_ingresso', 'custo_transporte',
            'hospedagem_necessaria', 'nome_hospedagem', 'custo_hospedagem'
        ];
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
            'SELECT meio_transporte, distancia_km, tempo_estimado, origem,
                    orcamento_total, custo_ingresso, custo_transporte,
                    hospedagem_necessaria, nome_hospedagem, custo_hospedagem
             FROM rota WHERE id_rota = ? AND id_user = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $id, $idUsuario);
        executarStatementApi($stmt);
        $atual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$atual) {
            responder(['erro' => 'Planejamento não encontrado'], 404);
        }

        $planejamento = validarPlanejamento([
            'meio_transporte' => $dados['meio_transporte'] ?? $atual['meio_transporte'],
            'distancia_km' => $dados['distancia_km'] ?? $atual['distancia_km'],
            'tempo_estimado' => $dados['tempo_estimado'] ?? $atual['tempo_estimado'],
            'origem' => $dados['origem'] ?? $atual['origem'],
            'orcamento_total' => $dados['orcamento_total'] ?? $atual['orcamento_total'],
            'custo_ingresso' => $dados['custo_ingresso'] ?? $atual['custo_ingresso'],
            'custo_transporte' => $dados['custo_transporte'] ?? $atual['custo_transporte'],
            'hospedagem_necessaria' => $dados['hospedagem_necessaria'] ?? $atual['hospedagem_necessaria'],
            'nome_hospedagem' => $dados['nome_hospedagem'] ?? $atual['nome_hospedagem'],
            'custo_hospedagem' => $dados['custo_hospedagem'] ?? $atual['custo_hospedagem'],
        ]);

        $stmt = $conn->prepare(
            'UPDATE rota
             SET meio_transporte = ?, distancia_km = ?, tempo_estimado = ?, origem = ?,
                 orcamento_total = ?, custo_ingresso = ?, custo_transporte = ?,
                 hospedagem_necessaria = ?, nome_hospedagem = ?, custo_hospedagem = ?
             WHERE id_rota = ? AND id_user = ?'
        );
        $stmt->bind_param(
            'sdisdddisdii',
            $planejamento['meio_transporte'],
            $planejamento['distancia_km'],
            $planejamento['tempo_estimado'],
            $planejamento['origem'],
            $planejamento['orcamento_total'],
            $planejamento['custo_ingresso'],
            $planejamento['custo_transporte'],
            $planejamento['hospedagem_necessaria'],
            $planejamento['nome_hospedagem'],
            $planejamento['custo_hospedagem'],
            $id,
            $idUsuario
        );
        executarStatementApi($stmt);
        $stmt->close();

        responder([
            'mensagem' => 'Planejamento atualizado com sucesso',
            'planejamento' => normalizarPlanejamentoApi(['id_rota' => $id] + $planejamento),
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do planejamento na URL'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM rota WHERE id_rota = ? AND id_user = ?');
        $stmt->bind_param('ii', $id, $idUsuario);
        executarStatementApi($stmt);
        $removido = $stmt->affected_rows;
        $stmt->close();

        if ($removido === 0) {
            responder(['erro' => 'Planejamento não encontrado'], 404);
        }

        responder(['mensagem' => 'Planejamento removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
