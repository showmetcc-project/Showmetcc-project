<?php require_once __DIR__ . '/config/verifica_login.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Eventos - ShowMe</title>

    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">

    <!-- Fonts (igual ao index) -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS (mesmo padrão do index) -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">

    <!-- CSS customizado — sempre por último -->
    <link rel="stylesheet" href="assets/css/favoritos.css">



    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">


    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">


    <!-- Vendor CSS Files -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">


    <!-- Principal CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">




    <!--Google fontes, Anton-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
        rel="stylesheet">

</head>

<body>

    <!-- NAV FIXO: fica preso no topo ao rolar a página -->
    <header class="nav-fixo">
        <div class="topo">
            <a href="inicio.php" class="voltar">
                <i class="bi bi-arrow-left"></i>
                Meus <span>Eventos</span>
            </a>
        </div>
        <div class="linha-verde"></div>
    </header>

    <main class="eventos-container">

        <!-- ABAS -->
        <div class="abas">

            <button class="aba ativa" data-tab="favoritos">
                <i class="bi bi-heart"></i>
                Favoritos (3)
            </button>

            <button class="aba" data-tab="planejados">
                <i class="bi bi-calendar-event"></i>
                Planejados (2)
            </button>

        </div>

        <!-- ══════════════════════════
             FAVORITOS
        ═══════════════════════════ -->
        <section id="favoritos" class="conteudo ativa">

            <!-- Card 1 -->
            <div class="card-evento">
                <img src="img/harry.jpg" alt="Harry Styles">
                <div class="info">
                    <h3>Harry Styles: Together, Together</h3>
                    <p><i class="bi bi-geo-alt-fill"></i> São Paulo, SP</p>
                    <p><i class="bi bi-calendar3"></i> 15 Jun 2026</p>
                    <button class="btn-detalhes"><a href="detalhesEvento.php">Ver detalhes</a></button>
                </div>
                <div class="acoes">
                    <span class="tag pago">Pago</span>
                    <button class="btn-excluir" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="card-evento">
                <img src="img/virada.jpg" alt="Virada Cultural">
                <div class="info">
                    <h3>Virada Cultural 2026</h3>
                    <p><i class="bi bi-geo-alt-fill"></i> São Paulo, SP</p>
                    <p><i class="bi bi-calendar3"></i> 22 Jul 2026</p>
                    <button class="btn-detalhes">Ver detalhes</button>
                </div>
                <div class="acoes">
                    <span class="tag gratis">Grátis</span>
                    <button class="btn-excluir" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="card-evento">
                <img src="img/rockinrio.jpg" alt="Rock in Rio">
                <div class="info">
                    <h3>Rock in Rio</h3>
                    <p><i class="bi bi-geo-alt-fill"></i> Rio de Janeiro, RJ</p>
                    <p><i class="bi bi-calendar3"></i> 15 Set 2026</p>
                    <button class="btn-detalhes">Ver detalhes</button>
                </div>
                <div class="acoes">
                    <span class="tag pago">Pago</span>
                    <button class="btn-excluir" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

        </section>

        <!-- ══════════════════════════
             PLANEJADOS
        ═══════════════════════════ -->
        <section id="planejados" class="conteudo">

            <!-- Card 1 -->
            <div class="card-evento">
                <img src="img/harry.jpg" alt="Harry Styles">
                <div class="info">
                    <h3>Harry Styles: Together, Together</h3>
                    <p><i class="bi bi-geo-alt-fill"></i> São Paulo, SP</p>
                    <p><i class="bi bi-calendar3"></i> 15 Jun 2026</p>
                    <div class="botoes-duplos">
                        <button class="btn-detalhes"><a href="detalhesEvento.php">Ver detalhes</a></button>
                        <button class="btn-planejamento">Ver Planejamento</button>
                    </div>
                </div>
                <div class="acoes">
                    <span class="tag pago">Pago</span>
                    <button class="btn-excluir" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="card-evento">
                <img src="img/virada.jpg" alt="Virada Cultural">
                <div class="info">
                    <h3>Virada Cultural 2026</h3>
                    <p><i class="bi bi-geo-alt-fill"></i> São Paulo, SP</p>
                    <p><i class="bi bi-calendar3"></i> 22 Jul 2026</p>
                    <div class="botoes-duplos">
                        <button class="btn-detalhes">Ver detalhes</button>
                        <button class="btn-planejamento">Ver Planejamento</button>
                    </div>
                </div>
                <div class="acoes">
                    <span class="tag gratis">Grátis</span>
                    <button class="btn-excluir" title="Remover">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>

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
    <script src="js/favoritos.js"></script>
    <script>AOS.init();</script>

</body>

</html>
