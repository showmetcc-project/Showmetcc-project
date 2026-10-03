<?php require_once __DIR__ . '/config/verificaLogin.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidades - ShowMe</title>
    <link href="assets/img/showme.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/comunidade.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="com-cabecalho-padrao cabecalho-tipo-c pagina-comunidades">
<?php
$tipoCabecalho = 'C';
$configuracaoCabecalho = [
    'titulo_primario' => 'Comunidade',
    'fallback' => 'inicio.php',
    'acao' => 'busca_comunidades',
];
require __DIR__ . '/cabecalho.php';
?>
<main class="container comunidade-listagem">
    <header class="comunidade-intro">
        <p class="comunidade-eyebrow"><i class="bi bi-people" aria-hidden="true"></i> Conversas por evento</p>
        <p>Troque dicas, combine trajetos e compartilhe experiências com quem acompanha os mesmos eventos.</p>
    </header>

    <section aria-labelledby="tituloSeusEventos">
        <h2 id="tituloSeusEventos">Seus eventos</h2>
        <div id="comunidadesUsuario" class="row g-4 comunidade-grid" aria-live="polite">
            <p class="comunidade-estado">Carregando suas comunidades...</p>
        </div>
    </section>

    <section aria-labelledby="tituloTodasComunidades">
        <h2 id="tituloTodasComunidades">Todas as comunidades</h2>
        <div id="todasComunidades" class="row g-4 comunidade-grid" aria-live="polite">
            <p class="comunidade-estado">Carregando comunidades...</p>
        </div>
    </section>
</main>
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/comunidade.js"></script>
</body>
</html>
