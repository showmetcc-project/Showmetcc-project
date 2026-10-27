<?php

require_once __DIR__ . '/apiHelper.php';

function normalizarSessaoEventoApi(array $sessao): array
{
    return normalizarRegistroApi($sessao, ['id', 'evento_id']);
}

function normalizarDataSessao($valor): ?string
{
    $texto = trim((string) $valor);

    if ($texto === '') {
        return null;
    }

    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);

    return $data !== false && $data->format('Y-m-d') === $texto ? $texto : null;
}

function normalizarHoraSessao($valor): ?string
{
    $texto = trim((string) $valor);

    if (!preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $texto, $partes)) {
        return null;
    }

    return sprintf('%02d:%02d:%02d', (int) $partes[1], (int) $partes[2], (int) ($partes[3] ?? 0));
}

function calcularHoraFimSessao(string $horaInicio): string
{
    $hora = normalizarHoraSessao($horaInicio);

    if ($hora === null) {
        return '23:59:59';
    }

    $inicio = DateTimeImmutable::createFromFormat('!H:i:s', $hora);
    $fim = $inicio->modify('+2 hours');

    // Sessões não atravessam a meia-noite; o fallback encerra no fim do dia.
    return $fim->format('Y-m-d') === $inicio->format('Y-m-d') ? $fim->format('H:i:s') : '23:59:59';
}

function listarSessoesEvento(mysqli $conn, int $idEvento): array
{
    $stmt = $conn->prepare(
        'SELECT id, evento_id, data_evento, hora_inicio, hora_fim
         FROM evento_sessoes
         WHERE evento_id = ?
         ORDER BY data_evento ASC, hora_inicio ASC',
    );
    $stmt->bind_param('i', $idEvento);
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $sessoes = [];

    while ($sessao = $resultado->fetch_assoc()) {
        $sessoes[] = normalizarSessaoEventoApi($sessao);
    }

    $stmt->close();

    return $sessoes;
}

function obterSessoesEvento(mysqli $conn, int $idEvento, ?array $evento = null): array
{
    $sessoes = listarSessoesEvento($conn, $idEvento);

    if ($sessoes !== []) {
        return $sessoes;
    }

    if ($evento === null) {
        $stmt = $conn->prepare('SELECT data_evento, horario_evento FROM evento WHERE id_evento = ? LIMIT 1');
        $stmt->bind_param('i', $idEvento);
        executarStatementApi($stmt);
        $evento = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }

    $data = normalizarDataSessao($evento['data_evento'] ?? '');
    $horaInicio = normalizarHoraSessao($evento['horario_evento'] ?? '');

    if ($data === null || $horaInicio === null) {
        return [];
    }

    $horaFim = calcularHoraFimSessao($horaInicio);
    $stmt = $conn->prepare(
        'INSERT IGNORE INTO evento_sessoes (evento_id, data_evento, hora_inicio, hora_fim)
         VALUES (?, ?, ?, ?)',
    );
    $stmt->bind_param('isss', $idEvento, $data, $horaInicio, $horaFim);
    executarStatementApi($stmt);
    $stmt->close();

    return listarSessoesEvento($conn, $idEvento);
}

function buscarSessaoEvento(mysqli $conn, int $idEvento, int $idSessao): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, evento_id, data_evento, hora_inicio, hora_fim
         FROM evento_sessoes
         WHERE id = ? AND evento_id = ?
         LIMIT 1',
    );
    $stmt->bind_param('ii', $idSessao, $idEvento);
    executarStatementApi($stmt);
    $sessao = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $sessao === null ? null : normalizarSessaoEventoApi($sessao);
}

function anexarSessoesEventos(mysqli $conn, array &$eventos): void
{
    if ($eventos === []) {
        return;
    }

    $ids = [];

    foreach ($eventos as $evento) {
        $idEvento = (int) ($evento['id_evento'] ?? 0);

        if ($idEvento > 0) {
            $ids[$idEvento] = true;
        }
    }

    if ($ids === []) {
        return;
    }

    $ids = array_keys($ids);
    $marcadores = implode(', ', array_fill(0, count($ids), '?'));
    $tipos = str_repeat('i', count($ids));
    $stmt = $conn->prepare(
        "SELECT id, evento_id, data_evento, hora_inicio, hora_fim
         FROM evento_sessoes
         WHERE evento_id IN ($marcadores)
         ORDER BY evento_id ASC, data_evento ASC, hora_inicio ASC",
    );
    $stmt->bind_param($tipos, ...$ids);
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $sessoesPorEvento = [];

    while ($sessao = $resultado->fetch_assoc()) {
        $sessoesPorEvento[(int) $sessao['evento_id']][] = normalizarSessaoEventoApi($sessao);
    }

    $stmt->close();

    foreach ($eventos as &$evento) {
        $evento['sessoes'] = $sessoesPorEvento[(int) ($evento['id_evento'] ?? 0)] ?? [];
    }

    unset($evento);
}
