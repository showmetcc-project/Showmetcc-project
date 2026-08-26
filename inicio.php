<?php

session_start();

/* =========================================================
   CONEXÃO COM O BANCO
========================================================= */

$host = "localhost";
$usuario = "root";
$senha = "";
$banco = "showme";

$conn = new mysqli(
    $host,
    $usuario,
    $senha,
    $banco
);

if ($conn->connect_error) {
    die("Erro ao conectar ao banco: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


/* =========================================================
   FUNÇÃO PARA ESCAPAR HTML
========================================================= */

function e($texto)
{
    return htmlspecialchars(
        $texto ?? "",
        ENT_QUOTES,
        "UTF-8"
    );
}


/* =========================================================
   FUNÇÃO DA IMAGEM
========================================================= */

function imagemEvento($imagem)
{
    $imagem = trim($imagem ?? "");

    /*
     * Se não tiver imagem no banco
     */
    if ($imagem === "") {
        return "assets/img/banner_site_565x235px.png";
    }

    /*
     * Se for uma URL
     */
    if (
        str_starts_with($imagem, "http://") ||
        str_starts_with($imagem, "https://")
    ) {
        return $imagem;
    }

    /*
     * Se já tiver o caminho assets/
     */
    if (str_starts_with($imagem, "assets/")) {
        return $imagem;
    }

    /*
     * Se estiver salvo somente como:
     *
     * rock.png
     *
     * transforma em:
     *
     * assets/img/rock.png
     */
    return "assets/img/" . ltrim($imagem, "/");
}


/* =========================================================
   USUÁRIO LOGADO
========================================================= */

$idUser = $_SESSION['id_user'] ?? 1;


/* =========================================================
   BUSCAR TODOS OS EVENTOS
========================================================= */

$sql = "
    SELECT
        id_evento,
        num_evento,
        nome_evento,
        local_evento,
        rua_evento,
        cidade_evento,
        uf,
        descricao_evento,
        data_evento,
        gratuidade,
        categoria_evento,
        link_oficial,
        imagem_evento
    FROM evento
    ORDER BY data_evento ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Erro ao buscar eventos: " . $conn->error);
}


/* =========================================================
   TRANSFORMAR EVENTOS EM ARRAY
========================================================= */

$eventos = [];

while ($evento = $result->fetch_assoc()) {

    $eventos[] = $evento;

}


/* =========================================================
   BUSCAR RECOMENDAÇÕES
========================================================= */

$recomendados = [];

/*
 * Busca eventos relacionados:
 *
 * 1. Aos gêneros da tabela preferencias
 *
 * 2. Aos gêneros salvos no Spotify
 *
 * 3. Aos artistas mais tocados no Spotify
 */

$sqlRec = "

    SELECT DISTINCT

        e.id_evento,
        e.num_evento,
        e.nome_evento,
        e.local_evento,
        e.rua_evento,
        e.cidade_evento,
        e.uf,
        e.descricao_evento,
        e.data_evento,
        e.gratuidade,
        e.categoria_evento,
        e.link_oficial,
        e.imagem_evento

    FROM evento e

    LEFT JOIN artista_evento ae
        ON e.id_evento = ae.id_evento

    LEFT JOIN artista a
        ON ae.id_artista = a.id_artista

    LEFT JOIN preferencias p
        ON p.id_user = ?

    LEFT JOIN spotify s
        ON s.id_user = ?

    WHERE

        (
            p.genero_preferido IS NOT NULL

            AND p.genero_preferido <> ''

            AND LOWER(e.categoria_evento)
            LIKE CONCAT(
                '%',
                LOWER(p.genero_preferido),
                '%'
            )
        )

        OR

        (
            s.generos_preferidos IS NOT NULL

            AND s.generos_preferidos <> ''

            AND LOWER(e.categoria_evento)
            LIKE CONCAT(
                '%',
                LOWER(s.generos_preferidos),
                '%'
            )
        )

        OR

        (
            s.artistas_mais_tocados IS NOT NULL

            AND s.artistas_mais_tocados <> ''

            AND LOWER(a.nome_artista)
            LIKE CONCAT(
                '%',
                LOWER(s.artistas_mais_tocados),
                '%'
            )
        )

    ORDER BY e.data_evento ASC
";


$stmtRec = $conn->prepare($sqlRec);


if ($stmtRec) {

    $stmtRec->bind_param(
        "ii",
        $idUser,
        $idUser
    );

    $stmtRec->execute();

    $resultadoRec =
        $stmtRec->get_result();


    while (
        $evento =
        $resultadoRec->fetch_assoc()
    ) {

        $recomendados[] = $evento;

    }

    $stmtRec->close();
}

?>

<!doctype html>

<html lang="pt-br">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>ShowMe</title>


    <!-- =====================================================
         FAVICONS
    ====================================================== -->

    <link
        href="assets/img/showme.png"
        rel="icon"
    >

    <link
        href="assets/img/apple-touch-icon.png"
        rel="apple-touch-icon"
    >


    <!-- =====================================================
         FONTS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com"
        rel="preconnect"
    >

    <link
        href="https://fonts.gstatic.com"
        rel="preconnect"
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="assets/vendor/bootstrap/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="assets/vendor/bootstrap-icons/bootstrap-icons.css"
        rel="stylesheet"
    >

    <link
        href="assets/vendor/aos/aos.css"
        rel="stylesheet"
    >

    <link
        href="assets/vendor/glightbox/css/glightbox.min.css"
        rel="stylesheet"
    >

    <link
        href="assets/vendor/swiper/swiper-bundle.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         GOOGLE FONTS
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:wght@300..800&family=Roboto+Condensed:wght@100..900&family=Story+Script&family=Vend+Sans:wght@300..700&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        href="assets/css/inicio.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS DOS CARDS / CARROSSEL
    ====================================================== -->

    <style>

        /* =====================================================
           CARROSSEL
        ===================================================== */

        .area-carrossel {

            position: relative;

            width: 100%;

        }


        .eventos-carrossel {

            width: 95%;

            margin: 0 auto 50px auto;

            display: flex;

            gap: 20px;

            overflow-x: auto;

            scroll-behavior: smooth;

            padding: 10px 5px 25px 5px;

            scrollbar-width: thin;

        }


        .eventos-carrossel::-webkit-scrollbar {

            height: 8px;

        }


        .eventos-carrossel::-webkit-scrollbar-thumb {

            border-radius: 10px;

        }


        /* =====================================================
           CARD
        ====================================================== */

        .eventos-carrossel .card-evento {

            flex: 0 0 300px;

            width: 300px;

            margin: 0;

        }


        .eventos-carrossel .card-evento img {

            width: 100%;

            height: 180px;

            object-fit: cover;

            display: block;

        }


        .eventos-carrossel .card-evento a {

            display: block;

            text-decoration: none;

        }


        /* =====================================================
           IMAGEM
        ====================================================== */

        .imagem-evento {

            width: 100%;

            height: 180px;

            object-fit: cover;

            border-radius: 10px 10px 0 0;

        }


        /* =====================================================
           BOTÕES
        ====================================================== */

        .botao-carrossel {

            position: absolute;

            top: 42%;

            transform: translateY(-50%);

            z-index: 20;

            width: 45px;

            height: 45px;

            border: none;

            border-radius: 50%;

            background: rgba(20, 20, 20, 0.90);

            color: white;

            cursor: pointer;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .botao-carrossel:hover {

            transform:
                translateY(-50%)
                scale(1.08);

        }


        .botao-esquerda {

            left: 1%;

        }


        .botao-direita {

            right: 1%;

        }


        /* =====================================================
           MOBILE
        ====================================================== */

        @media (max-width: 768px) {

            .eventos-carrossel .card-evento {

                flex: 0 0 260px;

                width: 260px;

            }


            .botao-carrossel {

                display: none;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     HEADER
========================================================= -->

<header class="topbar">


    <button
        class="hamburger-toggle"
        id="hamburgerToggle"
        aria-label="Abrir menu"
        aria-expanded="false"
    >

        <i class="bi bi-list"></i>

    </button>


    <div class="logo">

        <a href="sobre.html">

            <img
                src="assets/img/showme.png"
                alt="ShowMe"
            >

        </a>

    </div>


    <div
        class="topbar-nav"
        id="topbarNav"
    >


        <div class="search-box">

            <i class="bi bi-search"></i>

            <input
                type="text"
                placeholder="Buscar eventos..."
            >

        </div>


        <div class="topbar-actions">


            <a
                href="cadastro-evento.php"
                class="btn-cadastrar"
            >

                <i class="bi bi-plus-circle"></i>

                Cadastrar Evento

            </a>


            <nav class="menu-links">


                <a
                    href="favoritos.html"
                    class="link-favoritos"
                >

                    <i class="bi bi-heart heart-outline"></i>

                    <i class="bi bi-heart-fill heart-filled"></i>

                    Favoritos

                </a>


                <a href="sobre.html">

                    <i class="bi bi-info-circle"></i>

                    Sobre

                </a>


                <a href="#footer">

                    <i class="bi bi-telephone"></i>

                    Contato

                </a>


            </nav>


            <button class="btn-user">

                <i class="bi bi-person-fill"></i>

                <a
                    class="preto"
                    href="perfilusuario.html"
                >

                    Usuário

                </a>

            </button>


        </div>

    </div>

</header>



<!-- =========================================================
     MAIN
========================================================= -->

<main class="inicio">


    <!-- =====================================================
         BANNER
    ====================================================== -->

    <section class="banner-section">


        <h2 class="titulo-home">

            Recomendamos para Você

        </h2>


        <div class="banner swiper">


            <div class="swiper-wrapper">


                <div class="swiper-slide">

                    <img
                        src="assets/img/banner_site_565x235px.png"
                        alt="Banner Principal"
                    >

                </div>


                <div class="swiper-slide">

                    <img
                        src="assets/img/banner.png"
                        alt="Rock in Rio"
                    >

                </div>


                <div class="swiper-slide">

                    <img
                        src="assets/img/shrek.png"
                        alt="Shrek: O Musical"
                    >

                </div>


            </div>


            <div class="swiper-pagination"></div>

            <div class="swiper-button-prev"></div>

            <div class="swiper-button-next"></div>


        </div>

    </section>



    <!-- =====================================================
         EVENTOS
    ====================================================== -->

    <section class="eventos">


        <!-- =================================================
             RECOMENDADOS
        ================================================== -->

        <?php if (count($recomendados) > 0): ?>


            <h3>

                Recomendados para Você

            </h3>


            <div class="area-carrossel">


                <!-- BOTÃO ESQUERDA -->

                <button
                    class="botao-carrossel botao-esquerda"
                    onclick="moverCarrossel('recomendados', -1)"
                >

                    <i class="bi bi-chevron-left"></i>

                </button>


                <!-- CARDS -->

                <div
                    class="eventos-carrossel"
                    id="recomendados"
                >


                    <?php foreach (
                        $recomendados
                        as $evento
                    ): ?>


                        <?php

                        $imagem =
                            imagemEvento(
                                $evento['imagem_evento']
                            );


                        $data =
                            !empty(
                                $evento['data_evento']
                            )
                            ?
                            date(
                                'd/m/Y',
                                strtotime(
                                    $evento['data_evento']
                                )
                            )
                            :
                            'Data não informada';


                        $gratuito =
                            (bool)
                            $evento['gratuidade'];

                        ?>


                        <div class="card-evento">


                            <a
                                href="detalhesEvento.php?id=<?= (int)$evento['id_evento'] ?>"
                            >


                                <!-- PAGO / GRATUITO -->

                                <?php if ($gratuito): ?>

                                    <div
                                        class="badge-evento gratuito"
                                    >

                                        Gratuito

                                    </div>

                                <?php else: ?>

                                    <div
                                        class="badge-evento pago"
                                    >

                                        Pago

                                    </div>

                                <?php endif; ?>


                                <!-- IMAGEM -->

                                <img
                                    src="<?= e($imagem) ?>"
                                    class="imagem-evento"
                                    alt="<?= e($evento['nome_evento']) ?>"
                                    onerror="this.onerror=null;this.src='assets/img/banner_site_565x235px.png';"
                                >


                                <!-- CONTEÚDO -->

                                <div class="card-conteudo">


                                    <h4>

                                        <?= e(
                                            $evento['nome_evento']
                                        ) ?>

                                    </h4>


                                    <div class="info-evento">


                                        <span>

                                            <i class="bi bi-geo-alt-fill"></i>

                                            <?= e(
                                                $evento['cidade_evento']
                                            ) ?>

                                            -

                                            <?= e(
                                                $evento['uf']
                                            ) ?>

                                        </span>


                                        <span>

                                            <i class="bi bi-calendar-event"></i>

                                            <?= $data ?>

                                        </span>


                                    </div>


                                </div>


                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


                <!-- BOTÃO DIREITA -->

                <button
                    class="botao-carrossel botao-direita"
                    onclick="moverCarrossel('recomendados', 1)"
                >

                    <i class="bi bi-chevron-right"></i>

                </button>


            </div>


        <?php endif; ?>



        <!-- =================================================
             TODOS OS EVENTOS
        ================================================== -->

        <h3>

            Eventos Musicais

        </h3>


        <?php if (count($eventos) > 0): ?>


            <div class="area-carrossel">


                <!-- BOTÃO ESQUERDA -->

                <button
                    class="botao-carrossel botao-esquerda"
                    onclick="moverCarrossel('todos-eventos', -1)"
                >

                    <i class="bi bi-chevron-left"></i>

                </button>


                <!-- CARDS -->

                <div
                    class="eventos-carrossel"
                    id="todos-eventos"
                >


                    <?php foreach (
                        $eventos
                        as $evento
                    ): ?>


                        <?php

                        $imagem =
                            imagemEvento(
                                $evento['imagem_evento']
                            );


                        $data =
                            !empty(
                                $evento['data_evento']
                            )
                            ?
                            date(
                                'd/m/Y',
                                strtotime(
                                    $evento['data_evento']
                                )
                            )
                            :
                            'Data não informada';


                        $gratuito =
                            (bool)
                            $evento['gratuidade'];

                        ?>


                        <div class="card-evento">


                            <a
                                href="detalhesEvento.php?id=<?= (int)$evento['id_evento'] ?>"
                            >


                                <!-- PAGO / GRATUITO -->

                                <?php if ($gratuito): ?>

                                    <div
                                        class="badge-evento gratuito"
                                    >

                                        Gratuito

                                    </div>

                                <?php else: ?>

                                    <div
                                        class="badge-evento pago"
                                    >

                                        Pago

                                    </div>

                                <?php endif; ?>


                                <!-- IMAGEM -->

                                <img
                                    src="<?= e($imagem) ?>"
                                    class="imagem-evento"
                                    alt="<?= e($evento['nome_evento']) ?>"
                                    onerror="this.onerror=null;this.src='assets/img/banner_site_565x235px.png';"
                                >


                                <!-- CONTEÚDO -->

                                <div class="card-conteudo">


                                    <h4>

                                        <?= e(
                                            $evento['nome_evento']
                                        ) ?>

                                    </h4>


                                    <div class="info-evento">


                                        <span>

                                            <i class="bi bi-geo-alt-fill"></i>

                                            <?= e(
                                                $evento['cidade_evento']
                                            ) ?>

                                            -

                                            <?= e(
                                                $evento['uf']
                                            ) ?>

                                        </span>


                                        <span>

                                            <i class="bi bi-calendar-event"></i>

                                            <?= $data ?>

                                        </span>


                                    </div>


                                </div>


                            </a>


                        </div>


                    <?php endforeach; ?>


                </div>


                <!-- BOTÃO DIREITA -->

                <button
                    class="botao-carrossel botao-direita"
                    onclick="moverCarrossel('todos-eventos', 1)"
                >

                    <i class="bi bi-chevron-right"></i>

                </button>


            </div>


        <?php else: ?>


            <div class="sem-eventos">


                <i class="bi bi-calendar-x"></i>


                <p>

                    Nenhum evento cadastrado no momento.

                </p>


            </div>


        <?php endif; ?>


    </section>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<footer
    id="footer"
    class="footer"
>


    <div class="footer-line"></div>


    <div class="container footer-top">


        <div class="row gy-4">


            <div class="col-lg-4 col-md-6 footer-about">


                <h4 class="logo-footer">

                    <span class="verde">

                        Show

                    </span>

                    <span class="rosa">

                        Me

                    </span>

                </h4>


                <p>

                    Democratizando o acesso à cultura desde 2026.

                </p>


                <div class="social-links">


                    <a
                        href="https://www.instagram.com/showmetcc/"
                    >

                        <i class="bi bi-instagram"></i>

                    </a>


                    <a href="#">

                        <i class="bi bi-twitter-x"></i>

                    </a>


                    <a href="#">

                        <i class="bi bi-facebook"></i>

                    </a>


                </div>


            </div>



            <div class="col-lg-4 col-md-6">


                <h4>

                    Entre em Contato

                </h4>


                <form
                    class="footer-contact-form"
                >


                    <input
                        type="email"
                        placeholder="Seu e-mail"
                    >


                    <button type="submit">

                        <i class="bi bi-envelope"></i>

                    </button>


                </form>


            </div>



            <div
                class="col-lg-4 col-md-12 footer-links"
            >


                <h4>

                    Informações

                </h4>


                <ul>


                    <li>

                        <a href="#">

                            Termos de Uso

                        </a>

                    </li>


                    <li>

                        <a href="#">

                            Política de Privacidade

                        </a>

                    </li>


                </ul>


                <p class="copyright-text">

                    © 2026 ShowMe. Todos os direitos reservados.

                </p>


            </div>


        </div>


    </div>


</footer>



<!-- =========================================================
     SCROLL TOP
========================================================= -->

<a
    href="#"
    id="scroll-top"
    class="scroll-top d-flex align-items-center justify-content-center"
>

    <i class="bi bi-arrow-up-short"></i>

</a>



<!-- =========================================================
     PRELOADER
========================================================= -->

<div id="preloader"></div>



<!-- =========================================================
     JAVASCRIPT VENDOR
========================================================= -->

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="assets/vendor/aos/aos.js"></script>

<script src="assets/vendor/glightbox/js/glightbox.min.js"></script>

<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

<script src="assets/vendor/waypoints/noframework.waypoints.js"></script>

<script src="assets/vendor/imagesloaded/imagesloaded.pkgd.min.js"></script>

<script src="assets/vendor/isotope-layout/isotope.pkgd.min.js"></script>

<script src="assets/js/main.js"></script>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>


/* =========================================================
   MENU HAMBÚRGUER
========================================================= */

const hamburgerToggle =
    document.getElementById(
        "hamburgerToggle"
    );


const topbarNav =
    document.getElementById(
        "topbarNav"
    );


if (
    hamburgerToggle &&
    topbarNav
) {

    hamburgerToggle.addEventListener(
        "click",
        function() {


            const aberto =
                topbarNav.classList.toggle(
                    "ativo"
                );


            this.setAttribute(
                "aria-expanded",
                aberto
            );


            this.innerHTML =
                aberto

                ?

                '<i class="bi bi-x-lg"></i>'

                :

                '<i class="bi bi-list"></i>';

        }
    );


    topbarNav
        .querySelectorAll("a")
        .forEach(
            function(link) {


                link.addEventListener(
                    "click",
                    function() {


                        topbarNav.classList.remove(
                            "ativo"
                        );


                        hamburgerToggle.innerHTML =
                            '<i class="bi bi-list"></i>';


                        hamburgerToggle.setAttribute(
                            "aria-expanded",
                            "false"
                        );

                    }
                );

            }
        );

}



/* =========================================================
   CARROSSEL DO BANNER
========================================================= */

if (
    typeof Swiper !== "undefined"
) {

    const bannerSwiper =
        new Swiper(
            ".banner.swiper",
            {

                loop: true,

                autoplay: {

                    delay: 4500,

                    disableOnInteraction: false

                },

                pagination: {

                    el: ".swiper-pagination"

                },

                navigation: {

                    nextEl:
                        ".swiper-button-next",

                    prevEl:
                        ".swiper-button-prev"

                }

            }
        );

}



/* =========================================================
   CARROSSEL DOS EVENTOS
========================================================= */

function moverCarrossel(
    id,
    direcao
) {

    const carrossel =
        document.getElementById(id);


    if (!carrossel) {

        return;

    }


    const distancia = 330;


    carrossel.scrollBy({

        left:
            distancia * direcao,

        behavior:
            "smooth"

    });

}



/* =========================================================
   TOPBAR NO SCROLL
========================================================= */

const topbarEl =
    document.querySelector(
        ".topbar"
    );


const DISTANCIA_MAX = 250;

const OPACIDADE_MIN = 0.55;

const OPACIDADE_MAX = 0.97;


function atualizaTopbar() {


    if (!topbarEl) {

        return;

    }


    const distancia =
        Math.min(
            window.scrollY,
            DISTANCIA_MAX
        );


    const progresso =
        distancia /
        DISTANCIA_MAX;


    const opacidade =
        OPACIDADE_MIN +
        progresso *
        (
            OPACIDADE_MAX -
            OPACIDADE_MIN
        );


    topbarEl.style.background =
        `rgba(17, 17, 17, ${opacidade.toFixed(2)})`;

}


window.addEventListener(
    "scroll",
    atualizaTopbar
);


atualizaTopbar();


</script>


</body>

</html>


<?php

$conn->close();

?>