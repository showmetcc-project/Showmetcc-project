<?php

use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Exception as GoogleServiceException;

require_once __DIR__ . '/middleware/apiCommon.php';
require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/sessoesEventoHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/googleCalendarHelper.php';

function buscarEventoParaAgenda(mysqli $conn, int $idEvento, ?int $idSessao = null): ?array
{
    $stmt = $conn->prepare(
        'SELECT id_evento, nome_evento, endereco_evento, numero_endereco,
                rua_evento, cidade_evento, uf,
                data_evento, horario_evento, link_oficial
         FROM evento
         WHERE id_evento = ?
         LIMIT 1',
    );
    $stmt->bind_param('i', $idEvento);
    executarStatementApi($stmt);
    $evento = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($evento === null) {
        return null;
    }

    if ($idSessao === null) {
        $sessoes = obterSessoesEvento($conn, $idEvento, $evento);
        $sessao = $sessoes[0] ?? null;
    } else {
        $sessao = buscarSessaoEvento($conn, $idEvento, $idSessao);
    }

    if ($sessao === null) {
        return null;
    }

    $evento['sessao_id'] = $sessao['id'];
    $evento['data_evento'] = $sessao['data_evento'];
    $evento['horario_evento'] = $sessao['hora_inicio'];
    $evento['hora_fim'] = $sessao['hora_fim'];

    return $evento;
}

function janelaEventoGoogleCalendar(array $evento, int $tempoDeslocamento = 0): array
{
    $configuracao = configuracaoGoogleCalendar();
    $fuso = new DateTimeZone($configuracao['timezone'] ?: API_FUSO_HORARIO);
    $data = trim((string) ($evento['data_evento'] ?? ''));
    $horario = trim((string) ($evento['horario_evento'] ?? ''));
    $horarioFim = normalizarHoraSessao($evento['hora_fim'] ?? '');

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
    $fimEvento =
        $horarioFim === null
            ? $inicioEvento->modify('+2 hours')
            : new DateTimeImmutable($data . ' ' . $horarioFim, $fuso);

    if ($fimEvento <= $inicioEvento) {
        $fimEvento = $inicioEvento->modify('+2 hours');
    }

    return [
        'inicio' => $inicio,
        'fim' => $fimEvento,
        'inicio_evento' => $inicioEvento,
        'dia_inteiro' => false,
    ];
}

function descreverSessaoGoogleCalendar(array $evento): string
{
    $data = trim((string) ($evento['data_evento'] ?? ''));
    $horaInicio = normalizarHoraSessao($evento['horario_evento'] ?? '');
    $horaFim = normalizarHoraSessao($evento['hora_fim'] ?? '');

    if ($data === '') {
        return '';
    }

    $dataFormatada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

    if ($dataFormatada === false) {
        return '';
    }

    $descricao = $dataFormatada->format('d/m/Y');

    if ($horaInicio !== null) {
        $descricao .= ' — ' . substr($horaInicio, 0, 5) . 'h';

        if ($horaFim !== null) {
            $descricao .= ' às ' . substr($horaFim, 0, 5) . 'h';
        }
    }

    return $descricao;
}

function idSessaoGoogleCalendar(array $dados): ?int
{
    if (!array_key_exists('sessao_id', $dados) || $dados['sessao_id'] === null || $dados['sessao_id'] === '') {
        return null;
    }

    $idSessao = filter_var($dados['sessao_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($idSessao === false) {
        responder(['erro' => 'sessao_id deve ser um inteiro positivo'], 400);
    }

    return (int) $idSessao;
}

function enderecoEventoGoogleCalendar(array $evento): string
{
    if (!empty($evento['endereco_evento'])) {
        return implode(
            ', ',
            array_filter(
                [$evento['endereco_evento'], $evento['numero_endereco'] ?? null],
                static fn($valor) => trim((string) $valor) !== '',
            ),
        );
    }

    return implode(
        ', ',
        array_filter(
            [$evento['rua_evento'] ?? null, $evento['cidade_evento'] ?? null, $evento['uf'] ?? null],
            static fn($valor) => trim((string) $valor) !== '',
        ),
    );
}

function idEventoGoogleCalendar(int $idUsuario, int $idRota): string
{
    // O ID determinístico evita duplicações e permite reconhecer o evento
    // exportado ao revisitar o planejamento.
    return '5a0e' . substr(hash('sha256', $idUsuario . ':' . $idRota), 0, 32);
}

function idGoogleEventoDoPlanejamento(mysqli $conn, int $idUsuario, int $idRota): ?string
{
    $stmt = $conn->prepare('SELECT id_rota FROM rota WHERE id_rota = ? AND id_user = ? LIMIT 1');
    $stmt->bind_param('ii', $idRota, $idUsuario);
    executarStatementApi($stmt);
    $planejamento = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $planejamento ? idEventoGoogleCalendar($idUsuario, $idRota) : null;
}

function idRotaIgnorarGoogleAgenda(array $dados): ?int
{
    if (!array_key_exists('id_rota', $dados) || $dados['id_rota'] === null || $dados['id_rota'] === '') {
        return null;
    }

    $idRota = filter_var($dados['id_rota'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($idRota === false) {
        responder(['erro' => 'id_rota deve ser um inteiro positivo'], 400);
    }

    return (int) $idRota;
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

        if (!in_array($acao, ['verificar_conflito', 'verificar_data', 'verificar_exportado', 'exportar'], true)) {
            responder(['erro' => 'Ação inválida para o Google Agenda'], 400);
        }

        $token = buscarTokenGoogleCalendar($conn, $idUsuario);

        if ($token === null) {
            if ($acao === 'exportar') {
                responder(
                    [
                        'erro' => 'Conecte o Google Agenda antes de exportar o planejamento',
                        'conectado' => false,
                    ],
                    409,
                );
            }

            responder([
                'conectado' => false,
                'conflito' => false,
                'conflitos' => [],
                'exportado' => false,
                'mensagem' => 'Google Agenda não está conectado',
            ]);
        }

        if (!googleCalendarConfigurado()) {
            responder(['erro' => 'Google Agenda ainda não foi configurado no servidor'], 503);
        }

        try {
            $cliente = clienteGoogleCalendarAutorizado($conn, $idUsuario);
            $servico = new Calendar($cliente);

            if ($acao === 'verificar_exportado') {
                $idRota = idRotaIgnorarGoogleAgenda($dados);

                if ($idRota === null) {
                    responder(['erro' => 'id_rota é obrigatório'], 400);
                }

                $idGoogleEvento = idGoogleEventoDoPlanejamento($conn, $idUsuario, $idRota);

                if ($idGoogleEvento === null) {
                    responder(['erro' => 'Planejamento não encontrado'], 404);
                }

                try {
                    $eventoGoogle = $servico->events->get('primary', $idGoogleEvento);

                    responder([
                        'exportado' => true,
                        'google_event_id' => $eventoGoogle->getId(),
                        'html_link' => $eventoGoogle->getHtmlLink(),
                    ]);
                } catch (GoogleServiceException $erro) {
                    if ((int) $erro->getCode() === 404) {
                        responder([
                            'exportado' => false,
                            'google_event_id' => $idGoogleEvento,
                            'html_link' => null,
                        ]);
                    }

                    throw $erro;
                }
            }

            if ($acao === 'verificar_conflito') {
                $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);
                $tempo = filter_var($dados['tempo_estimado'] ?? 0, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 0],
                ]);
                $idSessao = idSessaoGoogleCalendar($dados);

                if ($idEvento === false || $tempo === false) {
                    responder(['erro' => 'id_evento e tempo_estimado devem ser inteiros válidos'], 400);
                }

                $evento = buscarEventoParaAgenda($conn, (int) $idEvento, $idSessao);

                if ($evento === null) {
                    responder(['erro' => 'Evento ou sessão não encontrados'], 404);
                }

                $idRotaIgnorar = idRotaIgnorarGoogleAgenda($dados);
                $idGoogleAtual =
                    $idRotaIgnorar === null ? null : idGoogleEventoDoPlanejamento($conn, $idUsuario, $idRotaIgnorar);

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

                    // O próprio planejamento editado não é um conflito consigo mesmo.
                    if ($idGoogleAtual !== null && $item->getId() === $idGoogleAtual) {
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

            if ($acao === 'verificar_data') {
                $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1],
                ]);

                if ($idEvento === false) {
                    responder(['erro' => 'id_evento deve ser um inteiro válido'], 400);
                }

                $idSessao = idSessaoGoogleCalendar($dados);
                $evento = buscarEventoParaAgenda($conn, (int) $idEvento, $idSessao);

                if ($evento === null) {
                    responder(['erro' => 'Evento ou sessão não encontrados'], 404);
                }

                $data = trim((string) ($evento['data_evento'] ?? ''));

                if ($data === '') {
                    responder([
                        'conectado' => true,
                        'conflito' => false,
                        'conflitos' => [],
                        'data' => null,
                    ]);
                }

                $configuracao = configuracaoGoogleCalendar();
                $fuso = new DateTimeZone($configuracao['timezone'] ?: API_FUSO_HORARIO);
                $inicio = new DateTimeImmutable($data . ' 00:00:00', $fuso);
                $fim = $inicio->modify('+1 day');

                $idRotaIgnorar = idRotaIgnorarGoogleAgenda($dados);
                $idGoogleAtual =
                    $idRotaIgnorar === null ? null : idGoogleEventoDoPlanejamento($conn, $idUsuario, $idRotaIgnorar);

                $resultado = $servico->events->listEvents('primary', [
                    'timeMin' => $inicio->format(DATE_RFC3339),
                    'timeMax' => $fim->format(DATE_RFC3339),
                    'singleEvents' => true,
                    'orderBy' => 'startTime',
                    'maxResults' => 50,
                ]);
                $conflitos = [];

                foreach ($resultado->getItems() as $item) {
                    if ($item->getStatus() === 'cancelled') {
                        continue;
                    }

                    // Ignora o evento do planejamento que está sendo editado.
                    if ($idGoogleAtual !== null && $item->getId() === $idGoogleAtual) {
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
                    'data' => $data,
                ]);
            }

            $idRota = filter_var($dados['id_rota'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if ($idRota === false) {
                responder(['erro' => 'id_rota deve ser um inteiro positivo'], 400);
            }

            $stmt = $conn->prepare(
                'SELECT r.id_rota, r.id_evento, r.sessao_id, r.tempo_estimado,
                        e.nome_evento, e.endereco_evento, e.numero_endereco,
                        e.rua_evento, e.cidade_evento, e.uf,
                        COALESCE(s.data_evento, e.data_evento) AS data_evento,
                        COALESCE(s.hora_inicio, e.horario_evento) AS horario_evento,
                        s.hora_fim, e.link_oficial
                 FROM rota r
                 INNER JOIN evento e ON e.id_evento = r.id_evento
                 LEFT JOIN evento_sessoes s ON s.id = r.sessao_id
                 WHERE r.id_rota = ? AND r.id_user = ?
                 LIMIT 1',
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
            $sessaoDescricao = descreverSessaoGoogleCalendar($planejamento);

            if ($sessaoDescricao !== '') {
                $descricao .= "\nSessão: " . $sessaoDescricao;
            }

            if (!empty($planejamento['link_oficial'])) {
                $descricao .= "\n" . $planejamento['link_oficial'];
            }

            $eventoGoogle = new GoogleCalendarEvent([
                // ID determinístico impede duplicação em cliques/recarregamentos posteriores.
                'id' => idEventoGoogleCalendar($idUsuario, $idRota),
                'summary' => $planejamento['nome_evento'],
                'description' => $descricao,
                'location' => enderecoEventoGoogleCalendar($planejamento),
            ]);

            if ($janela['dia_inteiro']) {
                $eventoGoogle->setStart(
                    new EventDateTime([
                        'date' => $janela['inicio']->format('Y-m-d'),
                    ]),
                );
                $eventoGoogle->setEnd(
                    new EventDateTime([
                        'date' => $janela['fim']->format('Y-m-d'),
                    ]),
                );
            } else {
                $eventoGoogle->setStart(
                    new EventDateTime([
                        'dateTime' => $janela['inicio_evento']->format(DATE_RFC3339),
                        'timeZone' => configuracaoGoogleCalendar()['timezone'],
                    ]),
                );
                $eventoGoogle->setEnd(
                    new EventDateTime([
                        'dateTime' => $janela['fim']->format(DATE_RFC3339),
                        'timeZone' => configuracaoGoogleCalendar()['timezone'],
                    ]),
                );
            }

            try {
                $criado = $servico->events->insert('primary', $eventoGoogle);
                responder(
                    [
                        'mensagem' => 'Evento adicionado ao Google Agenda',
                        'google_event_id' => $criado->getId(),
                        'html_link' => $criado->getHtmlLink(),
                    ],
                    201,
                );
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
