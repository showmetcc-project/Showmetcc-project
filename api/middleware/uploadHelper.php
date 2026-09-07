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

function removerArquivoUpload(string $caminhoRelativo): void
{
    $caminhoNormalizado = str_replace('\\', '/', ltrim($caminhoRelativo, '/'));

    if (!str_starts_with($caminhoNormalizado, 'assets/uploads/')) {
        return;
    }

    $diretorioBase = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads');
    $arquivo = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $caminhoNormalizado));

    if ($diretorioBase === false || $arquivo === false) {
        return;
    }

    $prefixoSeguro = rtrim($diretorioBase, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (str_starts_with($arquivo, $prefixoSeguro) && is_file($arquivo)) {
        @unlink($arquivo);
    }
}
