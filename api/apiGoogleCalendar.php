<?php

use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Exception as GoogleServiceException;

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/googleCalendarHelper.php';

function buscarEventoParaAgenda(mysqli $conn, int $idEvento): ?array
{
    $stmt = $conn->prepare(
        'SELECT id_evento, nome_evento, local_evento, rua_evento, cidade_evento, uf,
                data_evento, horario_evento, link_oficial
         FROM evento
         WHERE id_evento = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $idEvento);
    executarStatementApi($stmt);
    $evento = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    return $evento;
}

function janelaEventoGoogleCalendar(array $evento, int $tempoDeslocamento = 0): array
{
    $configuracao = configuracaoGoogleCalendar();
    $fuso = new DateTimeZone($configuracao['timezone'] ?: API_FUSO_HORARIO);
    $data = trim((string) ($evento['data_evento'] ?? ''));
    $horario = trim((string) ($evento['horario_evento'] ?? ''));

    if ($data === '') {
        responder(['erro' => 'O evento não possui data definida'], 422);
    }

    if ($horario === '') {
        $inicio = new DateTimeImmutable($data . ' 00:00:00', $fuso);
        $fim = $inicio->modify('+1 day');

        return ['inicio' => $inicio, 'fim' => $fim, 'dia_inteiro' => true];
    }

    $inicioEvento = new DateTimeImmutable($data . ' ' . $horario, $fuso);
    $inicio = $inicioEvento->modify('-' . max(0, $tempoDeslocamento) . ' minutes');

    return [
        'inicio' => $inicio,
        // O schema atual não possui horário final; duas horas é a duração operacional padrão.
        'fim' => $inicioEvento->modify('+2 hours'),
        'inicio_evento' => $inicioEvento,
        'dia_inteiro' => false,
    ];
}

function enderecoEventoGoogleCalendar(array $evento): string
{
    return implode(', ', array_filter([
        $evento['local_evento'] ?? null,
        $evento['rua_evento'] ?? null,
        $evento['cidade_evento'] ?? null,
        $evento['uf'] ?? null,
    ], static fn ($valor) => trim((string) $valor) !== ''));
}

$id = obterIdApi();

if ($id !== null) {
    responder(['erro' => 'Este recurso não recebe ID na URL'], 400);
}

$idUsuario = exigirLogin();
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    case 'GET':
        $token = buscarTokenGoogleCalendar($conn, $idUsuario);
        responder([
            'configurado' => googleCalendarConfigurado(),
            'conectado' => $token !== null,
            'data_conexao' => $token === null ? null : normalizarDataHoraApi($token['data_conexao']),
        ]);

    case 'DELETE':
        $token = buscarTokenGoogleCalendar($conn, $idUsuario);

        if ($token !== null && googleCalendarConfigurado()) {
            try {
                $cliente = criarClienteGoogleCalendar();
                $cliente->setAccessToken([
                    'access_token' => $token['access_token'],
                    'refresh_token' => $token['refresh_token'],
                ]);
                $cliente->revokeToken();
            } catch (Throwable $erro) {
                error_log('Falha ao revogar token do Google Agenda: ' . $erro->getMessage());
            }
        }

        removerTokenGoogleCalendar($conn, $idUsuario);
        responder(['mensagem' => 'Google Agenda desconectado com sucesso']);

    case 'POST':
        $dados = lerJson();
        $acao = trim((string) ($dados['acao'] ?? ''));

        if (!in_array($acao, ['verificar_conflito', 'exportar'], true)) {
            responder(['erro' => 'Ação inválida para o Google Agenda'], 400);
        }

        $token = buscarTokenGoogleCalendar($conn, $idUsuario);

        if ($token === null) {
            if ($acao === 'exportar') {
                responder([
                    'erro' => 'Conecte o Google Agenda antes de exportar o planejamento',
                    'conectado' => false,
                ], 409);
            }

            responder([
                'conectado' => false,
                'conflito' => false,
                'conflitos' => [],
                'mensagem' => 'Google Agenda não está conectado',
            ]);
        }

        if (!googleCalendarConfigurado()) {
            responder(['erro' => 'Google Agenda ainda não foi configurado no servidor'], 503);
        }

        try {
            $cliente = clienteGoogleCalendarAutorizado($conn, $idUsuario);
            $servico = new Calendar($cliente);

            if ($acao === 'verificar_conflito') {
                $idEvento = filter_var(
                    $dados['id_evento'] ?? null,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                );
                $tempo = filter_var(
                    $dados['tempo_estimado'] ?? 0,
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 0]]
                );

                if ($idEvento === false || $tempo === false) {
                    responder(['erro' => 'id_evento e tempo_estimado devem ser inteiros válidos'], 400);
                }

                $evento = buscarEventoParaAgenda($conn, (int) $idEvento);

                if ($evento === null) {
                    responder(['erro' => 'Evento não encontrado'], 404);
                }

                $janela = janelaEventoGoogleCalendar($evento, (int) $tempo);
                $resultado = $servico->events->listEvents('primary', [
                    'timeMin' => $janela['inicio']->format(DATE_RFC3339),
                    'timeMax' => $janela['fim']->format(DATE_RFC3339),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'maxResults' => 20,
                ]);
                $conflitos = [];

                foreach ($resultado->getItems() as $item) {
                    if ($item->getStatus() === 'cancelled') {
                        continue;
                    }

                    $conflitos[] = [
                        'titulo' => $item->getSummary() ?: 'Compromisso sem título',
                        'inicio' => $item->getStart()?->getDateTime() ?: $item->getStart()?->getDate(),
                        'fim' => $item->getEnd()?->getDateTime() ?: $item->getEnd()?->getDate(),
                    ];
                }

                responder([
                    'conectado' => true,
                    'conflito' => count($conflitos) > 0,
                    'conflitos' => $conflitos,
                    'janela_consultada' => [
                        'inicio' => $janela['inicio']->format(DATE_RFC3339),
                        'fim' => $janela['fim']->format(DATE_RFC3339),
                    ],
                ]);
            }

            $idRota = filter_var(
                $dados['id_rota'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1]]
            );

            if ($idRota === false) {
                responder(['erro' => 'id_rota deve ser um inteiro positivo'], 400);
            }

            $stmt = $conn->prepare(
                'SELECT r.id_rota, r.id_evento, r.tempo_estimado,
                        e.nome_evento, e.local_evento, e.rua_evento, e.cidade_evento,
                        e.uf, e.data_evento, e.horario_evento, e.link_oficial
                 FROM rota r
                 INNER JOIN evento e ON e.id_evento = r.id_evento
                 WHERE r.id_rota = ? AND r.id_user = ?
                 LIMIT 1'
            );
            $stmt->bind_param('ii', $idRota, $idUsuario);
            executarStatementApi($stmt);
            $planejamento = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$planejamento) {
                responder(['erro' => 'Planejamento não encontrado'], 404);
            }

            $janela = janelaEventoGoogleCalendar($planejamento);
            $descricao = 'Evento adicionado pelo ShowMe.';

            if (!empty($planejamento['link_oficial'])) {
                $descricao .= "\n" . $planejamento['link_oficial'];
            }

            $eventoGoogle = new GoogleCalendarEvent([
                // ID determinístico impede duplicação em cliques/recarregamentos posteriores.
                'id' => '5a0e' . substr(hash('sha256', $idUsuario . ':' . $idRota), 0, 32),
                'summary' => $planejamento['nome_evento'],
                'description' => $descricao,
                'location' => enderecoEventoGoogleCalendar($planejamento),
            ]);

            if ($janela['dia_inteiro']) {
                $eventoGoogle->setStart(new EventDateTime([
                    'date' => $janela['inicio']->format('Y-m-d'),
                ]));
                $eventoGoogle->setEnd(new EventDateTime([
                    'date' => $janela['fim']->format('Y-m-d'),
                ]));
            } else {
                $eventoGoogle->setStart(new EventDateTime([
                    'dateTime' => $janela['inicio_evento']->format(DATE_RFC3339),
                    'timeZone' => configuracaoGoogleCalendar()['timezone'],
                ]));
                $eventoGoogle->setEnd(new EventDateTime([
                    'dateTime' => $janela['fim']->format(DATE_RFC3339),
                    'timeZone' => configuracaoGoogleCalendar()['timezone'],
                ]));
            }

            try {
                $criado = $servico->events->insert('primary', $eventoGoogle);
                responder([
                    'mensagem' => 'Evento adicionado ao Google Agenda',
                    'google_event_id' => $criado->getId(),
                    'html_link' => $criado->getHtmlLink(),
                ], 201);
            } catch (GoogleServiceException $erro) {
                if ((int) $erro->getCode() === 409) {
                    responder([
                        'mensagem' => 'Este planejamento já foi adicionado ao Google Agenda',
                        'ja_existia' => true,
                    ]);
                }

                throw $erro;
            }
        } catch (mysqli_sql_exception $erro) {
            responderErroInfraestrutura('Falha de banco na integração Google Agenda', $erro);
        } catch (Throwable $erro) {
            error_log('Falha na integração Google Agenda: ' . $erro->getMessage());
            responder(['erro' => 'Não foi possível comunicar com o Google Agenda'], 502);
        }

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
