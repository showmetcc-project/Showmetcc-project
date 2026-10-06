<?php require_once __DIR__ . '/config/verificaUsuarioComum.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Evento - ShowMe</title>

  <!-- Favicons -->
  <link href="assets/img/showme.png" rel="icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- CSS da página -->
  <link href="assets/css/cadastroEvento.css" rel="stylesheet">

  <!-- CSS compartilhado por último para preservar os cabeçalhos -->
  <link href="assets/css/main.css" rel="stylesheet">

</head>

<body class="com-cabecalho-padrao cabecalho-tipo-c pagina-cadastro-evento">

    <?php
    $tipoCabecalho = 'C';
    $configuracaoCabecalho = [
        'titulo_primario' => 'Cadastrar',
        'titulo_secundario' => 'Evento',
        'icone' => 'bi-calendar-plus',
        'fallback' => 'inicio.php',
    ];
    require __DIR__ . '/cabecalho.php';
    ?>

    <div class="container-fluid evento-container">

        <div class="evento-card">

            <p class="subtitulo">
                Preencha as informações abaixo. Nossa equipe analisará e aprovará seu evento.
            </p>

            <form
                id="formEvento"
                action="api/eventos/"
                method="post"
                enctype="multipart/form-data"
                novalidate>

                <div class="mb-3">
                    <label class="form-label" for="imagemEvento">
                        Foto do evento <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </label>
                    <div
                        id="dropzoneFotoEvento"
                        class="upload-area"
                        role="button"
                        tabindex="0"
                        aria-controls="imagemEvento"
                        aria-describedby="ajudaFotoEvento nomeFotoEvento">
                        <input
                            type="file"
                            id="imagemEvento"
                            name="foto"
                            accept="image/jpeg,image/png,image/webp">
                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                        <p>Clique ou arraste uma imagem aqui</p>
                        <small id="ajudaFotoEvento">PNG, JPG, WEBP — máx. 10 MB</small>
                        <strong id="nomeFotoEvento" class="nome-arquivo-upload" aria-live="polite"></strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nomeEvento">Nome do evento <span class="campo-obrigatorio" aria-hidden="true">*</span></label>

                    <input
                        id="nomeEvento"
                        name="nome_evento"
                        type="text"
                        class="form-control"
                        maxlength="100"
                        placeholder="Ex: Festival de Jazz 2026"
                        required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label" for="cepEvento">
                            CEP <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>
                        <input id="cepEvento" name="cep_evento" type="text" class="form-control"
                            maxlength="9" inputmode="numeric" autocomplete="postal-code"
                            placeholder="00000-000" required>
                    </div>

                    <div class="col-md-8 mb-3">
                        <label class="form-label" for="numeroEvento">Número</label>
                        <input id="numeroEvento" name="numero_endereco" type="text" class="form-control"
                            maxlength="20" placeholder="Ex: 1000 (opcional)">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="enderecoEvento">Endereço <span class="campo-obrigatorio" aria-hidden="true">*</span></label>
                    <input id="enderecoEvento" name="endereco_evento" type="text" class="form-control"
                        maxlength="255" placeholder="Preenchido automaticamente pelo CEP" readonly required>
                    <div id="statusCepEvento" class="form-text" aria-live="polite">
                        Digite o CEP para preencher o endereço automaticamente.
                    </div>
                </div>

                <input id="ruaEvento" name="rua_evento" type="hidden">
                <input id="cidadeEvento" name="cidade_evento" type="hidden">
                <input id="ufEvento" name="uf" type="hidden">

                <fieldset class="mb-3 categorias-evento" id="seletorCategoriasEvento">
                    <legend class="form-label">
                        Categorias <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </legend>
                    <button
                        type="button"
                        id="botaoCategoriasEvento"
                        class="campo-categorias"
                        aria-expanded="false"
                        aria-controls="painelCategoriasEvento"
                        aria-describedby="erroCategoriasEvento">
                        <span id="resumoCategoriasEvento" class="resumo-categorias">
                            <span class="placeholder-categorias">Selecione uma ou mais categorias</span>
                        </span>
                        <i class="bi bi-chevron-down seta-categorias" aria-hidden="true"></i>
                    </button>
                    <div id="painelCategoriasEvento" class="painel-categorias" hidden>
                        <p class="ajuda-categorias">Clique nas categorias que descrevem o evento.</p>
                        <div class="grade-categorias">
                            <?php
                            $categoriasEvento = [
                                ['valor' => 'Música', 'icone' => 'bi-music-note-beamed'],
                                ['valor' => 'Cinema', 'icone' => 'bi-film'],
                                ['valor' => 'Show Nacional', 'icone' => 'bi-mic'],
                                ['valor' => 'Show Internacional', 'icone' => 'bi-globe-americas'],
                                ['valor' => 'Workshop', 'icone' => 'bi-easel2'],
                                ['valor' => 'Oficina', 'icone' => 'bi-tools'],
                                ['valor' => 'Gastronômico', 'icone' => 'bi-cup-hot'],
                            ];
                            foreach ($categoriasEvento as $indice => $categoria):
                                $idCategoria = 'categoriaEvento' . $indice;
                            ?>
                                <label class="opcao-categoria" for="<?= $idCategoria ?>">
                                    <input id="<?= $idCategoria ?>" name="categoria_evento[]" type="checkbox"
                                        value="<?= htmlspecialchars($categoria['valor'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi <?= $categoria['icone'] ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($categoria['valor'], ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="erroCategoriasEvento" class="form-text erro" aria-live="polite"></div>
                </fieldset>

                <div class="row">

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="dataEvento">Data <span class="campo-obrigatorio" aria-hidden="true">*</span></label>

                    <input id="dataEvento" name="data_evento" type="date" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="horarioEvento">Horário <span class="campo-obrigatorio" aria-hidden="true">*</span></label>

                        <input id="horarioEvento" name="horario_evento" type="time" class="form-control" required>
                    </div>

                </div>

                <div class="mb-3">

                    <label class="form-label">
                        Tipo de evento <span class="campo-obrigatorio" aria-hidden="true">*</span>
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

                <div class="mb-3">
                    <label class="form-label" for="linkOficialEvento">Link oficial <span class="campo-obrigatorio" aria-hidden="true">*</span></label>
                    <input id="linkOficialEvento" name="link_oficial" type="url" class="form-control"
                        maxlength="255" placeholder="https://exemplo.com/evento" required>
                </div>

                <div id="camposValorIngresso" class="row campos-valor-ingresso" hidden>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="valorIngressoMinimo">
                            Valor mínimo estimado (R$) <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>
                        <input id="valorIngressoMinimo" name="valor_ingresso_minimo" type="number"
                            class="form-control" min="0.01" max="99999999.99" step="0.01"
                            placeholder="Ex: 80,00">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="valorIngressoMaximo">Valor máximo estimado (R$)</label>
                        <input id="valorIngressoMaximo" name="valor_ingresso_maximo" type="number"
                            class="form-control" min="0.01" max="99999999.99" step="0.01"
                            placeholder="Ex: 250,00 (opcional)">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nomeArtistaSolicitado">Nome do artista / atração <span class="campo-obrigatorio" aria-hidden="true">*</span></label>
                    <input id="nomeArtistaSolicitado" name="nome_artista_solicitado" type="text"
                        class="form-control" maxlength="150" placeholder="Ex: Banda Exemplo" required>
                </div>

                <div class="mb-5">
                    <label class="form-label" for="descricaoEvento">
                        Descrição do evento <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </label>

                    <textarea id="descricaoEvento" name="descricao_evento" maxlength="1000" class="form-control descricao-grande"
                        placeholder="Conte sobre o evento: programação, atrações, experiências..." required></textarea>
                </div>

                <div class="mb-5">      
                    <label class="form-label" for="descricaoArtista">
                        Descrição do artista / atração <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </label>

                    <textarea id="descricaoArtista" name="descricao_artista" maxlength="1000" class="form-control descricao-media"
                        placeholder="Quem são os artistas ou atrações principais? Biografia, estilo, destaque..." required></textarea>
                </div>

                <button type="submit" class="btn-enviar" id="botaoEnviarEvento">
                    Enviar para análise
                </button>

            </form>

        </div>

    </div>



    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/cadastroEvento.js"></script>


</body>

</html>
