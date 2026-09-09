<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
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
         FAVICON
    ====================================================== -->

    <link
        href="assets/img/showme.png"
        rel="icon"
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


    <!-- =====================================================
         SWIPER
    ====================================================== -->

    <link
        href="assets/vendor/swiper/swiper-bundle.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         FONTES
    ====================================================== -->

    <link
        href="https://fonts.googleapis.com"
        rel="preconnect"
    >

    <link
        href="https://fonts.gstatic.com"
        rel="preconnect"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS PRINCIPAL
    ====================================================== -->

    <link
        href="assets/css/main.css"
        rel="stylesheet"
    >

    <link
        href="assets/css/inicio.css"
        rel="stylesheet"
    >

    <link
        href="assets/css/cardsEvento.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         CSS DOS CARDS E CARROSSÉIS
    ====================================================== -->

    <style>

        /* =====================================================
           ÁREA DOS EVENTOS
        ====================================================== */

        .eventos {
            width: 100%;
            padding: 40px 0;
        }


        .eventos h3 {
            margin-left: 5%;
            margin-bottom: 25px;
        }


        /* =====================================================
           CARROSSEL
        ====================================================== */

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


        /* =====================================================
           CARD
        ====================================================== */

        .carrossel-wrapper .card-evento {
            flex: 0 0 300px;
            width: 300px;
            min-width: 300px;
        }


        /* =====================================================
           BOTÕES DO CARROSSEL
        ====================================================== */

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
            transform:
                translateY(-50%)
                scale(1.08);
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


        /* =====================================================
           QUANDO NÃO HÁ EVENTOS
        ====================================================== */

        .sem-eventos {
            width: 100%;

            text-align: center;

            padding: 50px;

            color: #777;
        }


        .sem-eventos i {
            font-size: 45px;

            display: block;

            margin-bottom: 10px;
        }


        /* =====================================================
           MOBILE
        ====================================================== */

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


                <!-- BANNER 1 -->

                <div class="swiper-slide">

                    <img
                        src="assets/img/banner_site_565x235px.png"
                        alt="Banner Principal"
                    >

                </div>


                <!-- BANNER 2 -->

                <div class="swiper-slide">

                    <img
                        src="assets/img/banner.png"
                        alt="Rock in Rio"
                    >

                </div>


                <!-- BANNER 3 -->

                <div class="swiper-slide">

                    <img
                        src="assets/img/shrek.png"
                        alt="Shrek: O Musical"
                    >

                </div>


            </div>


            <!-- PAGINAÇÃO -->

            <div class="swiper-pagination"></div>


            <!-- BOTÕES -->

            <div class="swiper-button-prev"></div>

            <div class="swiper-button-next"></div>


        </div>

    </section>



    <!-- =====================================================
         EVENTOS RECOMENDADOS
    ====================================================== -->

    <section class="eventos recomendados">


        <h3>

            Recomendados para Você

        </h3>


        <div class="carrossel-eventos">


            <!-- BOTÃO ESQUERDA -->

            <button
                class="btn-carrossel esquerda"
                onclick="moverCarrossel('recomendados', -1)"
                aria-label="Eventos recomendados anteriores"
            >

                <i class="bi bi-chevron-left"></i>

            </button>


            <!-- CARDS -->

            <div
                class="carrossel-wrapper"
                id="recomendados"
                aria-live="polite"
            >

                <div class="sem-eventos">

                    <i class="bi bi-hourglass-split"></i>

                    <p>
                        Carregando eventos...
                    </p>

                </div>

            </div>


            <!-- BOTÃO DIREITA -->

            <button
                class="btn-carrossel direita"
                onclick="moverCarrossel('recomendados', 1)"
                aria-label="Próximos eventos recomendados"
            >

                <i class="bi bi-chevron-right"></i>

            </button>


        </div>

    </section>



    <!-- =====================================================
         OUTROS EVENTOS
    ====================================================== -->

    <section class="eventos">


        <h3>

            Eventos Musicais

        </h3>


        <div class="carrossel-eventos">


            <!-- BOTÃO ESQUERDA -->

            <button
                class="btn-carrossel esquerda"
                onclick="moverCarrossel('outrosEventos', -1)"
                aria-label="Eventos anteriores"
            >

                <i class="bi bi-chevron-left"></i>

            </button>


            <!-- CARDS -->

            <div
                class="carrossel-wrapper"
                id="outrosEventos"
                aria-live="polite"
            >

                <div class="sem-eventos">

                    <i class="bi bi-hourglass-split"></i>

                    <p>
                        Carregando eventos...
                    </p>

                </div>

            </div>


            <!-- BOTÃO DIREITA -->

            <button
                class="btn-carrossel direita"
                onclick="moverCarrossel('outrosEventos', 1)"
                aria-label="Próximos eventos"
            >

                <i class="bi bi-chevron-right"></i>

            </button>


        </div>

    </section>


</main>



<!-- =========================================================
     FOOTER
========================================================= -->

<?php require __DIR__ . '/rodape.php'; ?>



<!-- =========================================================
     SCRIPTS
========================================================= -->

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="assets/vendor/swiper/swiper-bundle.min.js"></script>

<script src="assets/js/main.js"></script>



<script>

/* =========================================================
   BANNER SWIPER
========================================================= */

const bannerSwiper = new Swiper(
    ".banner.swiper",
    {

        loop: true,

        autoplay: {

            delay: 4500,

            disableOnInteraction: false

        },

        pagination: {

            el: ".swiper-pagination",

            clickable: true

        },

        navigation: {

            nextEl: ".swiper-button-next",

            prevEl: ".swiper-button-prev"

        }

    }
);



/* =========================================================
   CARROSSEL DE EVENTOS
========================================================= */

function moverCarrossel(id, direcao) {

    const carrossel =
        document.getElementById(id);


    if (!carrossel) {

        return;

    }


    const distancia = 330;


    carrossel.scrollBy({

        left: distancia * direcao,

        behavior: "smooth"

    });

}



/* =========================================================
   API DE EVENTOS
========================================================= */

const API_EVENTOS = "api/eventos.php";



/* =========================================================
   ESCAPAR HTML
========================================================= */

function escaparHTML(valor) {

    const div =
        document.createElement("div");


    div.textContent =
        valor ?? "";


    return div.innerHTML;

}



/* =========================================================
   FORMATAR DATA
========================================================= */

function formatarData(data) {

    if (!data) {

        return "Data não informada";

    }


    const partes =
        String(data).split("-");


    if (partes.length !== 3) {

        return String(data);

    }


    return `${partes[2]}/${partes[1]}/${partes[0]}`;

}



/* =========================================================
   IMAGEM DO EVENTO
========================================================= */

function imagemEventoJS(imagem) {

    if (!imagem) {

        return "assets/img/banner_site_565x235px.png";

    }


    const valor =
        String(imagem);


    /*
     * Se a API mandar somente:
     *
     * imagem.jpg
     *
     * acrescenta assets/img/
     */

    if (
        !valor.includes("/") &&
        !valor.includes("\\") &&
        !valor.startsWith("http")
    ) {

        return "assets/img/" + valor;

    }


    return valor;

}



/* =========================================================
   CRIAR CARD DO EVENTO
========================================================= */

function criarCardEvento(evento) {


    const gratuito =
        Number(evento.gratuidade) === 1;


    const imagem =
        imagemEventoJS(evento.imagem_evento);


    const nome =
        evento.nome_evento ||
        "Evento sem nome";


    const cidade =
        evento.cidade_evento ||
        "Local não informado";


    const uf =
        evento.uf ||
        "";


    /*
     * ID do evento
     */

    const idEvento =
        evento.id_evento ||
        evento.num_evento ||
        "";


    return `

        <div class="card-evento">

            <a
                href="detalhesEvento.php?id_evento=${encodeURIComponent(idEvento)}"
            >


                <!-- BADGE -->

                <div
                    class="badge-evento ${gratuito ? "gratuito" : "pago"}"
                >

                    ${gratuito ? "Gratuito" : "Pago"}

                </div>


                <!-- IMAGEM -->

                <img
                    src="${escaparHTML(imagem)}"
                    alt="${escaparHTML(nome)}"
                    onerror="this.src='assets/img/banner_site_565x235px.png';"
                >


                <!-- CONTEÚDO -->

                <div class="card-conteudo">


                    <h4>

                        ${escaparHTML(nome)}

                    </h4>


                    <div class="info-evento">


                        <!-- LOCAL -->

                        <span>

                            <i class="bi bi-geo-alt-fill"></i>

                            ${escaparHTML(cidade)}

                            ${
                                uf
                                    ? " - " + escaparHTML(uf)
                                    : ""
                            }

                        </span>


                        <!-- DATA -->

                        <span>

                            <i class="bi bi-calendar-event"></i>

                            ${formatarData(evento.data_evento)}

                        </span>


                    </div>


                </div>


            </a>

        </div>

    `;

}



/* =========================================================
   MOSTRAR ESTADO DO CARROSSEL
========================================================= */

function mostrarEstado(id, icone, mensagem) {


    const container =
        document.getElementById(id);


    if (!container) {

        return;

    }


    container.innerHTML = `

        <div class="sem-eventos">

            <i class="bi ${icone}"></i>

            <p>

                ${escaparHTML(mensagem)}

            </p>

        </div>

    `;

}



/* =========================================================
   CARREGAR EVENTOS DA API
========================================================= */

async function carregarEventos() {


    try {


        const resposta =
            await fetch(
                API_EVENTOS,
                {
                    method: "GET",

                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


        /*
         * Verifica erro HTTP
         */

        if (!resposta.ok) {

            throw new Error(
                "Erro HTTP " + resposta.status
            );

        }


        /*
         * Converte resposta para JSON
         */

        const dados =
            await resposta.json();


        /*
         * Verifica se a API retornou
         * um array de eventos
         */

        const eventos =
            Array.isArray(dados.eventos)
                ? dados.eventos
                : [];


        /*
         * Primeiros 5 eventos
         * ficam em recomendados
         */

        const recomendados =
            eventos.slice(0, 5);


        /*
         * Restante dos eventos
         */

        const outrosEventos =
            eventos.slice(5);



        /* =================================================
           RECOMENDADOS
        ================================================= */

        const containerRecomendados =
            document.getElementById(
                "recomendados"
            );


        if (recomendados.length) {


            containerRecomendados.innerHTML =
                recomendados
                    .map(criarCardEvento)
                    .join("");


        } else {


            mostrarEstado(
                "recomendados",
                "bi-calendar-x",
                "Nenhum evento disponível no momento."
            );

        }



        /* =================================================
           OUTROS EVENTOS
        ================================================= */

        const containerOutros =
            document.getElementById(
                "outrosEventos"
            );


        if (outrosEventos.length) {


            containerOutros.innerHTML =
                outrosEventos
                    .map(criarCardEvento)
                    .join("");


        } else {


            mostrarEstado(
                "outrosEventos",
                "bi-calendar-x",
                "Nenhum outro evento cadastrado no momento."
            );

        }


    } catch (erro) {


        console.error(
            "Erro ao carregar eventos:",
            erro
        );


        /*
         * Mostra erro nos dois carrosséis
         */

        mostrarEstado(
            "recomendados",
            "bi-exclamation-triangle",
            "Não foi possível carregar os eventos."
        );


        mostrarEstado(
            "outrosEventos",
            "bi-exclamation-triangle",
            "Não foi possível carregar os eventos."
        );

    }

}



/* =========================================================
   INICIAR CARREGAMENTO
========================================================= */

carregarEventos();

</script>


</body>

</html>