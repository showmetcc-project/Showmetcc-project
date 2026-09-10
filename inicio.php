<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
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
        href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/inicio.css" rel="stylesheet">
    <link href="assets/css/cardsEvento.css" rel="stylesheet">

    <style>
        .eventos {
            width: 100%;
            padding: 40px 0;
        }

        .eventos h3 {
            margin-left: 5%;
            margin-bottom: 25px;
        }

        .carrossel-eventos {
            width: 90%;
            margin: auto;
            position: relative;
            overflow: hidden;
            padding: 10px 45px 20px;
        }

        .carrossel-wrapper {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            scroll-behavior: smooth;
            scrollbar-width: none;
            padding-bottom: 10px;
        }

        .carrossel-wrapper::-webkit-scrollbar {
            display: none;
        }

        .carrossel-wrapper .card-evento {
            flex: 0 0 300px;
            width: 300px;
            min-width: 300px;
        }

        .btn-carrossel {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 42px;
            height: 42px;
            border: none;
            border-radius: 50%;
            background: #111;
            color: white;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-carrossel:hover {
            transform: translateY(-50%) scale(1.08);
        }

        .btn-carrossel i {
            font-size: 20px;
        }

        .btn-carrossel.esquerda {
            left: 0;
        }

        .btn-carrossel.direita {
            right: 0;
        }

        .btn-carrossel[hidden] {
            display: none;
        }

        .sem-eventos {
            text-align: center;
            padding: 50px;
            color: #777;
        }

        .sem-eventos i {
            font-size: 45px;
            display: block;
            margin-bottom: 10px;
        }

        .estado-eventos {
            flex: 1 0 100%;
            width: 100%;
        }

        .estado-eventos.erro {
            color: #b42318;
        }

        @media (max-width: 768px) {
            .carrossel-eventos {
                width: 100%;
                padding-left: 20px;
                padding-right: 20px;
            }

            .carrossel-wrapper .card-evento {
                flex: 0 0 270px;
                min-width: 270px;
                width: 270px;
            }

            .btn-carrossel {
                display: none;
            }
        }
    </style>
</head>

<body class="com-cabecalho-padrao">
    <?php require __DIR__ . '/cabecalho.php'; ?>

    <main class="inicio">
        <section class="banner-section">
            <h2 class="titulo-home">Recomendamos para Você</h2>

            <div class="banner swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <img src="assets/img/banner_site_565x235px.png" alt="Banner Principal">
                    </div>
                    <div class="swiper-slide">
                        <img src="assets/img/banner.png" alt="Rock in Rio">
                    </div>
                    <div class="swiper-slide">
                        <img src="assets/img/shrek.png" alt="Shrek: O Musical">
                    </div>
                </div>

                <div class="swiper-pagination"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
            </div>
        </section>

        <section id="secaoRecomendados" class="eventos recomendados" aria-busy="true">
            <h3>Recomendados para Você</h3>

            <div class="carrossel-eventos">
                <button
                    type="button"
                    class="btn-carrossel esquerda"
                    data-carrossel="recomendados"
                    data-direcao="-1"
                    aria-label="Eventos recomendados anteriores"
                    hidden>
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>

                <div class="carrossel-wrapper" id="recomendados" aria-live="polite">
                    <div class="sem-eventos estado-eventos">
                        <span class="spinner-border" aria-hidden="true"></span>
                        <p>Carregando eventos...</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-carrossel direita"
                    data-carrossel="recomendados"
                    data-direcao="1"
                    aria-label="Próximos eventos recomendados"
                    hidden>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </section>

        <section id="secaoOutrosEventos" class="eventos" aria-busy="true">
            <h3>Eventos Musicais</h3>

            <div class="carrossel-eventos">
                <button
                    type="button"
                    class="btn-carrossel esquerda"
                    data-carrossel="outrosEventos"
                    data-direcao="-1"
                    aria-label="Eventos anteriores"
                    hidden>
                    <i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>

                <div class="carrossel-wrapper" id="outrosEventos" aria-live="polite">
                    <div class="sem-eventos estado-eventos">
                        <span class="spinner-border" aria-hidden="true"></span>
                        <p>Carregando eventos...</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn-carrossel direita"
                    data-carrossel="outrosEventos"
                    data-direcao="1"
                    aria-label="Próximos eventos"
                    hidden>
                    <i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </section>
    </main>

    <?php require __DIR__ . '/rodape.php'; ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/favoritosToggle.js"></script>
    <script src="assets/js/inicio.js"></script>
</body>

</html>
