<?php

session_start();

require '../assets/config/conexao.php';
require '../api_spotify/spotify_config.php';

if (!isset($_GET['code'])) {
    die('Autorização do Spotify não recebida.');
}

if (
    !isset($_GET['state']) ||
    !isset($_SESSION['spotify_state']) ||
    $_GET['state'] !== $_SESSION['spotify_state']
) {
    die('Estado de segurança inválido.');
}

unset($_SESSION['spotify_state']);

$code = $_GET['code'];

/*
|--------------------------------------------------------------------------
| SOLICITA TOKEN
|--------------------------------------------------------------------------
*/

$ch = curl_init('https://accounts.spotify.com/api/token');

curl_setopt_array($ch, [
    CURLOPT_POST => true,

    CURLOPT_POSTFIELDS => http_build_query([
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => SPOTIFY_REDIRECT_URI
    ]),

    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' .
        base64_encode(
            SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET
        ),

        'Content-Type: application/x-www-form-urlencoded'
    ],

    CURLOPT_RETURNTRANSFER => true
]);

$resposta = curl_exec($ch);

if ($resposta === false) {
    die('Erro ao conectar ao Spotify.');
}

curl_close($ch);

$token = json_decode($resposta, true);

if (!isset($token['access_token'])) {
    die('Não foi possível obter o token do Spotify.');
}

$accessToken = $token['access_token'];

$refreshToken = $token['refresh_token'] ?? null;

/*
|--------------------------------------------------------------------------
| BUSCAR PERFIL DO USUÁRIO
|--------------------------------------------------------------------------
*/

$ch = curl_init('https://api.spotify.com/v1/me');

curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken
    ],

    CURLOPT_RETURNTRANSFER => true
]);

$resposta = curl_exec($ch);

curl_close($ch);

$perfil = json_decode($resposta, true);

if (!isset($perfil['id'])) {
    die('Não foi possível obter o usuário do Spotify.');
}

$spotifyId = $perfil['id'];

/*
|--------------------------------------------------------------------------
| USUÁRIO DO SHOWME
|--------------------------------------------------------------------------
*/

$idUser =
    $_SESSION['id_user']
    ?? $_SESSION['usuario']['id_user']
    ?? null;

if (!$idUser) {
    die('Usuário do ShowMe não está logado.');
}

/*
|--------------------------------------------------------------------------
| ARTISTAS MAIS OUVIDOS
|--------------------------------------------------------------------------
*/

$url =
    'https://api.spotify.com/v1/me/top/artists'
    . '?limit=20'
    . '&time_range=medium_term';

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $accessToken
    ],

    CURLOPT_RETURNTRANSFER => true
]);

$resposta = curl_exec($ch);

curl_close($ch);

$dadosArtistas = json_decode($resposta, true);

$artistas = [];
$generos = [];

if (isset($dadosArtistas['items'])) {

    foreach ($dadosArtistas['items'] as $artista) {

        $artistas[] = $artista['name'];

        if (!empty($artista['genres'])) {

            foreach ($artista['genres'] as $genero) {

                $generos[] = $genero;

            }

        }

    }
}

/*
|--------------------------------------------------------------------------
| REMOVE DUPLICADOS
|--------------------------------------------------------------------------
*/

$artistas = array_values(
    array_unique($artistas)
);

$generos = array_values(
    array_unique($generos)
);

/*
|--------------------------------------------------------------------------
| CONVERTE PARA TEXTO
|--------------------------------------------------------------------------
*/

$artistasTexto =
    implode(', ', $artistas);

$generosTexto =
    implode(', ', $generos);

/*
|--------------------------------------------------------------------------
| SALVA NO BANCO
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT id_spotify
    FROM spotify
    WHERE id_user = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    'i',
    $idUser
);

$stmt->execute();

$resultado = $stmt->get_result();

$existe = $resultado->fetch_assoc();

$stmt->close();


if ($existe) {

    /*
    | Atualiza
    */

    $sql = "
        UPDATE spotify
        SET
            spotify_id = ?,
            artistas_mais_tocados = ?,
            generos_preferidos = ?
        WHERE id_user = ?
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'sssi',
        $spotifyId,
        $artistasTexto,
        $generosTexto,
        $idUser
    );

    $stmt->execute();

    $stmt->close();

} else {

    /*
    | Novo registro
    */

    $sql = "
        SELECT COALESCE(MAX(id_spotify), 0) + 1
        AS proximo
        FROM spotify
    ";

    $resultado =
        $conn->query($sql);

    $linha =
        $resultado->fetch_assoc();

    $idSpotify =
        $linha['proximo'];

    $sql = "
        INSERT INTO spotify
        (
            id_spotify,
            id_user,
            spotify_id,
            artistas_mais_tocados,
            generos_preferidos
        )
        VALUES (?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        'iisss',
        $idSpotify,
        $idUser,
        $spotifyId,
        $artistasTexto,
        $generosTexto
    );

    $stmt->execute();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| GUARDA TOKEN NA SESSÃO
|--------------------------------------------------------------------------
*/

$_SESSION['spotify_access_token'] =
    $accessToken;

if ($refreshToken) {

    $_SESSION['spotify_refresh_token'] =
        $refreshToken;

}

/*
|--------------------------------------------------------------------------
| VOLTA PARA O SITE
|--------------------------------------------------------------------------
*/

header(
    'Location: ../inicio.php?spotify=conectado'
);

exit;