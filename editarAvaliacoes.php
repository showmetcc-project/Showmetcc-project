<?php

require_once __DIR__ . '/config/verifica_login.php';

$idAvaliacao = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);

if (!$idAvaliacao) {
    $idAvaliacao = filter_input(INPUT_GET, 'id_avaliacao', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
}

$idUsuarioSessao = (int) ($_SESSION['id_user'] ?? 0);
$usuarioAdmin = ($_SESSION['tipo_usuario'] ?? '') === 'admin';
?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar avaliação - ShowMe</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/editarAvaliacoes.css" rel="stylesheet">
</head>

<body
    class="com-cabecalho-padrao"
    data-avaliacao-id="<?= $idAvaliacao ? (int) $idAvaliacao : 0 ?>"
    data-user-id="<?= $idUsuarioSessao ?>"
    data-user-admin="<?= $usuarioAdmin ? 'true' : 'false' ?>">

    <?php require __DIR__ . '/cabecalho.php'; ?>

    <main class="editar-avaliacao-main">
        <div class="editar-avaliacao-container">
            <div id="estadoEditarAvaliacao" class="estado-edicao" role="status" aria-live="polite">
                Carregando avaliação...
            </div>

            <article id="cardEditarAvaliacao" class="editar-avaliacao-card" hidden>
                <h1><i class="bi bi-pencil" aria-hidden="true"></i> Editar avaliação</h1>
                <p id="nomeEventoAvaliacao" class="evento-nome"></p>

                <div id="mensagemEditarAvaliacao" class="alert d-none" role="alert"></div>

                <form id="formEditarAvaliacao">
                    <fieldset>
                        <legend>Sua nota:</legend>
                        <div class="estrelas" id="estrelasEdicao" aria-label="Nota de 1 a 5">
                            <?php for ($nota = 1; $nota <= 5; $nota++): ?>
                                <button
                                    type="button"
                                    class="estrela bi bi-star"
                                    data-valor="<?= $nota ?>"
                                    aria-label="<?= $nota ?> estrela<?= $nota > 1 ? 's' : '' ?>"
                                    aria-pressed="false"></button>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="nota" id="notaEdicao">
                    </fieldset>

                    <label for="comentarioEdicao">Seu comentário:</label>
                    <textarea
                        name="comentario"
                        id="comentarioEdicao"
                        maxlength="1000"
                        required></textarea>

                    <section class="midias-existentes" aria-labelledby="tituloMidiasExistentes">
                        <h2 id="tituloMidiasExistentes">Mídias da avaliação</h2>
                        <div id="midiasAvaliacaoEdicao" class="grade-midias-edicao"></div>
                        <small>As mídias existentes são mantidas. Nesta tela, a edição altera somente nota e comentário.</small>
                    </section>

                    <div class="botoes-edicao">
                        <a id="linkCancelarEdicao" href="inicio.php" class="btn-voltar">Cancelar</a>
                        <button type="submit" class="btn-salvar" id="btnSalvarAvaliacao">
                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                            Salvar alterações
                        </button>
                        <button type="button" class="btn-excluir-avaliacao" id="btnExcluirAvaliacao">
                            <i class="bi bi-trash3" aria-hidden="true"></i>
                            Excluir avaliação
                        </button>
                    </div>
                </form>
            </article>
        </div>
    </main>

    <?php require __DIR__ . '/rodape.php'; ?>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/editarAvaliacoes.js"></script>
</body>

</html>
