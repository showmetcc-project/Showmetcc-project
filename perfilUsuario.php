<?php
require_once __DIR__ . '/config/verificaUsuarioComum.php';
require_once __DIR__ . '/config/midiasPerfil.php';

$catalogoMidiasPerfil = catalogoMidiasPerfil();

function renderizarModalPersonalizacao(
    string $tipo,
    string $modalId,
    string $titulo,
    string $inputId,
    array $categorias
): void {
    $ehAvatar = $tipo === 'foto_perfil';
    ?>
    <div class="modal fade personalizacao-modal" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>" tabindex="-1" aria-labelledby="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>Titulo" aria-hidden="true" data-modal-midia="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title" id="<?= htmlspecialchars($modalId, ENT_QUOTES, 'UTF-8') ?>Titulo"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></h2>
                        <p>Envie uma imagem sua ou escolha uma das opções da ShowMe.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>

                <div class="modal-body">
                    <label class="dropzone-midia" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="dropzone-chamada">
                            <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                            <span>Escolher imagem dos meus arquivos</span>
                        </span>
                        <small>Até 10 MB</small>
                    </label>
                    <input type="file" id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>" data-input-upload-midia="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>" accept="image/jpeg,image/png,image/webp" hidden>

                    <div class="divisor-galeria"><span>OU EXPLORE A GALERIA</span></div>

                    <div class="galeria-midias" aria-label="Galeria de imagens prontas">
                        <?php foreach ($categorias as $categoria => $dadosCategoria): ?>
                            <section class="categoria-galeria">
                                <h3>
                                    <i class="bi <?= htmlspecialchars($dadosCategoria['icone'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                    <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <div class="grid-galeria <?= $ehAvatar ? 'grid-galeria-avatar' : 'grid-galeria-banner' ?>">
                                    <?php foreach ($dadosCategoria['imagens'] as $imagem): ?>
                                        <button
                                            type="button"
                                            class="opcao-galeria"
                                            data-galeria-midia="<?= htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8') ?>"
                                            data-caminho="<?= htmlspecialchars($imagem['caminho'], ENT_QUOTES, 'UTF-8') ?>"
                                            aria-label="Usar <?= htmlspecialchars($imagem['legenda'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                            <span class="moldura-galeria">
                                                <img src="<?= htmlspecialchars($imagem['caminho'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($imagem['legenda'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                                                <span class="estado-selecao"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                                            </span>
                                            <span class="legenda-galeria"><?= htmlspecialchars($imagem['legenda'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </section>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - ShowMe</title>

    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700;800&family=Jost:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet">

    <!-- CSS da página e, por último, o compartilhado do cabeçalho -->
    <link href="assets/css/usuario.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="com-cabecalho-padrao cabecalho-tipo-c">

    <?php
    $tipoCabecalho = 'C';
    $configuracaoCabecalho = [
        'titulo_primario' => 'Meu',
        'titulo_secundario' => 'Perfil',
        'fallback' => 'inicio.php',
        'acao' => 'logout',
    ];
    require __DIR__ . '/cabecalho.php';
    ?>

    
    <div class="banner-wrap">
        <button type="button" class="banner" id="perfilBanner" data-visualizar-midia="foto_banner" aria-label="Ampliar banner atual"></button>

        <button type="button" class="btn-alterar-banner" data-abrir-editor-midia="foto_banner" aria-label="Alterar banner" title="Alterar banner">
            <i class="bi bi-camera-fill" aria-hidden="true"></i>
        </button>

        <div class="avatar-area">
            <button type="button" class="avatar" id="perfilAvatar" data-visualizar-midia="foto_perfil" aria-label="Ampliar foto de perfil atual">
                <img id="perfilAvatarImagem" alt="Foto de perfil" hidden>
                <i class="bi bi-person" id="perfilAvatarPlaceholder" aria-hidden="true"></i>
            </button>
            <button type="button" class="btn-camera-avatar" data-abrir-editor-midia="foto_perfil" aria-label="Alterar foto de perfil">
                <i class="bi bi-camera-fill" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <main class="perfil-container">

 
        <div class="perfil-header">
            <div class="email-principal" id="perfilEmailPrincipal">Carregando...</div>
            <button type="button" class="btn-editar" id="btnEditarPerfil" aria-pressed="false">
                <i class="bi bi-pencil" aria-hidden="true"></i>
                <span>Editar Perfil</span>
            </button>
        </div>

     
        <form
            class="perfil-form row g-4"
            id="perfilForm"
            data-endpoint="api/usuarios/<?= (int) $_SESSION['id_user'] ?>"
        >

            <div class="grupo col-12 col-md-6">
                <label>Nome</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input id="perfilNome" type="text" value="" maxlength="100" required readonly>
                </div>
            </div>

            <div class="grupo col-12 col-md-6">
                <label>Sobrenome</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input id="perfilSobrenome" type="text" value="" maxlength="100" required readonly>
                </div>
            </div>

            <div class="grupo full col-12">
                <label>E-mail</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input id="perfilEmail" type="email" value="" maxlength="100" required readonly>
                </div>
            </div>

            <p id="perfilFeedback" class="perfil-feedback col-12" role="status" aria-live="polite" hidden></p>

        </form>

        <!-- ESTATÍSTICAS -->
        <section class="integracao-agenda" aria-labelledby="tituloGoogleAgenda">
            <div class="integracao-agenda-icone" aria-hidden="true">
                <i class="bi bi-calendar2-check"></i>
            </div>
            <div class="integracao-agenda-conteudo">
                <h2 id="tituloGoogleAgenda">Google Agenda</h2>
                <p id="googleAgendaStatus">Verificando conexão...</p>
            </div>
            <div class="integracao-agenda-acoes">
                <a class="btn-conectar-agenda" id="btnConectarGoogleAgenda" href="googleCalendarConectar.php?retorno=perfilUsuario.php" hidden>
                    <i class="bi bi-google" aria-hidden="true"></i>
                    Conectar Google Agenda
                </a>
                <button class="btn-desconectar-agenda" id="btnDesconectarGoogleAgenda" type="button" hidden>Desconectar</button>
            </div>
        </section>

        <section class="estatisticas">

            <h2>Estatísticas</h2>

            <div class="stats-grid row g-4">

                <div class="col-12 col-md-4">
                    <div class="stat-card h-100">
                        <i class="bi bi-plus-lg stat-icon"></i>
                        <h3 data-estatistica="eventos_cadastrados" aria-live="polite">&mdash;</h3>
                        <p>Eventos cadastrados</p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="stat-card h-100">
                        <i class="bi bi-map-fill stat-icon"></i>
                        <h3 data-estatistica="viagens_planejadas" aria-live="polite">&mdash;</h3>
                        <p>Viagens planejadas</p>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <div class="stat-card h-100">
                        <i class="bi bi-chat-square-text-fill stat-icon"></i>
                        <h3 data-estatistica="publicacoes_comunidade" aria-live="polite">&mdash;</h3>
                        <p>Publicações na Comunidade</p>
                    </div>
                </div>

            </div>

        </section>

    </main>

    <?php
    renderizarModalPersonalizacao(
        'foto_perfil',
        'modalPersonalizarPerfil',
        'Personalize sua foto de perfil',
        'inputFotoPerfil',
        $catalogoMidiasPerfil['foto_perfil']
    );
    renderizarModalPersonalizacao(
        'foto_banner',
        'modalPersonalizarBanner',
        'Personalize seu banner',
        'inputFotoBanner',
        $catalogoMidiasPerfil['foto_banner']
    );
    ?>

    <div class="lightbox-midia" id="lightboxMidia" role="dialog" aria-modal="true" aria-labelledby="lightboxMidiaTitulo" hidden>
        <div class="lightbox-conteudo">
            <h2 id="lightboxMidiaTitulo" class="visually-hidden">Visualização da imagem</h2>
            <button type="button" class="lightbox-fechar" id="fecharLightboxMidia" aria-label="Fechar visualização"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            <img id="lightboxMidiaImagem" alt="Imagem atual do perfil" hidden>
            <div class="lightbox-fallback" id="lightboxMidiaFallback" hidden><i class="bi bi-person" aria-hidden="true"></i></div>
        </div>
    </div>

    <div class="recorte-overlay" id="recorteOverlay" role="dialog" aria-modal="true" aria-labelledby="recorteTitulo" hidden>
        <div class="recorte-dialogo">
            <div class="recorte-cabecalho">
                <h2 id="recorteTitulo">Ajustar imagem</h2>
                <button type="button" id="cancelarRecorteTopo" aria-label="Fechar recorte"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <div class="recorte-area">
                <img id="recorteImagem" alt="Imagem a ser recortada">
            </div>
            <div class="recorte-acoes">
                <button type="button" class="btn-secundario-perfil" id="cancelarRecorte">Cancelar</button>
                <button type="button" class="btn-salvar-perfil" id="confirmarRecorte">Usar este recorte</button>
            </div>
        </div>
    </div>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS (mesmo padrão do index) -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="assets/js/perfil.js"></script>

    <script>AOS.init();</script>

</body>

</html>
