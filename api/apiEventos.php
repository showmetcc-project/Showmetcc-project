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

function normalizarArtistaEvento(array $artista): array
{
    $artista['id_artista'] = (int) $artista['id_artista'];
    return $artista;
}

function normalizarSolicitacao(array $solicitacao): array
{
    $solicitacao['id_solicitacao'] = (int) $solicitacao['id_solicitacao'];
    $solicitacao['id_user'] = (int) $solicitacao['id_user'];
    $solicitacao['gratuidade'] = (bool) $solicitacao['gratuidade'];
    $solicitacao['id_evento'] = $solicitacao['id_evento'] === null
        ? null
        : (int) $solicitacao['id_evento'];
    return $solicitacao;
}

function tamanhoTexto(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
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

require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/verifica_login.php';
require_once __DIR__ . '/middleware/verifica_admin.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

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

$camposEventoComAlias = 'e.id_evento, e.num_evento, e.nome_evento, e.local_evento,
                         e.rua_evento, e.cidade_evento, e.uf, e.descricao_evento,
                         e.data_evento, e.gratuidade, e.categoria_evento,
                         e.link_oficial, e.imagem_evento, e.status_evento';

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

            $stmt = $conn->prepare(
                'SELECT a.id_artista, a.nome_artista, a.genero_artista, a.imagem_artista
                 FROM artista a
                 INNER JOIN artista_evento ae ON ae.id_artista = a.id_artista
                 WHERE ae.id_evento = ?
                 ORDER BY a.nome_artista ASC'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
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

            if (array_key_exists('busca', $_GET) || array_key_exists('limite', $_GET)) {
                responder(['erro' => 'Não combine solicitacoes com busca ou limite'], 400);
            }

            exigirAdmin();

            $camposSolicitacao =
                's.id_solicitacao, s.id_user, s.nome_evento, s.status_solicitacao,
                 s.foto, s.horario_evento, s.data_evento, s.local_evento,
                 s.gratuidade, s.descricao_evento, s.descricao_artista,
                 s.data_solicitacao, u.nome_user, u.sobrenome, u.email_user,
                 e.id_evento';
            $sqlSolicitacoes =
                "SELECT $camposSolicitacao
                 FROM solicitacao s
                 INNER JOIN usuario u ON u.id_user = s.id_user
                 LEFT JOIN evento e ON e.num_evento = s.id_solicitacao";

            if ($statusSolicitacoes !== 'todas') {
                $sqlSolicitacoes .= ' WHERE s.status_solicitacao = ?';
            }

            $sqlSolicitacoes .= ' ORDER BY s.data_solicitacao DESC, s.id_solicitacao DESC';

            try {
                $stmt = $conn->prepare($sqlSolicitacoes);

                if ($statusSolicitacoes !== 'todas') {
                    $stmt->bind_param('s', $statusSolicitacoes);
                }

                $stmt->execute();
                $resultado = $stmt->get_result();
                $solicitacoes = [];

                while ($solicitacao = $resultado->fetch_assoc()) {
                    $solicitacoes[] = normalizarSolicitacao($solicitacao);
                }

                $stmt->close();
                responder(['solicitacoes' => $solicitacoes]);
            } catch (mysqli_sql_exception $erro) {
                error_log('Falha ao listar solicitações: ' . $erro->getMessage());
                responder(['erro' => 'Não foi possível carregar as solicitações'], 500);
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

            $sqlBusca = "SELECT DISTINCT $camposEventoComAlias
                         FROM evento e
                         LEFT JOIN artista_evento ae ON ae.id_evento = e.id_evento
                         LEFT JOIN artista a ON a.id_artista = ae.id_artista
                         WHERE e.nome_evento LIKE ? ESCAPE '='
                            OR e.cidade_evento LIKE ? ESCAPE '='
                            OR e.categoria_evento LIKE ? ESCAPE '='
                            OR a.nome_artista LIKE ? ESCAPE '='
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

            $stmt->execute();
            $resultado = $stmt->get_result();

            while ($evento = $resultado->fetch_assoc()) {
                $eventos[] = normalizarEvento($evento);
            }

            $stmt->close();
        } else {
            $resultado = $conn->query("SELECT $camposEvento FROM evento ORDER BY data_evento ASC");

            while ($evento = $resultado->fetch_assoc()) {
                $eventos[] = normalizarEvento($evento);
            }
        }

        responder(['eventos' => $eventos]);

    case 'POST':
        if ($id !== null) {
            responder(['erro' => 'A criação não recebe ID na URL'], 400);
        }

        $idUsuario = exigirLogin();
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
        $local = isset($dados['local_evento']) ? trim((string) $dados['local_evento']) : '';
        $gratuidadeRecebida = filter_var(
            $dados['gratuidade'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
            FILTER_NULL_ON_FAILURE
        );
        $descricao = isset($dados['descricao_evento']) ? trim((string) $dados['descricao_evento']) : '';
        $descricaoArtista = isset($dados['descricao_artista']) ? trim((string) $dados['descricao_artista']) : '';

        if ($nome === '' || tamanhoTexto($nome) > 100) {
            responder(['erro' => 'Nome do evento é obrigatório e deve ter até 100 caracteres'], 400);
        }

        if (tamanhoTexto($local) > 255) {
            responder(['erro' => 'Local do evento deve ter até 255 caracteres'], 400);
        }

        if (tamanhoTexto($descricao) > 1000 || tamanhoTexto($descricaoArtista) > 1000) {
            responder(['erro' => 'As descrições devem ter até 1000 caracteres'], 400);
        }

        if ($gratuidadeRecebida === null) {
            responder(['erro' => 'gratuidade deve ser true ou false'], 400);
        }

        $gratuidade = $gratuidadeRecebida ? 1 : 0;

        if ($data !== '') {
            $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

            if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                responder(['erro' => 'data_evento deve ser uma data válida no formato YYYY-MM-DD'], 400);
            }
        }

        if ($horario !== '') {
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
        $local = $local === '' ? null : $local;
        $descricao = $descricao === '' ? null : $descricao;
        $descricaoArtista = $descricaoArtista === '' ? null : $descricaoArtista;

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
        } catch (UploadInvalidoException $erro) {
            responder(['erro' => $erro->getMessage()], 400);
        } catch (Throwable $erro) {
            if ($fotoSalva !== null) {
                removerArquivoUpload($fotoSalva['caminho_arquivo']);
            }
            responder(['erro' => 'Não foi possível criar a solicitação de evento'], 500);
        }

        responder([
            'mensagem' => 'Solicitação enviada, aguardando aprovação',
            'solicitacao' => [
                'id_solicitacao' => $idSolicitacao,
                'status_solicitacao' => 'pendente',
                'foto' => $foto
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

        if ($acao === 'editar_solicitacao') {
            $camposEditaveisSolicitacao = [
                'nome_evento', 'horario_evento', 'data_evento', 'local_evento',
                'gratuidade', 'descricao_evento', 'descricao_artista'
            ];

            if (array_intersect($camposEditaveisSolicitacao, array_keys($dados)) === []) {
                responder(['erro' => 'Informe ao menos um campo editável da solicitação'], 400);
            }

            $stmt = $conn->prepare(
                'SELECT nome_evento, horario_evento, data_evento, local_evento,
                        gratuidade, descricao_evento, descricao_artista, status_solicitacao
                 FROM solicitacao
                 WHERE id_solicitacao = ?
                 LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
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
            $local = atualizarTextoOpcional($dados, 'local_evento', $solicitacaoAtual['local_evento']);
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

            if ($nome === '' || tamanhoTexto($nome) > 100) {
                responder(['erro' => 'Nome do evento é obrigatório e deve ter até 100 caracteres'], 400);
            }

            if ($local !== null && tamanhoTexto($local) > 255) {
                responder(['erro' => 'Local do evento deve ter até 255 caracteres'], 400);
            }

            if (
                ($descricao !== null && tamanhoTexto($descricao) > 1000)
                || ($descricaoArtista !== null && tamanhoTexto($descricaoArtista) > 1000)
            ) {
                responder(['erro' => 'As descrições devem ter até 1000 caracteres'], 400);
            }

            if ($data !== null) {
                $dataValidada = DateTimeImmutable::createFromFormat('!Y-m-d', $data);

                if (!$dataValidada || $dataValidada->format('Y-m-d') !== $data) {
                    responder(['erro' => 'data_evento deve ser uma data válida no formato YYYY-MM-DD'], 400);
                }
            }

            if ($horario !== null) {
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

            $stmt = $conn->prepare(
                "UPDATE solicitacao
                 SET nome_evento = ?, horario_evento = ?, data_evento = ?, local_evento = ?,
                     gratuidade = ?, descricao_evento = ?, descricao_artista = ?
                 WHERE id_solicitacao = ? AND status_solicitacao = 'pendente'"
            );
            $stmt->bind_param(
                'ssssissi',
                $nome,
                $horario,
                $data,
                $local,
                $gratuidade,
                $descricao,
                $descricaoArtista,
                $id
            );
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare(
                'SELECT s.id_solicitacao, s.id_user, s.nome_evento, s.status_solicitacao,
                        s.foto, s.horario_evento, s.data_evento, s.local_evento,
                        s.gratuidade, s.descricao_evento, s.descricao_artista,
                        s.data_solicitacao, u.nome_user, u.sobrenome, u.email_user,
                        NULL AS id_evento
                 FROM solicitacao s
                 INNER JOIN usuario u ON u.id_user = s.id_user
                 WHERE s.id_solicitacao = ?
                 LIMIT 1'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
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
            responder(['erro' => 'acao deve ser moderar, editar_solicitacao ou editar'], 400);
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
