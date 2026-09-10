<?php require_once __DIR__ . '/config/verifica_admin.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowMe - Painel Administrativo</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
</head>

<body class="com-cabecalho-padrao">
    <?php require __DIR__ . '/cabecalho.php'; ?>

    <main class="container py-4">
        <section class="header-admin">
            <div class="header-row">
                <div>
                    <h1><span class="verde">Painel</span> Administrativo</h1>
                    <p>Gerencie os eventos enviados pelos usuários.</p>
                </div>

                <label class="busca" for="buscaSolicitacao">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input
                        type="search"
                        id="buscaSolicitacao"
                        placeholder="Buscar evento ou solicitante..."
                        autocomplete="off">
                </label>
            </div>
        </section>

        <section class="stats" aria-label="Filtros das solicitações">
            <div class="total-destaque" title="Total de solicitações">
                <span id="totalSolicitacoes">0</span>
            </div>

            <button type="button" class="status-item ativo" data-filtro="todas">
                Todas <span class="badge-count" id="contadorTodas">0</span>
            </button>
            <button type="button" class="status-item" data-filtro="pendente">
                Pendentes <span class="badge-count" id="contadorPendentes">0</span>
            </button>
            <button type="button" class="status-item" data-filtro="aprovado">
                Aprovadas <span class="badge-count" id="contadorAprovadas">0</span>
            </button>
            <button type="button" class="status-item" data-filtro="recusado">
                Recusadas <span class="badge-count" id="contadorRecusadas">0</span>
            </button>

            <div class="stats-barra" aria-hidden="true"></div>
        </section>

        <div id="mensagemAdmin" class="alert d-none" role="alert" aria-live="assertive"></div>

        <section
            id="listaSolicitacoes"
            class="lista-solicitacoes"
            aria-live="polite"
            aria-busy="true">
            <div class="estado-solicitacoes">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                Carregando solicitações...
            </div>
        </section>
    </main>

    <div class="modal fade" id="modalEditarSolicitacao" tabindex="-1" aria-labelledby="tituloModalEditarSolicitacao" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-solicitacao">
                <form id="formEditarSolicitacao">
                    <div class="modal-header">
                        <h2 class="modal-title fs-4" id="tituloModalEditarSolicitacao">Corrigir solicitação</h2>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                    </div>

                    <div class="modal-body">
                        <div id="mensagemEdicaoSolicitacao" class="alert d-none" role="alert"></div>
                        <input type="hidden" id="idSolicitacaoEdicao">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nomeEventoEdicao" class="form-label">Nome do evento</label>
                                <input type="text" class="form-control" id="nomeEventoEdicao" maxlength="100" required>
                            </div>
                            <div class="col-md-7">
                                <label for="localEventoEdicao" class="form-label">Local</label>
                                <input type="text" class="form-control" id="localEventoEdicao" maxlength="255">
                            </div>
                            <div class="col-md-3">
                                <label for="dataEventoEdicao" class="form-label">Data</label>
                                <input type="date" class="form-control" id="dataEventoEdicao">
                            </div>
                            <div class="col-md-2">
                                <label for="horarioEventoEdicao" class="form-label">Horário</label>
                                <input type="time" class="form-control" id="horarioEventoEdicao">
                            </div>
                            <div class="col-md-4">
                                <label for="gratuidadeEdicao" class="form-label">Tipo</label>
                                <select class="form-select" id="gratuidadeEdicao">
                                    <option value="true">Gratuito</option>
                                    <option value="false">Pago</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="descricaoEventoEdicao" class="form-label">Descrição do evento</label>
                                <textarea class="form-control" id="descricaoEventoEdicao" rows="4" maxlength="1000"></textarea>
                            </div>
                            <div class="col-12">
                                <label for="descricaoArtistaEdicao" class="form-label">Artista / atração</label>
                                <textarea class="form-control" id="descricaoArtistaEdicao" rows="3" maxlength="1000"></textarea>
                            </div>
                        </div>

                        <p class="aviso-foto-edicao">
                            <i class="bi bi-image" aria-hidden="true"></i>
                            A foto enviada pelo usuário será mantida.
                        </p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn-modal-cancelar" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-modal-salvar" id="btnSalvarSolicitacao">
                            <i class="bi bi-check-lg" aria-hidden="true"></i>
                            Salvar correções
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php require __DIR__ . '/rodape.php'; ?>

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
    </a>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/admin.js"></script>
</body>

</html>
