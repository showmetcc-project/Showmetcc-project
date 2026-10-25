<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function responder(int $status, array $dados): never
{
    http_response_code($status);
    echo json_encode(
        $dados,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    responder(405, [
        'sucesso' => false,
        'erro' => 'Método não permitido.'
    ]);
}

$apiKey = ('F6BAuSTUJFsjsthBfDm6GMHbvfNFP7wy');

if ($apiKey === '') {
    responder(500, [
        'sucesso' => false,
        'erro' => 'A chave da API do Ticketmaster não está configurada.'
    ]);
}

$termo = trim((string) ($_GET['q'] ?? ''));
$cidade = trim((string) ($_GET['cidade'] ?? ''));

if (mb_strlen($termo) > 100 || mb_strlen($cidade) > 100) {
    responder(400, [
        'sucesso' => false,
        'erro' => 'O termo e a cidade devem ter no máximo 100 caracteres.'
    ]);
}

$params = [
    'apikey' => $apiKey,
    'countryCode' => 'BR',
    'size' => 10,
    'sort' => 'date,asc'
];

if ($termo !== '') {
    $params['keyword'] = $termo;
}

if ($cidade !== '') {
    $params['city'] = $cidade;
}

$url = 'https://app.ticketmaster.com/discovery/v2/events.json?'
    . http_build_query($params);

$contexto = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 20,
        'ignore_errors' => true,
        'header' => "Accept: application/json\r\n"
    ]
]);

$resposta = @file_get_contents($url, false, $contexto);

if ($resposta === false) {
    responder(502, [
        'sucesso' => false,
        'erro' => 'Não foi possível conectar à Ticketmaster.'
    ]);
}

$httpStatus = 0;

foreach (($http_response_header ?? []) as $cabecalho) {
    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $cabecalho, $matches)) {
        $httpStatus = (int) $matches[1];
    }
}

$dados = json_decode($resposta, true);

if (!is_array($dados)) {
    responder(502, [
        'sucesso' => false,
        'erro' => 'A Ticketmaster retornou uma resposta inválida.'
    ]);
}

if ($httpStatus !== 200) {
    responder(502, [
        'sucesso' => false,
        'erro' => 'A Ticketmaster retornou HTTP ' . $httpStatus,
        'detalhes' => $dados['fault']['faultstring']
            ?? $dados['errors'][0]['detail']
            ?? $dados['message']
            ?? 'A API não forneceu detalhes do erro.'
    ]);
}

$eventos = $dados['_embedded']['events'] ?? [];

$resultados = [];

foreach ($eventos as $evento) {
    $local = $evento['_embedded']['venues'][0] ?? [];
    $inicio = $evento['dates']['start'] ?? [];
    $artistas = $evento['_embedded']['attractions'] ?? [];
    $classificacao = $evento['classifications'][0] ?? [];
    $preco = $evento['priceRanges'][0] ?? [];

    $nomesArtistas = [];

    foreach ($artistas as $artista) {
        if (!empty($artista['name'])) {
            $nomesArtistas[] = (string) $artista['name'];
        }
    }

    $resultados[] = [
        'id' => $evento['id'] ?? '',
        'nome' => $evento['name'] ?? '',
        'data' => $inicio['localDate'] ?? null,
        'horario' => $inicio['localTime'] ?? null,
        'imagem' => $evento['images'][0]['url'] ?? null,
        'link' => $evento['url'] ?? null,
        'artistas' => $nomesArtistas,
        'local' => [
            'nome' => $local['name'] ?? null,
            'endereco' => $local['address']['line1'] ?? null,
            'cidade' => $local['city']['name'] ?? null,
            'estado' => $local['state']['stateCode']
                ?? $local['state']['name']
                ?? null,
            'cep' => isset($local['postalCode'])
                ? preg_replace('/\D/', '', $local['postalCode'])
                : null,
            'pais' => $local['country']['countryCode'] ?? null
        ],
        'categoria' => $classificacao['segment']['name']
            ?? $classificacao['genre']['name']
            ?? null,
        'classificacao' => $evento['ageRestrictions']['legalAgeEnforced']
            ?? null,
        'preco' => [
            'minimo' => $preco['min'] ?? null,
            'maximo' => $preco['max'] ?? null,
            'moeda' => $preco['currency'] ?? null
        ],
        'descricao' => $evento['info']
            ?? $evento['pleaseNote']
            ?? null
    ];
}

responder(200, [
    'sucesso' => true,
    'total' => count($resultados),
    'eventos' => $resultados,
    'pagina' => $dados['page'] ?? null
]);
