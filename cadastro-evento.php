<?php require_once __DIR__ . '/config/verifica_login.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Evento - ShowMe</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

  <!-- Favicons -->
  <link href="assets/img/showme.png" rel="icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Anton (títulos) + Oswald (parágrafos/labels) -->
  <link
    href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

  <link href="assets/css/main.css" rel="stylesheet">

  <!-- Principal CSS File (por último, para ter prioridade sobre o Bootstrap) -->
  <link href="assets/css/cadastro-evento.css" rel="stylesheet">

</head>

<body class="com-cabecalho-padrao">

    <?php require __DIR__ . '/cabecalho.php'; ?>

    <div class="container-fluid evento-container">

        <div class="evento-card">

            <h2>
                <span class="titulo-verde">Cadastrar</span>
                <span class="titulo-rosa">Evento</span>
            </h2>

            <p class="subtitulo">
                Preencha as informações abaixo. Nossa equipe analisará e aprovará seu evento.
            </p>

            <div id="mensagemEvento" class="alert d-none" role="alert"></div>

            <form
                id="formEvento"
                action="api/eventos/"
                method="post"
                enctype="multipart/form-data"
                novalidate>

                <div class="mb-3">
                    <label class="form-label" for="imagemEvento">Foto do evento</label>
                    <input
                        type="file"
                        id="imagemEvento"
                        name="foto"
                        class="form-control"
                        accept="image/jpeg,image/png,image/webp"
                        required
                    >
                    <div class="form-text">JPG, PNG ou WebP, com até 10 MB.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nomeEvento">Nome do evento</label>

                    <input
                        id="nomeEvento"
                        name="nome_evento"
                        type="text"
                        class="form-control"
                        maxlength="100"
                        placeholder="Ex: Festival de Jazz 2026"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="localEvento">Local</label>

                    <input
                        id="localEvento"
                        name="local_evento"
                        type="text"
                        class="form-control"
                        maxlength="255"
                        placeholder="Ex: Parque Villa-Lobos, São Paulo - SP">
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="dataEvento">Data</label>

                        <input id="dataEvento" name="data_evento" type="date" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="horarioEvento">Horário</label>

                        <input id="horarioEvento" name="horario_evento" type="time" class="form-control">
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Tipo de evento
                    </label>

                    <div class="tipo-ingresso">

                        <input type="radio" name="gratuidade" id="gratuito" value="true" checked>

                        <label for="gratuito" class="opcao">
                            Gratuito
                        </label>

                        <input type="radio" name="gratuidade" id="pago" value="false">

                        <label for="pago" class="opcao">
                            Pago
                        </label>

                    </div>

                </div>

                <div class="mb-5">
                    <label class="form-label" for="descricaoEvento">
                        Descrição do evento
                    </label>

                    <textarea id="descricaoEvento" name="descricao_evento" maxlength="1000" class="form-control descricao-grande"
                        placeholder="Conte sobre o evento: programação, atrações, experiências..."></textarea>
                </div>

                <div class="mb-5">      
                    <label class="form-label" for="descricaoArtista">
                        Descrição do artista / atração
                    </label>

                    <textarea id="descricaoArtista" name="descricao_artista" maxlength="1000" class="form-control descricao-media"
                        placeholder="Quem são os artistas ou atrações principais? Biografia, estilo, destaque..."></textarea>
                </div>

                <button type="submit" class="btn-enviar" id="botaoEnviarEvento">
                    Enviar para análise
                </button>

            </form>

        </div>

    </div>


<?php require __DIR__ . '/rodape.php'; ?>


    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/cadastroEvento.js"></script>


</body>

</html>
