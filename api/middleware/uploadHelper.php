<?php

class UploadInvalidoException extends RuntimeException
{
}

function normalizarArquivosUpload(?array $campo): array
{
    if ($campo === null || !isset($campo['error'])) {
        return [];
    }

    if (!is_array($campo['error'])) {
        return (int) $campo['error'] === UPLOAD_ERR_NO_FILE ? [] : [$campo];
    }

    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $chave) {
        if (!isset($campo[$chave]) || !is_array($campo[$chave])) {
            throw new UploadInvalidoException('Estrutura do campo de arquivos inválida');
        }
    }

    $arquivos = [];
    $quantidade = count($campo['error']);

    for ($indice = 0; $indice < $quantidade; $indice++) {
        if (
            is_array($campo['error'][$indice] ?? null)
            || is_array($campo['name'][$indice] ?? null)
            || is_array($campo['type'][$indice] ?? null)
            || is_array($campo['tmp_name'][$indice] ?? null)
            || is_array($campo['size'][$indice] ?? null)
        ) {
            throw new UploadInvalidoException('Estrutura do campo de arquivos inválida');
        }

        $erro = (int) ($campo['error'][$indice] ?? UPLOAD_ERR_NO_FILE);

        if ($erro === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $arquivos[] = [
            'name' => $campo['name'][$indice] ?? '',
            'type' => $campo['type'][$indice] ?? '',
            'tmp_name' => $campo['tmp_name'][$indice] ?? '',
            'error' => $erro,
            'size' => $campo['size'][$indice] ?? 0
        ];
    }

    return $arquivos;
}

function validarTamanhoTotalUpload(int $limiteBytes = 39845888): void
{
    $tamanho = filter_var($_SERVER['CONTENT_LENGTH'] ?? null, FILTER_VALIDATE_INT);

    if ($tamanho !== false && $tamanho !== null && $tamanho > $limiteBytes) {
        throw new UploadInvalidoException('O conjunto de dados enviado não pode ultrapassar 38 MB');
    }
}

function validarEstruturaMp4(string $arquivo): bool
{
    $tamanhoArquivo = filesize($arquivo);
    if ($tamanhoArquivo === false || $tamanhoArquivo < 24) {
        return false;
    }

    $handle = @fopen($arquivo, 'rb');
    if ($handle === false) {
        return false;
    }

    $encontrouFtyp = false;
    $encontrouMoov = false;
    $encontrouMdat = false;
    $offset = 0;
    $quantidadeBoxes = 0;

    try {
        while ($offset < $tamanhoArquivo && $quantidadeBoxes < 1024) {
            if ($tamanhoArquivo - $offset < 8 || fseek($handle, $offset) !== 0) {
                return false;
            }

            $cabecalho = fread($handle, 8);
            if ($cabecalho === false || strlen($cabecalho) !== 8) {
                return false;
            }

            $dadosTamanho = unpack('Ntamanho', substr($cabecalho, 0, 4));
            $tamanhoBox = (int) ($dadosTamanho['tamanho'] ?? 0);
            $tipoBox = substr($cabecalho, 4, 4);
            $tamanhoCabecalho = 8;

            if (!preg_match('/^[\x20-\x7E]{4}$/', $tipoBox)) {
                return false;
            }

            if ($tamanhoBox === 1) {
                $estendido = fread($handle, 8);
                if ($estendido === false || strlen($estendido) !== 8) {
                    return false;
                }

                $partes = unpack('Nalto/Nbaixo', $estendido);
                if (($partes['alto'] ?? 1) !== 0) {
                    return false;
                }

                $tamanhoBox = (int) ($partes['baixo'] ?? 0);
                $tamanhoCabecalho = 16;
            } elseif ($tamanhoBox === 0) {
                $tamanhoBox = $tamanhoArquivo - $offset;
            }

            if (
                $tamanhoBox < $tamanhoCabecalho
                || $offset + $tamanhoBox > $tamanhoArquivo
            ) {
                return false;
            }

            if ($tipoBox === 'ftyp') {
                if ($offset > 4096 || $tamanhoBox < $tamanhoCabecalho + 8) {
                    return false;
                }

                $marcaPrincipal = fread($handle, 4);
                if (
                    $marcaPrincipal === false
                    || strlen($marcaPrincipal) !== 4
                    || !preg_match('/^[\x20-\x7E]{4}$/', $marcaPrincipal)
                ) {
                    return false;
                }
                $encontrouFtyp = true;
            } elseif ($tipoBox === 'moov' && $tamanhoBox > $tamanhoCabecalho) {
                $encontrouMoov = true;
            } elseif ($tipoBox === 'mdat' && $tamanhoBox > $tamanhoCabecalho) {
                $encontrouMdat = true;
            }

            $offset += $tamanhoBox;
            $quantidadeBoxes++;
        }
    } finally {
        fclose($handle);
    }

    return $offset === $tamanhoArquivo
        && $encontrouFtyp
        && $encontrouMoov
        && $encontrouMdat;
}

function lerElementoEbml($handle, int $offset, int $limite): ?array
{
    if ($offset >= $limite || fseek($handle, $offset) !== 0) {
        return null;
    }

    $primeiroId = fread($handle, 1);
    if ($primeiroId === false || strlen($primeiroId) !== 1) {
        return null;
    }

    $byteId = ord($primeiroId);
    $mascaraId = 0x80;
    $tamanhoId = 1;
    while ($tamanhoId <= 4 && ($byteId & $mascaraId) === 0) {
        $mascaraId >>= 1;
        $tamanhoId++;
    }

    if ($tamanhoId > 4 || $offset + $tamanhoId >= $limite) {
        return null;
    }

    $id = $byteId;
    if ($tamanhoId > 1) {
        $restanteId = fread($handle, $tamanhoId - 1);
        if ($restanteId === false || strlen($restanteId) !== $tamanhoId - 1) {
            return null;
        }
        foreach (unpack('C*', $restanteId) as $byte) {
            $id = ($id << 8) | $byte;
        }
    }

    $primeiroTamanho = fread($handle, 1);
    if ($primeiroTamanho === false || strlen($primeiroTamanho) !== 1) {
        return null;
    }

    $byteTamanho = ord($primeiroTamanho);
    $mascaraTamanho = 0x80;
    $comprimentoTamanho = 1;
    while ($comprimentoTamanho <= 8 && ($byteTamanho & $mascaraTamanho) === 0) {
        $mascaraTamanho >>= 1;
        $comprimentoTamanho++;
    }

    if ($comprimentoTamanho > 8) {
        return null;
    }

    $valorTamanho = $byteTamanho & ($mascaraTamanho - 1);
    $tamanhoDesconhecido = $valorTamanho === ($mascaraTamanho - 1);

    if ($comprimentoTamanho > 1) {
        $restanteTamanho = fread($handle, $comprimentoTamanho - 1);
        if ($restanteTamanho === false || strlen($restanteTamanho) !== $comprimentoTamanho - 1) {
            return null;
        }
        foreach (unpack('C*', $restanteTamanho) as $byte) {
            $valorTamanho = ($valorTamanho << 8) | $byte;
            $tamanhoDesconhecido = $tamanhoDesconhecido && $byte === 0xFF;
        }
    }

    $inicioDados = $offset + $tamanhoId + $comprimentoTamanho;
    $tamanho = $tamanhoDesconhecido ? null : $valorTamanho;

    if ($tamanho !== null && ($tamanho < 0 || $inicioDados + $tamanho > $limite)) {
        return null;
    }

    return [
        'id' => $id,
        'inicio_dados' => $inicioDados,
        'tamanho' => $tamanho,
        'proximo' => $tamanho === null ? null : $inicioDados + $tamanho
    ];
}

function validarEstruturaWebm(string $arquivo): bool
{
    $tamanhoArquivo = filesize($arquivo);
    if ($tamanhoArquivo === false || $tamanhoArquivo < 32) {
        return false;
    }

    $handle = @fopen($arquivo, 'rb');
    if ($handle === false) {
        return false;
    }

    try {
        $cabecalho = lerElementoEbml($handle, 0, $tamanhoArquivo);
        if (
            $cabecalho === null
            || $cabecalho['id'] !== 0x1A45DFA3
            || $cabecalho['tamanho'] === null
            || $cabecalho['proximo'] === null
        ) {
            return false;
        }

        if (fseek($handle, $cabecalho['inicio_dados']) !== 0) {
            return false;
        }
        $conteudoCabecalho = fread($handle, $cabecalho['tamanho']);
        if ($conteudoCabecalho === false || stripos($conteudoCabecalho, 'webm') === false) {
            return false;
        }

        $segmento = lerElementoEbml($handle, $cabecalho['proximo'], $tamanhoArquivo);
        if ($segmento === null || $segmento['id'] !== 0x18538067) {
            return false;
        }

        $limiteSegmento = $segmento['proximo'] ?? $tamanhoArquivo;
        $offset = $segmento['inicio_dados'];
        $encontrouInfo = false;
        $encontrouTracks = false;
        $encontrouCluster = false;
        $quantidadeElementos = 0;

        while ($offset < $limiteSegmento && $quantidadeElementos < 2048) {
            $elemento = lerElementoEbml($handle, $offset, $limiteSegmento);
            if ($elemento === null) {
                return false;
            }

            if ($elemento['id'] === 0x1549A966) {
                $encontrouInfo = true;
            } elseif ($elemento['id'] === 0x1654AE6B) {
                $encontrouTracks = true;
            } elseif ($elemento['id'] === 0x1F43B675) {
                $encontrouCluster = true;
            }

            if ($elemento['proximo'] === null) {
                return $elemento['id'] === 0x1F43B675
                    && $encontrouInfo
                    && $encontrouTracks;
            }

            $offset = $elemento['proximo'];
            $quantidadeElementos++;
        }

        return $encontrouInfo && $encontrouTracks && $encontrouCluster;
    } finally {
        fclose($handle);
    }
}

function validarEstruturaVideo(string $arquivo, string $mime): bool
{
    return match ($mime) {
        'video/mp4' => validarEstruturaMp4($arquivo),
        'video/webm' => validarEstruturaWebm($arquivo),
        default => false
    };
}

function salvarArquivoUpload(array $arquivo, array $tiposAceitos, string $subpasta): array
{
    $formatos = [
        'image/jpeg' => ['tipo' => 'foto', 'extensao' => 'jpg', 'limite' => 10 * 1024 * 1024],
        'image/png' => ['tipo' => 'foto', 'extensao' => 'png', 'limite' => 10 * 1024 * 1024],
        'image/webp' => ['tipo' => 'foto', 'extensao' => 'webp', 'limite' => 10 * 1024 * 1024],
        'video/mp4' => ['tipo' => 'video', 'extensao' => 'mp4', 'limite' => 30 * 1024 * 1024],
        'video/webm' => ['tipo' => 'video', 'extensao' => 'webm', 'limite' => 30 * 1024 * 1024]
    ];

    if (!preg_match('/^[a-z0-9_-]+$/', $subpasta)) {
        throw new InvalidArgumentException('Subpasta de upload inválida');
    }

    $erro = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro !== UPLOAD_ERR_OK) {
        $mensagens = [
            UPLOAD_ERR_INI_SIZE => 'O arquivo excede o limite configurado no servidor',
            UPLOAD_ERR_FORM_SIZE => 'O arquivo excede o limite permitido pelo formulário',
            UPLOAD_ERR_PARTIAL => 'O upload do arquivo foi interrompido',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo foi enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária de upload não está disponível',
            UPLOAD_ERR_CANT_WRITE => 'Não foi possível gravar o arquivo temporário',
            UPLOAD_ERR_EXTENSION => 'O upload foi bloqueado por uma extensão do servidor'
        ];
        throw new UploadInvalidoException($mensagens[$erro] ?? 'Falha desconhecida no upload');
    }

    $temporario = (string) ($arquivo['tmp_name'] ?? '');
    $tamanho = (int) ($arquivo['size'] ?? 0);

    if ($temporario === '' || !is_uploaded_file($temporario) || $tamanho < 1) {
        throw new UploadInvalidoException('O arquivo enviado é inválido');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporario);

    if (!is_string($mime) || !isset($formatos[$mime])) {
        throw new UploadInvalidoException('Formato de arquivo não permitido');
    }

    $formato = $formatos[$mime];
    if (!in_array($formato['tipo'], $tiposAceitos, true)) {
        throw new UploadInvalidoException(
            $formato['tipo'] === 'video'
                ? 'Vídeos não são permitidos neste upload'
                : 'Fotos não são permitidas neste upload'
        );
    }

    if ($tamanho > $formato['limite']) {
        $limiteMb = (int) ($formato['limite'] / 1024 / 1024);
        throw new UploadInvalidoException("O arquivo excede o limite de {$limiteMb} MB");
    }

    if ($formato['tipo'] === 'foto' && @getimagesize($temporario) === false) {
        throw new UploadInvalidoException('O conteúdo enviado não é uma imagem válida');
    }

    if ($formato['tipo'] === 'video' && !validarEstruturaVideo($temporario, $mime)) {
        throw new UploadInvalidoException('O conteúdo enviado não possui uma estrutura de vídeo válida');
    }

    $diretorioBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
    $diretorioDestino = $diretorioBase . DIRECTORY_SEPARATOR . $subpasta;

    if (!is_dir($diretorioDestino) && !mkdir($diretorioDestino, 0755, true) && !is_dir($diretorioDestino)) {
        throw new RuntimeException('Não foi possível preparar o diretório de upload');
    }

    $nomeAleatorio = bin2hex(random_bytes(16)) . '.' . $formato['extensao'];
    $destino = $diretorioDestino . DIRECTORY_SEPARATOR . $nomeAleatorio;

    if (!move_uploaded_file($temporario, $destino)) {
        throw new RuntimeException('Não foi possível salvar o arquivo enviado');
    }

    return [
        'tipo_midia' => $formato['tipo'],
        'caminho_arquivo' => "assets/uploads/{$subpasta}/{$nomeAleatorio}"
    ];
}

function salvarImagemRedimensionadaUpload(
    array $arquivo,
    string $subpasta,
    int $larguraDestino,
    int $alturaDestino,
    int $qualidade = 86
): array {
    if (!extension_loaded('gd')) {
        throw new RuntimeException('A extensão GD precisa estar habilitada para processar imagens');
    }

    if (!preg_match('/^[a-z0-9_-]+$/', $subpasta)) {
        throw new InvalidArgumentException('Subpasta de upload inválida');
    }

    if ($larguraDestino < 1 || $alturaDestino < 1) {
        throw new InvalidArgumentException('Dimensões de imagem inválidas');
    }

    $erro = (int) ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro !== UPLOAD_ERR_OK) {
        $mensagens = [
            UPLOAD_ERR_INI_SIZE => 'A imagem excede o limite configurado no servidor',
            UPLOAD_ERR_FORM_SIZE => 'A imagem excede o limite permitido pelo formulário',
            UPLOAD_ERR_PARTIAL => 'O upload da imagem foi interrompido',
            UPLOAD_ERR_NO_FILE => 'Nenhuma imagem foi enviada',
            UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária de upload não está disponível',
            UPLOAD_ERR_CANT_WRITE => 'Não foi possível gravar a imagem temporária',
            UPLOAD_ERR_EXTENSION => 'O upload foi bloqueado por uma extensão do servidor',
        ];
        throw new UploadInvalidoException($mensagens[$erro] ?? 'Falha desconhecida no upload');
    }

    $temporario = (string) ($arquivo['tmp_name'] ?? '');
    $tamanho = (int) ($arquivo['size'] ?? 0);

    if ($temporario === '' || !is_uploaded_file($temporario) || $tamanho < 1) {
        throw new UploadInvalidoException('A imagem enviada é inválida');
    }

    if ($tamanho > 10 * 1024 * 1024) {
        throw new UploadInvalidoException('A imagem não pode ultrapassar 10 MB');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporario);
    $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (!is_string($mime) || !in_array($mime, $mimesPermitidos, true)) {
        throw new UploadInvalidoException('Use uma imagem JPG, PNG ou WebP válida');
    }

    $dimensoes = @getimagesize($temporario);
    if ($dimensoes === false) {
        throw new UploadInvalidoException('O conteúdo enviado não é uma imagem válida');
    }

    $larguraOrigem = (int) ($dimensoes[0] ?? 0);
    $alturaOrigem = (int) ($dimensoes[1] ?? 0);
    if (
        $larguraOrigem < 1
        || $alturaOrigem < 1
        || $larguraOrigem > 12000
        || $alturaOrigem > 12000
        || $larguraOrigem * $alturaOrigem > 40000000
    ) {
        throw new UploadInvalidoException('As dimensões da imagem não são permitidas');
    }

    $conteudo = @file_get_contents($temporario);
    $origem = $conteudo === false ? false : @imagecreatefromstring($conteudo);
    if ($origem === false) {
        throw new UploadInvalidoException('Não foi possível decodificar a imagem enviada');
    }

    $destinoImagem = imagecreatetruecolor($larguraDestino, $alturaDestino);
    if ($destinoImagem === false) {
        imagedestroy($origem);
        throw new RuntimeException('Não foi possível preparar a imagem final');
    }

    imagealphablending($destinoImagem, false);
    imagesavealpha($destinoImagem, true);
    $transparente = imagecolorallocatealpha($destinoImagem, 0, 0, 0, 127);
    imagefill($destinoImagem, 0, 0, $transparente);

    $proporcaoOrigem = $larguraOrigem / $alturaOrigem;
    $proporcaoDestino = $larguraDestino / $alturaDestino;
    $origemX = 0;
    $origemY = 0;
    $recorteLargura = $larguraOrigem;
    $recorteAltura = $alturaOrigem;

    if ($proporcaoOrigem > $proporcaoDestino) {
        $recorteLargura = (int) round($alturaOrigem * $proporcaoDestino);
        $origemX = (int) floor(($larguraOrigem - $recorteLargura) / 2);
    } elseif ($proporcaoOrigem < $proporcaoDestino) {
        $recorteAltura = (int) round($larguraOrigem / $proporcaoDestino);
        $origemY = (int) floor(($alturaOrigem - $recorteAltura) / 2);
    }

    $redimensionou = imagecopyresampled(
        $destinoImagem,
        $origem,
        0,
        0,
        $origemX,
        $origemY,
        $larguraDestino,
        $alturaDestino,
        $recorteLargura,
        $recorteAltura
    );
    imagedestroy($origem);

    if (!$redimensionou) {
        imagedestroy($destinoImagem);
        throw new RuntimeException('Não foi possível redimensionar a imagem');
    }

    $diretorioBase = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads';
    $diretorioDestino = $diretorioBase . DIRECTORY_SEPARATOR . $subpasta;

    if (!is_dir($diretorioDestino) && !mkdir($diretorioDestino, 0755, true) && !is_dir($diretorioDestino)) {
        imagedestroy($destinoImagem);
        throw new RuntimeException('Não foi possível preparar o diretório de upload');
    }

    $usarWebp = function_exists('imagewebp');
    $extensao = $usarWebp ? 'webp' : 'jpg';
    $nomeAleatorio = bin2hex(random_bytes(16)) . '.' . $extensao;
    $destino = $diretorioDestino . DIRECTORY_SEPARATOR . $nomeAleatorio;

    if ($usarWebp) {
        $salvou = imagewebp($destinoImagem, $destino, max(0, min(100, $qualidade)));
    } else {
        $fundo = imagecreatetruecolor($larguraDestino, $alturaDestino);
        $preto = imagecolorallocate($fundo, 10, 10, 10);
        imagefill($fundo, 0, 0, $preto);
        imagealphablending($fundo, true);
        imagecopy($fundo, $destinoImagem, 0, 0, 0, 0, $larguraDestino, $alturaDestino);
        $salvou = imagejpeg($fundo, $destino, max(0, min(100, $qualidade)));
        imagedestroy($fundo);
    }

    imagedestroy($destinoImagem);

    if (!$salvou) {
        throw new RuntimeException('Não foi possível salvar a imagem processada');
    }

    return [
        'tipo_midia' => 'foto',
        'caminho_arquivo' => "assets/uploads/{$subpasta}/{$nomeAleatorio}",
        'largura' => $larguraDestino,
        'altura' => $alturaDestino,
    ];
}

function removerArquivoUpload(string $caminhoRelativo): bool
{
    $caminhoNormalizado = str_replace('\\', '/', ltrim($caminhoRelativo, '/'));

    if (
        !str_starts_with($caminhoNormalizado, 'assets/uploads/')
        || str_contains($caminhoNormalizado, '../')
    ) {
        error_log('Upload não removido: caminho fora de assets/uploads: ' . $caminhoRelativo);
        return false;
    }

    $diretorioBase = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
    $arquivo = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminhoNormalizado));

    if ($diretorioBase === false || $arquivo === false) {
        error_log('Upload não removido: arquivo inexistente ou caminho inacessível: ' . $caminhoNormalizado);
        return false;
    }

    $prefixoSeguro = rtrim($diretorioBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (!str_starts_with($arquivo, $prefixoSeguro) || !is_file($arquivo)) {
        error_log('Upload não removido: destino inválido: ' . $caminhoNormalizado);
        return false;
    }

    if (!@unlink($arquivo)) {
        $ultimoErro = error_get_last();
        $detalhe = is_array($ultimoErro) ? (string) ($ultimoErro['message'] ?? '') : '';
        error_log('Falha ao remover upload ' . $caminhoNormalizado . ($detalhe === '' ? '' : ': ' . $detalhe));
        return false;
    }

    return true;
}

function caminhoUploadAindaReferenciado(mysqli $conn, string $caminhoRelativo): bool
{
    $consultas = [
        'SELECT 1 FROM comunidade_midia WHERE caminho_arquivo = ? LIMIT 1',
        'SELECT 1 FROM solicitacao WHERE foto = ? LIMIT 1',
        'SELECT 1 FROM evento WHERE imagem_evento = ? LIMIT 1',
        'SELECT 1 FROM usuario WHERE foto_perfil = ? OR foto_banner = ? LIMIT 1',
    ];

    foreach ($consultas as $sql) {
        $stmt = $conn->prepare($sql);
        if (substr_count($sql, '?') === 2) {
            $stmt->bind_param('ss', $caminhoRelativo, $caminhoRelativo);
        } else {
            $stmt->bind_param('s', $caminhoRelativo);
        }
        executarStatementApi($stmt);
        $stmt->store_result();
        $referenciado = $stmt->num_rows > 0;
        $stmt->close();

        if ($referenciado) {
            return true;
        }
    }

    return false;
}

function removerArquivosUploadSemReferencia(mysqli $conn, array $caminhos): void
{
    $caminhosUnicos = array_unique(array_filter(
        $caminhos,
        static fn($caminho): bool => is_string($caminho)
            && str_starts_with(str_replace('\\', '/', ltrim(trim($caminho), '/')), 'assets/uploads/')
    ));

    foreach ($caminhosUnicos as $caminho) {
        try {
            if (!caminhoUploadAindaReferenciado($conn, $caminho)) {
                removerArquivoUpload($caminho);
            }
        } catch (Throwable $erro) {
            error_log('Não foi possível conferir/remover o upload ' . $caminho . ': ' . $erro->getMessage());
        }
    }
}
