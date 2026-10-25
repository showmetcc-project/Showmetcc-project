<?php
declare(strict_types=1);

/*
 * ShowMe — sincronização automática Ticketmaster
 * Executar pelo terminal/agendador, nunca como endpoint público.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Acesso permitido somente pelo terminal.\n");
}

require_once __DIR__ . '/../config/conexao.php';

$apiKey = ('F6BAuSTUJFsjsthBfDm6GMHbvfNFP7wy');
$idUsuarioImportacao =('11');

if ($apiKey === '') {
    exit("Configure a variável TICKETMASTER_API_KEY.\n");
}

if ($idUsuarioImportacao < 1) {
    exit("Configure SHOWME_IMPORT_USER_ID com o ID de um usuário existente.\n");
}

/* Confirma que o usuário responsável pela importação existe. */
$stmt = $conn->prepare(
    'SELECT id_user FROM usuario WHERE id_user = ? LIMIT 1'
);
$stmt->bind_param('i', $idUsuarioImportacao);
$stmt->execute();
$usuarioExiste = $stmt->get_result()->num_rows > 0;
$stmt->close();

if (!$usuarioExiste) {
    exit("O usuário de importação configurado não existe.\n");
}

$url = 'https://app.ticketmaster.com/discovery/v2/events.json?' .
    http_build_query([
        'apikey' => $apiKey,
        'countryCode' => 'BR',
        'size' => 100,
        'sort' => 'date,asc'
    ]);

$contexto = stream_context_create([
    'http' => [
        'timeout' => 30,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n"
    ]
]);

$resposta = @file_get_contents($url, false, $contexto);

if ($resposta === false) {
    exit("Não foi possível consultar a Ticketmaster.\n");
}

$dados = json_decode($resposta, true);

if (!is_array($dados) || isset($dados['fault'])) {
    exit("A Ticketmaster retornou uma resposta inválida.\n");
}

$eventos = $dados['_embedded']['events'] ?? [];

$importados = 0;
$duplicados = 0;
$ignorados = 0;

foreach ($eventos as $evento) {
    $idExterno = trim((string) ($evento['id'] ?? ''));
    $nome = trim((string) ($evento['name'] ?? ''));

    $data = $evento['dates']['start']['localDate'] ?? null;
    $horario = $evento['dates']['start']['localTime'] ?? null;

    $local = $evento['_embedded']['venues'][0] ?? [];
    $cep = preg_replace(
        '/\D/',
        '',
        (string) ($local['postalCode'] ?? '')
    );

    $cidade = trim((string) (
        $local['city']['name'] ?? ''
    ));

    $uf = strtoupper(trim((string) (
        $local['state']['stateCode'] ?? ''
    )));

    $endereco = trim((string) (
        $local['address']['line1'] ?? ''
    ));

    $numero = trim((string) (
        $local['address']['line2'] ?? ''
    ));

    $imagem = null;
    foreach (($evento['images'] ?? []) as $foto) {
        $urlImagem = trim((string) ($foto['url'] ?? ''));
        if ($urlImagem !== '' && filter_var($urlImagem, FILTER_VALIDATE_URL)) {
            $imagem = $urlImagem;
            break;
        }
    }

    $link = trim((string) ($evento['url'] ?? ''));

    $artistas = $evento['_embedded']['attractions'] ?? [];
    $nomeArtista = trim((string) (
        $artistas[0]['name'] ?? ''
    ));

    $segmento = trim((string) (
        $evento['classifications'][0]['segment']['name'] ?? ''
    ));

    $classificacao = trim((string) (
        $evento['classifications'][0]['rating']['code'] ?? 'Livre'
    ));

    $descricao = trim((string) (
        $evento['info'] ?? $evento['pleaseNote'] ?? ''
    ));

    $precos = $evento['priceRanges'][0] ?? [];
    $precoMin = isset($precos['min']) ? (float) $precos['min'] : null;
    $precoMax = isset($precos['max']) ? (float) $precos['max'] : null;

    
/*
 * Valida apenas os campos realmente essenciais.
 * Dados complementares podem estar ausentes na Ticketmaster.
 */
if (
    $idExterno === '' ||
    $nome === '' ||
    !$data ||
    $link === ''
) {
    $ignorados++;
    continue;

    }

    $stmt = $conn->prepare(
        'SELECT id_solicitacao
         FROM solicitacao
         WHERE ticketmaster_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('s', $idExterno);
    $stmt->execute();
    $jaExiste = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if ($jaExiste) {
        $duplicados++;
        continue;
    }

    $gratuidade = false;
    $fotoAlt = 'Imagem do evento ' . $nome;
    $rua = $endereco;

    $descricaoArtista = 'Artista informado pela Ticketmaster: ' . $nomeArtista;

    /*
     * Esta importação pressupõe que os campos abaixo aceitem os dados
     * da Ticketmaster e que a estrutura SQL tenha sido ajustada.
     * Não atribuímos um usuário aleatório: usamos o ID configurado.
     */
    $stmt = $conn->prepare(
        "INSERT INTO solicitacao (
            id_user, nome_evento, status_solicitacao, foto, alt_imagem,
            horario_evento, data_evento, cep_evento, endereco_evento,
            numero_endereco, rua_evento, cidade_evento, uf,
            categoria_evento, classificacao_indicativa, link_oficial,
            gratuidade, valor_ingresso_minimo, valor_ingresso_maximo,
            descricao_evento, descricao_artista, nome_artista_solicitado,
            genero_artista, ticketmaster_id
        ) VALUES (
            ?, ?, 'pendente', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, ?
        )"
    );

    $genero = $segmento;

    $stmt->bind_param(
        'issssssssssssssiddsssss',
        $idUsuarioImportacao,
        $nome,
        $imagem,
        $fotoAlt,
        $horario,
        $data,
        $cep,
        $endereco,
        $numero,
        $rua,
        $cidade,
        $uf,
        $segmento,
        $classificacao,
        $link,
        $gratuidade,
        $precoMin,
        $precoMax,
        $descricao,
        $descricaoArtista,
        $nomeArtista,
        $genero,
        $idExterno
    );

    try {
        $stmt->execute();
        $importados++;
    } catch (mysqli_sql_exception $erro) {
        if ((int) $erro->getCode() === 1062) {
            $duplicados++;
        } else {
            error_log('Ticketmaster import: ' . $erro->getMessage());
            $ignorados++;
        }
    } finally {
        $stmt->close();
    }
}

echo "Sincronização concluída.\n";
echo "Importados: {$importados}\n";
echo "Já existentes: {$duplicados}\n";
echo "Ignorados por dados ausentes ou erro: {$ignorados}\n";
