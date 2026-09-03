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
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

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

  <!-- Principal CSS File (por último, para ter prioridade sobre o Bootstrap) -->
  <link href="assets/css/cadastro-evento.css" rel="stylesheet">

</head>

<body>

    <div class="top-bar">
        <a href="inicio.php" class="voltar">
            <i class="fa-solid fa-arrow-left"></i>
            Voltar
        </a>
    </div>

    <div class="linha-topo"></div>

    <div class="container-fluid evento-container">

        <div class="evento-card">

            <h2>
                <span class="titulo-verde">Cadastrar</span>
                <span class="titulo-rosa">Evento</span>
            </h2>

            <p class="subtitulo">
                Preencha as informações abaixo. Nossa equipe analisará e aprovará seu evento.
            </p>

            <form>

                <label class="form-label">Foto do evento</label>

                <label class="upload-area">

                    <input type="file" id="imagemEvento" accept="image/*">

                    <i class="fa-solid fa-arrow-up-from-bracket"></i>

                    <p id="texto-upload">
                        Clique ou arraste uma imagem aqui<br>
                        PNG, JPG, WEBP — máx. 10 MB
                    </p>

                </label>

                <div class="mb-3">
                    <label class="form-label">Nome do evento</label>

                    <input type="text" class="form-control" placeholder="Ex: Festival de Jazz 2026">
                </div>

                <div class="mb-3">
                    <label class="form-label">Local</label>

                    <input type="text" class="form-control" placeholder="Ex: Parque Villa-Lobos, São Paulo - SP">
                </div>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Data</label>

                        <input type="date" class="form-control">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Horário</label>

                        <input type="time" class="form-control">
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Tipo de evento
                    </label>

                    <div class="tipo-ingresso">

                        <input type="radio" name="ingresso" id="gratuito" checked>

                        <label for="gratuito" class="opcao">
                            Gratuito
                        </label>

                        <input type="radio" name="ingresso" id="pago">

                        <label for="pago" class="opcao">
                            Pago
                        </label>

                    </div>

                </div>

                <div class="mb-5">
                    <label class="form-label">
                        Descrição do evento
                    </label>

                    <textarea class="form-control descricao-grande"
                        placeholder="Conte sobre o evento: programação, atrações, experiências..."></textarea>
                </div>

                <div class="mb-5">      
                    <label class="form-label">
                        Descrição do artista / atração
                    </label>

                    <textarea class="form-control descricao-media"
                        placeholder="Quem são os artistas ou atrações principais? Biografia, estilo, destaque..."></textarea>
                </div>

                <button type="submit" class="btn-enviar">
                    Enviar para análise
                </button>

            </form>

        </div>

    </div>


<?php require __DIR__ . '/rodape.php'; ?>



    <script>

        const inputImagem = document.getElementById("imagemEvento");
        const textoUpload = document.getElementById("texto-upload");

        inputImagem.addEventListener("change", function () {

            if (this.files.length > 0) {

                textoUpload.innerHTML =
                    this.files[0].name;

            }

        });

</script>


</body>

</html>
