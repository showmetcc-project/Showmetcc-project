
<?php



/*
 * Roteador exclusivo para o servidor PHP do Codespaces.
 * Não substitui nem modifica as regras do .htaccess.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = is_string($path) ? rawurldecode($path) : '/';

if ($path === '/') {
    require __DIR__ . '/index.php';
    exit;
}

// Arquivos estáticos e arquivos PHP acessados diretamente.
$arquivo = __DIR__ . $path;

if (is_file($arquivo) || is_dir($arquivo)) {
    return false;
}

$rotas = [
    '#^/api/google-calendar/?$#' => 'apiGoogleCalendar.php',
    '#^/api/csrf/?$#' => 'apiCsrf.php',
    '#^/api/comunidade-posts(?:/(\d+))?/?$#' => 'apiComunidadePosts.php',
    '#^/api/comunidade-respostas(?:/(\d+))?/?$#' => 'apiComunidadeRespostas.php',
    '#^/api/comunidade-curtidas/?$#' => 'apiComunidadeCurtidas.php',
    '#^/api/comunidade-denuncias(?:/(\d+))?/?$#' => 'apiComunidadeDenuncias.php',
    '#^/api/comunidade-midias(?:/(\d+))?/?$#' => 'apiComunidadeMidia.php',
    '#^/api/eventos(?:/(\d+))?/?$#' => 'apiEventos.php',
    '#^/api/favoritos(?:/(\d+))?/?$#' => 'apiFavoritos.php',
    '#^/api/planejamento(?:/(\d+))?/?$#' => 'apiPlanejamento.php',
    '#^/api/sessoes(?:/(\d+))?/?$#' => 'apiSessoes.php',
    '#^/api/usuarios(?:/(\d+))?/?$#' => 'apiUsuarios.php',
];

foreach ($rotas as $padrao => $destino) {
    if (preg_match($padrao, $path, $correspondencia)) {
        if (!empty($correspondencia[1])) {
            $_GET['id'] = (int) $correspondencia[1];
            $_REQUEST['id'] = (int) $correspondencia[1];
        }

        require __DIR__ . '/api/' . $destino;
        exit;
    }
}

// Deixa o servidor responder normalmente para caminhos não mapeados.
return false;
