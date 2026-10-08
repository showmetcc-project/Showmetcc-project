<?php require_once __DIR__ . '/config/verificaAdmin.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowMe - Painel Administrativo</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="com-cabecalho-padrao cabecalho-tipo-c pagina-admin">
    <?php
    $tipoCabecalho = 'C';
    $configuracaoCabecalho = [
        'titulo_primario' => 'Painel',
        'titulo_secundario' => 'Admin',
        'mostrar_voltar' => false,
        'acao' => 'logout',
    ];
    require __DIR__ . '/cabecalho.php';
    ?>

    <main class="container py-4">
        <div class="admin-controles-listagem">
            <label class="busca busca-admin-geral" for="buscaAdmin">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input
                    type="search"
                    id="buscaAdmin"
                    placeholder="Buscar evento ou solicitante..."
                    autocomplete="off">
            </label>
            <label class="admin-filtro-periodo" for="filtroPeriodoAdmin">
                <span>Período</span>
                <select id="filtroPeriodoAdmin">
                    <option value="0">Todo o período</option>
                    <option value="1">Há 1 dia</option>
                    <option value="7">Há 7 dias</option>
                    <option value="30">Há 30 dias</option>
                    <option value="60">Há 60 dias</option>
                    <option value="90">Há 90 dias</option>
                    <option value="120">Há 120 dias</option>
                    <option value="180">Há 180 dias</option>
                    <option value="365">Há 1 ano</option>
                </select>
            </label>
        </div>

        <nav class="admin-abas" role="tablist" aria-label="Áreas de moderação">
            <button
                type="button"
                class="ativa"
                id="abaEventosAdmin"
                data-aba-admin="eventos"
                role="tab"
                aria-selected="true"
                aria-controls="painelEventosAdmin">
                <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                Eventos enviados
            </button>
            <button
                type="button"
                id="abaDenunciasAdmin"
                data-aba-admin="denuncias"
                role="tab"
                aria-selected="false"
                aria-controls="painelDenunciasAdmin">
                <i class="bi bi-flag" aria-hidden="true"></i>
                Conteúdos denunciados
                <span class="admin-aba-contador" id="contadorDenunciasAba">0</span>
            </button>
        </nav>

        <section id="painelEventosAdmin" class="admin-painel ativa" role="tabpanel" aria-labelledby="abaEventosAdmin">
            <header class="header-admin">
                <div class="header-row">
                    <div>
                        <h2>Eventos enviados</h2>
                        <p>Revise solicitações e edite ou remova eventos que já foram publicados.</p>
                    </div>
                </div>
            </header>

            <div class="admin-filtros" aria-label="Filtrar eventos enviados">
                <button type="button" class="ativo" data-filtro-evento="pendente">
                    Pendentes <span id="contadorPendentes">0</span>
                </button>
                <button type="button" data-filtro-evento="todas">
                    Todas <span id="contadorTodas">0</span>
                </button>
                <button type="button" data-filtro-evento="aprovado">
                    Aprovados <span id="contadorAprovadas">0</span>
                </button>
                <button type="button" data-filtro-evento="recusado">
                    Reprovados <span id="contadorRecusadas">0</span>
                </button>
                <button type="button" data-filtro-evento="removido">
                    Removidos <span id="contadorRemovidas">0</span>
                </button>
            </div>

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
        </section>

        <section id="painelDenunciasAdmin" class="admin-painel" role="tabpanel" aria-labelledby="abaDenunciasAdmin" hidden>
            <header class="header-admin">
                <div class="header-row">
                    <div>
                        <h2>Conteúdos denunciados</h2>
                        <p>Analise posts e fotos denunciados e decida se o conteúdo permanece ou é removido.</p>
                    </div>
                </div>
            </header>

            <div class="admin-filtros" aria-label="Filtrar conteúdos denunciados">
                <button type="button" class="ativo" data-filtro-denuncia="pendente">
                    Pendentes <span id="contadorDenunciasPendentes">0</span>
                </button>
                <button type="button" data-filtro-denuncia="todas">
                    Todas <span id="contadorDenunciasTodas">0</span>
                </button>
                <button type="button" data-filtro-denuncia="mantido">
                    Mantidos <span id="contadorDenunciasMantidas">0</span>
                </button>
                <button type="button" data-filtro-denuncia="removido">
                    Removidos <span id="contadorDenunciasRemovidas">0</span>
                </button>
            </div>

            <section
                id="listaDenuncias"
                class="lista-denuncias"
                aria-live="polite"
                aria-busy="true">
                <div class="estado-solicitacoes">
                    <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                    Carregando denúncias...
                </div>
            </section>
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
                        <input type="hidden" id="idSolicitacaoEdicao">

                        <div class="row g-3">
                            <div class="col-12">
                                <label for="nomeEventoEdicao" class="form-label">Nome do evento</label>
                                <input type="text" class="form-control" id="nomeEventoEdicao" maxlength="100" required>
                            </div>
                            <div class="col-md-6">
                                <label for="dataEventoEdicao" class="form-label">Data</label>
                                <input type="date" class="form-control" id="dataEventoEdicao">
                            </div>
                            <div class="col-md-6">
                                <label for="horarioEventoEdicao" class="form-label">Horário</label>
                                <input type="time" class="form-control" id="horarioEventoEdicao">
                            </div>
                            <div class="col-md-3">
                                <label for="cepEventoEdicao" class="form-label">CEP</label>
                                <input type="text" class="form-control" id="cepEventoEdicao" maxlength="8" pattern="\d{8}">
                            </div>
                            <div class="col-md-7">
                                <label for="enderecoEventoEdicao" class="form-label">Endereço consolidado</label>
                                <input type="text" class="form-control" id="enderecoEventoEdicao" maxlength="255">
                            </div>
                            <div class="col-md-2">
                                <label for="numeroEnderecoEdicao" class="form-label">Número</label>
                                <input type="text" class="form-control" id="numeroEnderecoEdicao" maxlength="20">
                            </div>
                            <div class="col-12">
                                <label for="ruaEventoEdicao" class="form-label">Logradouro (busca/mapa)</label>
                                <input type="text" class="form-control" id="ruaEventoEdicao" maxlength="100">
                            </div>
                            <div class="col-md-8">
                                <label for="cidadeEventoEdicao" class="form-label">Cidade</label>
                                <input type="text" class="form-control" id="cidadeEventoEdicao" maxlength="100">
                            </div>
                            <div class="col-md-4">
                                <label for="ufEventoEdicao" class="form-label">UF</label>
                                <input type="text" class="form-control" id="ufEventoEdicao" maxlength="2" pattern="[A-Za-z]{2}">
                            </div>
                            <div class="col-md-4">
                                <label for="valorMinimoEdicao" class="form-label">Valor mínimo (R$)</label>
                                <input type="number" class="form-control" id="valorMinimoEdicao" min="0.01" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label for="valorMaximoEdicao" class="form-label">Valor máximo (R$)</label>
                                <input type="number" class="form-control" id="valorMaximoEdicao" min="0.01" step="0.01">
                            </div>
                            <div class="col-md-5">
                                <label for="categoriaEventoEdicao" class="form-label">Categoria</label>
                                <input type="text" class="form-control" id="categoriaEventoEdicao" maxlength="255"
                                    placeholder="Ex.: Música, Show Nacional">
                            </div>
                            <div class="col-md-7">
                                <label for="linkOficialEdicao" class="form-label">Link oficial</label>
                                <input type="url" class="form-control" id="linkOficialEdicao" maxlength="255">
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
                            <div class="col-12 campo-apenas-solicitacao">
                                <label for="descricaoArtistaEdicao" class="form-label">Artista / atração</label>
                                <textarea class="form-control" id="descricaoArtistaEdicao" rows="3" maxlength="1000"></textarea>
                            </div>
                            <div class="col-12 campo-apenas-solicitacao">
                                <label for="nomeArtistaSolicitadoEdicao" class="form-label">Nome do artista / atração</label>
                                <input type="text" class="form-control" id="nomeArtistaSolicitadoEdicao" maxlength="150">
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

    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short" aria-hidden="true"></i>
    </a>

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/admin.js"></script>
</body>

</html>
