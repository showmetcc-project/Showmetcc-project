<?php

session_start();

require_once __DIR__ . '/spotify_config.php';

$state = bin2hex(random_bytes(16));

$_SESSION['spotify_state'] = $state;

$params = http_build_query([
    'client_id' => SPOTIFY_CLIENT_ID,
    'response_type' => 'code',
    'redirect_uri' => SPOTIFY_REDIRECT_URI,
    'scope' => SPOTIFY_SCOPES,
    'state' => $state
]);

$url = 'https://accounts.spotify.com/authorize?' . $params;

header('Location: ' . $url);
exit;