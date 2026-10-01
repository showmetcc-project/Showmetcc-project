<?php

require_once __DIR__ . '/middleware/apiCommon.php';

function validarAvaliacao(array $dados): array
{
    $nota = filter_var($dados['nota'] ?? null, FILTER_VALIDATE_INT);
    $comentario = trim((string) ($dados['comentario'] ?? ''));

    if (!$nota || $nota < 1 || $nota > 5) {
        responder(['erro' => 'A nota deve estar entre 1 e 5'], 400);
    }

    if ($comentario === '') {
        responder(['erro' => 'O comentário é obrigatório'], 400);
    }

    if (tamanhoTextoApi($comentario) > 1000) {
        responder(['erro' => 'O comentário deve ter no máximo 1000 caracteres'], 400);
    }

    return [(int) $nota, $comentario];
}

require_once dirname(__DIR__) . '/config/conexao.php';
require_once __DIR__ . '/middleware/apiHelper.php';
require_once __DIR__ . '/middleware/verifica_login.php';
require_once __DIR__ . '/middleware/uploadHelper.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$id = obterIdApi();

switch ($metodo) {
    case 'GET':
        if ($id !== null) {
            try {
                $stmt = $conn->prepare(
                    'SELECT a.id_avaliacao, a.id_evento, a.id_user, a.nota, a.comentario,
                            a.data_avaliacao, u.nome_user, u.sobrenome, e.nome_evento
                     FROM avaliacao a
                     INNER JOIN usuario u ON u.id_user = a.id_user
                     INNER JOIN evento e ON e.id_evento = a.id_evento
                     WHERE a.id_avaliacao = ?
                     LIMIT 1'
                );
                $stmt->bind_param('i', $id);
                executarStatementApi($stmt);
                $avaliacao = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$avaliacao) {
                    responder(['erro' => 'Avaliação não encontrada'], 404);
                }

                $avaliacao = normalizarAvaliacaoApi($avaliacao);
                $avaliacao['midias'] = [];

                $stmt = $conn->prepare(
                    'SELECT id_midia, id_avaliacao, tipo_midia, caminho_arquivo, data_upload
                     FROM avaliacao_midia
                     WHERE id_avaliacao = ?
                     ORDER BY id_midia ASC'
                );
                $stmt->bind_param('i', $id);
                executarStatementApi($stmt);
                $resultadoMidias = $stmt->get_result();

                while ($midia = $resultadoMidias->fetch_assoc()) {
                    $avaliacao['midias'][] = normalizarMidiaAvaliacaoApi($midia);
                }
                $stmt->close();

                responder(['avaliacao' => $avaliacao]);
            } catch (mysqli_sql_exception $erro) {
                responderErroInfraestrutura('Falha ao carregar avaliação', $erro);
            }
        }

        $idEvento = filter_input(INPUT_GET, 'evento_id', FILTER_VALIDATE_INT);

        if (!$idEvento) {
            responder(['erro' => 'evento_id é obrigatório'], 400);
        }

        try {
            $stmt = $conn->prepare(
                'SELECT a.id_avaliacao, a.id_evento, a.id_user, a.nota, a.comentario,
                        a.data_avaliacao, u.nome_user, u.sobrenome
                 FROM avaliacao a
                 INNER JOIN usuario u ON u.id_user = a.id_user
                 WHERE a.id_evento = ?
                 ORDER BY a.data_avaliacao DESC, a.id_avaliacao DESC'
            );
            $stmt->bind_param('i', $idEvento);
            executarStatementApi($stmt);
            $resultado = $stmt->get_result();
            $avaliacoes = [];

            while ($avaliacao = $resultado->fetch_assoc()) {
                $avaliacao = normalizarAvaliacaoApi($avaliacao);
                $avaliacao['midias'] = [];
                $avaliacoes[] = $avaliacao;
            }
            $stmt->close();

            $stmt = $conn->prepare(
                'SELECT m.id_midia, m.id_avaliacao, m.tipo_midia, m.caminho_arquivo, m.data_upload
                 FROM avaliacao_midia m
                 INNER JOIN avaliacao a ON a.id_avaliacao = m.id_avaliacao
                 WHERE a.id_evento = ?
                 ORDER BY m.id_midia ASC'
            );
            $stmt->bind_param('i', $idEvento);
            executarStatementApi($stmt);
            $resultadoMidias = $stmt->get_result();
            $midiasPorAvaliacao = [];

            while ($midia = $resultadoMidias->fetch_assoc()) {
                $midia = normalizarMidiaAvaliacaoApi($midia);
                $idAvaliacao = $midia['id_avaliacao'];
                $midiasPorAvaliacao[$idAvaliacao][] = $midia;
            }
            $stmt->close();

            foreach ($avaliacoes as &$avaliacao) {
                $avaliacao['midias'] = $midiasPorAvaliacao[$avaliacao['id_avaliacao']] ?? [];
            }
            unset($avaliacao);

            responder(['avaliacoes' => $avaliacoes]);
        } catch (mysqli_sql_exception $erro) {
            responderErroInfraestrutura('Falha ao listar avaliações', $erro);
        }

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
        $idEvento = filter_var($dados['id_evento'] ?? null, FILTER_VALIDATE_INT);
        [$nota, $comentario] = validarAvaliacao($dados);

        if (!$idEvento) {
            responder(['erro' => 'id_evento é obrigatório'], 400);
        }

        try {
            $arquivos = normalizarArquivosUpload($_FILES['midias'] ?? null);
        } catch (UploadInvalidoException $erro) {
            responder(['erro' => $erro->getMessage()], 400);
        }

        if (count($arquivos) < 1 || count($arquivos) > 5) {
            responder(['erro' => 'Envie de 1 a 5 fotos ou vídeos'], 400);
        }

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
            'SELECT id_avaliacao FROM avaliacao WHERE id_user = ? AND id_evento = ? LIMIT 1'
        );
        $stmt->bind_param('ii', $idUsuario, $idEvento);
        executarStatementApi($stmt);
        $avaliacaoExistente = $stmt->get_result()->fetch_assoc();

        if ($avaliacaoExistente) {
            $stmt->close();
            responder([
                'erro' => 'Você já avaliou este evento; edite sua avaliação existente',
                'id_avaliacao' => (int) $avaliacaoExistente['id_avaliacao']
            ], 409);
        }
        $stmt->close();

        $midiasSalvas = [];
        try {
            foreach ($arquivos as $arquivo) {
                $midiasSalvas[] = salvarArquivoUpload(
                    $arquivo,
                    ['foto', 'video'],
                    'avaliacoes'
                );
            }
        } catch (UploadInvalidoException $erro) {
            foreach ($midiasSalvas as $midia) {
                removerArquivoUpload($midia['caminho_arquivo']);
            }
            responder(['erro' => $erro->getMessage()], 400);
        } catch (Throwable $erro) {
            foreach ($midiasSalvas as $midia) {
                removerArquivoUpload($midia['caminho_arquivo']);
            }
            responderErroInfraestrutura('Falha ao salvar mídias da avaliação', $erro);
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare(
                'INSERT INTO avaliacao (id_user, id_evento, nota, comentario) VALUES (?, ?, ?, ?)'
            );
            $stmt->bind_param('iiis', $idUsuario, $idEvento, $nota, $comentario);
            executarStatementApi($stmt);
            $idAvaliacao = $conn->insert_id;
            $stmt->close();

            $stmtMidia = $conn->prepare(
                'INSERT INTO avaliacao_midia (id_avaliacao, tipo_midia, caminho_arquivo)
                 VALUES (?, ?, ?)'
            );

            foreach ($midiasSalvas as $indiceMidia => $midia) {
                $tipoMidia = $midia['tipo_midia'];
                $caminhoArquivo = $midia['caminho_arquivo'];
                $stmtMidia->bind_param('iss', $idAvaliacao, $tipoMidia, $caminhoArquivo);
                executarStatementApi($stmtMidia);
                $midiasSalvas[$indiceMidia]['id_midia'] = $conn->insert_id;
                $midiasSalvas[$indiceMidia]['id_avaliacao'] = $idAvaliacao;
                $midiasSalvas[$indiceMidia] = normalizarMidiaAvaliacaoApi(
                    $midiasSalvas[$indiceMidia]
                );
            }

            $stmtMidia->close();
            $conn->commit();
        } catch (mysqli_sql_exception $erro) {
            $conn->rollback();
            foreach ($midiasSalvas as $midia) {
                removerArquivoUpload($midia['caminho_arquivo']);
            }

            if ((int) $erro->getCode() === 1062) {
                responder([
                    'erro' => 'Você já avaliou este evento; edite sua avaliação existente'
                ], 409);
            }

            responderErroInfraestrutura('Falha ao criar avaliação', $erro);
        } catch (Throwable $erro) {
            $conn->rollback();
            foreach ($midiasSalvas as $midia) {
                removerArquivoUpload($midia['caminho_arquivo']);
            }
            responderErroInfraestrutura('Falha ao criar avaliação', $erro);
        }

        $avaliacaoCriada = normalizarAvaliacaoApi([
            'id_avaliacao' => $idAvaliacao,
            'id_evento' => $idEvento,
            'id_user' => $idUsuario,
            'nota' => $nota,
            'comentario' => $comentario,
        ]);
        $avaliacaoCriada['midias'] = $midiasSalvas;

        responder([
            'mensagem' => 'Avaliação criada com sucesso',
            'avaliacao' => $avaliacaoCriada
        ], 201);

    case 'PUT':
        if ($id === null) {
            responder(['erro' => 'Informe o ID da avaliação na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $dados = lerJson();
        [$nota, $comentario] = validarAvaliacao($dados);

        $stmt = $conn->prepare(
            'SELECT id_user, id_evento FROM avaliacao WHERE id_avaliacao = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $avaliacao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$avaliacao) {
            responder(['erro' => 'Avaliação não encontrada'], 404);
        }

        $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
        if ((int) $avaliacao['id_user'] !== $idUsuario && !$ehAdmin) {
            responder(['erro' => 'Você não pode editar esta avaliação'], 403);
        }

        $stmt = $conn->prepare(
            'UPDATE avaliacao SET nota = ?, comentario = ? WHERE id_avaliacao = ?'
        );
        $stmt->bind_param('isi', $nota, $comentario, $id);
        executarStatementApi($stmt);
        $stmt->close();

        responder([
            'mensagem' => 'Avaliação atualizada com sucesso',
            'avaliacao' => normalizarAvaliacaoApi([
                'id_avaliacao' => $id,
                'id_evento' => $avaliacao['id_evento'],
                'id_user' => $avaliacao['id_user'],
                'nota' => $nota,
                'comentario' => $comentario,
            ])
        ]);

    case 'DELETE':
        if ($id === null) {
            responder(['erro' => 'Informe o ID da avaliação na URL'], 400);
        }

        $idUsuario = exigirLogin();
        $stmt = $conn->prepare(
            'SELECT id_user FROM avaliacao WHERE id_avaliacao = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $avaliacao = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$avaliacao) {
            responder(['erro' => 'Avaliação não encontrada'], 404);
        }

        $ehAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
        if ((int) $avaliacao['id_user'] !== $idUsuario && !$ehAdmin) {
            responder(['erro' => 'Você não pode apagar esta avaliação'], 403);
        }

        $stmt = $conn->prepare(
            'SELECT caminho_arquivo FROM avaliacao_midia WHERE id_avaliacao = ?'
        );
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $resultadoMidias = $stmt->get_result();
        $caminhosMidias = [];

        while ($midia = $resultadoMidias->fetch_assoc()) {
            $caminhosMidias[] = $midia['caminho_arquivo'];
        }
        $stmt->close();

        $stmt = $conn->prepare('DELETE FROM avaliacao WHERE id_avaliacao = ?');
        $stmt->bind_param('i', $id);
        executarStatementApi($stmt);
        $stmt->close();

        foreach ($caminhosMidias as $caminhoMidia) {
            removerArquivoUpload($caminhoMidia);
        }

        responder(['mensagem' => 'Avaliação removida com sucesso']);

    default:
        responder(['erro' => 'Método não permitido'], 405);
}
