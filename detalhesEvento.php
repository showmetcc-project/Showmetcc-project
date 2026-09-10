<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$idUsuarioSessao = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
$usuarioAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
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
        href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/detalhesEvento.css" rel="stylesheet">

    <style>
        .banner-evento { width: 100%; max-height: 420px; overflow: hidden; position: relative; }
        .banner-evento img { width: 100%; height: 420px; object-fit: cover; display: block; }
        .badge-gratuidade { display: inline-block; padding: 7px 15px; border-radius: 20px; font-size: 14px; font-weight: 700; margin-bottom: 12px; }
        .badge-gratuidade.gratuito { background: #a8ff00; color: #111; }
        .badge-gratuidade.pago { background: #ff4f9a; color: #fff; }
        #mapaEventoBanco { width: 100%; height: 300px; border-radius: 14px; overflow: hidden; margin-top: 20px; }
        .avaliacao-item { padding: 18px; margin-bottom: 15px; border-radius: 12px; background: rgba(255, 255, 255, .04); }
        .avaliacao-topo { display: flex; justify-content: space-between; align-items: flex-start; gap: 15px; }
        .avaliacao-corpo { flex: 1; min-width: 0; }
        .estrelas-exibir { white-space: nowrap; font-size: 18px; }
        .artista-item { margin-bottom: 18px; }
        .artista-item:last-child { margin-bottom: 0; }
        .imagem-artista { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; margin-right: 15px; }
        .estado-detalhes { min-height: 45vh; padding: 90px 20px; text-align: center; color: #d8d8d8; }
        .estado-detalhes.erro { color: #ff8cab; }
        .midias-avaliacao { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px; margin-top: 14px; }
        .midia-avaliacao { width: 100%; height: 150px; border-radius: 10px; background: #080808; object-fit: cover; }
        .acoes-avaliacao { display: flex; gap: 10px; margin-top: 12px; }
        .acoes-avaliacao a, .acoes-avaliacao button { padding: 6px 12px; border: 1px solid #39ff14; border-radius: 8px; background: transparent; color: #39ff14; font-size: .82rem; }
        .acoes-avaliacao button { border-color: #ff4f9a; color: #ff4f9a; }
        @media (max-width: 768px) { .avaliacao-topo { flex-wrap: wrap; } }
    </style>
</head>

<body
    class="com-cabecalho-padrao"
    data-user-id="<?= $idUsuarioSessao ?>"
    data-user-admin="<?= $usuarioAdmin ? 'true' : 'false' ?>">
    <?php require __DIR__ . '/cabecalho.php'; ?>

    <div class="pagina-wrapper">
        <div class="container-fluid px-0">
            <a href="inicio.php" class="btn-voltar" aria-label="Voltar para a página inicial">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        </div>

        <div id="estadoPagina" class="estado-detalhes" role="status" aria-live="polite">
            <span class="spinner-border" aria-hidden="true"></span>
            <p>Carregando evento...</p>
        </div>

        <div id="conteudoEvento" hidden>
            <div class="banner-evento">
                <img id="imagemEvento" src="assets/img/banner_site_565x235px.png" alt="">
            </div>

            <div class="container conteudo-principal">
                <span id="badgeGratuidade" class="badge-gratuidade"></span>
                <span id="badgeCategoria" class="badge-evento" hidden></span>
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

                        <section class="secao" id="avaliacoes">
                            <h2>Avaliações</h2>
                            <div id="listaAvaliacoes" class="lista-avaliacoes" aria-live="polite">
                                <p>Carregando avaliações...</p>
                            </div>
                        </section>

                        <section class="secao avaliar-card">
                            <?php if ($idUsuarioSessao > 0): ?>
                                <div id="mensagemAvaliacao" class="alert d-none" role="alert"></div>
                                <form id="formAvaliacao" enctype="multipart/form-data" hidden>
                                    <h2>Avaliar este evento/local</h2>
                                    <input type="hidden" name="id_evento" id="idEventoAvaliacao">

                                    <label>Sua nota:</label>
                                    <div class="estrelas-input" id="estrelasInput">
                                        <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                                            <i
                                                class="bi bi-star estrela"
                                                data-valor="<?= $nota ?>"
                                                role="button"
                                                tabindex="0"
                                                aria-label="<?= $nota ?> estrela<?= $nota > 1 ? 's' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <input type="hidden" name="nota" id="nota" value="">

                                    <label for="comentario">Seu comentário:</label>
                                    <textarea
                                        name="comentario"
                                        id="comentario"
                                        maxlength="1000"
                                        placeholder="Conte a sua experiência aqui..."
                                        required></textarea>

                                    <label for="midiasAvaliacao">Fotos e vídeos:</label>
                                    <input
                                        type="file"
                                        name="midias[]"
                                        id="midiasAvaliacao"
                                        accept="image/jpeg,image/png,image/webp,video/mp4,video/webm"
                                        multiple
                                        required>
                                    <small>Envie de 1 a 5 arquivos: JPG, PNG, WebP, MP4 ou WebM.</small>

                                    <button type="submit" class="btn-enviar" id="btnEnviarAvaliacao">Enviar avaliação</button>
                                </form>
                            <?php else: ?>
                                <div class="login-avaliacao">
                                    <h2>Avaliar este evento/local</h2>
                                    <p>Você precisa estar logado para avaliar este evento.</p>
                                    <a href="login.php" class="btn-enviar">
                                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                                        Entrar para avaliar
                                    </a>
                                </div>
                            <?php endif; ?>
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

    <?php require __DIR__ . '/rodape.php'; ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="assets/js/detalhesEvento.js"></script>
</body>

</html>
