<?php
require_once __DIR__ . '/config/verificaLogin.php';

$idUsuarioSessao = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evento - ShowMe</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="assets/css/detalhesEvento.css" rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body
    class="cabecalho-tipo-d"
    data-user-id="<?= $idUsuarioSessao ?>">
    <?php
    $tipoCabecalho = 'D';
    $configuracaoCabecalho = ['fallback' => 'inicio.php'];
    require __DIR__ . '/cabecalho.php';
    ?>

    <div class="pagina-wrapper">
        <div id="estadoPagina" class="estado-detalhes" role="status" aria-live="polite">
            <span class="spinner-border" aria-hidden="true"></span>
            <p>Carregando evento...</p>
        </div>

        <div id="conteudoEvento" hidden>
            <div class="banner-evento">
                <img id="imagemEvento" src="assets/img/bannerEventoPadrao.png" alt="">
            </div>

            <div class="container conteudo-principal">
                <span id="badgeGratuidade" class="badge-gratuidade"></span>
                <span id="badgeCategoria" class="badge-evento" hidden></span>
                <span id="badgeEncerrado" class="badge-evento-encerrado" hidden>Evento encerrado</span>
                <h1 id="tituloEvento" class="titulo-evento"></h1>

                <div class="infos-rapidas">
                    <span id="infoData"><i class="bi bi-calendar3" aria-hidden="true"></i><span></span></span>
                    <span id="infoCidade"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><span></span></span>
                    <span id="infoLocal"><i class="bi bi-building" aria-hidden="true"></i><span></span></span>
                </div>

                <div class="row mt-4 g-4">
                    <div class="col-lg-8">
                        <section class="secao">
                            <h2>Sobre o evento</h2>
                            <p id="descricaoEvento"></p>
                        </section>

                        <section class="secao">
                            <h2>Artistas e atrações</h2>
                            <div id="listaArtistas"></div>
                        </section>

                        <section class="secao">
                            <h2>Local</h2>
                            <div class="card-showme">
                                <h4 id="nomeLocal"></h4>
                                <p id="enderecoLocal"></p>
                                <div id="mapaEventoBanco" aria-label="Mapa do local do evento"></div>
                            </div>
                        </section>

                        <section class="secao comunidade-convite">
                            <div>
                                <h2>Converse com a comunidade</h2>
                                <p>Troque dicas, combine trajetos e veja fotos compartilhadas por outras pessoas.</p>
                            </div>
                            <a id="linkComunidade" href="comunidadeEvento.php" class="btn-acessar-comunidade">
                                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                Acessar comunidade
                            </a>
                        </section>
                    </div>

                    <div class="col-lg-4">
                        <div class="ingresso-card">
                            <h3>Ingressos</h3>
                            <p id="textoIngressos"></p>
                            <a id="linkIngressos" href="#" target="_blank" rel="noopener noreferrer" class="btn-comprar" hidden>
                                Comprar Ingressos
                            </a>
                            <small id="avisoIngressos" hidden>Confira os valores no canal oficial do evento.</small>
                        </div>

                        <button type="button" class="btn-acao btn-favoritar" aria-pressed="false">
                            <i class="bi bi-heart" aria-hidden="true"></i>
                            Favoritar
                        </button>

                        <a id="linkPlanejamento" href="planejamento.php" class="btn-acao btn-planejar">
                            <i class="bi bi-briefcase" aria-hidden="true"></i>
                            Planejar viagem
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/detalhesEvento.js"></script>
</body>

</html>
