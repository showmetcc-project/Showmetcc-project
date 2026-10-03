<?php
require_once __DIR__ . '/config/verificaLogin.php';
$idUsuarioSessao = (int) ($_SESSION['id_user'] ?? 0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidade do evento - ShowMe</title>
    <link href="assets/img/showme.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:wght@300;400;500;600;700;800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/comunidade.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="com-cabecalho-padrao cabecalho-tipo-c pagina-comunidade-evento" data-user-id="<?= $idUsuarioSessao ?>">
<?php
$tipoCabecalho = 'C';
$configuracaoCabecalho = [
    'titulo_primario' => 'Comunidade',
    'titulo_secundario' => 'do evento',
    'icone' => 'bi-people',
    'fallback' => 'comunidade.php',
];
require __DIR__ . '/cabecalho.php';
?>
<main class="container comunidade-evento-main">
    <div id="estadoComunidade" class="comunidade-estado" role="status">Carregando comunidade...</div>
    <div id="conteudoComunidade" hidden>
        <section class="comunidade-evento-hero">
            <img id="imagemComunidadeEvento" src="assets/img/bannerEventoPadrao.png" alt="">
            <div><p>Comunidade do evento</p><h2 id="nomeComunidadeEvento"></h2><span id="dataComunidadeEvento"></span></div>
        </section>

        <div class="comunidade-abas" role="tablist">
            <button type="button" class="ativa" data-aba-comunidade="publicacoes" role="tab" aria-selected="true"><i class="bi bi-chat-dots"></i> Publicações</button>
            <button type="button" data-aba-comunidade="galeria" role="tab" aria-selected="false"><i class="bi bi-images"></i> Galeria</button>
        </div>

        <section id="abaPublicacoes" class="comunidade-painel ativa">
            <div class="comunidade-barra-acoes">
                <div id="filtrosCategoria" class="comunidade-filtros" aria-label="Filtrar publicações">
                    <button type="button" class="ativo" data-categoria="">Todas</button>
                    <button type="button" data-categoria="Duvida">Dúvida</button>
                    <button type="button" data-categoria="Dica">Dica</button>
                    <button type="button" data-categoria="Transporte">Transporte</button>
                    <button type="button" data-categoria="Hospedagem">Hospedagem</button>
                    <button type="button" data-categoria="Companhia">Companhia</button>
                    <button type="button" data-categoria="Relato">Relato</button>
                </div>
                <button type="button" class="btn-showme btn-criar-post" data-bs-toggle="modal" data-bs-target="#modalPublicacao"><i class="bi bi-plus-lg"></i> Criar publicação</button>
            </div>
            <div id="feedComunidade" class="comunidade-feed" aria-live="polite"></div>
        </section>

        <section id="abaGaleria" class="comunidade-painel" hidden>
            <div class="comunidade-galeria-topo">
                <div><h2>Galeria da comunidade</h2><p>Veja e compartilhe imagens do local e do evento.</p></div>
                <button type="button" class="btn-showme btn-adicionar-midia" data-bs-toggle="modal" data-bs-target="#modalMidia"><i class="bi bi-image"></i> Adicionar mídia</button>
            </div>
            <div id="galeriaComunidade" class="comunidade-galeria" aria-live="polite"></div>
        </section>
    </div>
</main>

<div class="modal fade" id="modalPublicacao" tabindex="-1" aria-labelledby="modalPublicacaoTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content modal-comunidade">
    <div class="modal-header"><h2 id="modalPublicacaoTitulo" class="modal-title">Criar publicação</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <form id="formPublicacao"><div class="modal-body">
      <label for="categoriaPublicacao">Categoria</label>
      <select id="categoriaPublicacao" name="categoria" required><option value="">Selecione</option><option value="Duvida">Dúvida</option><option value="Dica">Dica</option><option value="Transporte">Transporte</option><option value="Hospedagem">Hospedagem</option><option value="Companhia">Companhia</option><option value="Relato">Relato</option></select>
      <label for="textoPublicacao">O que você quer compartilhar?</label>
      <textarea id="textoPublicacao" name="texto" rows="5" maxlength="5000" required></textarea>
      <p id="mensagemPublicacao" class="comunidade-feedback" aria-live="polite"></p>
    </div><div class="modal-footer"><button id="btnPublicar" class="btn-showme" type="submit" disabled>Publicar</button></div></form>
  </div></div>
</div>

<div class="modal fade" id="modalMidia" tabindex="-1" aria-labelledby="modalMidiaTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content modal-comunidade">
    <div class="modal-header"><h2 id="modalMidiaTitulo" class="modal-title">Adicionar foto</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
    <form id="formMidia"><div class="modal-body">
      <label class="comunidade-dropzone" for="arquivoMidia"><i class="bi bi-cloud-arrow-up"></i><strong>Escolher uma imagem</strong><span>JPG, PNG ou WebP — até 10 MB</span></label>
      <input id="arquivoMidia" name="midia" type="file" accept="image/jpeg,image/png,image/webp" required hidden>
      <p id="nomeArquivoMidia" class="arquivo-selecionado"></p>
      <label for="legendaMidia">Legenda (opcional)</label><input id="legendaMidia" name="legenda" maxlength="255">
      <label class="comunidade-checkbox"><input name="permitir_download" type="checkbox" value="1"> Permitir que outras pessoas baixem esta imagem</label>
      <p id="mensagemMidia" class="comunidade-feedback" aria-live="polite"></p>
    </div><div class="modal-footer"><button class="btn-showme" type="submit">Publicar foto</button></div></form>
  </div></div>
</div>

<dialog id="lightboxComunidade" class="lightbox-comunidade"><button type="button" data-fechar-lightbox aria-label="Fechar"><i class="bi bi-x-lg"></i></button><img alt=""><div><strong></strong><time></time><p></p><div class="lightbox-acoes"></div></div></dialog>

<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="assets/js/comunidadeEvento.js"></script>
</body>
</html>
