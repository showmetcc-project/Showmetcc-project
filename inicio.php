<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$secoesHome = [
    ['id' => 'eventosMusicais', 'titulo' => 'Eventos Musicais', 'cor' => 'verde'],
    ['id' => 'pertoVoce', 'titulo' => 'Perto de Você', 'cor' => 'rosa'],
    ['id' => 'cinema', 'titulo' => 'Cinema', 'cor' => 'verde'],
    ['id' => 'showsInternacionais', 'titulo' => 'Shows Internacionais', 'cor' => 'rosa'],
    ['id' => 'showsNacionais', 'titulo' => 'Shows Nacionais', 'cor' => 'verde'],
    ['id' => 'emBreve', 'titulo' => 'Em Breve', 'cor' => 'rosa'],
];
?>
<!doctype html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowMe</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="assets/css/inicio.css" rel="stylesheet">
    <link href="assets/css/cardsEvento.css" rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="com-cabecalho-padrao">
    <?php
    $tipoCabecalho = 'A';
    require __DIR__ . '/cabecalho.php';
    ?>

    <main class="inicio">
        <section class="banner-section">
            <h2 class="titulo-home">Recomendados para Você</h2>

            <div class="banner swiper" aria-live="polite" aria-busy="true">
                <div class="swiper-wrapper" id="bannerEventos">
                    <div class="swiper-slide banner-estado">
                        <span class="spinner-border" aria-hidden="true"></span>
                        <p>Carregando eventos em destaque...</p>
                    </div>
                </div>

                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev" hidden></div>
                <div class="swiper-button-next" hidden></div>
            </div>
        </section>

        <?php foreach ($secoesHome as $secao): ?>
            <section
                id="secao<?= htmlspecialchars($secao['id'], ENT_QUOTES, 'UTF-8') ?>"
                class="eventos secao-eventos-home"
                data-secao-categoria="<?= htmlspecialchars($secao['id'], ENT_QUOTES, 'UTF-8') ?>"
                aria-busy="true">
                <h3 class="titulo-secao-home titulo-<?= htmlspecialchars($secao['cor'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($secao['titulo'], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <div class="carrossel-eventos">
                    <button
                        type="button"
                        class="btn-carrossel esquerda"
                        data-carrossel="<?= htmlspecialchars($secao['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-direcao="-1"
                        aria-label="Eventos anteriores de <?= htmlspecialchars($secao['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                        hidden>
                        <i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>

                    <div
                        class="carrossel-wrapper"
                        id="<?= htmlspecialchars($secao['id'], ENT_QUOTES, 'UTF-8') ?>"
                        aria-live="polite">
                        <div class="sem-eventos estado-eventos">
                            <span class="spinner-border" aria-hidden="true"></span>
                            <p>Carregando eventos...</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="btn-carrossel direita"
                        data-carrossel="<?= htmlspecialchars($secao['id'], ENT_QUOTES, 'UTF-8') ?>"
                        data-direcao="1"
                        aria-label="Próximos eventos de <?= htmlspecialchars($secao['titulo'], ENT_QUOTES, 'UTF-8') ?>"
                        hidden>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>
            </section>
        <?php endforeach; ?>
    </main>

    <?php require __DIR__ . '/rodape.php'; ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/favoritosToggle.js"></script>
    <script src="assets/js/inicio.js"></script>
</body>

</html>
