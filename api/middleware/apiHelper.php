<?php

require_once dirname(__DIR__, 2) . '/config/erroInfraestrutura.php';

set_exception_handler(static function (Throwable $erro): void {
    responderErroInfraestrutura('Exceção não tratada na API', $erro);
});

const API_FUSO_HORARIO = 'America/Sao_Paulo';

function executarStatementApi(mysqli_stmt $stmt): void
{
    if (!$stmt->execute()) {
        throw new RuntimeException(
            $stmt->error !== '' ? $stmt->error : 'Falha ao executar operação no banco de dados'
        );
    }
}

function tamanhoTextoApi(string $texto): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($texto, 'UTF-8')
        : strlen($texto);
}

function validarLimitesTextoApi(array $valores, array $limites): ?string
{
    foreach ($limites as $campo => $limite) {
        $valor = $valores[$campo] ?? null;

        if ($valor === null) {
            continue;
        }

        if (tamanhoTextoApi((string) $valor) > $limite) {
            return "$campo deve ter no máximo $limite caracteres";
        }
    }

    return null;
}

function normalizarDataHoraApi($valor): ?string
{
    if ($valor === null || $valor === '') {
        return null;
    }

    $fuso = new DateTimeZone(API_FUSO_HORARIO);
    $data = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', (string) $valor, $fuso);

    if ($data === false) {
        return (string) $valor;
    }

    return $data->format(DATE_ATOM);
}

function normalizarRegistroApi(
    array $registro,
    array $camposInteiros = [],
    array $camposBooleanos = [],
    array $camposDecimais = [],
    array $camposDataHora = []
): array {
    foreach ($camposInteiros as $campo) {
        if (array_key_exists($campo, $registro) && $registro[$campo] !== null) {
            $registro[$campo] = (int) $registro[$campo];
        }
    }

    foreach ($camposBooleanos as $campo) {
        if (array_key_exists($campo, $registro) && $registro[$campo] !== null) {
            $registro[$campo] = (bool) $registro[$campo];
        }
    }

    foreach ($camposDecimais as $campo) {
        if (array_key_exists($campo, $registro) && $registro[$campo] !== null) {
            $registro[$campo] = (float) $registro[$campo];
        }
    }

    foreach ($camposDataHora as $campo) {
        if (array_key_exists($campo, $registro)) {
            $registro[$campo] = normalizarDataHoraApi($registro[$campo]);
        }
    }

    return $registro;
}

function normalizarUsuarioApi(array $usuario): array
{
    return normalizarRegistroApi($usuario, ['id_user'], [], [], ['data_cadastro']);
}

function normalizarEventoApi(array $evento): array
{
    return normalizarRegistroApi(
        $evento,
        ['id_evento', 'id_solicitacao_origem', 'num_evento'],
        ['gratuidade'],
        ['valor_ingresso_minimo', 'valor_ingresso_maximo']
    );
}

function normalizarArtistaApi(array $artista): array
{
    return normalizarRegistroApi($artista, ['id_artista']);
}

function normalizarSolicitacaoApi(array $solicitacao): array
{
    return normalizarRegistroApi(
        $solicitacao,
        ['id_solicitacao', 'id_user', 'id_evento'],
        ['gratuidade'],
        ['valor_ingresso_minimo', 'valor_ingresso_maximo'],
        ['data_solicitacao']
    );
}

function normalizarFavoritoApi(array $favorito): array
{
    return normalizarRegistroApi(
        $favorito,
        ['id_favorito', 'id_evento'],
        ['gratuidade']
    );
}

function normalizarPlanejamentoApi(array $planejamento): array
{
    return normalizarRegistroApi(
        $planejamento,
        ['id_rota', 'id_evento', 'tempo_estimado'],
        ['gratuidade', 'hospedagem_necessaria'],
        [
            'distancia_km', 'orcamento_total', 'custo_ingresso',
            'custo_transporte', 'custo_hospedagem', 'investimento_total',
            'valor_ingresso_minimo', 'valor_ingresso_maximo'
        ]
    );
}

function normalizarPostComunidadeApi(array $post): array
{
    return normalizarRegistroApi(
        $post,
        ['id_post', 'id_evento', 'id_usuario', 'total_curtidas'],
        ['curtido_usuario'],
        [],
        ['data_criacao']
    );
}

function normalizarRespostaComunidadeApi(array $resposta): array
{
    return normalizarRegistroApi(
        $resposta,
        ['id_resposta', 'id_post', 'id_usuario'],
        [],
        [],
        ['data_criacao']
    );
}

function normalizarMidiaComunidadeApi(array $midia): array
{
    return normalizarRegistroApi(
        $midia,
        ['id_midia', 'id_evento', 'id_usuario'],
        ['permitir_download'],
        [],
        ['data_criacao']
    );
}

function normalizarResumoComunidadeApi(array $evento): array
{
    return normalizarRegistroApi(
        $evento,
        ['id_evento', 'total_posts'],
        ['evento_do_usuario']
    );
}

function normalizarDenunciaComunidadeApi(array $denuncia): array
{
    return normalizarRegistroApi(
        $denuncia,
        [
            'id_denuncia', 'id_post', 'id_denunciante', 'id_admin_moderacao',
            'id_evento', 'id_autor'
        ],
        [],
        [],
        ['data_denuncia', 'data_moderacao', 'data_post']
    );
}
