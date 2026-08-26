<?php

session_start();

require './assets/config/conexao.php';

/* =========================================================
   PEGAR ID DO EVENTO
========================================================= */

$id_evento = filter_input(INPUT_GET, 'id_evento', FILTER_VALIDATE_INT);

/*
 * Também aceita ?id= caso seus cards antigos estejam usando isso.
 */
if (!$id_evento) {
    $id_evento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
}

if (!$id_evento) {
    die("Evento não informado.");
}


/* =========================================================
   FUNÇÃO PARA ESCAPAR HTML
========================================================= */

function e($texto)
{
    return htmlspecialchars(
        $texto ?? '',
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   BUSCAR EVENTO
========================================================= */

$sql = "
    SELECT
        id_evento,
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
    WHERE id_evento = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar consulta do evento: " . $conn->error);
}

$stmt->bind_param("i", $id_evento);
$stmt->execute();

$resultado = $stmt->get_result();

$evento = $resultado->fetch_assoc();

$stmt->close();


if (!$evento) {
    die("Evento não encontrado.");
}


/* =========================================================
   BUSCAR ARTISTAS
========================================================= */

$artistas = [];

$sqlArtistas = "
    SELECT
        a.id_artista,
        a.nome_artista,
        a.genero_artista,
        a.imagem_artista
    FROM artista a

    INNER JOIN artista_evento ae
        ON ae.id_artista = a.id_artista

    WHERE ae.id_evento = ?

    ORDER BY a.nome_artista ASC
";

$stmt = $conn->prepare($sqlArtistas);

if ($stmt) {

    $stmt->bind_param("i", $id_evento);

    $stmt->execute();

    $resultadoArtistas = $stmt->get_result();

    while ($artista = $resultadoArtistas->fetch_assoc()) {

        $artistas[] = $artista;

    }

    $stmt->close();
}


/* =========================================================
   BUSCAR AVALIAÇÕES
========================================================= */

$avaliacoes = [];

$sqlAvaliacoes = "
    SELECT
        av.id_avaliacao,
        av.id_user,
        av.nota,
        av.comentario,
        av.data_avaliacao,

        CONCAT(
            COALESCE(u.nome_user, ''),
            ' ',
            COALESCE(u.sobrenome, '')
        ) AS nome_usuario

    FROM avaliacao av

    INNER JOIN usuario u
        ON u.id_user = av.id_user

    WHERE av.id_evento = ?

    ORDER BY av.data_avaliacao DESC
";

$stmt = $conn->prepare($sqlAvaliacoes);

if ($stmt) {

    $stmt->bind_param("i", $id_evento);

    $stmt->execute();

    $resultadoAvaliacoes = $stmt->get_result();

    while ($avaliacao = $resultadoAvaliacoes->fetch_assoc()) {

        $avaliacoes[] = $avaliacao;

    }

    $stmt->close();
}


/* =========================================================
   USUÁRIO LOGADO
========================================================= */

$id_user = $_SESSION['id_user']
    ?? ($_SESSION['usuario']['id_user'] ?? null);


/* =========================================================
   DATA
========================================================= */

$dataFormatada = "Data não informada";

if (!empty($evento['data_evento'])) {

    $timestamp = strtotime($evento['data_evento']);

    if ($timestamp !== false) {

        $dataFormatada = date(
            'd/m/Y',
            $timestamp
        );

    }
}


/* =========================================================
   GRATUITO / PAGO
========================================================= */

$gratuito = !empty($evento['gratuidade']);


/* =========================================================
   IMAGEM
========================================================= */

$imagemEvento = !empty($evento['imagem_evento'])
    ? $evento['imagem_evento']
    : 'assets/img/banner_site_565x235px.png';

?>

<!DOCTYPE html>

<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($evento['nome_evento']) ?> - ShowMe
    </title>


    <!-- FAVICON -->

    <link
        href="assets/img/showme.png"
        rel="icon"
    >


    <!-- GOOGLE FONTS -->

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
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- BOOTSTRAP -->

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


    <!-- LEAFLET -->

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >


    <!-- CSS DO PROJETO -->

    <link
        rel="stylesheet"
        href="assets/css/detalhesEvento.css"
    >


    <style>

        /* =====================================================
           BANNER DO EVENTO
        ===================================================== */

        .banner-evento {

            width: 100%;
            max-height: 420px;

            overflow: hidden;

            position: relative;

        }


        .banner-evento img {

            width: 100%;
            height: 420px;

            object-fit: cover;

            display: block;

        }


        /* =====================================================
           BADGE PAGO / GRATUITO
        ===================================================== */

        .badge-gratuidade {

            display: inline-block;

            padding: 7px 15px;

            border-radius: 20px;

            font-size: 14px;

            font-weight: 700;

            margin-bottom: 12px;

        }


        .badge-gratuidade.gratuito {

            background: #a8ff00;

            color: #111;

        }


        .badge-gratuidade.pago {

            background: #ff4f9a;

            color: white;

        }


        /* =====================================================
           MAPA
        ===================================================== */

        #mapaEventoBanco {

            width: 100%;

            height: 300px;

            border-radius: 14px;

            overflow: hidden;

            margin-top: 20px;

        }


        /* =====================================================
           AVALIAÇÕES
        ===================================================== */

        .avaliacao-item {

            padding: 18px;

            margin-bottom: 15px;

            border-radius: 12px;

            background: rgba(255,255,255,0.04);

        }


        .avaliacao-topo {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 15px;

        }


        .estrelas-exibir {

            white-space: nowrap;

            font-size: 18px;

        }


        /* =====================================================
           ARTISTAS
        ===================================================== */

        .artista-item {

            margin-bottom: 18px;

        }


        .artista-item:last-child {

            margin-bottom: 0;

        }


        /* =====================================================
           IMAGEM DO ARTISTA
        ===================================================== */

        .imagem-artista {

            width: 70px;

            height: 70px;

            object-fit: cover;

            border-radius: 50%;

            margin-right: 15px;

        }

    </style>

</head>


<body>


<div class="pagina-wrapper">


    <!-- =====================================================
         VOLTAR
    ===================================================== -->

    <div class="container-fluid px-0">

        <a
            href="inicio.php"
            class="btn-voltar"
        >

            <i class="bi bi-arrow-left"></i>

        </a>

    </div>


    <!-- =====================================================
         BANNER
    ===================================================== -->

    <div class="banner-evento">

        <img
            src="<?= e($imagemEvento) ?>"
            alt="<?= e($evento['nome_evento']) ?>"
            onerror="this.onerror=null; this.src='assets/img/banner_site_565x235px.png';"
        >

    </div>



    <!-- =====================================================
         CONTEÚDO
    ===================================================== -->

    <div class="container conteudo-principal">


        <!-- PAGO / GRATUITO -->

        <?php if ($gratuito): ?>

            <span class="badge-gratuidade gratuito">

                Gratuito

            </span>

        <?php else: ?>

            <span class="badge-gratuidade pago">

                Pago

            </span>

        <?php endif; ?>


        <!-- CATEGORIA -->

        <?php if (!empty($evento['categoria_evento'])): ?>

            <span class="badge-evento">

                <?= e($evento['categoria_evento']) ?>

            </span>

        <?php endif; ?>


        <!-- =================================================
             TÍTULO
        ================================================= -->

        <h1 class="titulo-evento">

            <?= e($evento['nome_evento']) ?>

        </h1>


        <!-- =================================================
             INFORMAÇÕES
        ================================================= -->

        <div class="infos-rapidas">


            <span>

                <i class="bi bi-calendar3"></i>

                <?= $dataFormatada ?>

            </span>


            <span>

                <i class="bi bi-geo-alt-fill"></i>

                <?= e($evento['cidade_evento']) ?>

                <?php if (!empty($evento['uf'])): ?>

                    -

                    <?= e($evento['uf']) ?>

                <?php endif; ?>

            </span>


            <?php if (!empty($evento['local_evento'])): ?>

                <span>

                    <i class="bi bi-building"></i>

                    <?= e($evento['local_evento']) ?>

                </span>

            <?php endif; ?>


        </div>



        <!-- =================================================
             DUAS COLUNAS
        ================================================= -->

        <div class="row mt-4 g-4">


            <!-- =================================================
                 COLUNA ESQUERDA
            ================================================= -->

            <div class="col-lg-8">


                <!-- SOBRE -->

                <section class="secao">

                    <h2>

                        Sobre o evento

                    </h2>


                    <?php if (!empty($evento['descricao_evento'])): ?>

                        <p>

                            <?= nl2br(
                                e($evento['descricao_evento'])
                            ) ?>

                        </p>

                    <?php else: ?>

                        <p>

                            Este evento ainda não possui
                            uma descrição cadastrada.

                        </p>

                    <?php endif; ?>

                </section>



                <!-- =================================================
                     ARTISTAS
                ================================================= -->

                <section class="secao">

                    <h2>

                        Artistas e atrações

                    </h2>


                    <?php if (!empty($artistas)): ?>


                        <?php foreach ($artistas as $artista): ?>


                            <div class="card-showme artista-item">


                                <div
                                    style="
                                        display:flex;
                                        align-items:center;
                                    "
                                >


                                    <?php if (!empty($artista['imagem_artista'])): ?>

                                        <img
                                            src="<?= e($artista['imagem_artista']) ?>"
                                            class="imagem-artista"
                                            alt="<?= e($artista['nome_artista']) ?>"
                                        >

                                    <?php endif; ?>


                                    <div>

                                        <h4>

                                            <?= e(
                                                $artista['nome_artista']
                                            ) ?>

                                        </h4>


                                        <?php if (!empty($artista['genero_artista'])): ?>

                                            <p>

                                                <?= e(
                                                    $artista['genero_artista']
                                                ) ?>

                                            </p>

                                        <?php endif; ?>

                                    </div>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="card-showme">

                            <h4>

                                Artista não informado

                            </h4>

                            <p>

                                Este evento ainda não possui
                                artista relacionado no banco.

                            </p>

                        </div>


                    <?php endif; ?>


                </section>



                <!-- =================================================
                     LOCAL
                ================================================= -->

                <section class="secao">

                    <h2>

                        Local

                    </h2>


                    <div class="card-showme">


                        <?php if (!empty($evento['local_evento'])): ?>

                            <h4>

                                <?= e(
                                    $evento['local_evento']
                                ) ?>

                            </h4>

                        <?php endif; ?>


                        <?php if (!empty($evento['rua_evento'])): ?>

                            <p>

                                <?= e(
                                    $evento['rua_evento']
                                ) ?>

                                <br>

                                <?= e(
                                    $evento['cidade_evento']
                                ) ?>

                                <?php if (!empty($evento['uf'])): ?>

                                    -

                                    <?= e(
                                        $evento['uf']
                                    ) ?>

                                <?php endif; ?>

                            </p>

                        <?php else: ?>

                            <p>

                                <?= e(
                                    $evento['cidade_evento']
                                ) ?>

                                <?php if (!empty($evento['uf'])): ?>

                                    -

                                    <?= e(
                                        $evento['uf']
                                    ) ?>

                                <?php endif; ?>

                            </p>

                        <?php endif; ?>


                        <!-- MAPA -->

                        <div id="mapaEventoBanco"></div>


                    </div>

                </section>



                <!-- =================================================
                     AVALIAÇÕES
                ================================================= -->

                <section
                    class="secao"
                    id="avaliacoes"
                >

                    <h2>

                        Avaliações

                    </h2>


                    <div
                        class="lista-avaliacoes"
                        id="listaAvaliacoes"
                    >


                        <?php if (!empty($avaliacoes)): ?>


                            <?php foreach ($avaliacoes as $avaliacao): ?>


                                <div class="avaliacao-item">


                                    <div class="avaliacao-topo">


                                        <div>


                                            <strong>

                                                <?= e(
                                                    trim(
                                                        $avaliacao['nome_usuario']
                                                    )
                                                ) ?: 'Usuário' ?>

                                            </strong>


                                            <p>

                                                <?= nl2br(
                                                    e(
                                                        $avaliacao['comentario']
                                                    )
                                                ) ?>

                                            </p>


                                            <?php if (!empty($avaliacao['data_avaliacao'])): ?>

                                                <small>

                                                    <?= date(
                                                        'd/m/Y',
                                                        strtotime(
                                                            $avaliacao['data_avaliacao']
                                                        )
                                                    ) ?>

                                                </small>

                                            <?php endif; ?>


                                        </div>


                                        <div class="estrelas-exibir">

                                            <?php

                                            $nota =
                                                max(
                                                    0,
                                                    min(
                                                        5,
                                                        (int)$avaliacao['nota']
                                                    )
                                                );

                                            echo str_repeat(
                                                '★',
                                                $nota
                                            );

                                            echo str_repeat(
                                                '☆',
                                                5 - $nota
                                            );

                                            ?>

                                        </div>


                                    </div>


                                </div>


                            <?php endforeach; ?>


                        <?php else: ?>


                            <p>

                                Nenhuma avaliação cadastrada
                                para este evento.

                            </p>


                        <?php endif; ?>


                    </div>


                </section>



                <!-- =================================================
                     FORMULÁRIO DE AVALIAÇÃO
                ================================================= -->

                <section class="secao avaliar-card">


                    <?php if ($id_user): ?>


                        <form
                            action="./php/avaliacoes.php"
                            method="POST"
                            enctype="multipart/form-data"
                            id="formAvaliacao"
                        >


                            <h2>

                                Avaliar este evento/local

                            </h2>


                            <!-- ID USUÁRIO -->

                            <input
                                type="hidden"
                                name="id_user"
                                value="<?= (int)$id_user ?>"
                            >


                            <!-- ID EVENTO -->

                            <input
                                type="hidden"
                                name="id_evento"
                                value="<?= (int)$id_evento ?>"
                            >


                            <!-- NOTA -->

                            <label>

                                Sua nota:

                            </label>


                            <div
                                class="estrelas-input"
                                id="estrelasInput"
                            >


                                <i
                                    class="bi bi-star estrela"
                                    data-valor="1"
                                ></i>


                                <i
                                    class="bi bi-star estrela"
                                    data-valor="2"
                                ></i>


                                <i
                                    class="bi bi-star estrela"
                                    data-valor="3"
                                ></i>


                                <i
                                    class="bi bi-star estrela"
                                    data-valor="4"
                                ></i>


                                <i
                                    class="bi bi-star estrela"
                                    data-valor="5"
                                ></i>


                            </div>


                            <input
                                type="hidden"
                                name="nota"
                                id="nota"
                                value=""
                            >


                            <!-- COMENTÁRIO -->

                            <label>

                                Seu comentário:

                            </label>


                            <textarea
                                name="comentario"
                                id="comentario"
                                placeholder="Conte a sua experiência aqui..."
                                required
                            ></textarea>


                            <!-- ARQUIVOS -->

                            <label>

                                Suas fotos e vídeos:

                            </label>


                            <label
                                class="upload-area"
                                for="uploadFotos"
                            >

                                <i class="bi bi-image"></i>


                                <span>

                                    Adicionar fotos e vídeos

                                </span>


                                <input
                                    type="file"
                                    id="uploadFotos"
                                    name="arquivos[]"
                                    accept="image/*,video/*"
                                    multiple
                                    hidden
                                >


                            </label>


                            <!-- ENVIAR -->

                            <button
                                type="submit"
                                class="btn-enviar"
                                id="btnEnviarAvaliacao"
                            >

                                Enviar avaliação

                            </button>


                        </form>


                    <?php else: ?>


                        <div class="login-avaliacao">


                            <h2>

                                Avaliar este evento/local

                            </h2>


                            <p>

                                Você precisa estar logado
                                para avaliar este evento.

                            </p>


                            <a
                                href="login.html"
                                class="btn-enviar"
                            >

                                <i class="bi bi-box-arrow-in-right"></i>

                                Entrar para avaliar

                            </a>


                        </div>


                    <?php endif; ?>


                </section>


            </div>



            <!-- =================================================
                 COLUNA DIREITA
            ================================================= -->

            <div class="col-lg-4">


                <!-- =================================================
                     INGRESSOS
                ================================================= -->

                <div class="ingresso-card">


                    <h3>

                        Ingressos

                    </h3>


                    <?php if ($gratuito): ?>


                        <p>

                            Este evento é gratuito.

                        </p>


                    <?php else: ?>


                        <p>

                            Este evento é pago.

                            Adquira seus ingressos
                            através dos canais oficiais.

                        </p>


                    <?php endif; ?>


                    <?php if (!empty($evento['link_oficial'])): ?>


                        <a
                            href="<?= e(
                                $evento['link_oficial']
                            ) ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="btn-comprar"
                        >

                            Comprar Ingressos

                        </a>


                        <small>

                            Confira os valores
                            no canal oficial do evento.

                        </small>


                    <?php else: ?>


                        <p>

                            Link oficial não informado.

                        </p>


                    <?php endif; ?>


                </div>



                <!-- =================================================
                     FAVORITAR
                ================================================= -->

                <button
                    type="button"
                    class="btn-acao btn-favoritar"
                >

                    <i class="bi bi-heart"></i>

                    Favoritar

                </button>



                <!-- =================================================
                     PLANEJAR
                ================================================= -->

                <a
                    href="planejamento.html?id_evento=<?= (int)$id_evento ?>"
                    class="btn-acao btn-planejar"
                    style="display:block;"
                >

                    <i class="bi bi-briefcase"></i>

                    Planejar viagem

                </a>


            </div>


        </div>


    </div>


</div>



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

                    Democratizando o acesso
                    à cultura desde 2026.

                </p>


                <div class="social-links">


                    <a href="https://www.instagram.com/showmetcc/">

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


                <form class="footer-contact-form">


                    <input
                        type="email"
                        placeholder="Seu e-mail"
                    >


                    <textarea
                        placeholder="Sua mensagem"
                    ></textarea>


                    <button type="submit">

                        <i class="bi bi-envelope"></i>

                        Enviar

                    </button>


                </form>


            </div>



            <div class="col-lg-4 col-md-12 footer-links">


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

                    © 2026 ShowMe.
                    Todos os direitos reservados.

                </p>


            </div>


        </div>


    </div>


</footer>



<!-- =========================================================
     JAVASCRIPT
========================================================= -->


<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script src="assets/vendor/aos/aos.js"></script>

<script>

    AOS.init();

</script>



<!-- LEAFLET -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<script>

    /* =========================================================
       ESTRELAS DE AVALIAÇÃO
    ========================================================= */

    const estrelas =
        document.querySelectorAll(".estrela");

    const campoNota =
        document.getElementById("nota");

    let notaAtual = 0;


    function iluminar(nota) {

        estrelas.forEach(function(estrela) {

            const valor =
                parseInt(
                    estrela.dataset.valor
                );

            if (valor <= nota) {

                estrela.classList.remove(
                    "bi-star"
                );

                estrela.classList.add(
                    "bi-star-fill"
                );

            } else {

                estrela.classList.remove(
                    "bi-star-fill"
                );

                estrela.classList.add(
                    "bi-star"
                );

            }

        });

    }


    estrelas.forEach(function(estrela) {


        estrela.addEventListener(
            "mouseover",
            function() {

                iluminar(
                    parseInt(
                        this.dataset.valor
                    )
                );

            }
        );


        estrela.addEventListener(
            "mouseout",
            function() {

                iluminar(notaAtual);

            }
        );


        estrela.addEventListener(
            "click",
            function() {

                notaAtual =
                    parseInt(
                        this.dataset.valor
                    );

                campoNota.value =
                    notaAtual;

                iluminar(notaAtual);

            }
        );


    });



    /* =========================================================
       MAPA
    ========================================================= */

    const mapaElemento =
        document.getElementById(
            "mapaEventoBanco"
        );


    /*
     * Por enquanto não temos latitude/longitude
     * no SELECT do evento.
     *
     * Então usamos o endereço para tentar
     * localizar o evento automaticamente.
     */

    const enderecoEvento =
        <?= json_encode(
            trim(
                ($evento['local_evento'] ?? '') .
                ', ' .
                ($evento['rua_evento'] ?? '') .
                ', ' .
                ($evento['cidade_evento'] ?? '') .
                ' - ' .
                ($evento['uf'] ?? '')
            ),
            JSON_UNESCAPED_UNICODE
        ) ?>;


    if (mapaElemento && enderecoEvento.trim() !== "") {


        fetch(
            "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" +
            encodeURIComponent(enderecoEvento)
        )

        .then(function(response) {

            return response.json();

        })

        .then(function(dados) {


            if (!dados || dados.length === 0) {

                mapaElemento.innerHTML =
                    "<div style='padding:20px;text-align:center;'>Localização não encontrada.</div>";

                return;

            }


            const latitude =
                parseFloat(
                    dados[0].lat
                );


            const longitude =
                parseFloat(
                    dados[0].lon
                );


            const mapa =
                L.map(
                    "mapaEventoBanco"
                ).setView(
                    [
                        latitude,
                        longitude
                    ],
                    15
                );


            L.tileLayer(
                "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",
                {

                    attribution:
                        "&copy; OpenStreetMap &copy; CARTO",

                    subdomains:
                        "abcd",

                    maxZoom:
                        20

                }
            ).addTo(mapa);


            const icone =
                L.divIcon({

                    className:
                        "pin-evento",

                    html: `
                        <div style="
                            width:40px;
                            height:40px;
                            display:flex;
                            align-items:center;
                            justify-content:center;
                            color:#a8ff00;
                            font-size:32px;
                            filter:
                                drop-shadow(0 0 5px #a8ff00)
                                drop-shadow(0 0 12px #a8ff00);
                        ">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                    `,

                    iconSize:
                        [40,40],

                    iconAnchor:
                        [20,40],

                    popupAnchor:
                        [0,-40]

                });


            L.marker(
                [
                    latitude,
                    longitude
                ],
                {
                    icon: icone
                }
            )

            .addTo(mapa)

            .bindPopup(
                `
                    <strong>
                        <?= e($evento['nome_evento']) ?>
                    </strong>
                    <br>
                    <?= e($evento['local_evento']) ?>
                    <br>
                    <?= e($evento['cidade_evento']) ?>
                    -
                    <?= e($evento['uf']) ?>
                `
            )

            .openPopup();


        })

        .catch(function(erro) {

            console.error(
                "Erro ao carregar mapa:",
                erro
            );

            mapaElemento.innerHTML =
                "<div style='padding:20px;text-align:center;'>Não foi possível carregar o mapa.</div>";

        });


    }

</script>


</body>

</html>


<?php

$conn->close();

?>