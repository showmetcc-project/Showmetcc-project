<?php

require_once __DIR__ . '/middleware/apiCommon.php';
require_once __DIR__ . '/../config/categoriasEvento.php';
require_once __DIR__ . '/../config/classificacoesIndicativas.php';

function normalizarEvento(array $evento): array
{
    return normalizarEventoApi($evento);
}

function normalizarArtistaEvento(array $artista): array
{
    return normalizarArtistaApi($artista);
}

function normalizarSolicitacao(array $solicitacao): array
{
    return normalizarSolicitacaoApi($solicitacao);
}

function tamanhoTexto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

function normalizarEspacos(string $texto): string
{
    $texto = trim($texto);
    $normalizado = preg_replace('/\s+/u', ' ', $texto);
    return $normalizado === null ? $texto : $normalizado;
}

function chaveCategoriaEvento(string $categoria): string
{
    $categoria = normalizarEspacos($categoria);
    $semAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $categoria);
    return strtolower($semAcentos === false ? $categoria : $semAcentos);
}

function normalizarCategoriasEvento($valor): string
{
    $permitidas = categoriasEventoOficiais();
    $mapa = [];
    foreach ($permitidas as $categoria) {
        $mapa[chaveCategoriaEvento($categoria)] = $categoria;
    }

    $recebidas = is_array($valor)
        ? $valor
        : preg_split('/\s*,\s*/u', (string) $valor, -1, PREG_SPLIT_NO_EMPTY);
    $normalizadas = [];

    foreach ($recebidas ?: [] as $categoria) {
        if (!is_scalar($categoria)) {
            responder(['erro' => 'categoria_evento contém um valor inválido'], 400);
        }
        $chave = chaveCategoriaEvento((string) $categoria);
        if (!isset($mapa[$chave])) {
            responder(['erro' => 'Categoria de evento inválida'], 400);
        }
        $normalizadas[$mapa[$chave]] = true;
    }

    if ($normalizadas === []) {
        responder(['erro' => 'Selecione pelo menos uma categoria para o evento'], 400);
    }

    return implode(', ', array_keys($normalizadas));
}

function separarValoresRecomendacao(string $valor): array
{
    $valor = trim($valor);

    if ($valor === '') {
        return [];
    }

    $partes = preg_split('/\s*[,;\/]\s*/u', $valor, -1, PREG_SPLIT_NO_EMPTY);
    return $partes === false ? [] : $partes;
}

function chaveRecomendacao(string $valor): string
{
    $valor = normalizarEspacos($valor);
    $semAcentos = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor);

    return strtolower($semAcentos === false ? $valor : $semAcentos);
}

/**
 * Monta o histórico enxuto do usuário: um evento conta uma vez, mesmo que
 * esteja nos favoritos e em uma rota. Categorias e gêneros são separados
 * para que "Música, Show Nacional" gere peso para as duas categorias.
 */
function carregarHistoricoRecomendacao(mysqli $conn, int $idUsuario): array
{
    $stmt = $conn->prepare(
        'SELECT e.id_evento, e.categoria_evento, a.genero_artista
         FROM evento e
         LEFT JOIN artista_evento ae ON ae.id_evento = e.id_evento
         LEFT JOIN artista a ON a.id_artista = ae.id_artista
         WHERE e.id_evento IN (
             SELECT id_evento FROM favoritos WHERE id_user = ?
             UNION
             SELECT id_evento FROM rota WHERE id_user = ?
         )'
    );
    $stmt->bind_param('ii', $idUsuario, $idUsuario);
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $historico = [];

    while ($linha = $resultado->fetch_assoc()) {
        $idEvento = (int) $linha['id_evento'];

        if (!isset($historico[$idEvento])) {
            $historico[$idEvento] = [
                'categorias' => [],
                'generos' => [],
            ];
        }

        foreach (separarValoresRecomendacao((string) ($linha['categoria_evento'] ?? '')) as $categoria) {
            $chave = chaveRecomendacao($categoria);

            if ($chave !== '') {
                $historico[$idEvento]['categorias'][$chave] = $categoria;
            }
        }

        foreach (separarValoresRecomendacao((string) ($linha['genero_artista'] ?? '')) as $genero) {
            $chave = chaveRecomendacao($genero);

            if ($chave !== '') {
                $historico[$idEvento]['generos'][$chave] = $genero;
            }
        }
    }

    $stmt->close();
    return $historico;
}

function montarPesosRecomendacao(array $historico): array
{
    $categorias = [];
    $generos = [];

    foreach ($historico as $evento) {
        foreach ($evento['categorias'] as $chave => $rotulo) {
            if (!isset($categorias[$chave])) {
                $categorias[$chave] = ['categoria' => $rotulo, 'peso' => 0];
            }
            $categorias[$chave]['peso']++;
        }

        foreach ($evento['generos'] as $chave => $rotulo) {
            if (!isset($generos[$chave])) {
                $generos[$chave] = ['genero' => $rotulo, 'peso' => 0];
            }
            $generos[$chave]['peso']++;
        }
    }

    $categorias = array_values($categorias);
    $generos = array_values($generos);

    usort($categorias, static function (array $a, array $b): int {
        return ($b['peso'] <=> $a['peso'])
            ?: strcasecmp($a['categoria'], $b['categoria']);
    });
    usort($generos, static function (array $a, array $b): int {
        return ($b['peso'] <=> $a['peso'])
            ?: strcasecmp($a['genero'], $b['genero']);
    });

    return [
        'personalizado' => $categorias !== [] || $generos !== [],
        'categorias' => $categorias,
        'generos' => $generos,
    ];
}

/**
 * Busca em uma única consulta os gêneros ligados aos eventos que serão
 * devolvidos pela Home.
 */
function carregarGenerosPorEvento(mysqli $conn, array $idsEventos): array
{
    $ids = [];

    foreach ($idsEventos as $idEvento) {
        $idEvento = (int) $idEvento;

        if ($idEvento > 0) {
            $ids[$idEvento] = $idEvento;
        }
    }

    $ids = array_values($ids);

    if ($ids === []) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare(
        "SELECT ae.id_evento, a.genero_artista
         FROM artista_evento ae
         INNER JOIN artista a ON a.id_artista = ae.id_artista
         WHERE ae.id_evento IN ($marcadores)
           AND a.genero_artista IS NOT NULL
           AND TRIM(a.genero_artista) <> ''"
    );
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    executarStatementApi($stmt);
    $resultado = $stmt->get_result();
    $generos = [];

    while ($linha = $resultado->fetch_assoc()) {
        $idEvento = (int) $linha['id_evento'];

        if (!isset($generos[$idEvento])) {
            $generos[$idEvento] = [];
        }

        foreach (separarValoresRecomendacao((string) $linha['genero_artista']) as $genero) {
            $chave = chaveRecomendacao($genero);

            if ($chave !== '') {
                $generos[$idEvento][$chave] = $genero;
            }
        }
    }

    $stmt->close();
    return $generos;
}

/**
 * Ordena os eventos por peso de recomendação e usa a data mais próxima
 * como desempate. Eventos sem histórico ficam com peso zero e mantêm a
 * ordem cronológica recebida do banco.
 */
function aplicarRecomendacaoEventos(array &$eventos, array $preferencias, array $generosPorEvento): void
{
    $pesosCategorias = [];
    $pesosGeneros = [];

    foreach ($preferencias['categorias'] as $item) {
        $pesosCategorias[chaveRecomendacao((string) $item['categoria'])] = (int) $item['peso'];
    }

    foreach ($preferencias['generos'] as $item) {
        $pesosGeneros[chaveRecomendacao((string) $item['genero'])] = (int) $item['peso'];
    }

    $pesosEventos = [];

    foreach ($eventos as $evento) {
        $idEvento = (int) $evento['id_evento'];
        $peso = 0;

        foreach (separarValoresRecomendacao((string) ($evento['categoria_evento'] ?? '')) as $categoria) {
            $peso += $pesosCategorias[chaveRecomendacao($categoria)] ?? 0;
        }

        foreach (array_keys($generosPorEvento[$idEvento] ?? []) as $chaveGenero) {
            $peso += $pesosGeneros[$chaveGenero] ?? 0;
        }

        $pesosEventos[$idEvento] = $peso;
    }

    usort($eventos, static function (array $a, array $b) use ($pesosEventos): int {
        $idA = (int) $a['id_evento'];
        $idB = (int) $b['id_evento'];
        $comparacaoPeso = ($pesosEventos[$idB] ?? 0) <=> ($pesosEventos[$idA] ?? 0);

        if ($comparacaoPeso !== 0) {
            return $comparacaoPeso;
        }

        $comparacaoData = strcmp(
            (string) ($a['data_evento'] ?? ''),
            (string) ($b['data_evento'] ?? '')
        );

        return $comparacaoData !== 0 ? $comparacaoData : ($idA <=> $idB);
    });
}

function atualizarTextoOpcional(array $dados, string $campo, $valorAtual): ?string
{
    if (!array_key_exists($campo, $dados)) {
        return $valorAtual;
    }

    $valor = trim((string) $dados[$campo]);
    return $valor === '' ? null : $valor;
}

function normalizarCepEvento($valor): string
{
    $cep = preg_replace('/\D/', '', (string) $valor);

    if ($cep === null || strlen($cep) !== 8) {
        responder(['erro' => 'cep_evento deve conter exatamente 8 números'], 400);
    }

    return $cep;
}

function normalizarValorIngresso($valor, string $campo, bool $obrigatorio): ?float
{
    if ($valor === null || $valor === '') {
        if ($obrigatorio) {
            responder(['erro' => "$campo é obrigatório para eventos pagos"], 400);
        }

        return null;
    }

    if (!is_numeric($valor)) {
        responder(['erro' => "$campo deve ser um número válido"], 400);
    }

    $numero = round((float) $valor, 2);
    if ($numero <= 0 || $numero > 99999999.99) {
        responder(['erro' => "$campo deve ser maior que zero"], 400);
    }

    return $numero;
}

function validarFaixaIngresso(int $gratuidade, $minimo, $maximo): array
{
    if ($gratuidade === 1) {
        return [null, null];
    }

    $minimoNormalizado = normalizarValorIngresso($minimo, 'valor_ingresso_minimo', true);
    $maximoNormalizado = normalizarValorIngresso($maximo, 'valor_ingresso_maximo', false);

    if ($maximoNormalizado !== null && $maximoNormalizado < $minimoNormalizado) {
        responder(['erro' => 'valor_ingresso_maximo não pode ser menor que o mínimo'], 400);
    }

    return [$minimoNormalizado, $maximoNormalizado];
}

require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verificaLogin.php';
require_once __DIR__ . '/middleware/verificaAdmin.php';
require_once __DIR__ . '/middleware/uploadHelper.php';
require_once dirname(__DIR__) . '/config/emailHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

$camposEvento = 'id_evento, id_solicitacao_origem, num_evento, nome_evento,
                 cep_evento, endereco_evento, numero_endereco, rua_evento,
                 cidade_evento, uf, descricao_evento, data_evento, gratuidade,
                 valor_ingresso_minimo, valor_ingresso_maximo,
                 horario_evento, categoria_evento, classificacao_indicativa,
                 link_oficial, imagem_evento, alt_imagem, status_evento';

$camposEventoComAlias = 'e.id_evento, e.id_solicitacao_origem, e.num_evento, e.nome_evento,
                         e.cep_evento, e.endereco_evento, e.numero_endereco,
                         e.rua_evento, e.cidade_evento, e.uf, e.descricao_evento,
                         e.data_evento, e.gratuidade, e.valor_ingresso_minimo,
                         e.valor_ingresso_maximo, e.horario_evento, e.categoria_evento,
                         e.classificacao_indicativa,
                         e.link_oficial, e.imagem_evento, e.alt_imagem, e.status_evento';

switch ($metodo) {
    case 'GET':
        if (array_key_exists('listar_locais', $_GET)) {
            if (!is_string($_GET['listar_locais']) || $_GET['listar_locais'] !== '1') {
                responder(['erro' => 'listar_locais deve ser 1'], 400);
            }

            if ($id !== null || array_key_exists('busca', $_GET)) {
                responder(['erro' => 'listar_locais não pode ser combinado com ID ou busca'], 400);
            }

            $resultadoLocais = $conn->query(
                "SELECT DISTINCT uf, cidade_evento
                 FROM evento
                 WHERE status_evento = 'ativo'
                   AND uf IS NOT NULL AND TRIM(uf) <> ''
                   AND cidade_evento IS NOT NULL AND TRIM(cidade_evento) <> ''
                 ORDER BY uf ASC, cidade_evento ASC"
            );
            $ufs = [];
            $cidades = [];
            while ($local = $resultadoLocais->fetch_assoc()) {
                $ufLocal = strtoupper(trim((string) $local['uf']));
                $cidadeLocal = trim((string) $local['cidade_evento']);
                $ufs[$ufLocal] = $ufLocal;
                $cidades[] = ['cidade' => $cidadeLocal, 'uf' => $ufLocal];
            }

            // O filtro exibe estritamente as 9 categorias oficiais, na ordem
            // canônica definida em config/categoriasEvento.php.
            $categorias = categoriasEventoOficiais();

            responder([
                'ufs' => array_values($ufs),
                'cidades' => $cidades,
                'categorias' => $categorias,
            ]);
        }

        $incluirCancelados = false;

        if (array_key_exists('incluir_cancelados', $_GET)) {
            $valorIncluirCancelados = $_GET['incluir_cancelados'];

            if (
                !is_string($valorIncluirCancelados)
                || !in_array($valorIncluirCancelados, ['0', '1'], true)
            ) {
                responder(['erro' => 'incluir_cancelados deve ser 0 ou 1'], 400);
            }

            if ($valorIncluirCancelados === '1') {
                exigirAdmin();
                $incluirCancelados = true;
            }
        }

        if ($id !== null) {
            $filtroStatus = $incluirCancelados ? '' : " AND status_evento = 'ativo'";
            $stmt = $conn->prepare(
                "SELECT $camposEvento FROM evento WHERE id_evento = ?$filtroStatus LIMIT 1"
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $evento = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$evento) {
                responder(['erro' => 'Evento não encontrado'], 404);
            }

            $stmt = $conn->prepare(
                'SELECT a.id_artista, a.nome_artista, a.genero_artista, a.imagem_artista
                 FROM artista a
                 INNER JOIN artista_evento ae ON ae.id_artista = a.id_artista
                 WHERE ae.id_evento = ?
                 ORDER BY a.nome_artista ASC'
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $resultadoArtistas = $stmt->get_result();
            $artistas = [];

            while ($artista = $resultadoArtistas->fetch_assoc()) {
                $artistas[] = normalizarArtistaEvento($artista);
            }

            $stmt->close();
            $evento = normalizarEvento($evento);
            $evento['artistas'] = $artistas;

            responder(['evento' => $evento]);
        }

        if (array_key_exists('solicitacoes', $_GET)) {
            if (!is_string($_GET['solicitacoes'])) {
                responder(['erro' => 'O parâmetro solicitacoes deve ser um texto'], 400);
            }

            $statusSolicitacoes = strtolower(trim($_GET['solicitacoes']));

            if (!in_array($statusSolicitacoes, ['pendente', 'aprovado', 'recusado', 'removido', 'todas'], true)) {
                responder([
                    'erro' => 'solicitacoes deve ser pendente, aprovado, recusado, removido ou todas'
                ], 400);
            }

            if (array_key_exists('incluir_cancelados', $_GET)) {
                responder(['erro' => 'Não combine solicitacoes com incluir_cancelados'], 400);
            }

            exigirAdmin();

            if (array_key_exists('busca', $_GET) && !is_string($_GET['busca'])) {
                responder(['erro' => 'busca deve ser um texto'], 400);
            }
            $buscaSolicitacoes = trim((string) ($_GET['busca'] ?? ''));
            if (tamanhoTexto($buscaSolicitacoes) > 100) {
                responder(['erro' => 'busca deve ter no máximo 100 caracteres'], 400);
            }

            $diasSolicitacoes = 0;
            if (array_key_exists('dias', $_GET)) {
                $diasInformados = $_GET['dias'];
                if (!is_string($diasInformados) || !ctype_digit($diasInformados)
                    || (int) $diasInformados > 3650) {
                    responder(['erro' => 'dias deve ser um inteiro entre 0 e 3650'], 400);
                }
                $diasSolicitacoes = (int) $diasInformados;
            }

            $limiteSolicitacoes = null;
            $offsetSolicitacoes = 0;
            if (array_key_exists('limite', $_GET)) {
                $limiteInformado = $_GET['limite'];
                if (!is_string($limiteInformado) || !ctype_digit($limiteInformado)
                    || (int) $limiteInformado < 1 || (int) $limiteInformado > 50) {
                    responder(['erro' => 'limite deve ser um inteiro entre 1 e 50'], 400);
                }
                $limiteSolicitacoes = (int) $limiteInformado;
            }

            if (array_key_exists('offset', $_GET)) {
                $offsetInformado = $_GET['offset'];
                if ($limiteSolicitacoes === null || !is_string($offsetInformado)
                    || !ctype_digit($offsetInformado)) {
                    responder(['erro' => 'offset deve ser um inteiro maior ou igual a zero e exige limite'], 400);
                }
                $offsetSolicitacoes = (int) $offsetInformado;
            }

            $camposSolicitacao =
                's.id_solicitacao, s.id_user,
                 IF(e.id_evento IS NULL, s.nome_evento, e.nome_evento) AS nome_evento,
                 s.status_solicitacao,
                 IF(e.id_evento IS NULL, s.foto, e.imagem_evento) AS foto,
                 IF(e.id_evento IS NULL, s.alt_imagem, e.alt_imagem) AS alt_imagem,
                 IF(e.id_evento IS NULL, s.horario_evento, e.horario_evento) AS horario_evento,
                 IF(e.id_evento IS NULL, s.data_evento, e.data_evento) AS data_evento,
                 IF(e.id_evento IS NULL, s.cep_evento, e.cep_evento) AS cep_evento,
                 IF(e.id_evento IS NULL, s.endereco_evento, e.endereco_evento) AS endereco_evento,
                 IF(e.id_evento IS NULL, s.numero_endereco, e.numero_endereco) AS numero_endereco,
                 IF(e.id_evento IS NULL, s.rua_evento, e.rua_evento) AS rua_evento,
                 IF(e.id_evento IS NULL, s.cidade_evento, e.cidade_evento) AS cidade_evento,
                 IF(e.id_evento IS NULL, s.uf, e.uf) AS uf,
                 IF(e.id_evento IS NULL, s.categoria_evento, e.categoria_evento) AS categoria_evento,
                 IF(e.id_evento IS NULL, s.classificacao_indicativa, e.classificacao_indicativa) AS classificacao_indicativa,
                 IF(e.id_evento IS NULL, s.link_oficial, e.link_oficial) AS link_oficial,
                 IF(e.id_evento IS NULL, s.gratuidade, e.gratuidade) AS gratuidade,
                 IF(e.id_evento IS NULL, s.valor_ingresso_minimo, e.valor_ingresso_minimo) AS valor_ingresso_minimo,
                 IF(e.id_evento IS NULL, s.valor_ingresso_maximo, e.valor_ingresso_maximo) AS valor_ingresso_maximo,
                 IF(e.id_evento IS NULL, s.descricao_evento, e.descricao_evento) AS descricao_evento,
                 s.descricao_artista,
                 s.nome_artista_solicitado,
                 s.genero_artista,
                 s.data_solicitacao, u.nome_user, u.sobrenome, u.email_user,
                 e.id_evento';
            $sqlBaseSolicitacoes =
                "SELECT $camposSolicitacao
                 FROM solicitacao s
                 INNER JOIN usuario u ON u.id_user = s.id_user
                 LEFT JOIN evento e ON e.id_solicitacao_origem = s.id_solicitacao
                 WHERE (
                        ? = 'todas'
                        OR (? = 'removido' AND s.status_solicitacao = 'aprovado' AND e.id_evento IS NULL)
                        OR (? = 'aprovado' AND s.status_solicitacao = 'aprovado' AND e.id_evento IS NOT NULL)
                        OR (? = 'pendente' AND s.status_solicitacao = 'pendente')
                        OR (? = 'recusado' AND s.status_solicitacao = 'recusado')
                   )
                   AND (? = 0 OR s.data_solicitacao >= DATE_SUB(NOW(), INTERVAL ? DAY))
                   AND (? = '' OR CONCAT_WS(' ',
                        s.nome_evento, s.endereco_evento, s.rua_evento,
                        s.cidade_evento, s.uf, s.categoria_evento,
                        s.descricao_evento, s.descricao_artista,
                        s.nome_artista_solicitado, s.genero_artista, u.nome_user,
                        u.sobrenome, u.email_user, e.nome_evento,
                        e.endereco_evento, e.rua_evento, e.cidade_evento,
                        e.uf, e.categoria_evento, e.descricao_evento
                   ) LIKE CONCAT('%', ?, '%'))";
            $sqlSolicitacoes = $sqlBaseSolicitacoes
                . ' ORDER BY s.data_solicitacao DESC, s.id_solicitacao DESC';

            if ($limiteSolicitacoes !== null) {
                $sqlSolicitacoes .= ' LIMIT ? OFFSET ?';
            }

            try {
                $stmt = $conn->prepare($sqlSolicitacoes);

                if ($limiteSolicitacoes === null) {
                    $stmt->bind_param(
                        'sssssiiss',
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $diasSolicitacoes,
                        $diasSolicitacoes,
                        $buscaSolicitacoes,
                        $buscaSolicitacoes
                    );
                } else {
                    $stmt->bind_param(
                        'sssssiissii',
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $statusSolicitacoes,
                        $diasSolicitacoes,
                        $diasSolicitacoes,
                        $buscaSolicitacoes,
                        $buscaSolicitacoes,
                        $limiteSolicitacoes,
                        $offsetSolicitacoes
                    );
                }

                executarStatementApi($stmt);
                $resultado = $stmt->get_result();
                $solicitacoes = [];

                while ($solicitacao = $resultado->fetch_assoc()) {
                    $solicitacoes[] = normalizarSolicitacao($solicitacao);
                }

                $stmt->close();

                $sqlContadores =
                    "SELECT COUNT(*) AS todas,
                            SUM(s.status_solicitacao = 'pendente') AS pendente,
                            SUM(s.status_solicitacao = 'aprovado' AND e.id_evento IS NOT NULL) AS aprovado,
                            SUM(s.status_solicitacao = 'recusado') AS recusado,
                            SUM(s.status_solicitacao = 'aprovado' AND e.id_evento IS NULL) AS removido
                     FROM solicitacao s
                     INNER JOIN usuario u ON u.id_user = s.id_user
                     LEFT JOIN evento e ON e.id_solicitacao_origem = s.id_solicitacao
                     WHERE (? = 0 OR s.data_solicitacao >= DATE_SUB(NOW(), INTERVAL ? DAY))
                       AND (? = '' OR CONCAT_WS(' ',
                            s.nome_evento, s.endereco_evento, s.rua_evento,
                            s.cidade_evento, s.uf, s.categoria_evento,
                            s.descricao_evento, s.descricao_artista,
                            s.nome_artista_solicitado, u.nome_user,
                            u.sobrenome, u.email_user, e.nome_evento,
                            e.endereco_evento, e.rua_evento, e.cidade_evento,
                            e.uf, e.categoria_evento, e.descricao_evento
                       ) LIKE CONCAT('%', ?, '%'))";
                $stmt = $conn->prepare($sqlContadores);
                $stmt->bind_param(
                    'iiss',
                    $diasSolicitacoes,
                    $diasSolicitacoes,
                    $buscaSolicitacoes,
                    $buscaSolicitacoes
                );
                executarStatementApi($stmt);
                $contadores = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                foreach (['todas', 'pendente', 'aprovado', 'recusado', 'removido'] as $campoContador) {
                    $contadores[$campoContador] = (int) ($contadores[$campoContador] ?? 0);
                }

                $totalStatus = $contadores[$statusSolicitacoes === 'todas'
                    ? 'todas'
                    : $statusSolicitacoes];
                responder([
                    'solicitacoes' => $solicitacoes,
                    'contadores' => $contadores,
                    'total' => $totalStatus,
                    'limite' => $limiteSolicitacoes,
                    'offset' => $offsetSolicitacoes,
                    'tem_mais' => $limiteSolicitacoes !== null
                        && $offsetSolicitacoes + count($solicitacoes) < $totalStatus
                ]);
            } catch (mysqli_sql_exception $erro) {
                responderErroInfraestrutura('Falha ao listar solicitações', $erro);
            }
        }

        $busca = null;

        if (array_key_exists('busca', $_GET)) {
            if (!is_string($_GET['busca'])) {
                responder(['erro' => 'O parâmetro busca deve ser um texto'], 400);
            }

            $busca = trim($_GET['busca']);
            $busca = $busca === '' ? null : $busca;

            if ($busca !== null && tamanhoTexto($busca) > 100) {
                responder(['erro' => 'busca deve ter no máximo 100 caracteres'], 400);
            }
        }

        $categoria = null;

        foreach (['categoria', 'id_categoria'] as $parametroCategoria) {
            if (!array_key_exists($parametroCategoria, $_GET)) {
                continue;
            }

            if (!is_string($_GET[$parametroCategoria])) {
                responder(['erro' => "$parametroCategoria deve ser um texto"], 400);
            }

            $categoria = normalizarEspacos($_GET[$parametroCategoria]);
            $categoria = $categoria === '' ? null : $categoria;
            break;
        }

        if ($categoria !== null && tamanhoTexto($categoria) > 100) {
            responder(['erro' => 'categoria deve ter no máximo 100 caracteres'], 400);
        }

        $classificacoes = [];

        foreach (['classificacao', 'classificacao_indicativa'] as $parametroClassificacao) {
            if (!array_key_exists($parametroClassificacao, $_GET)) {
                continue;
            }

            $valorClassificacao = $_GET[$parametroClassificacao];
            $recebidas = is_array($valorClassificacao)
                ? $valorClassificacao
                : preg_split('/\s*,\s*/u', (string) $valorClassificacao, -1, PREG_SPLIT_NO_EMPTY);

            foreach ($recebidas ?: [] as $classificacaoInformada) {
                if (!is_scalar($classificacaoInformada)) {
                    responder(['erro' => "$parametroClassificacao contém um valor inválido"], 400);
                }

                if (trim((string) $classificacaoInformada) === '') {
                    continue;
                }

                $classificacaoNormalizada = normalizarClassificacaoIndicativa($classificacaoInformada);

                if ($classificacaoNormalizada === null) {
                    responder(['erro' => "$parametroClassificacao contém uma classificação inválida"], 400);
                }

                $classificacoes[$classificacaoNormalizada] = $classificacaoNormalizada;
            }

            break;
        }

        $classificacoes = array_values($classificacoes);

        $ingresso = 'todos';

        foreach (['ingresso', 'preco'] as $parametroIngresso) {
            if (!array_key_exists($parametroIngresso, $_GET)) {
                continue;
            }

            $valorIngresso = $_GET[$parametroIngresso];

            if (!is_string($valorIngresso)
                || !in_array($valorIngresso, ['todos', 'gratuitos', 'pagos'], true)) {
                responder(['erro' => "$parametroIngresso deve ser todos, gratuitos ou pagos"], 400);
            }

            $ingresso = $valorIngresso;
            break;
        }

        $valorMaximo = null;

        foreach (['valor_maximo', 'preco_max'] as $parametroValor) {
            if (!array_key_exists($parametroValor, $_GET)) {
                continue;
            }

            $valorInformado = $_GET[$parametroValor];

            if (!is_string($valorInformado) || !is_numeric($valorInformado)) {
                responder(['erro' => "$parametroValor deve ser um número válido"], 400);
            }

            $valorNumerico = (float) $valorInformado;

            if ($valorNumerico < 0 || $valorNumerico > 99999999.99) {
                responder(['erro' => "$parametroValor deve estar entre 0 e 99999999.99"], 400);
            }

            $valorMaximo = $valorNumerico;
            break;
        }

        $uf = null;

        if (array_key_exists('uf', $_GET)) {
            if (!is_string($_GET['uf'])) {
                responder(['erro' => 'uf deve ser um texto'], 400);
            }

            $ufInformada = strtoupper(trim($_GET['uf']));

            if ($ufInformada !== '') {
                if (!preg_match('/^[A-Z]{2}$/', $ufInformada)) {
                    responder(['erro' => 'uf deve conter duas letras'], 400);
                }

                $uf = $ufInformada;
            }
        }

        $cidade = null;

        if (array_key_exists('cidade', $_GET)) {
            if (!is_string($_GET['cidade'])) {
                responder(['erro' => 'cidade deve ser um texto'], 400);
            }

            $cidadeInformada = trim($_GET['cidade']);

            if ($cidadeInformada !== '') {
                if (tamanhoTexto($cidadeInformada) > 100) {
                    responder(['erro' => 'cidade deve ter no máximo 100 caracteres'], 400);
                }

                $cidade = $cidadeInformada;
            }
        }

        $periodo = null;

        if (array_key_exists('periodo', $_GET)) {
            if (!is_string($_GET['periodo'])) {
                responder(['erro' => 'periodo deve ser um texto'], 400);
            }

            $periodoInformado = strtolower(trim($_GET['periodo']));

            if ($periodoInformado !== '') {
                $periodosValidos = [
                    'hoje',
                    'amanha',
                    'fim_de_semana',
                    'nesta_semana',
                    'proxima_semana',
                    'neste_mes',
                    'proximo_mes'
                ];

                if (!in_array($periodoInformado, $periodosValidos, true)) {
                    responder([
                        'erro' => 'periodo deve ser hoje, amanha, fim_de_semana, nesta_semana, proxima_semana, neste_mes ou proximo_mes'
                    ], 400);
                }

                $periodo = $periodoInformado;
            }
        }

        $limite = null;

        if (array_key_exists('limite', $_GET)) {
            $limiteInformado = $_GET['limite'];

            if (
                !is_string($limiteInformado)
                || !ctype_digit($limiteInformado)
                || (int) $limiteInformado < 1
                || (int) $limiteInformado > 50
            ) {
                responder(['erro' => 'O parâmetro limite deve ser um inteiro entre 1 e 50'], 400);
            }

            $limite = (int) $limiteInformado;
        }

        $possuiFiltros = $busca !== null
            || $categoria !== null
            || $classificacoes !== []
            || $ingresso !== 'todos'
            || $valorMaximo !== null
            || $uf !== null
            || $cidade !== null
            || $periodo !== null;

        $eventos = [];

        if ($possuiFiltros) {
            $condicoes = [];
            $tiposParametros = '';
            $valoresParametros = [];

            if (!$incluirCancelados) {
                $condicoes[] = "e.status_evento = 'ativo'";
                $condicoes[] = 'e.data_evento >= CURDATE()';
            }

            if ($busca !== null) {
                // O caractere = funciona como escape para que %, _ e o próprio =
                // sejam pesquisados literalmente, sem alterar o padrão do LIKE.
                $termoEscapado = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $busca);
                $padraoBusca = '%' . $termoEscapado . '%';
                $condicoes[] = "(e.nome_evento LIKE ? ESCAPE '='
                    OR e.cidade_evento LIKE ? ESCAPE '='
                    OR e.categoria_evento LIKE ? ESCAPE '='
                    OR a.nome_artista LIKE ? ESCAPE '=')";
                $tiposParametros .= 'ssss';
                $valoresParametros[] = $padraoBusca;
                $valoresParametros[] = $padraoBusca;
                $valoresParametros[] = $padraoBusca;
                $valoresParametros[] = $padraoBusca;
            }

            if ($categoria !== null) {
                $categoriaEscapada = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $categoria);
                $condicoes[] = "e.categoria_evento LIKE ? ESCAPE '='";
                $tiposParametros .= 's';
                $valoresParametros[] = '%' . $categoriaEscapada . '%';
            }

            if ($classificacoes !== []) {
                $marcadoresClassificacao = implode(', ', array_fill(0, count($classificacoes), '?'));
                $condicoes[] = "e.classificacao_indicativa IN ($marcadoresClassificacao)";
                $tiposParametros .= str_repeat('s', count($classificacoes));
                array_push($valoresParametros, ...$classificacoes);
            }

            if ($ingresso === 'gratuitos') {
                $condicoes[] = 'e.gratuidade = 1';
            } elseif ($ingresso === 'pagos') {
                $condicoes[] = 'e.gratuidade = 0';
            }

            if ($valorMaximo !== null) {
                // Eventos gratuitos permanecem visíveis; eventos pagos entram
                // quando o ingresso mínimo cabe no preço máximo informado.
                $condicoes[] = '(e.gratuidade = 1 OR e.valor_ingresso_minimo <= ?)';
                $tiposParametros .= 'd';
                $valoresParametros[] = $valorMaximo;
            }

            if ($uf !== null) {
                $condicoes[] = 'UPPER(e.uf) = ?';
                $tiposParametros .= 's';
                $valoresParametros[] = $uf;
            }

            if ($cidade !== null) {
                $condicoes[] = 'e.cidade_evento = ?';
                $tiposParametros .= 's';
                $valoresParametros[] = $cidade;
            }

            if ($periodo === 'hoje') {
                $condicoes[] = 'e.data_evento = CURDATE()';
            } elseif ($periodo === 'amanha') {
                $condicoes[] = 'e.data_evento = DATE_ADD(CURDATE(), INTERVAL 1 DAY)';
            } elseif ($periodo === 'fim_de_semana') {
                // Sábado e domingo do fim de semana corrente. No sábado/domingo
                // a janela inclui o próprio dia (WEEKDAY: seg=0 ... dom=6).
                $inicioFimDeSemana = 'CASE
                    WHEN WEEKDAY(CURDATE()) >= 5
                        THEN DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) - 5 DAY)
                    ELSE DATE_ADD(CURDATE(), INTERVAL 5 - WEEKDAY(CURDATE()) DAY)
                END';
                $condicoes[] = "e.data_evento BETWEEN $inicioFimDeSemana
                    AND DATE_ADD($inicioFimDeSemana, INTERVAL 1 DAY)";
            } elseif ($periodo === 'nesta_semana') {
                // De hoje até o próximo domingo.
                $condicoes[] = 'e.data_evento BETWEEN CURDATE()
                    AND DATE_ADD(CURDATE(), INTERVAL 6 - WEEKDAY(CURDATE()) DAY)';
            } elseif ($periodo === 'proxima_semana') {
                // Próxima segunda até o domingo seguinte.
                $condicoes[] = 'e.data_evento BETWEEN
                    DATE_ADD(CURDATE(), INTERVAL 7 - WEEKDAY(CURDATE()) DAY)
                    AND DATE_ADD(CURDATE(), INTERVAL 13 - WEEKDAY(CURDATE()) DAY)';
            } elseif ($periodo === 'neste_mes') {
                $condicoes[] = 'e.data_evento BETWEEN CURDATE() AND LAST_DAY(CURDATE())';
            } elseif ($periodo === 'proximo_mes') {
                $condicoes[] = 'e.data_evento BETWEEN
                    DATE_ADD(LAST_DAY(CURDATE()), INTERVAL 1 DAY)
                    AND LAST_DAY(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))';
            }

            $sqlFiltros = "SELECT DISTINCT $camposEventoComAlias
                           FROM evento e
                           LEFT JOIN artista_evento ae ON ae.id_evento = e.id_evento
                           LEFT JOIN artista a ON a.id_artista = ae.id_artista
                           WHERE " . implode(' AND ', $condicoes) . '
                           ORDER BY e.data_evento ASC';

            if ($limite !== null) {
                $sqlFiltros .= ' LIMIT ?';
                $tiposParametros .= 'i';
                $valoresParametros[] = $limite;
            }

            $stmt = $conn->prepare($sqlFiltros);

            if ($valoresParametros !== []) {
                $stmt->bind_param($tiposParametros, ...$valoresParametros);
            }

            executarStatementApi($stmt);
            $resultado = $stmt->get_result();

            while ($evento = $resultado->fetch_assoc()) {
                $eventos[] = normalizarEvento($evento);
            }

            $stmt->close();
        } else {
            $filtroStatus = $incluirCancelados
                ? ''
                : " WHERE status_evento = 'ativo' AND data_evento >= CURDATE()";
            $resultado = $conn->query(
                "SELECT $camposEvento FROM evento$filtroStatus ORDER BY data_evento ASC"
            );

            while ($evento = $resultado->fetch_assoc()) {
                $eventos[] = normalizarEvento($evento);
            }
        }

        $preferenciasRecomendacao = null;

        if (!$possuiFiltros && !$incluirCancelados && isset($_SESSION['id_user'])) {
            $historicoRecomendacao = carregarHistoricoRecomendacao(
                $conn,
                (int) $_SESSION['id_user']
            );
            $preferenciasRecomendacao = montarPesosRecomendacao($historicoRecomendacao);

            if ($preferenciasRecomendacao['personalizado']) {
                $generosPorEvento = carregarGenerosPorEvento(
                    $conn,
                    array_column($eventos, 'id_evento')
                );
                aplicarRecomendacaoEventos(
                    $eventos,
                    $preferenciasRecomendacao,
                    $generosPorEvento
                );
            }
        }

        $resposta = ['eventos' => $eventos];

        if ($preferenciasRecomendacao !== null) {
            $resposta['recomendacoes'] = $preferenciasRecomendacao;
        }

        responder($resposta);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $idUsuario = exigirUsuarioComum();
        $tipoConteudo = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (!str_starts_with(strtolower($tipoConteudo), 'multipart/form-data')) {
            responder(['erro' => 'Envie os dados como multipart/form-data'], 400);
        }

        try {
            validarTamanhoTotalUpload();
        } catch (UploadInvalidoException $erro) {
            responder(['erro' => $erro->getMessage()], 400);
        }

        $dados = $_POST;
        $nome = trim((string) ($dados['nome_evento'] ?? ''));
        $horario = isset($dados['horario_evento']) ? trim((string) $dados['horario_evento']) : '';
        $data = isset($dados['data_evento']) ? trim((string) $dados['data_evento']) : '';
        $cep = normalizarCepEvento($dados['cep_evento'] ?? '');
        $endereco = trim((string) ($dados['endereco_evento'] ?? ''));
        $numero = trim((string) ($dados['numero_endereco'] ?? ''));
        $rua = isset($dados['rua_evento']) ? trim((string) $dados['rua_evento']) : '';
        $cidade = isset($dados['cidade_evento']) ? trim((string) $dados['cidade_evento']) : '';
        $uf = isset($dados['uf']) ? strtoupper(trim((string) $dados['uf'])) : '';
        $categoria = normalizarCategoriasEvento($dados['categoria_evento'] ?? []);
        $classificacaoIndicativa = normalizarClassificacaoIndicativa(
            $dados['classificacao_indicativa'] ?? null
        );

        if ($classificacaoIndicativa === null) {
            responder(['erro' => 'classificacao_indicativa inválida'], 400);
        }
        $linkOficial = isset($dados['link_oficial']) ? trim((string) $dados['link_oficial']) : '';
        $altImagem = isset($dados['alt_imagem']) ? trim((string) $dados['alt_imagem']) : '';
        if (!array_key_exists('gratuidade', $dados)) {
            responder(['erro' => 'gratuidade é obrigatória'], 400);
        }
        $gratuidadeRecebida = filter_var(
            $dados['gratuidade'],
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        $descricao = isset($dados['descricao_evento']) ? trim((string) $dados['descricao_evento']) : '';
        $descricaoArtista = isset($dados['descricao_artista']) ? trim((string) $dados['descricao_artista']) : '';
        $nomeArtistaSolicitado = isset($dados['nome_artista_solicitado'])
            ? normalizarEspacos((string) $dados['nome_artista_solicitado'])
            : '';
        $generoArtista = isset($dados['genero_artista'])
            ? normalizarEspacos((string) $dados['genero_artista'])
            : '';

        if ($nome === '' || tamanhoTexto($nome) > 100) {
            responder(['erro' => 'Nome do evento é obrigatório e deve ter até 100 caracteres'], 400);
        }

        if ($endereco === '' || tamanhoTexto($endereco) > 255 || tamanhoTexto($numero) > 20) {
            responder(['erro' => 'Endereço é obrigatório (até 255 caracteres) e número deve ter até 20'], 400);
        }

        if (
            tamanhoTexto($rua) > 100
            || tamanhoTexto($cidade) > 100
            || tamanhoTexto($categoria) > 255
            || tamanhoTexto($linkOficial) > 255
            || tamanhoTexto($altImagem) > 255
            || tamanhoTexto($nomeArtistaSolicitado) > 150
            || tamanhoTexto($generoArtista) > 100
        ) {
            responder(['erro' => 'Um dos campos de endereço, categoria, link, imagem ou artista excede o limite'], 400);
        }

        if ($uf !== '' && !preg_match('/^[A-Z]{2}$/', $uf)) {
            responder(['erro' => 'uf deve conter duas letras'], 400);
        }

        if ($cidade === '' || $uf === '') {
            responder(['erro' => 'O CEP precisa retornar cidade e UF válidas'], 400);
        }

        if ($linkOficial === '' || filter_var($linkOficial, FILTER_VALIDATE_URL) === false) {
            responder(['erro' => 'link_oficial deve ser uma URL válida'], 400);
        }

        if ($descricao === '' || $descricaoArtista === ''
            || tamanhoTexto($descricao) > 1000 || tamanhoTexto($descricaoArtista) > 1000) {
            responder(['erro' => 'As descrições são obrigatórias e devem ter até 1000 caracteres'], 400);
        }

        if ($nomeArtistaSolicitado === '' || tamanhoTexto($nomeArtistaSolicitado) > 150) {
            responder(['erro' => 'Nome do artista ou atração é obrigatório e deve ter até 150 caracteres'], 400);
        }

        if ($gratuidadeRecebida === null) {
            responder(['erro' => 'gratuidade deve ser true ou false'], 400);
        }

        $gratuidade = $gratuidadeRecebida ? 1 : 0;
        [$valorMinimo, $valorMaximo] = validarFaixaIngresso(
            $gratuidade,
            $dados['valor_ingresso_minimo'] ?? null,
            $dados['valor_ingresso_maximo'] ?? null
        );

        if ($data === '') {
            responder(['erro' => 'data_evento é obrigatória'], 400);
        } else {
            $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

            if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                responder(['erro' => 'data_evento deve ser uma data válida no formato YYYY-MM-DD'], 400);
            }
        }

        if ($horario === '') {
            responder(['erro' => 'horario_evento é obrigatório'], 400);
        } else {
            $partesHorario = explode(':', $horario);
            $horarioValido = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)
                && (int) $partesHorario[0] <= 23
                && (int) $partesHorario[1] <= 59
                && (!isset($partesHorario[2]) || (int) $partesHorario[2] <= 59);

            if (!$horarioValido) {
                responder(['erro' => 'horario_evento deve ser um horário válido em HH:MM ou HH:MM:SS'], 400);
            }
        }

        $horario = $horario === '' ? null : $horario;
        $data = $data === '' ? null : $data;
        $numero = $numero === '' ? null : $numero;
        $rua = $rua === '' ? null : $rua;
        $cidade = $cidade === '' ? null : $cidade;
        $uf = $uf === '' ? null : $uf;
        $categoria = $categoria === '' ? null : $categoria;
        $linkOficial = $linkOficial === '' ? null : $linkOficial;
        $altImagem = $altImagem === '' ? null : $altImagem;
        $descricao = $descricao === '' ? null : $descricao;
        $descricaoArtista = $descricaoArtista === '' ? null : $descricaoArtista;
        $nomeArtistaSolicitado = $nomeArtistaSolicitado === '' ? null : $nomeArtistaSolicitado;
        $generoArtista = $generoArtista === '' ? null : $generoArtista;

        try {
            $arquivos = normalizarArquivosUpload($_FILES['foto'] ?? null);
        } catch (UploadInvalidoException $erro) {
            responder(['erro' => $erro->getMessage()], 400);
        }

        if (count($arquivos) !== 1) {
            responder(['erro' => 'Envie exatamente uma foto do evento'], 400);
        }

        $fotoSalva = null;

        try {
            $fotoSalva = salvarArquivoUpload($arquivos[0], ['foto'], 'eventos');
            $foto = $fotoSalva['caminho_arquivo'];

            $stmt = $conn->prepare(
                'INSERT INTO solicitacao
                 (id_user, nome_evento, foto, alt_imagem, horario_evento, data_evento,
                  cep_evento, endereco_evento, numero_endereco, rua_evento, cidade_evento,
                  uf, categoria_evento, classificacao_indicativa, link_oficial, gratuidade,
                  valor_ingresso_minimo, valor_ingresso_maximo, descricao_evento,
                  descricao_artista, nome_artista_solicitado, genero_artista)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'issssssssssssssiddssss',
                $idUsuario,
                $nome,
                $foto,
                $altImagem,
                $horario,
                $data,
                $cep,
                $endereco,
                $numero,
                $rua,
                $cidade,
                $uf,
                $categoria,
                $classificacaoIndicativa,
                $linkOficial,
                $gratuidade,
                $valorMinimo,
                $valorMaximo,
                $descricao,
                $descricaoArtista,
                $nomeArtistaSolicitado,
                $generoArtista
            );
            executarStatementApi($stmt);
            $idSolicitacao = $conn->insert_id;
            $stmt->close();
        } catch (UploadInvalidoException $erro) {
            responder(['erro' => $erro->getMessage()], 400);
        } catch (Throwable $erro) {
            if ($fotoSalva !== null) {
                removerArquivoUpload($fotoSalva['caminho_arquivo']);
            }
            responderErroInfraestrutura('Falha ao criar solicitação de evento', $erro);
        }

        responder([
            'mensagem' => 'Solicitação enviada, aguardando aprovação',
            'solicitacao' => normalizarSolicitacaoApi([
                'id_solicitacao' => $idSolicitacao,
                'status_solicitacao' => 'pendente',
                'foto' => $foto,
                'alt_imagem' => $altImagem
            ])
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
        $statusAcaoDireta = null;

        if ($acao === 'aprovar' || $acao === 'recusar') {
            $statusAcaoDireta = $acao === 'aprovar' ? 'aprovado' : 'recusado';
            $acao = 'moderar';
        }

        if ($acao === 'editar_solicitacao') {
            $camposEditaveisSolicitacao = [
                'nome_evento', 'horario_evento', 'data_evento',
                'cep_evento', 'endereco_evento', 'numero_endereco', 'rua_evento',
                'cidade_evento', 'uf', 'categoria_evento', 'classificacao_indicativa',
                'link_oficial', 'gratuidade',
                'valor_ingresso_minimo', 'valor_ingresso_maximo', 'descricao_evento',
                'descricao_artista', 'nome_artista_solicitado', 'genero_artista', 'alt_imagem'
            ];

            if (array_intersect($camposEditaveisSolicitacao, array_keys($dados)) === []) {
                responder(['erro' => 'Informe ao menos um campo editável da solicitação'], 400);
            }

            $stmt = $conn->prepare(
                'SELECT nome_evento, horario_evento, data_evento,
                        cep_evento, endereco_evento, numero_endereco, rua_evento,
                        cidade_evento, uf, categoria_evento, classificacao_indicativa,
                        link_oficial, gratuidade,
                        valor_ingresso_minimo, valor_ingresso_maximo, descricao_evento, descricao_artista,
                        nome_artista_solicitado, genero_artista, alt_imagem, status_solicitacao
                 FROM solicitacao
                 WHERE id_solicitacao = ?
                 LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $solicitacaoAtual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$solicitacaoAtual) {
                responder(['erro' => 'Solicitação não encontrada'], 404);
            }

            if ($solicitacaoAtual['status_solicitacao'] !== 'pendente') {
                responder(['erro' => 'Somente solicitações pendentes podem ser editadas'], 409);
            }

            $nome = array_key_exists('nome_evento', $dados)
                ? trim((string) $dados['nome_evento'])
                : $solicitacaoAtual['nome_evento'];
            $horario = atualizarTextoOpcional(
                $dados,
                'horario_evento',
                $solicitacaoAtual['horario_evento']
            );
            $data = atualizarTextoOpcional($dados, 'data_evento', $solicitacaoAtual['data_evento']);
            $cep = array_key_exists('cep_evento', $dados)
                ? normalizarCepEvento($dados['cep_evento'])
                : $solicitacaoAtual['cep_evento'];
            $endereco = atualizarTextoOpcional($dados, 'endereco_evento', $solicitacaoAtual['endereco_evento']);
            $numero = atualizarTextoOpcional($dados, 'numero_endereco', $solicitacaoAtual['numero_endereco']);
            $rua = atualizarTextoOpcional($dados, 'rua_evento', $solicitacaoAtual['rua_evento']);
            $cidade = atualizarTextoOpcional($dados, 'cidade_evento', $solicitacaoAtual['cidade_evento']);
            $uf = atualizarTextoOpcional($dados, 'uf', $solicitacaoAtual['uf']);
            $categoria = normalizarCategoriasEvento(
                $dados['categoria_evento'] ?? $solicitacaoAtual['categoria_evento']
            );
            $classificacaoIndicativa = normalizarClassificacaoIndicativa(
                $dados['classificacao_indicativa'] ?? $solicitacaoAtual['classificacao_indicativa']
            );

            if ($classificacaoIndicativa === null) {
                responder(['erro' => 'classificacao_indicativa inválida'], 400);
            }
            $linkOficial = atualizarTextoOpcional(
                $dados,
                'link_oficial',
                $solicitacaoAtual['link_oficial']
            );
            $descricao = atualizarTextoOpcional(
                $dados,
                'descricao_evento',
                $solicitacaoAtual['descricao_evento']
            );
            $descricaoArtista = atualizarTextoOpcional(
                $dados,
                'descricao_artista',
                $solicitacaoAtual['descricao_artista']
            );
            $nomeArtistaSolicitado = atualizarTextoOpcional(
                $dados,
                'nome_artista_solicitado',
                $solicitacaoAtual['nome_artista_solicitado']
            );
            $generoArtista = atualizarTextoOpcional(
                $dados,
                'genero_artista',
                $solicitacaoAtual['genero_artista']
            );
            $altImagem = atualizarTextoOpcional(
                $dados,
                'alt_imagem',
                $solicitacaoAtual['alt_imagem']
            );

            if ($nomeArtistaSolicitado !== null) {
                $nomeArtistaSolicitado = normalizarEspacos($nomeArtistaSolicitado);
                $nomeArtistaSolicitado = $nomeArtistaSolicitado === ''
                    ? null
                    : $nomeArtistaSolicitado;
            }

            if ($generoArtista !== null) {
                $generoArtista = normalizarEspacos($generoArtista);
                $generoArtista = $generoArtista === '' ? null : $generoArtista;
            }

            if ($nome === '' || tamanhoTexto($nome) > 100) {
                responder(['erro' => 'Nome do evento é obrigatório e deve ter até 100 caracteres'], 400);
            }

            if ($cep === null || $endereco === null || tamanhoTexto($endereco) > 255
                || ($numero !== null && tamanhoTexto($numero) > 20)) {
                responder(['erro' => 'CEP e endereço são obrigatórios; confira também o número'], 400);
            }

            if (
                ($rua !== null && tamanhoTexto($rua) > 100)
                || ($cidade !== null && tamanhoTexto($cidade) > 100)
                || tamanhoTexto($categoria) > 255
                || ($linkOficial !== null && tamanhoTexto($linkOficial) > 255)
                || ($altImagem !== null && tamanhoTexto($altImagem) > 255)
                || ($nomeArtistaSolicitado !== null && tamanhoTexto($nomeArtistaSolicitado) > 150)
                || ($generoArtista !== null && tamanhoTexto($generoArtista) > 100)
            ) {
                responder(['erro' => 'Um dos campos de endereço, categoria, link, imagem ou artista excede o limite'], 400);
            }

            if ($uf !== null) {
                $uf = strtoupper($uf);
                if (!preg_match('/^[A-Z]{2}$/', $uf)) {
                    responder(['erro' => 'uf deve conter duas letras'], 400);
                }
            }

            if ($cep === null || $endereco === null || $cidade === null || $uf === null) {
                responder(['erro' => 'CEP, endereço, cidade e UF são obrigatórios'], 400);
            }

            if ($linkOficial === null || filter_var($linkOficial, FILTER_VALIDATE_URL) === false) {
                responder(['erro' => 'link_oficial deve ser uma URL válida'], 400);
            }

            if (
                $descricao === null || $descricaoArtista === null
                || tamanhoTexto($descricao) > 1000
                || tamanhoTexto($descricaoArtista) > 1000
            ) {
                responder(['erro' => 'As descrições são obrigatórias e devem ter até 1000 caracteres'], 400);
            }

            if ($nomeArtistaSolicitado === null || tamanhoTexto($nomeArtistaSolicitado) > 150) {
                responder(['erro' => 'Nome do artista ou atração é obrigatório e deve ter até 150 caracteres'], 400);
            }

            if ($data === null) {
                responder(['erro' => 'data_evento é obrigatória'], 400);
            } else {
                $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

                if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                    responder(['erro' => 'data_evento deve ser uma data válida no formato YYYY-MM-DD'], 400);
                }
            }

            if ($horario === null) {
                responder(['erro' => 'horario_evento é obrigatório'], 400);
            } else {
                $partesHorario = explode(':', $horario);
                $horarioValido = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)
                    && (int) $partesHorario[0] <= 23
                    && (int) $partesHorario[1] <= 59
                    && (!isset($partesHorario[2]) || (int) $partesHorario[2] <= 59);

                if (!$horarioValido) {
                    responder(['erro' => 'horario_evento deve ser um horário válido em HH:MM ou HH:MM:SS'], 400);
                }
            }

            $gratuidade = (int) $solicitacaoAtual['gratuidade'];

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

            [$valorMinimo, $valorMaximo] = validarFaixaIngresso(
                $gratuidade,
                $dados['valor_ingresso_minimo'] ?? $solicitacaoAtual['valor_ingresso_minimo'],
                $dados['valor_ingresso_maximo'] ?? $solicitacaoAtual['valor_ingresso_maximo']
            );

            $stmt = $conn->prepare(
                "UPDATE solicitacao
                 SET nome_evento = ?, horario_evento = ?, data_evento = ?,
                     cep_evento = ?, endereco_evento = ?, numero_endereco = ?, rua_evento = ?,
                     cidade_evento = ?, uf = ?, categoria_evento = ?, classificacao_indicativa = ?,
                     link_oficial = ?,
                     gratuidade = ?, valor_ingresso_minimo = ?, valor_ingresso_maximo = ?, descricao_evento = ?,
                     descricao_artista = ?, nome_artista_solicitado = ?, genero_artista = ?, alt_imagem = ?
                 WHERE id_solicitacao = ? AND status_solicitacao = 'pendente'"
            );
            $stmt->bind_param(
                'ssssssssssssiddsssssi',
                $nome,
                $horario,
                $data,
                $cep,
                $endereco,
                $numero,
                $rua,
                $cidade,
                $uf,
                $categoria,
                $classificacaoIndicativa,
                $linkOficial,
                $gratuidade,
                $valorMinimo,
                $valorMaximo,
                $descricao,
                $descricaoArtista,
                $nomeArtistaSolicitado,
                $generoArtista,
                $altImagem,
                $id
            );
            executarStatementApi($stmt);
            $stmt->close();

            $stmt = $conn->prepare(
                'SELECT s.id_solicitacao, s.id_user, s.nome_evento, s.status_solicitacao,
                        s.foto, s.horario_evento, s.data_evento,
                        s.cep_evento, s.endereco_evento, s.numero_endereco,
                        s.rua_evento, s.cidade_evento, s.uf, s.categoria_evento,
                        s.classificacao_indicativa,
                        s.link_oficial, s.gratuidade, s.valor_ingresso_minimo,
                        s.valor_ingresso_maximo, s.descricao_evento,
                        s.descricao_artista, s.nome_artista_solicitado,
                        s.genero_artista,
                        s.alt_imagem,
                        s.data_solicitacao, u.nome_user, u.sobrenome, u.email_user,
                        NULL AS id_evento
                 FROM solicitacao s
                 INNER JOIN usuario u ON u.id_user = s.id_user
                 WHERE s.id_solicitacao = ?
                 LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $solicitacao = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$solicitacao) {
                responder(['erro' => 'Solicitação não encontrada após a edição'], 404);
            }

            if ($solicitacao['status_solicitacao'] !== 'pendente') {
                responder(['erro' => 'A solicitação foi analisada durante a edição'], 409);
            }

            responder([
                'mensagem' => 'Solicitação atualizada com sucesso',
                'solicitacao' => normalizarSolicitacao($solicitacao)
            ]);
        }

        if ($acao === 'moderar') {
            $novoStatus = $statusAcaoDireta
                ?? (string) ($dados['status_solicitacao'] ?? '');

            if (!in_array($novoStatus, ['aprovado', 'recusado'], true)) {
                responder(['erro' => 'status_solicitacao deve ser aprovado ou recusado'], 400);
            }

            $conn->begin_transaction();

            try {
                $stmt = $conn->prepare(
                    'SELECT s.nome_evento, s.foto, s.alt_imagem, s.horario_evento, s.data_evento,
                            s.cep_evento, s.endereco_evento, s.numero_endereco, s.rua_evento,
                            s.cidade_evento, s.uf, s.categoria_evento, s.classificacao_indicativa,
                            s.link_oficial, s.gratuidade,
                            s.valor_ingresso_minimo, s.valor_ingresso_maximo,
                            s.descricao_evento, s.nome_artista_solicitado, s.genero_artista,
                            s.status_solicitacao, u.nome_user, u.sobrenome, u.email_user
                     FROM solicitacao s
                     INNER JOIN usuario u ON u.id_user = s.id_user
                     WHERE s.id_solicitacao = ? LIMIT 1 FOR UPDATE'
                );
                $stmt->bind_param('i', $id);
                executarStatementApi($stmt);
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
                         (id_solicitacao_origem, nome_evento, cep_evento,
                          endereco_evento, numero_endereco, rua_evento, cidade_evento, uf,
                          descricao_evento, data_evento, horario_evento, gratuidade,
                          valor_ingresso_minimo, valor_ingresso_maximo, categoria_evento,
                          classificacao_indicativa, link_oficial, imagem_evento, alt_imagem)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'issssssssssiddsssss',
                        $id,
                        $solicitacao['nome_evento'],
                        $solicitacao['cep_evento'],
                        $solicitacao['endereco_evento'],
                        $solicitacao['numero_endereco'],
                        $solicitacao['rua_evento'],
                        $solicitacao['cidade_evento'],
                        $solicitacao['uf'],
                        $solicitacao['descricao_evento'],
                        $solicitacao['data_evento'],
                        $solicitacao['horario_evento'],
                        $gratuidade,
                        $solicitacao['valor_ingresso_minimo'],
                        $solicitacao['valor_ingresso_maximo'],
                        $solicitacao['categoria_evento'],
                        $solicitacao['classificacao_indicativa'],
                        $solicitacao['link_oficial'],
                        $solicitacao['foto'],
                        $solicitacao['alt_imagem']
                    );
                    executarStatementApi($stmt);
                    $idEvento = $conn->insert_id;
                    $stmt->close();

                    $nomeArtista = $solicitacao['nome_artista_solicitado'] === null
                        ? null
                        : normalizarEspacos($solicitacao['nome_artista_solicitado']);
                    $generoArtista = $solicitacao['genero_artista'] === null
                        ? null
                        : normalizarEspacos($solicitacao['genero_artista']);
                    $generoArtista = $generoArtista === '' ? null : $generoArtista;

                    if ($nomeArtista !== null && $nomeArtista !== '') {
                        $stmt = $conn->prepare(
                            'SELECT id_artista
                             FROM artista
                             WHERE LOWER(TRIM(nome_artista)) = LOWER(?)
                             LIMIT 1'
                        );
                        $stmt->bind_param('s', $nomeArtista);
                        executarStatementApi($stmt);
                        $artista = $stmt->get_result()->fetch_assoc();
                        $stmt->close();

                        if ($artista) {
                            $idArtista = (int) $artista['id_artista'];

                            if ($generoArtista !== null) {
                                $stmt = $conn->prepare(
                                    'UPDATE artista SET genero_artista = ? WHERE id_artista = ?'
                                );
                                $stmt->bind_param('si', $generoArtista, $idArtista);
                                executarStatementApi($stmt);
                                $stmt->close();
                            }
                        } else {
                            $stmt = $conn->prepare(
                                'INSERT INTO artista (nome_artista, genero_artista) VALUES (?, ?)'
                            );
                            $stmt->bind_param('ss', $nomeArtista, $generoArtista);

                            try {
                                executarStatementApi($stmt);
                                $idArtista = $conn->insert_id;
                                $stmt->close();
                            } catch (mysqli_sql_exception $erro) {
                                $stmt->close();

                                if ($erro->getCode() !== 1062) {
                                    throw $erro;
                                }

                                $stmt = $conn->prepare(
                                    'SELECT id_artista
                                     FROM artista
                                     WHERE LOWER(TRIM(nome_artista)) = LOWER(?)
                                     LIMIT 1'
                                );
                                $stmt->bind_param('s', $nomeArtista);
                                executarStatementApi($stmt);
                                $artista = $stmt->get_result()->fetch_assoc();
                                $stmt->close();

                                if (!$artista) {
                                    throw $erro;
                                }

                                $idArtista = (int) $artista['id_artista'];

                                if ($generoArtista !== null) {
                                    $stmt = $conn->prepare(
                                        'UPDATE artista SET genero_artista = ? WHERE id_artista = ?'
                                    );
                                    $stmt->bind_param('si', $generoArtista, $idArtista);
                                    executarStatementApi($stmt);
                                    $stmt->close();
                                }
                            }
                        }

                        $stmt = $conn->prepare(
                            'INSERT INTO artista_evento (id_artista, id_evento) VALUES (?, ?)'
                        );
                        $stmt->bind_param('ii', $idArtista, $idEvento);
                        executarStatementApi($stmt);
                        $stmt->close();
                    }
                }

                $caminhosUploads = [];

                if ($novoStatus === 'recusado') {
                    $caminhosUploads[] = $solicitacao['foto'];
                    $stmt = $conn->prepare(
                        'UPDATE solicitacao
                         SET status_solicitacao = ?, foto = NULL
                         WHERE id_solicitacao = ?'
                    );
                } else {
                    $stmt = $conn->prepare(
                        'UPDATE solicitacao
                         SET status_solicitacao = ?
                         WHERE id_solicitacao = ?'
                    );
                }
                $stmt->bind_param('si', $novoStatus, $id);
                executarStatementApi($stmt);
                $stmt->close();
                $conn->commit();

                removerArquivosUploadSemReferencia($conn, $caminhosUploads);

                try {
                    $nomeSolicitante = trim(implode(' ', array_filter([
                        $solicitacao['nome_user'] ?? '',
                        $solicitacao['sobrenome'] ?? '',
                    ])));
                    enviarEmailModeracaoEventoShowMe(
                        (string) ($solicitacao['email_user'] ?? ''),
                        $nomeSolicitante,
                        (string) $solicitacao['nome_evento'],
                        (string) $solicitacao['data_evento'],
                        $novoStatus,
                        $idEvento === null ? null : (int) $idEvento
                    );
                } catch (Throwable $erroEmail) {
                    error_log(sprintf(
                        'Falha ao enviar e-mail da moderação da solicitação %d: %s',
                        $id,
                        $erroEmail->getMessage()
                    ));
                }

                responder([
                    'mensagem' => 'Solicitação atualizada com sucesso',
                    'solicitacao' => normalizarSolicitacaoApi([
                        'id_solicitacao' => $id,
                        'status_solicitacao' => $novoStatus,
                        'id_evento' => $idEvento
                    ])
                ]);
            } catch (Throwable $erro) {
                $conn->rollback();
                responderErroInfraestrutura('Falha ao moderar solicitação', $erro);
            }
        }

        if ($acao !== 'editar') {
            responder([
                'erro' => 'acao deve ser aprovar, recusar, moderar, editar_solicitacao ou editar'
            ], 400);
        }

        $camposEditaveis = [
            'nome_evento', 'cep_evento', 'endereco_evento',
            'numero_endereco', 'rua_evento', 'cidade_evento', 'uf', 'descricao_evento',
            'data_evento', 'horario_evento', 'gratuidade', 'valor_ingresso_minimo',
            'valor_ingresso_maximo', 'categoria_evento', 'classificacao_indicativa',
            'link_oficial', 'imagem_evento', 'alt_imagem', 'status_evento',
            'genero_artista'
        ];

        if (array_intersect($camposEditaveis, array_keys($dados)) === []) {
            responder(['erro' => 'Informe ao menos um campo editável do evento'], 400);
        }

        $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $eventoAtual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$eventoAtual) {
            responder(['erro' => 'Evento não encontrado'], 404);
        }

        $stmt = $conn->prepare(
            'SELECT u.nome_user, u.sobrenome, u.email_user
             FROM evento e
             INNER JOIN solicitacao s ON s.id_solicitacao = e.id_solicitacao_origem
             INNER JOIN usuario u ON u.id_user = s.id_user
             WHERE e.id_evento = ?
             LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $solicitanteEvento = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $nome = array_key_exists('nome_evento', $dados)
            ? trim((string) $dados['nome_evento'])
            : $eventoAtual['nome_evento'];

        if ($nome === '') {
            responder(['erro' => 'Nome do evento é obrigatório'], 400);
        }

        $cep = array_key_exists('cep_evento', $dados)
            ? normalizarCepEvento($dados['cep_evento'])
            : $eventoAtual['cep_evento'];
        $endereco = atualizarTextoOpcional($dados, 'endereco_evento', $eventoAtual['endereco_evento']);
        $numero = atualizarTextoOpcional($dados, 'numero_endereco', $eventoAtual['numero_endereco']);
        $rua = atualizarTextoOpcional($dados, 'rua_evento', $eventoAtual['rua_evento']);
        $cidade = atualizarTextoOpcional($dados, 'cidade_evento', $eventoAtual['cidade_evento']);
        $uf = atualizarTextoOpcional($dados, 'uf', $eventoAtual['uf']);
        $descricao = atualizarTextoOpcional($dados, 'descricao_evento', $eventoAtual['descricao_evento']);
        $data = atualizarTextoOpcional($dados, 'data_evento', $eventoAtual['data_evento']);
        $horario = atualizarTextoOpcional($dados, 'horario_evento', $eventoAtual['horario_evento']);
        $categoria = normalizarCategoriasEvento(
            $dados['categoria_evento'] ?? $eventoAtual['categoria_evento']
        );
        $classificacaoIndicativa = normalizarClassificacaoIndicativa(
            $dados['classificacao_indicativa'] ?? $eventoAtual['classificacao_indicativa']
        );

        if ($classificacaoIndicativa === null) {
            responder(['erro' => 'classificacao_indicativa inválida'], 400);
        }
        $linkOficial = atualizarTextoOpcional($dados, 'link_oficial', $eventoAtual['link_oficial']);
        $imagem = atualizarTextoOpcional($dados, 'imagem_evento', $eventoAtual['imagem_evento']);
        $altImagem = atualizarTextoOpcional($dados, 'alt_imagem', $eventoAtual['alt_imagem']);
        $generoArtista = array_key_exists('genero_artista', $dados)
            ? trim((string) $dados['genero_artista'])
            : null;

        if ($generoArtista !== null) {
            $generoArtista = normalizarEspacos($generoArtista);
            $generoArtista = $generoArtista === '' ? null : $generoArtista;
        }

        if ($generoArtista !== null && tamanhoTexto($generoArtista) > 100) {
            responder(['erro' => 'genero_artista deve ter até 100 caracteres'], 400);
        }

        $erroLimite = validarLimitesTextoApi([
            'nome_evento' => $nome,
            'cep_evento' => $cep,
            'endereco_evento' => $endereco,
            'numero_endereco' => $numero,
            'rua_evento' => $rua,
            'cidade_evento' => $cidade,
            'uf' => $uf,
            'descricao_evento' => $descricao,
            'categoria_evento' => $categoria,
            'classificacao_indicativa' => $classificacaoIndicativa,
            'link_oficial' => $linkOficial,
            'imagem_evento' => $imagem,
            'alt_imagem' => $altImagem,
        ], [
            'nome_evento' => 100,
            'cep_evento' => 8,
            'endereco_evento' => 255,
            'numero_endereco' => 20,
            'rua_evento' => 100,
            'cidade_evento' => 100,
            'uf' => 2,
            'descricao_evento' => 1000,
            'categoria_evento' => 255,
            'classificacao_indicativa' => 20,
            'link_oficial' => 255,
            'imagem_evento' => 255,
            'alt_imagem' => 255,
        ]);

        if ($erroLimite !== null) {
            responder(['erro' => $erroLimite], 400);
        }

        if ($cep === null || $endereco === null || $cidade === null || $uf === null) {
            responder(['erro' => 'CEP, endereço, cidade e UF são obrigatórios'], 400);
        }

        if ($descricao === null) {
            responder(['erro' => 'descricao_evento é obrigatória'], 400);
        }

        if ($linkOficial === null || filter_var($linkOficial, FILTER_VALIDATE_URL) === false) {
            responder(['erro' => 'link_oficial deve ser uma URL válida'], 400);
        }

        if ($uf !== null) {
            $uf = strtoupper($uf);
            if (!preg_match('/^[A-Z]{2}$/', $uf)) {
                responder(['erro' => 'uf deve conter duas letras'], 400);
            }
        }

        if ($data === null) {
            responder(['erro' => 'data_evento é obrigatória'], 400);
        } else {
            $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);
            if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                responder(['erro' => 'data_evento deve usar uma data válida no formato YYYY-MM-DD'], 400);
            }
        }

        if ($horario === null) {
            responder(['erro' => 'horario_evento é obrigatório'], 400);
        } else {
            $partesHorario = explode(':', $horario);
            $horarioValido = preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horario)
                && (int) $partesHorario[0] <= 23
                && (int) $partesHorario[1] <= 59
                && (!isset($partesHorario[2]) || (int) $partesHorario[2] <= 59);

            if (!$horarioValido) {
                responder(['erro' => 'horario_evento deve ser um horário válido em HH:MM ou HH:MM:SS'], 400);
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

        [$valorMinimo, $valorMaximo] = validarFaixaIngresso(
            $gratuidade,
            $dados['valor_ingresso_minimo'] ?? $eventoAtual['valor_ingresso_minimo'],
            $dados['valor_ingresso_maximo'] ?? $eventoAtual['valor_ingresso_maximo']
        );

        $statusEvento = array_key_exists('status_evento', $dados)
            ? (string) $dados['status_evento']
            : $eventoAtual['status_evento'];

        if (!in_array($statusEvento, ['ativo', 'cancelado'], true)) {
            responder(['erro' => 'status_evento deve ser ativo ou cancelado'], 400);
        }

        $stmt = $conn->prepare(
            'UPDATE evento
             SET nome_evento = ?, cep_evento = ?, endereco_evento = ?,
                 numero_endereco = ?, rua_evento = ?, cidade_evento = ?, uf = ?,
                 descricao_evento = ?, data_evento = ?, horario_evento = ?, gratuidade = ?,
                 valor_ingresso_minimo = ?, valor_ingresso_maximo = ?,
                 categoria_evento = ?, link_oficial = ?, imagem_evento = ?, alt_imagem = ?,
                 classificacao_indicativa = ?, status_evento = ?
             WHERE id_evento = ?'
        );
        $stmt->bind_param(
            'ssssssssssiddssssssi',
            $nome,
            $cep,
            $endereco,
            $numero,
            $rua,
            $cidade,
            $uf,
            $descricao,
            $data,
            $horario,
            $gratuidade,
            $valorMinimo,
            $valorMaximo,
            $categoria,
            $linkOficial,
            $imagem,
            $altImagem,
            $classificacaoIndicativa,
            $statusEvento,
            $id
        );
        executarStatementApi($stmt);
        $stmt->close();

        if ($generoArtista !== null) {
            $stmt = $conn->prepare(
                'UPDATE artista a
                 INNER JOIN artista_evento ae ON ae.id_artista = a.id_artista
                 SET a.genero_artista = ?
                 WHERE ae.id_evento = ?'
            );
            $stmt->bind_param('si', $generoArtista, $id);
            executarStatementApi($stmt);
            $stmt->close();

            $stmt = $conn->prepare(
                'UPDATE solicitacao s
                 INNER JOIN evento e ON e.id_solicitacao_origem = s.id_solicitacao
                 SET s.genero_artista = ?
                 WHERE e.id_evento = ?'
            );
            $stmt->bind_param('si', $generoArtista, $id);
            executarStatementApi($stmt);
            $stmt->close();
        }

        $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $evento = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($solicitanteEvento) {
            try {
                enviarEmailAlteracaoEventoShowMe(
                    (string) $solicitanteEvento['email_user'],
                    trim((string) $solicitanteEvento['nome_user'] . ' ' . (string) $solicitanteEvento['sobrenome']),
                    (string) $evento['nome_evento'],
                    'editado',
                    $id
                );
            } catch (Throwable $erroEmail) {
                error_log(sprintf(
                    'Falha ao notificar a edição do evento %d: %s',
                    $id,
                    $erroEmail->getMessage()
                ));
            }
        }

        responder([
            'mensagem' => 'Evento atualizado com sucesso',
            'evento' => normalizarEvento($evento)
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID do evento na URL'], 400);
        }

        exigirAdmin();

        $caminhosUploads = [];
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                'SELECT e.imagem_evento, e.nome_evento,
                        u.nome_user, u.sobrenome, u.email_user
                 FROM evento e
                 LEFT JOIN solicitacao s ON s.id_solicitacao = e.id_solicitacao_origem
                 LEFT JOIN usuario u ON u.id_user = s.id_user
                 WHERE e.id_evento = ?
                 LIMIT 1
                 FOR UPDATE'
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $evento = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$evento) {
                $conn->rollback();
                responder(['erro' => 'Evento não encontrado'], 404);
            }

            $caminhosUploads[] = $evento['imagem_evento'];

            $stmt = $conn->prepare(
                'SELECT caminho_arquivo
                 FROM comunidade_midia
                 WHERE id_evento = ?'
            );
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $resultado = $stmt->get_result();

            while ($midia = $resultado->fetch_assoc()) {
                $caminhosUploads[] = $midia['caminho_arquivo'];
            }
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM evento WHERE id_evento = ?');
            $stmt->bind_param('i', $id);
            executarStatementApi($stmt);
            $removido = $stmt->affected_rows;
            $stmt->close();

            if ($removido === 0) {
                $conn->rollback();
                responder(['erro' => 'Evento não encontrado'], 404);
            }

            $conn->commit();
        } catch (Throwable $erro) {
            $conn->rollback();
            responderErroInfraestrutura('Falha ao excluir evento', $erro);
        }

        removerArquivosUploadSemReferencia($conn, $caminhosUploads);

        if (!empty($evento['email_user'])) {
            try {
                enviarEmailAlteracaoEventoShowMe(
                    (string) $evento['email_user'],
                    trim((string) $evento['nome_user'] . ' ' . (string) $evento['sobrenome']),
                    (string) $evento['nome_evento'],
                    'removido',
                    null
                );
            } catch (Throwable $erroEmail) {
                error_log(sprintf(
                    'Falha ao notificar a remoção do evento %d: %s',
                    $id,
                    $erroEmail->getMessage()
                ));
            }
        }

        responder(['mensagem' => 'Evento removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
