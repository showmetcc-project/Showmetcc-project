<?php

require_once __DIR__ . '/apiHelper.php';
require_once __DIR__ . '/sessoesEventoHelper.php';

function obterFimSessaoComunidade(array $sessao): ?DateTimeImmutable
{
    $data = normalizarDataSessao($sessao['data_evento'] ?? '');

    if ($data === null) {
        return null;
    }

    $horaFim = normalizarHoraSessao($sessao['hora_fim'] ?? '') ?? calcularHoraFimSessao($sessao['hora_inicio'] ?? '');
    $fuso = new DateTimeZone(API_FUSO_HORARIO);
    $fim = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', "$data $horaFim", $fuso);

    return $fim === false ? null : $fim;
}

function calcularCicloComunidadeEvento(array $sessoes, ?DateTimeImmutable $agora = null): array
{
    $fuso = new DateTimeZone(API_FUSO_HORARIO);
    $agora ??= new DateTimeImmutable('now', $fuso);
    $fimEvento = null;

    foreach ($sessoes as $sessao) {
        $fimSessao = obterFimSessaoComunidade($sessao);

        if ($fimSessao !== null && ($fimEvento === null || $fimSessao > $fimEvento)) {
            $fimEvento = $fimSessao;
        }
    }

    if ($fimEvento === null) {
        return [
            'somente_leitura' => false,
            'evento_encerrado_em' => null,
            'comunidade_somente_leitura_em' => null,
        ];
    }

    // A comunidade passa a funcionar como arquivo 48 horas após o término da última sessão.
    $limiteSomenteLeitura = $fimEvento->modify('+48 hours');

    return [
        'somente_leitura' => $agora >= $limiteSomenteLeitura,
        'evento_encerrado_em' => $fimEvento->format(DATE_ATOM),
        'comunidade_somente_leitura_em' => $limiteSomenteLeitura->format(DATE_ATOM),
    ];
}

function obterCicloComunidadeEvento(mysqli $conn, int $idEvento, ?array $evento = null): array
{
    return calcularCicloComunidadeEvento(obterSessoesEvento($conn, $idEvento, $evento));
}
