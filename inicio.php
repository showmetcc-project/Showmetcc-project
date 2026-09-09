<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
   FUNÇÃO PARA DEFINIR A IMAGEM
========================================================= */

function imagemEvento($imagem)
{
    if (empty($imagem)) {
        return "assets/img/banner_site_565x235px.png";
    }

    /*
     * Se no banco estiver salvo apenas o nome:
     *
     * exemplo:
     * rock.png
     *
     * procura dentro de assets/img/
     */

    if (
        strpos($imagem, "/") === false &&
        strpos($imagem, "\\") === false &&
        strpos($imagem, "http") !== 0
    ) {
        return "assets/img/" . $imagem;
    }

    return $imagem;
}


/* =========================================================
   BUSCAR TODOS OS EVENTOS
========================================================= */

$sql = "
    SELECT
        id_evento,
        nome_evento,
        local_evento,
        cidade_evento,
        uf,
        data_evento,
        gratuidade,
        imagem_evento,
        categoria_evento
    FROM evento
    ORDER BY data_evento ASC
";

$result = $conn->query($sql);

if (!$result) {
    die("Erro ao buscar eventos: " . $conn->error);
}


/* =========================================================
   TRANSFORMAR RESULTADO EM ARRAY
========================================================= */

$eventos = [];

while ($evento = $result->fetch_assoc()) {
    $eventos[] = $evento;
}


/* =========================================================
   RECOMENDAÇÕES
=========================================================

   Aqui estamos simulando a recomendação usando:
   - categoria do evento
   - gênero
   - artistas

   Depois podemos ligar isso diretamente à tabela
   spotify + artista + artista_evento.
========================================================= */

$recomendados = [];


/*
 * Por enquanto, pegamos os primeiros eventos.
 * Isso mantém a página funcionando mesmo sem Spotify.
 *
 * Depois substituímos por uma consulta que compara:
 *
 * spotify.generos_preferidos
 * spotify.artistas_mais_tocados
 *
 * com:
 *
 * artista.nome_artista
 * artista.genero_artista
 */

foreach ($eventos as $evento) {

    if (count($recomendados) >= 5) {
        break;
    }

    $recomendados[] = $evento;
}


/* =========================================================
   SEPARAR OUTROS EVENTOS
========================================================= */

$outrosEventos = [];

foreach ($eventos as $evento) {

    $jaRecomendado = false;

    foreach ($recomendados as $rec) {

        if (
            $rec['id_evento'] ==
            $evento['id_evento']
        ) {
            $jaRecomendado = true;
            break;
        }
    }

    if (!$jaRecomendado) {
        $outrosEventos[] = $evento;
    }
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


        /* =====================================================
           IMAGEM
        ====================================================== */

        /* =====================================================
           CONTEÚDO
        ====================================================== */

        /* =====================================================
           INFORMAÇÕES
        ====================================================== */

        /* =====================================================
           BADGES
        ====================================================== */

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

    <?php if (!empty($recomendados)): ?>


    <section class="eventos recomendados">


        <h3>

            Recomendados para Você

        </h3>


        <div class="carrossel-eventos">


            <button
                class="btn-carrossel esquerda"
                onclick="moverCarrossel('recomendados', -1)"
            >

                <i class="bi bi-chevron-left"></i>

            </button>


            <div
                class="carrossel-wrapper"
                id="recomendados"
            >


                <?php foreach ($recomendados as $evento): ?>


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


                        <?php if ($gratuito): ?>


                        <div class="badge-evento gratuito">

                            Gratuito

                        </div>


                        <?php else: ?>


                        <div class="badge-evento pago">

                            Pago

                        </div>


                        <?php endif; ?>


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


        <h3>

            Eventos Musicais

        </h3>


        <?php if (!empty($outrosEventos)): ?>


        <div class="carrossel-eventos">


            <button
                class="btn-carrossel esquerda"
                onclick="moverCarrossel('outrosEventos', -1)"
            >

                <i class="bi bi-chevron-left"></i>

            </button>


            <div
                class="carrossel-wrapper"
                id="outrosEventos"
            >


                <?php foreach ($outrosEventos as $evento): ?>


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
                     href="detalhesEvento.php?id_evento=<?= (int)$evento['id_evento'] ?>">


                        <?php if ($gratuito): ?>


                        <div class="badge-evento gratuito">

                            Gratuito

                        </div>


                        <?php else: ?>


                        <div class="badge-evento pago">

                            Pago

                        </div>


                        <?php endif; ?>


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
                onclick="moverCarrossel('outrosEventos', 1)"
            >

                <i class="bi bi-chevron-right"></i>

            </button>


        </div>


        <?php else: ?>


        <div class="sem-eventos">

            <i class="bi bi-calendar-x"></i>

            <p>

                Nenhum outro evento cadastrado no momento.

            </p>

        </div>


        <?php endif; ?>


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


</script>


</body>

</html>

<?php

$conn->close();

?>
