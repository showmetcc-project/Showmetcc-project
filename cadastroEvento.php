<?php
require_once __DIR__ . '/config/verificaUsuarioComum.php';
require_once __DIR__ . '/config/categoriasEvento.php';
require_once __DIR__ . '/config/classificacoesIndicativas.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastro de Evento - ShowMe</title>

  <link href="assets/img/showme.png" rel="icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

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
        'fallback' => 'index.php',
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
                    <label class="form-label" for="altImagemEvento">
                        Texto Alternativo da Imagem (Acessibilidade)
                    </label>
                    <input
                        id="altImagemEvento"
                        name="alt_imagem"
                        type="text"
                        class="form-control"
                        maxlength="255"
                        placeholder="Ex: Vocalista da banda cantando no palco sob iluminação azul"
                        aria-describedby="ajudaAltImagemEvento">
                    <div id="ajudaAltImagemEvento" class="form-text">
                        Descreva brevemente a imagem para pessoas com deficiência visual que utilizam leitores de tela.
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
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="cepEvento">
                            CEP <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>
                        <input id="cepEvento" name="cep_evento" type="text" class="form-control"
                            maxlength="9" inputmode="numeric" autocomplete="postal-code"
                            placeholder="00000-000" required>
                    </div>

                    <div class="col-md-6 mb-3">
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
                            $iconesCategorias = iconesCategoriasEvento();
                            $categoriasEvento = array_map(
                                static fn(string $categoria): array => [
                                    'valor' => $categoria,
                                    'icone' => $iconesCategorias[$categoria] ?? 'bi-tag',
                                ],
                                categoriasEventoOficiais(),
                            );
                            foreach ($categoriasEvento as $indice => $categoria):
                                $idCategoria = 'categoriaEvento' . $indice; ?>
                                <label class="opcao-categoria" for="<?= $idCategoria ?>">
                                    <input id="<?= $idCategoria ?>" name="categoria_evento[]" type="checkbox"
                                        value="<?= htmlspecialchars($categoria['valor'], ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="bi <?= $categoria['icone'] ?>" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($categoria['valor'], ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php
                            endforeach;
                            ?>
                        </div>
                    </div>
                    <div id="erroCategoriasEvento" class="form-text erro" aria-live="polite"></div>
                </fieldset>

                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label" for="classificacaoIndicativaEvento">
                            Classificação indicativa <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>

                        <select id="classificacaoIndicativaEvento" name="classificacao_indicativa"
                            class="form-select w-100" required>
                            <?php foreach (classificacoesIndicativasOficiais() as $classificacao): ?>
                                <option value="<?= htmlspecialchars($classificacao, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $classificacao === classificacaoIndicativaPadrao() ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($classificacao, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <fieldset class="mb-3 sessoes-evento" id="secaoSessoesEvento" aria-describedby="ajudaSessoesEvento">
                    <legend class="form-label">
                        Datas e horários <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </legend>
                    <p id="ajudaSessoesEvento" class="form-text">
                        Adicione uma ou mais sessões. Cada sessão precisa ter data, início e fim.
                    </p>

                    <div id="listaSessoesEvento" class="lista-sessoes-cadastro">
                        <div class="sessao-cadastro" data-sessao>
                            <div class="campo-sessao">
                                <label class="form-label" for="dataSessaoEvento0">Data</label>
                                <input id="dataSessaoEvento0" type="date" class="form-control"
                                    name="sessoes[0][data_evento]" data-campo="data_evento" required>
                            </div>

                            <div class="campo-sessao">
                                <label class="form-label" for="inicioSessaoEvento0">Início</label>
                                <input id="inicioSessaoEvento0" type="time" class="form-control"
                                    name="sessoes[0][hora_inicio]" data-campo="hora_inicio" step="60" required>
                            </div>

                            <div class="campo-sessao">
                                <label class="form-label" for="fimSessaoEvento0">Fim</label>
                                <input id="fimSessaoEvento0" type="time" class="form-control"
                                    name="sessoes[0][hora_fim]" data-campo="hora_fim" step="60" required>
                            </div>

                            <button type="button" class="botao-remover-sessao" data-remover-sessao
                                aria-label="Remover sessão" hidden>
                                <i class="bi bi-trash3" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="button" id="botaoAdicionarSessao" class="botao-adicionar-sessao">
                        <i class="bi bi-plus-circle" aria-hidden="true"></i>
                        Adicionar outra data
                    </button>
                    <div id="erroSessoesEvento" class="form-text erro" aria-live="polite"></div>
                </fieldset>

                <template id="templateSessaoEvento">
                    <div class="sessao-cadastro" data-sessao>
                        <div class="campo-sessao">
                            <label class="form-label">Data</label>
                            <input type="date" class="form-control" data-campo="data_evento" required>
                        </div>

                        <div class="campo-sessao">
                            <label class="form-label">Início</label>
                            <input type="time" class="form-control" data-campo="hora_inicio" step="60" required>
                        </div>

                        <div class="campo-sessao">
                            <label class="form-label">Fim</label>
                            <input type="time" class="form-control" data-campo="hora_fim" step="60" required>
                        </div>

                        <button type="button" class="botao-remover-sessao" data-remover-sessao
                            aria-label="Remover sessão">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                        </button>
                    </div>
                </template>

                <div class="mb-3">
                    <label class="form-label">
                        Tipo de evento <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </label>

                    <div class="tipo-ingresso" id="tipoIngressoEvento">
                        <input type="radio" name="gratuidade" id="gratuito" value="true" checked>

                        <label for="gratuito" class="opcao">
                            Gratuito / Entrada Livre
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

                <div class="mb-5">
                    <label class="form-label" for="descricaoEvento">
                        Descrição do evento <span class="campo-obrigatorio" aria-hidden="true">*</span>
                    </label>

                    <textarea id="descricaoEvento" name="descricao_evento" maxlength="1000" class="form-control descricao-grande"
                        placeholder="Conte sobre o evento: programação, experiências, destaques..." required></textarea>
                </div>

                <div class="mb-5 grupo-quem-estara-la">
                    <p id="ajudaQuemEstaraLa" class="form-text grupo-quem-estara-la-ajuda">
                        Informe quem faz parte do evento, independentemente da categoria.
                    </p>

                    <div class="mb-3">
                        <label class="form-label" for="nomeArtistaSolicitado">
                            Quem Estará Lá <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>
                        <input id="nomeArtistaSolicitado" name="nome_artista_solicitado" type="text"
                            class="form-control" maxlength="150"
                            placeholder="Ex: Nomes de bandas, palestrantes, facilitadores, expositores ou coletivos"
                            aria-describedby="ajudaQuemEstaraLa" required>
                    </div>

                    <div class="mb-3" id="campoGeneroArtista" hidden>
                        <label class="form-label" for="generoArtistaSolicitado">Gênero musical dos participantes</label>
                        <input id="generoArtistaSolicitado" name="genero_artista" type="text"
                            class="form-control" maxlength="100" placeholder="Ex: MPB, Rock, Sertanejo"
                            aria-describedby="generoArtistaAjuda" disabled>
                        <div id="generoArtistaAjuda" class="form-text">
                            Opcional. Ajuda o ShowMe a recomendar eventos parecidos com os que você curte.
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label" for="descricaoArtista">
                            Sobre quem estará lá <span class="campo-obrigatorio" aria-hidden="true">*</span>
                        </label>

                        <textarea id="descricaoArtista" name="descricao_artista" maxlength="1000" class="form-control descricao-media"
                            placeholder="Conte quem são as pessoas, grupos ou coletivos e o que elas apresentam."
                            required></textarea>
                    </div>
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
