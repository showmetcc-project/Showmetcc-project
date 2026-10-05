<?php require_once __DIR__ . '/config/verificaLogin.php'; ?>
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

    <link href="assets/css/meusEventos.css" rel="stylesheet">
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
        <div class="abas" role="tablist" aria-label="Categorias dos meus eventos">
            <button class="aba ativa" data-tab="favoritos" role="tab" aria-selected="true" aria-controls="favoritos">
                <i class="bi bi-heart" aria-hidden="true"></i>
                <span class="aba-rotulo">Favoritos (<span id="contadorFavoritos">0</span>)</span>
            </button>

            <button class="aba" data-tab="planejados" role="tab" aria-selected="false" aria-controls="planejados">
                <i class="bi bi-map-fill" aria-hidden="true"></i>
                <span class="aba-rotulo">Planejados (<span id="contadorPlanejados">0</span>)</span>
            </button>

            <button class="aba" data-tab="calendario" role="tab" aria-selected="false" aria-controls="calendario">
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                <span class="aba-rotulo">Calendário</span>
            </button>
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

        <!-- CALENDÁRIO DOS PLANEJADOS -->
        <section id="calendario" class="conteudo calendario-conteudo" aria-label="Calendário de eventos planejados">
            <div class="calendario-layout">
                <div class="calendario-coluna-principal">
                    <div class="calendario-card">
                        <div class="calendario-cabecalho">
                            <button type="button" id="calendarioAnterior" class="calendario-navegacao" aria-label="Mês anterior">
                                <i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>

                            <h2 id="calendarioTitulo">Mês e ano</h2>

                            <button type="button" id="calendarioProximo" class="calendario-navegacao" aria-label="Próximo mês">
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>

                            <button type="button" id="calendarioHoje" class="calendario-hoje">Hoje</button>
                        </div>

                        <div class="calendario-semana" aria-hidden="true">
                            <span>DOM</span>
                            <span>SEG</span>
                            <span>TER</span>
                            <span>QUA</span>
                            <span>QUI</span>
                            <span>SEX</span>
                            <span>SÁB</span>
                        </div>

                        <div id="calendarioGrid" class="calendario-grid" role="grid" aria-labelledby="calendarioTitulo"></div>
                    </div>

                </div>

                <div class="calendario-coluna-lateral">
                    <aside class="resumo-mes-card" aria-labelledby="resumoMesTitulo">
                        <h2 id="resumoMesTitulo">Resumo do mês</h2>

                        <div class="calendario-legenda" aria-label="Legenda do calendário">
                            <span><i class="legenda-marca legenda-hoje"></i>Hoje</span>
                            <span><i class="legenda-marca legenda-selecionado"></i>Selecionado</span>
                            <span><i class="legenda-marca legenda-evento"></i>Com evento(s)</span>
                        </div>

                        <div id="resumoMesConteudo" class="resumo-mes-conteudo">
                            <p>Carregando planejamentos...</p>
                        </div>
                    </aside>

                    <div id="calendarioDetalhes" class="calendario-detalhes" aria-live="polite">
                        <p class="calendario-estado-neutro">Selecione um dia para ver seus planejamentos.</p>
                    </div>
                </div>
            </div>
        </section>

    </main>



    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/meusEventos.js"></script>
    <script>AOS.init();</script>

</body>

</html>
