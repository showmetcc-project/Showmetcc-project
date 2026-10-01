<?php require_once __DIR__ . '/config/verifica_login.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Eventos - ShowMe</title>

    <link href="assets/img/showme.png" rel="icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <link href="assets/css/favoritos.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">

</head>

<body class="com-cabecalho-padrao cabecalho-tipo-c">

    <?php
    $tipoCabecalho = 'C';
    $configuracaoCabecalho = [
        'titulo_primario' => 'Meus',
        'titulo_secundario' => 'Eventos',
        'icone' => 'bi-calendar-event',
        'fallback' => 'inicio.php',
    ];
    require __DIR__ . '/cabecalho.php';
    ?>

    <main class="eventos-container">

        <!-- ABAS -->
        <div class="abas row g-3">
            <div class="col-12 col-md-6">
                <button class="aba ativa w-100" data-tab="favoritos">
                    <i class="bi bi-heart"></i>
                    Favoritos (<span id="contadorFavoritos">0</span>)
                </button>
            </div>

            <div class="col-12 col-md-6">
                <button class="aba w-100" data-tab="planejados">
                    <i class="bi bi-calendar-event"></i>
                    Planejados (<span id="contadorPlanejados">0</span>)
                </button>
            </div>

        </div>

        <!-- ══════════════════════════
             FAVORITOS
        ═══════════════════════════ -->
        <section id="favoritos" class="conteudo ativa row g-3">
            <p>Carregando seus favoritos...</p>
        </section>

        <!-- ══════════════════════════
             PLANEJADOS
        ═══════════════════════════ -->
        <section id="planejados" class="conteudo row g-3">
            <p>Carregando seus planejados...</p>

        </section>

    </main>

    

  <?php require __DIR__ . '/rodape.php'; ?>



    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/favoritos.js"></script>
    <script>AOS.init();</script>

</body>

</html>
