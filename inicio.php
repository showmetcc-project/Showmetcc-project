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
         CSS DOS CARDS
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
         RECOMENDADOS
    ====================================================== -->

    <section class="eventos recomendados">
    <h3>Recomendados para Você</h3>
    <div class="carrossel-eventos">
        <button class="btn-carrossel esquerda" onclick="moverCarrossel('recomendados', -1)" aria-label="Eventos recomendados anteriores">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="carrossel-wrapper" id="recomendados" aria-live="polite">
            <div class="sem-eventos">
                <i class="bi bi-hourglass-split"></i>
                <p>Carregando eventos...</p>
            </div>
        </div>
        <button class="btn-carrossel direita" onclick="moverCarrossel('recomendados', 1)" aria-label="Próximos eventos recomendados">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</section>


                        <img
                            src="<?= e($imagem) ?>"
                            alt="<?= e($evento['nome_evento']) ?>"
                            onerror="this.src='assets/img/banner_site_565x235px.png';"
                        >


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


            <button
                class="btn-carrossel direita"
                onclick="moverCarrossel('recomendados', 1)"
            >

                <i class="bi bi-chevron-right"></i>

            </button>


        </div>


    </section>


    <?php endif; ?>



    <!-- =====================================================
         OUTROS EVENTOS
    ====================================================== -->

    <section class="eventos">
    <h3>Eventos Musicais</h3>
    <div class="carrossel-eventos">
        <button class="btn-carrossel esquerda" onclick="moverCarrossel('outrosEventos', -1)" aria-label="Eventos anteriores">
            <i class="bi bi-chevron-left"></i>
        </button>
        <div class="carrossel-wrapper" id="outrosEventos" aria-live="polite">
            <div class="sem-eventos">
                <i class="bi bi-hourglass-split"></i>
                <p>Carregando eventos...</p>
            </div>
        </div>
        <button class="btn-carrossel direita" onclick="moverCarrossel('outrosEventos', 1)" aria-label="Próximos eventos">
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
   BANNER
========================================================= */

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

                nextEl: ".swiper-button-next",

                prevEl: ".swiper-button-prev"

            }

        }
    );


/* =========================================================
   CARROSSEL DE EVENTOS
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


    const distancia =
        330;


    carrossel.scrollBy({

        left:
            distancia *
            direcao,

        behavior:
            "smooth"

    });

}



/* =========================================================
   EVENTOS VINDOS DA API
========================================================= */

const API_EVENTOS = "api/eventos.php";

function escaparHTML(valor) {
    const div = document.createElement("div");
    div.textContent = valor ?? "";
    return div.innerHTML;
}

function formatarData(data) {
    if (!data) return "Data não informada";
    const partes = String(data).split("-");
    if (partes.length !== 3) return String(data);
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}

function imagemEventoJS(imagem) {
    if (!imagem) return "assets/img/banner_site_565x235px.png";
    const valor = String(imagem);

    if (!valor.includes("/") &&
        !valor.includes("\\") &&
        !valor.startsWith("http")) {
        return "assets/img/" + valor;
    }
    return valor;
}

function criarCardEvento(evento) {
    const gratuito = Number(evento.gratuidade) === 1;
    const imagem = imagemEventoJS(evento.imagem_evento);
    const nome = evento.nome_evento || "Evento sem nome";
    const cidade = evento.cidade_evento || "Local não informado";
    const uf = evento.uf || "";

    return `
        <div class="card-evento">
            <a href="detalhesEvento.php?id_evento=${encodeURIComponent(evento.id_evento)}">
                <div class="badge-evento ${gratuito ? "gratuito" : "pago"}">
                    ${gratuito ? "Gratuito" : "Pago"}
                </div>
                <img src="${escaparHTML(imagem)}"
                     alt="${escaparHTML(nome)}"
                     onerror="this.src='assets/img/banner_site_565x235px.png';">
                <div class="card-conteudo">
                    <h4>${escaparHTML(nome)}</h4>
                    <div class="info-evento">
                        <span>
                            <i class="bi bi-geo-alt-fill"></i>
                            ${escaparHTML(cidade)}${uf ? " - " + escaparHTML(uf) : ""}
                        </span>
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

function mostrarEstado(id, icone, mensagem) {
    const container = document.getElementById(id);
    if (!container) return;

    container.innerHTML = `
        <div class="sem-eventos">
            <i class="bi ${icone}"></i>
            <p>${escaparHTML(mensagem)}</p>
        </div>
    `;
}

async function carregarEventos() {
    try {
        const resposta = await fetch(API_EVENTOS, {
            method: "GET",
            headers: { "Accept": "application/json" }
        });

        if (!resposta.ok) {
            throw new Error("Erro HTTP " + resposta.status);
        }

        const dados = await resposta.json();
        const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];

        const recomendados = eventos.slice(0, 5);
        const outrosEventos = eventos.slice(5);

        if (recomendados.length) {
            document.getElementById("recomendados").innerHTML =
                recomendados.map(criarCardEvento).join("");
        } else {
            mostrarEstado("recomendados", "bi-calendar-x", "Nenhum evento disponível no momento.");
        }

        if (outrosEventos.length) {
            document.getElementById("outrosEventos").innerHTML =
                outrosEventos.map(criarCardEvento).join("");
        } else {
            mostrarEstado("outrosEventos", "bi-calendar-x", "Nenhum outro evento cadastrado no momento.");
        }
    } catch (erro) {
        console.error("Erro ao carregar eventos:", erro);
        mostrarEstado("recomendados", "bi-exclamation-triangle", "Não foi possível carregar os eventos.");
        mostrarEstado("outrosEventos", "bi-exclamation-triangle", "Não foi possível carregar os eventos.");
    }
}

carregarEventos();

</script>


</body>

</html>

<?php

$conn->close();

?>
