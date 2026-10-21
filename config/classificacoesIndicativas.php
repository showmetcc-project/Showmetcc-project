<?php

/**
 * Fonte única das classificações indicativas de evento do ShowMe.
 *
 * A ordem definida aqui é a mesma usada no filtro de cadastro, no painel
 * administrativo e nos selos (badges) dos cards e da página de detalhes.
 * Sempre inclua este arquivo com require_once para evitar redeclaração.
 */

/**
 * Retorna as classificações indicativas oficiais, na ordem de exibição.
 *
 * @return array<int, string>
 */
function classificacoesIndicativasOficiais(): array
{
    return [
        'Livre',
        '6 anos',
        '10 anos',
        '12 anos',
        '14 anos',
        '16 anos',
        '18 anos',
    ];
}

/**
 * Classificação assumida quando o campo não é informado.
 */
function classificacaoIndicativaPadrao(): string
{
    return 'Livre';
}

/**
 * Gera uma chave comparável (minúscula, sem "anos" e sem espaços duplicados),
 * tolerando variações como "12", "12 anos" ou "12 ANOS".
 */
function chaveClassificacaoIndicativa(string $valor): string
{
    $valor = strtolower(trim($valor));
    $valor = str_replace('anos', '', $valor);
    $normalizado = preg_replace('/\s+/', ' ', $valor);

    return trim($normalizado === null ? $valor : $normalizado);
}

/**
 * Normaliza um valor recebido para a classificação oficial correspondente.
 *
 * Retorna a classificação padrão quando o valor é ausente/vazio e `null`
 * quando o valor informado não corresponde a nenhuma classificação oficial.
 */
function normalizarClassificacaoIndicativa($valor): ?string
{
    if ($valor === null || (is_string($valor) && trim($valor) === '')) {
        return classificacaoIndicativaPadrao();
    }

    if (!is_scalar($valor)) {
        return null;
    }

    $chave = chaveClassificacaoIndicativa((string) $valor);

    foreach (classificacoesIndicativasOficiais() as $classificacao) {
        if (chaveClassificacaoIndicativa($classificacao) === $chave) {
            return $classificacao;
        }
    }

    return null;
}
