<?php

require_once __DIR__ . '/middleware/apiCommon.php';

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
    $permitidas = [
        'Música', 'Cinema', 'Show Nacional', 'Show Internacional',
        'Workshop', 'Oficina', 'Gastronômico'
    ];
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
                 horario_evento, categoria_evento, link_oficial, imagem_evento, status_evento';

$camposEventoComAlias = 'e.id_evento, e.id_solicitacao_origem, e.num_evento, e.nome_evento,
                         e.cep_evento, e.endereco_evento, e.numero_endereco,
                         e.rua_evento, e.cidade_evento, e.uf, e.descricao_evento,
                         e.data_evento, e.gratuidade, e.valor_ingresso_minimo,
                         e.valor_ingresso_maximo, e.horario_evento, e.categoria_evento,
                         e.link_oficial, e.imagem_evento, e.status_evento';

switch ($metodo) {
    case 'GET':
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

            if (!in_array($statusSolicitacoes, ['pendente', 'aprovado', 'recusado', 'todas'], true)) {
                responder([
                    'erro' => 'solicitacoes deve ser pendente, aprovado, recusado ou todas'
                ], 400);
            }

            if (
                array_key_exists('busca', $_GET)
                || array_key_exists('limite', $_GET)
                || array_key_exists('incluir_cancelados', $_GET)
            ) {
                responder([
                    'erro' => 'Não combine solicitacoes com busca, limite ou incluir_cancelados'
                ], 400);
            }

            exigirAdmin();

            $camposSolicitacao =
                's.id_solicitacao, s.id_user, s.nome_evento, s.status_solicitacao,
                 s.foto, s.horario_evento, s.data_evento,
                 s.cep_evento, s.endereco_evento, s.numero_endereco,
                 s.rua_evento, s.cidade_evento, s.uf, s.categoria_evento,
                 s.link_oficial, s.gratuidade, s.valor_ingresso_minimo,
                 s.valor_ingresso_maximo, s.descricao_evento, s.descricao_artista,
                 s.nome_artista_solicitado,
                 s.data_solicitacao, u.nome_user, u.sobrenome, u.email_user,
                 e.id_evento';
            $sqlSolicitacoes =
                "SELECT $camposSolicitacao
                 FROM solicitacao s
                 INNER JOIN usuario u ON u.id_user = s.id_user
                 LEFT JOIN evento e ON e.id_solicitacao_origem = s.id_solicitacao";

            if ($statusSolicitacoes !== 'todas') {
                $sqlSolicitacoes .= ' WHERE s.status_solicitacao = ?';
            }

            $sqlSolicitacoes .= ' ORDER BY s.data_solicitacao DESC, s.id_solicitacao DESC';

            try {
                $stmt = $conn->prepare($sqlSolicitacoes);

                if ($statusSolicitacoes !== 'todas') {
                    $stmt->bind_param('s', $statusSolicitacoes);
                }

                executarStatementApi($stmt);
                $resultado = $stmt->get_result();
                $solicitacoes = [];

                while ($solicitacao = $resultado->fetch_assoc()) {
                    $solicitacoes[] = normalizarSolicitacao($solicitacao);
                }

                $stmt->close();
                responder(['solicitacoes' => $solicitacoes]);
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
        }

        $eventos = [];

        if ($busca !== null) {
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

            // O caractere = funciona como escape para que %, _ e o próprio =
            // sejam pesquisados literalmente, sem alterar o padrão do LIKE.
            $termoEscapado = str_replace(['=', '%', '_'], ['==', '=%', '=_'], $busca);
            $padraoBusca = '%' . $termoEscapado . '%';

            $filtroStatusBusca = $incluirCancelados
                ? ''
                : "e.status_evento = 'ativo' AND e.data_evento >= CURDATE() AND ";
            $sqlBusca = "SELECT DISTINCT $camposEventoComAlias
                         FROM evento e
                         LEFT JOIN artista_evento ae ON ae.id_evento = e.id_evento
                         LEFT JOIN artista a ON a.id_artista = ae.id_artista
                         WHERE $filtroStatusBusca(e.nome_evento LIKE ? ESCAPE '='
                            OR e.cidade_evento LIKE ? ESCAPE '='
                            OR e.categoria_evento LIKE ? ESCAPE '='
                            OR a.nome_artista LIKE ? ESCAPE '=')
                         ORDER BY e.data_evento ASC";

            if ($limite !== null) {
                $sqlBusca .= ' LIMIT ?';
            }

            $stmt = $conn->prepare($sqlBusca);

            if ($limite !== null) {
                $stmt->bind_param(
                    'ssssi',
                    $padraoBusca,
                    $padraoBusca,
                    $padraoBusca,
                    $padraoBusca,
                    $limite
                );
            } else {
                $stmt->bind_param(
                    'ssss',
                    $padraoBusca,
                    $padraoBusca,
                    $padraoBusca,
                    $padraoBusca
                );
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

        responder(['eventos' => $eventos]);

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
        $linkOficial = isset($dados['link_oficial']) ? trim((string) $dados['link_oficial']) : '';
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
            || tamanhoTexto($nomeArtistaSolicitado) > 150
        ) {
            responder(['erro' => 'Um dos campos de endereço, categoria, link ou artista excede o limite'], 400);
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
        $descricao = $descricao === '' ? null : $descricao;
        $descricaoArtista = $descricaoArtista === '' ? null : $descricaoArtista;
        $nomeArtistaSolicitado = $nomeArtistaSolicitado === '' ? null : $nomeArtistaSolicitado;

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
                 (id_user, nome_evento, foto, horario_evento, data_evento,
                  cep_evento, endereco_evento, numero_endereco, rua_evento, cidade_evento,
                  uf, categoria_evento, link_oficial, gratuidade, valor_ingresso_minimo,
                  valor_ingresso_maximo, descricao_evento, descricao_artista, nome_artista_solicitado)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'issssssssssssiddsss',
                $idUsuario,
                $nome,
                $foto,
                $horario,
                $data,
                $cep,
                $endereco,
                $numero,
                $rua,
                $cidade,
                $uf,
                $categoria,
                $linkOficial,
                $gratuidade,
                $valorMinimo,
                $valorMaximo,
                $descricao,
                $descricaoArtista,
                $nomeArtistaSolicitado
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
                'foto' => $foto
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
                'cidade_evento', 'uf', 'categoria_evento', 'link_oficial', 'gratuidade',
                'valor_ingresso_minimo', 'valor_ingresso_maximo', 'descricao_evento',
                'descricao_artista', 'nome_artista_solicitado'
            ];

            if (array_intersect($camposEditaveisSolicitacao, array_keys($dados)) === []) {
                responder(['erro' => 'Informe ao menos um campo editável da solicitação'], 400);
            }

            $stmt = $conn->prepare(
                'SELECT nome_evento, horario_evento, data_evento,
                        cep_evento, endereco_evento, numero_endereco, rua_evento,
                        cidade_evento, uf, categoria_evento, link_oficial, gratuidade,
                        valor_ingresso_minimo, valor_ingresso_maximo, descricao_evento, descricao_artista,
                        nome_artista_solicitado, status_solicitacao
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

            if ($nomeArtistaSolicitado !== null) {
                $nomeArtistaSolicitado = normalizarEspacos($nomeArtistaSolicitado);
                $nomeArtistaSolicitado = $nomeArtistaSolicitado === ''
                    ? null
                    : $nomeArtistaSolicitado;
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
                || ($nomeArtistaSolicitado !== null && tamanhoTexto($nomeArtistaSolicitado) > 150)
            ) {
                responder(['erro' => 'Um dos campos de endereço, categoria, link ou artista excede o limite'], 400);
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
                     cidade_evento = ?, uf = ?, categoria_evento = ?, link_oficial = ?,
                     gratuidade = ?, valor_ingresso_minimo = ?, valor_ingresso_maximo = ?, descricao_evento = ?,
                     descricao_artista = ?, nome_artista_solicitado = ?
                 WHERE id_solicitacao = ? AND status_solicitacao = 'pendente'"
            );
            $stmt->bind_param(
                'sssssssssssiddsssi',
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
                $linkOficial,
                $gratuidade,
                $valorMinimo,
                $valorMaximo,
                $descricao,
                $descricaoArtista,
                $nomeArtistaSolicitado,
                $id
            );
            executarStatementApi($stmt);
            $stmt->close();

            $stmt = $conn->prepare(
                'SELECT s.id_solicitacao, s.id_user, s.nome_evento, s.status_solicitacao,
                        s.foto, s.horario_evento, s.data_evento,
                        s.cep_evento, s.endereco_evento, s.numero_endereco,
                        s.rua_evento, s.cidade_evento, s.uf, s.categoria_evento,
                        s.link_oficial, s.gratuidade, s.valor_ingresso_minimo,
                        s.valor_ingresso_maximo, s.descricao_evento,
                        s.descricao_artista, s.nome_artista_solicitado,
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
                    'SELECT s.nome_evento, s.foto, s.horario_evento, s.data_evento,
                            s.cep_evento, s.endereco_evento, s.numero_endereco, s.rua_evento,
                            s.cidade_evento, s.uf, s.categoria_evento, s.link_oficial, s.gratuidade,
                            s.valor_ingresso_minimo, s.valor_ingresso_maximo,
                            s.descricao_evento, s.nome_artista_solicitado,
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
                          link_oficial, imagem_evento)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->bind_param(
                        'issssssssssiddsss',
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
                        $solicitacao['link_oficial'],
                        $solicitacao['foto']
                    );
                    executarStatementApi($stmt);
                    $idEvento = $conn->insert_id;
                    $stmt->close();

                    $nomeArtista = $solicitacao['nome_artista_solicitado'] === null
                        ? null
                        : normalizarEspacos($solicitacao['nome_artista_solicitado']);

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
                        } else {
                            $stmt = $conn->prepare('INSERT INTO artista (nome_artista) VALUES (?)');
                            $stmt->bind_param('s', $nomeArtista);

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
            'valor_ingresso_maximo', 'categoria_evento', 'link_oficial',
            'imagem_evento', 'status_evento'
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
        $linkOficial = atualizarTextoOpcional($dados, 'link_oficial', $eventoAtual['link_oficial']);
        $imagem = atualizarTextoOpcional($dados, 'imagem_evento', $eventoAtual['imagem_evento']);

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
            'link_oficial' => $linkOficial,
            'imagem_evento' => $imagem,
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
            'link_oficial' => 255,
            'imagem_evento' => 255,
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
                 categoria_evento = ?, link_oficial = ?, imagem_evento = ?,
                 status_evento = ?
             WHERE id_evento = ?'
        );
        $stmt->bind_param(
            'ssssssssssiddssssi',
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
            $statusEvento,
            $id
        );
        executarStatementApi($stmt);
        $stmt->close();

        $stmt = $conn->prepare("SELECT $camposEvento FROM evento WHERE id_evento = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
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

        $caminhosUploads = [];
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                'SELECT imagem_evento
                 FROM evento
                 WHERE id_evento = ?
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

        responder(['mensagem' => 'Evento removido com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
