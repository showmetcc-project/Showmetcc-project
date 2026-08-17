<?php
session_start();
require_once __DIR__ . '/php/conexao.php';

$id_evento = filter_input(INPUT_GET, 'id_evento', FILTER_VALIDATE_INT);
if (!$id_evento) { die('Evento não informado.'); }

// Evento
$sql = "SELECT id_evento, nome_evento, descricao_evento, data_evento, cidade_evento, uf,
               rua_evento, local_evento, categoria_evento, link_ofcial
        FROM evento WHERE id_evento = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id_evento);
$stmt->execute();
$eventoResult = $stmt->get_result();
$evento = $eventoResult->fetch_assoc();
$stmt->close();
if (!$evento) { die('Evento não encontrado.'); }

// Artistas relacionados
$artistas = [];
$sql = "SELECT a.nome_artista, a.genero_artista
        FROM artista a
        INNER JOIN artista_evento ae ON ae.id_artista = a.id_artista
        WHERE ae.id_evento = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $id_evento);
    $stmt->execute();
    $r = $stmt->get_result();
    while ($row = $r->fetch_assoc()) $artistas[] = $row;
    $stmt->close();
}

/$sql = "SELECT 
            av.id_avaliacao,
            av.id_user,
            av.nota,
            av.comentario,
            av.data_avaliacao,
            COALESCE(
                NULLIF(
                    TRIM(
                        CONCAT(
                            COALESCE(u.nome_user,''),
                            ' ',
                            COALESCE(u.sobrenome,'')
                        )
                    ),
                    ''
                ),
                COALESCE(u.apelido_user, 'Usuário')
            ) AS nome_usuario
        FROM avaliacao av
        INNER JOIN usuario u ON u.id_user = av.id_user
        WHERE av.id_evento = ?
        ORDER BY av.data_avaliacao DESC";

$id_user = $_SESSION['id_user'] ?? ($_SESSION['usuario']['id_user'] ?? null);
?>
    <!doctype html>
    <html lang="pt-BR">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>
            <?= htmlspecialchars($evento["nome_evento"]) ?> - ShowMe</title>

        <!-- Favicons -->
        <link href="assets/img/showme.png" rel="icon" />

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com" rel="preconnect" />
        <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin />
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Vendor CSS -->
        <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet" />
        <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" />
        <link href="assets/vendor/aos/aos.css" rel="stylesheet" />

        <!-- CSS customizado -->
        <link rel="stylesheet" href="assets/css/detalhesEvento.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <style>
            #mapaEventoBanco {
                width: 100%;
                height: 300px;
                border-radius: 12px;
                overflow: hidden;
                margin-top: 15px
            }
        </style>
    </head>

    <body>
        <div class="pagina-wrapper">
            <!-- BOTÃO VOLTAR -->
            <div class="container-fluid px-0">
                <a href="inicio.html" class="btn-voltar">
                    <i class="bi bi-arrow-left"></i>
                </a>
            </div>

            <!-- BANNER -->
            <div class="banner-evento">
                <img src="img/harry-banner.jpg" alt="Harry Styles: Together, Together" />
            </div>

            <div class="container conteudo-principal">
                <!-- BADGE + TÍTULO + INFOS -->
                <span class="badge-evento"><?= htmlspecialchars($evento["categoria_evento"] ?: "Evento") ?></span>

                <h1 class="titulo-evento">
                    <?= htmlspecialchars($evento["nome_evento"]) ?>
                </h1>

                <div class="infos-rapidas">
                    <span><i class="bi bi-calendar3"></i> <?= date('d/m/Y', strtotime($evento['data_evento'])) ?></span>
                    <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($evento['cidade_evento']) ?> - <?= htmlspecialchars($evento['uf']) ?></span>
                    <span><i class="bi bi-tag"></i> <?= htmlspecialchars($evento['categoria_evento']) ?></span>
                </div>

                <!-- CONTEÚDO EM DUAS COLUNAS -->
                <div class="row mt-4 g-4">
                    <!-- COLUNA ESQUERDA -->
                    <div class="col-lg-8">
                        <!-- Sobre o evento -->
                        <section class="secao">
                            <h2>Sobre o evento</h2>
                            <p>
                                <?= nl2br(htmlspecialchars($evento['descricao_evento'] ?? 'Sem descrição cadastrada.')) ?>
                            </p>
                        </section>

                        <!-- Artista -->
                        <section class="secao">
                            <h2>Artista</h2>
                            <div class="card-showme">
                                <?php if ($artistas): ?>
                                <?php foreach ($artistas as $artista): ?>
                                <h4>
                                    <?= htmlspecialchars($artista['nome_artista']) ?>
                                </h4>
                                <?php if (!empty($artista['genero_artista'])): ?>
                                <p>
                                    <?= htmlspecialchars($artista['genero_artista']) ?>
                                </p>
                                <?php endif; ?>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <h4>Artista não informado</h4>
                                <p>Este evento ainda não possui artista relacionado no banco.</p>
                                <?php endif; ?>
                            </div>
                        </section>

                        <!-- Local -->
                        <section class="secao">
                            <h2>Local</h2>
                            <div class="card-showme">
                                <h4>
                                    <?= htmlspecialchars($evento['local_evento']) ?>
                                </h4>
                                <p>
                                    <?= htmlspecialchars($evento['rua_evento']) ?><br />
                                        <?= htmlspecialchars($evento['cidade_evento']) ?> –
                                            <?= htmlspecialchars($evento['uf']) ?>
                                </p>
                                <div class="mapa-placeholder">
                                    <i class="bi bi-geo-alt-fill"></i>
                                    <span>Mapa interativo do local</span>
                                </div>
                            </div>
                        </section>

                        <<!-- Avaliações -->
                            <section class="secao" id="avaliacoes">
                                <h2>Avaliações</h2>

                                <div class="lista-avaliacoes" id="listaAvaliacoes">

                                    <?php if ($avaliacoes): ?>

                                    <?php foreach ($avaliacoes as $avaliacao): ?>

                                    <div class="avaliacao-item">

                                        <div style="width: 100%;">

                                            <div style="
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            gap: 15px;
                        ">

                                                <div>
                                                    <strong>
                                    <?= htmlspecialchars($avaliacao['nome_usuario']) ?>
                                </strong>

                                                    <p>
                                                        <?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?>
                                                    </p>

                                                    <small>
                                    <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                </small>
                                                </div>

                                                <div class="estrelas-exibir">
                                                    <?= str_repeat('★', (int)$avaliacao['nota']) .
                                    str_repeat('☆', 5 - (int)$avaliacao['nota']) ?>
                                                </div>

                                            </div>


                                            <?php
                        
                        ?>

                                        </div>

                                    </div>

                                    <?php endforeach; ?>

                                    <?php else: ?>

                                    <p>Nenhuma avaliação cadastrada para este evento.</p>

                                    <?php endif; ?>

                                </div>
                            </section>
                            <!-- Formulário de avaliação -->
                            <section class="secao avaliar-card">

                                <?php if ($id_user): ?>

                                <form action="./php/avaliacoes.php" method="POST" enctype="multipart/form-data" id="formAvaliacao">

                                    <h2>Avaliar este evento/local</h2>

                                    <!-- IDs necessários para o PHP -->
                                    <input type="hidden" name="id_user" value="<?= htmlspecialchars((string)$id_user) ?>" />

                                    <input type="hidden" name="id_evento" value="<?= (int)$id_evento ?>" />


                                    <!-- Nota -->
                                    <label>Sua nota:</label>

                                    <div class="estrelas-input" id="estrelasInput">

                                        <i class="bi bi-star estrela" data-valor="1"></i>

                                        <i class="bi bi-star estrela" data-valor="2"></i>

                                        <i class="bi bi-star estrela" data-valor="3"></i>

                                        <i class="bi bi-star estrela" data-valor="4"></i>

                                        <i class="bi bi-star estrela" data-valor="5"></i>

                                    </div>


                                    <!-- Nota enviada para o PHP -->
                                    <input type="hidden" name="nota" id="nota" value="" />


                                    <!-- Comentário -->
                                    <label>Seu comentário:</label>

                                    <textarea name="comentario" id="comentario" placeholder="Conte a sua experiência aqui..." required></textarea>


                                    <!-- Fotos e vídeos -->
                                    <label>Suas fotos e vídeos:</label>

                                    <label class="upload-area" for="uploadFotos">

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
                />

            </label>


                                    <button type="submit" class="btn-enviar" <?=! $id_user ? 'disabled' : '' ?>

    Enviar avaliação
</button>

                                </form>


                                <?php else: ?>

                                <!-- Usuário não logado -->

                                <div class="login-avaliacao">

                                    <h2>Avaliar este evento/local</h2>

                                    <p>
                                        Você precisa estar logado para avaliar este evento.
                                    </p>

                                    <a href="login.html" class="btn-enviar">
                                        <i class="bi bi-box-arrow-in-right"></i> Entrar para avaliar
                                    </a>

                                </div>

                                <?php endif; ?>

                            </section>
                            <script>
                                const campoNota = document.getElementById("nota");

                                estrelas.forEach((estrela) => {
                                    estrela.addEventListener("click", function() {
                                        notaAtual = parseInt(this.dataset.valor);

                                        campoNota.value = notaAtual;

                                        estrelas.forEach((item) => {
                                            const valor = parseInt(item.dataset.valor);

                                            if (valor <= notaAtual) {
                                                item.classList.remove("bi-star");

                                                item.classList.add("bi-star-fill");
                                            } else {
                                                item.classList.remove("bi-star-fill");

                                                item.classList.add("bi-star");
                                            }
                                        });
                                    });
                                });
                            </script>
                    </div>

                    <!-- COLUNA DIREITA -->
                    <div class="col-lg-4">
                        <!-- Card ingressos -->
                        <div class="ingresso-card">
                            <h3>Ingressos</h3>
                            <p>Adquira seus ingressos através dos canais oficiais:</p>
                            <button class="btn-comprar">
                <a
                  href="<?= htmlspecialchars($evento['link_ofcial'] ?: '#') ?>"
                  target="_blank"
                  >Comprar Ingressos</a
                >
              </button>
                            <small>Confira os valores no canal oficial do evento.</small>
                        </div>

                        <!-- Botões de ação -->
                        <button class="btn-acao btn-favoritar">
              <i class="bi bi-heart"></i>
              Favoritar
            </button>

                        <button class="btn-acao btn-planejar">
              <i class="bi bi-briefcase"></i
              ><a href="planejamento.html"> Planejar viagem</a>
            </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- FOOTER (reutilizado do index) -->
        <footer id="footer" class="footer">
            <div class="footer-line"></div>

            <div class="container footer-top">
                <div class="row gy-4">
                    <div class="col-lg-4 col-md-6 footer-about">
                        <h4 class="logo-footer">
                            <span class="verde">Show</span><span class="rosa">Me</span>
                        </h4>

                        <p>Democratizando o acesso à cultura desde 2026.</p>

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
                        <h4>Entre em Contato</h4>

                        <form class="footer-contact-form">
                            <input type="email" placeholder="Seu e-mail" />

                            <textarea placeholder="Sua mensagem"></textarea>

                            <button type="submit">
                <i class="bi bi-envelope"></i>
                Enviar
              </button>
                        </form>
                    </div>

                    <div class="col-lg-4 col-md-12 footer-links">
                        <h4>Informações</h4>

                        <ul>
                            <li><a href="#">Termos de Uso</a></li>
                            <li><a href="#">Política de Privacidade</a></li>
                        </ul>

                        <p class="copyright-text">
                            © 2026 ShowMe. Todos os direitos reservados.
                        </p>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Scroll Top -->
        <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
            <i class="bi bi-arrow-up-short"></i>
        </a>

        <!-- Vendor JS -->
        <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
        <script src="assets/vendor/aos/aos.js"></script>
        <script>
            AOS.init();

            // Estrelas clicáveis
            const estrelas = document.querySelectorAll(".estrela");
            estrelas.forEach((estrela) => {
                estrela.addEventListener("mouseover", () =>
                    iluminar(+estrela.dataset.valor),
                );
                estrela.addEventListener("mouseout", () => iluminar(notaAtual));
                estrela.addEventListener("click", () => {
                    notaAtual = +estrela.dataset.valor;
                    iluminar(notaAtual);
                });
            });

            let notaAtual = 0;

            function iluminar(n) {
                estrelas.forEach((e) => {
                    const v = +e.dataset.valor;
                    e.classList.toggle("bi-star-fill", v <= n);
                    e.classList.toggle("bi-star", v > n);
                });
            }
        </script>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            const latEvento = <?= json_encode((float)$evento['latitude_evento']) ?>;
            const lngEvento = <?= json_encode((float)$evento['longitude_evento']) ?>;
            if (latEvento && lngEvento) {
                const mapaEvento = L.map('mapaEventoBanco').setView([latEvento, lngEvento], 15);
                L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                    attribution: '&copy; OpenStreetMap &copy; CARTO',
                    subdomains: 'abcd',
                    maxZoom: 20
                }).addTo(mapaEvento);
                L.marker([latEvento, lngEvento]).addTo(mapaEvento)
                    .bindPopup('<strong><?= htmlspecialchars($evento['
                        nome_evento '], ENT_QUOTES, '
                        UTF - 8 ') ?></strong>')
                    .openPopup();
            }
        </script>

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <script>
            /*
                                                                                                                                                                                                                         * MAPA DO EVENTO
                                                                                                                                                                                                                         * As coordenadas vêm diretamente da tabela evento:
                                                                                                                                                                                                                      
                                                                                                                                                                                                                         */

            const nomeEventoMapa = <?= json_encode($evento["nome_evento"], JSON_UNESCAPED_UNICODE) ?>;
            const localEventoMapa = <?= json_encode($evento["local_evento"], JSON_UNESCAPED_UNICODE) ?>;
            const cidadeEventoMapa = <?= json_encode(
        $evento["cidade_evento"] . " - " . $evento["uf"],
        JSON_UNESCAPED_UNICODE
    ) ?>;

            const mapaEvento = L.map("mapaEvento").setView(
                [latitudeEvento, longitudeEvento],
                15
            );

            L.tileLayer(
                "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png", {
                    attribution: "&copy; OpenStreetMap &copy; CARTO",
                    subdomains: "abcd",
                    maxZoom: 20
                }
            ).addTo(mapaEvento);

            const iconeEvento = L.divIcon({
                className: "pin-evento",
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
                iconSize: [40, 40],
                iconAnchor: [20, 40],
                popupAnchor: [0, -40]
            });


            <
            strong > $ {
                nomeEventoMapa
            } < /strong><br>
            $ {
                localEventoMapa
            } < br >
                $ {
                    cidadeEventoMapa
                }
            `)
                .openPopup();
        </script>

    </body>

    </html>