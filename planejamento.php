<?php
require_once __DIR__ . '/config/verificaUsuarioComum.php';

$idInformado = $_GET['id'] ?? $_GET['id_evento'] ?? null;

if (!is_string($idInformado) || !ctype_digit($idInformado) || (int) $idInformado < 1) {
    http_response_code(400);
    die('Evento não informado.');
}

$idEvento = (int) $idInformado;

?>
    <!doctype html>

    <html lang="pt-BR">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>
            Planejar Viagem - ShowMe
        </title>

        <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">

        <!-- BOOTSTRAP ICONS -->

        <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">

        <!-- LEAFLET -->

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

        <!-- CSS DO PROJETO -->

        <link rel="stylesheet" href="assets/css/planejamento.css">

        <!-- CSS compartilhado por último para preservar o cabeçalho -->

        <link rel="stylesheet" href="assets/css/main.css">

    </head>


    <body class="com-cabecalho-padrao cabecalho-tipo-c pagina-planejamento">

        <?php
        $tipoCabecalho = 'C';
        $configuracaoCabecalho = [
            'titulo_primario' => 'Planejamento',
            'titulo_secundario' => 'do Evento',
            'icone' => 'bi-signpost-split',
            'fallback' => 'meusEventos.php',
        ];
        require __DIR__ . '/cabecalho.php';
        ?>

        <p id="tituloEvento" class="planejamento-evento-atual">
            Carregando evento...
        </p>



        <!-- =====================================================
     CONTEÚDO PRINCIPAL
===================================================== -->

        <main class="planejamento-container container">
            <div class="row g-4 g-xl-5 align-items-start">


            <!-- =================================================
         PROGRESSO
    ================================================== -->

            <aside class="progresso col-12 col-lg-3">

                <h3>
                    Progresso
                </h3>


                <div class="timeline">


                    <!-- ETAPA 1 -->

                    <div class="passo ativo" id="passo1">

                        <div class="numero">
                            1
                        </div>

                        <span>
                    Orçamento
                </span>

                    </div>


                    <!-- ETAPA 2 -->

                    <div class="passo" id="passo2">

                        <div class="numero">
                            2
                        </div>

                        <span>
                    Transporte
                </span>

                    </div>


                    <!-- ETAPA 3 -->

                    <div class="passo" id="passo3">

                        <div class="numero">
                            3
                        </div>

                        <span>
                    Rota
                </span>

                    </div>


                    <!-- ETAPA 4 -->

                    <div class="passo" id="passo4">

                        <div class="numero">
                            4
                        </div>

                        <span>
                    Hospedagem
                </span>

                    </div>


                    <!-- ETAPA 5 -->

                    <div class="passo" id="passo5">

                        <div class="numero">
                            5
                        </div>

                        <span>
                    Resumo
                </span>

                    </div>


                </div>

            </aside>



            <!-- =================================================
         CARD
    ================================================== -->

            <div class="col-12 col-lg-9">
            <section class="card-planejamento">


                <!-- =================================================
             ETAPA 1 - ORÇAMENTO
        ================================================== -->

                <div class="etapa ativa" id="etapa1">

                    <div class="titulo-card">

                        <i class="bi bi-currency-dollar"></i>

                        <h2>
                            Defina seu Orçamento
                        </h2>

                    </div>


                    <p>
                        Quanto você pretende gastar para ir a este evento?
                    </p>


                    <label>
                Valor total disponível (R$)
            </label>


                    <input type="number" id="orcamento" placeholder="500" min="0" required>


                    <div class="alerta" id="custoMinimoEvento">

                        Carregando o valor de acesso ao evento...

                    </div>


                    <button onclick="proximaEtapa(2)">

                Continuar

            </button>

                </div>



                <!-- =================================================
             ETAPA 2 - TRANSPORTE
        ================================================== -->

                <div class="etapa" id="etapa2">

                    <div class="titulo-card">

                        <i class="bi bi-bus-front"></i>

                        <h2>
                            Transporte
                        </h2>

                    </div>


                    <p id="textoDestinoTransporte">
                        Carregando o destino do evento...
                    </p>


                    <div class="orcamento-restante">

                        Seu orçamento

                        <strong id="valorOrcamento">
                    R$ 0,00
                </strong>

                    </div>


                    <h3>
                        Pesquise opções de transporte:
                    </h3>


                    <div class="opcoes">


                        <div class="opcao-transporte-card">
                            <button type="button" class="opcao" data-transporte="Ônibus" aria-pressed="false" onclick="selecionarTransporte('Ônibus')">
                                <i class="bi bi-bus-front icone-opcao" aria-hidden="true"></i>
                                <strong>Ônibus</strong>
                                <small>Passagens rodoviárias</small>
                            </button>
                            <a class="link-pesquisa-transporte" data-link-transporte="Ônibus" href="#" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                Pesquisar passagens
                            </a>
                        </div>



                        <div class="opcao-transporte-card">
                            <button type="button" class="opcao" data-transporte="Carro" aria-pressed="false" onclick="selecionarTransporte('Carro')">
                                <i class="bi bi-car-front icone-opcao" aria-hidden="true"></i>
                                <strong>Carro</strong>
                                <small>Viagem de carro</small>
                            </button>
                            <a class="link-pesquisa-transporte" data-link-transporte="Carro" href="#" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                Abrir rota
                            </a>
                        </div>



                        <div class="opcao-transporte-card">
                            <button type="button" class="opcao" data-transporte="Avião" aria-pressed="false" onclick="selecionarTransporte('Avião')">
                                <i class="bi bi-airplane icone-opcao" aria-hidden="true"></i>
                                <strong>Avião</strong>
                                <small>Passagens aéreas</small>
                            </button>
                            <a class="link-pesquisa-transporte" data-link-transporte="Avião" href="#" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                Pesquisar voos
                            </a>
                        </div>



                        <div class="opcao-transporte-card">
                            <button type="button" class="opcao" data-transporte="Uber" aria-pressed="false" onclick="selecionarTransporte('Uber')">
                                <i class="bi bi-taxi-front icone-opcao" aria-hidden="true"></i>
                                <strong>Uber</strong>
                                <small>Transporte por aplicativo</small>
                            </button>
                            <a class="link-pesquisa-transporte" data-link-transporte="Uber" href="#" target="_blank" rel="noopener noreferrer">
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                Abrir Uber
                            </a>
                        </div>


                    </div>


                    <!-- ESTIMATIVA -->

                    <div id="estimativaTransporte" hidden>
                    </div>


                    <div class="campo-valor-planejamento">
                        <label for="transporte">Valor do transporte escolhido (R$)</label>
                        <input type="number" id="transporte" value="0" min="0" step="0.01" inputmode="decimal">
                        <p id="transporteSelecionado">Nenhum transporte selecionado.</p>
                    </div>


                    <div class="botoes">

                        <button onclick="voltarEtapa(1)">
                    Voltar
                </button>


                        <button onclick="proximaEtapa(3)">
                    Continuar
                </button>

                    </div>


                </div>



                <!-- =================================================
             ETAPA 3 - ROTA
        ================================================== -->

                <div class="etapa" id="etapa3">

                    <div class="titulo-card">

                        <i class="bi bi-geo-alt-fill"></i>

                        <h2>
                            Planeje sua rota
                        </h2>

                    </div>


                    <p>

                        Veja a localização do evento e calcule a melhor rota a partir do seu ponto de partida.

                    </p>


                    <!-- LOCAL -->

                    <div class="localizacao-box">

                        <strong>

                    <i class="bi bi-geo-alt-fill"></i>

                    Local do evento

                </strong>


                        <p id="enderecoEvento">

                            Carregando endereço...

                        </p>


                        <button type="button" onclick="centralizarEvento()">

                    <i class="bi bi-map"></i>

                    Ver localização

                </button>

                    </div>


                    <!-- MAPA -->

                    <div id="mapaRota"></div>


                    <!-- ORIGEM -->

                    <div class="origem-rota">

                        <h3>

                            <i class="bi bi-signpost-split"></i> De onde você vai sair?

                        </h3>


                        <p class="origem-atual">

                            Origem:

                            <strong id="origemTexto">
                        Não definida
                    </strong>

                        </p>


                        <p class="descricao-rota">

                            Para calcular a rota até o evento, precisamos saber sua localização.

                        </p>


                        <button type="button" class="botao-localizacao" onclick="usarMinhaLocalizacao()">

                    <i class="bi bi-crosshair"></i>

                    Usar minha localização

                </button>


                        <div class="separador-rota">

                            <span>
                        ou
                    </span>

                        </div>


                        <label for="origemManual">

                    Informe um ponto de partida

                </label>


                        <input type="text" id="origemManual" placeholder="Ex: São Paulo - SP">


                        <button type="button" class="botao-origem" onclick="calcularRotaManual()">

                    Calcular rota

                </button>

                    </div>


                    <!-- STATUS -->

                    <p id="mensagemRota" class="mensagem-rota">
                    </p>


                    <!-- INFORMAÇÕES -->

                    <div class="informacoes-rota" id="informacoesRota" hidden>

                        <div class="info-rota">

                            <i class="bi bi-signpost-2"></i>

                            <span>
                        Distância
                    </span>

                            <strong id="distanciaRota">
                        --
                    </strong>

                        </div>


                        <div class="info-rota">

                            <i class="bi bi-clock"></i>

                            <span>
                        Tempo estimado
                    </span>

                            <strong id="tempoRota">
                        --
                    </strong>

                        </div>

                    </div>


                    <div class="dados-rota-planejamento">
                        <h3><i class="bi bi-signpost-split" aria-hidden="true"></i> Dados da rota</h3>
                        <p>Carro e Uber são preenchidos automaticamente. Para ônibus ou avião, informe a estimativa consultada no aplicativo de navegação.</p>
                        <div class="campos-rota-planejamento">
                            <div>
                                <label for="distanciaPlanejamento">Distância (km)</label>
                                <input id="distanciaPlanejamento" type="number" min="0.01" max="99999999.99" step="0.01" placeholder="Ex.: 125,5" required>
                            </div>
                            <div>
                                <label for="tempoPlanejamento">Tempo estimado (minutos)</label>
                                <input id="tempoPlanejamento" type="number" min="1" step="1" placeholder="Ex.: 150" required>
                            </div>
                        </div>
                    </div>


                    <!-- NAVEGAÇÃO -->

                    <div class="navegacao-box" id="navegacaoBox" hidden>

                        <h3>

                            <i class="bi bi-compass"></i> Continuar navegação

                        </h3>


                        <p>

                            Você pode continuar sua rota utilizando um aplicativo de navegação.

                        </p>


                        <div class="botoes-navegacao">


                            <button type="button" onclick="abrirGoogleMaps()">

                        <i class="bi bi-map"></i>

                        Google Maps

                        <i class="bi bi-box-arrow-up-right icone-link-externo" aria-hidden="true"></i>

                    </button>


                            <button type="button" onclick="abrirWaze()">

                        <i class="bi bi-car-front"></i>

                        Waze

                        <i class="bi bi-box-arrow-up-right icone-link-externo" aria-hidden="true"></i>

                    </button>


                        </div>

                    </div>


                    <!-- BOTÕES -->

                    <div class="botoes">

                        <button onclick="voltarEtapa(2)">

                    Voltar

                </button>


                        <button onclick="proximaEtapa(4)">

                    Continuar

                </button>

                    </div>

                </div>



                <!-- =================================================
             ETAPA 4 - HOSPEDAGEM
        ================================================== -->

                <div class="etapa" id="etapa4">

                    <div class="titulo-card">

                        <i class="bi bi-house"></i>

                        <h2>
                            Hospedagem
                        </h2>

                    </div>


                    <p>
                        Você precisa de hospedagem para este evento?
                    </p>


                    <div class="opcoes hospedagem-escolha">


                        <button type="button" id="opcaoHospedagemSim" class="opcao" aria-pressed="false" onclick="mostrarHospedagem()">

                    <i class="bi bi-building icone-opcao" aria-hidden="true"></i>

                    <strong>
                        Sim, preciso de hospedagem
                    </strong>

                    <small>
                        Ver opções próximas ao evento
                    </small>

                </button>


                        <button type="button" id="opcaoHospedagemNao" class="opcao" aria-pressed="false" onclick="semHospedagem()">

                    <i class="bi bi-house-check icone-opcao" aria-hidden="true"></i>

                    <strong>
                        Não, já tenho onde ficar
                    </strong>

                    <small>
                        Continuar sem hospedagem
                    </small>

                </button>


                    </div>


                    <!-- OPÇÕES -->

                    <div id="opcoesHospedagem" hidden>


                        <div class="orcamento-restante">

                            Orçamento restante

                            <strong id="restante">
                        R$ 0,00
                    </strong>

                        </div>


                        <h3>
                            Hospedagens próximas ao evento:
                        </h3>

                        <p class="fonte-hospedagem">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            Sugestões de estabelecimentos cadastrados no OpenStreetMap, consultadas pela API Overpass em um raio de 5 km. A lista não informa preços nem disponibilidade.
                        </p>


                        <div id="resultadoHospedagem">

                            <p>
                                Pesquisando opções...
                            </p>

                        </div>


                        <h3>Pesquise preços e disponibilidade:</h3>

                        <div class="opcoes">
                            <div class="opcao-hospedagem-card">
                                <button type="button" class="opcao" data-hospedagem="Booking.com" aria-pressed="false" onclick="selecionarHospedagem('Booking.com')">
                                    <i class="bi bi-building icone-opcao" aria-hidden="true"></i>
                                    <strong>Booking.com</strong>
                                    <small>Hotéis e pousadas</small>
                                </button>
                                <a class="link-pesquisa-hospedagem" data-link-hospedagem="Booking.com" href="https://www.booking.com/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir site</a>
                            </div>

                            <div class="opcao-hospedagem-card">
                                <button type="button" class="opcao" data-hospedagem="Airbnb" aria-pressed="false" onclick="selecionarHospedagem('Airbnb')">
                                    <i class="bi bi-house-door icone-opcao" aria-hidden="true"></i>
                                    <strong>Airbnb</strong>
                                    <small>Casas e apartamentos</small>
                                </button>
                                <a class="link-pesquisa-hospedagem" data-link-hospedagem="Airbnb" href="https://www.airbnb.com.br/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir site</a>
                            </div>

                            <div class="opcao-hospedagem-card">
                                <button type="button" class="opcao" data-hospedagem="Hotels.com" aria-pressed="false" onclick="selecionarHospedagem('Hotels.com')">
                                    <i class="bi bi-door-open icone-opcao" aria-hidden="true"></i>
                                    <strong>Hotels.com</strong>
                                    <small>Acomodações</small>
                                </button>
                                <a class="link-pesquisa-hospedagem" data-link-hospedagem="Hotels.com" href="https://www.hoteis.com/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir site</a>
                            </div>

                            <div class="opcao-hospedagem-card">
                                <button type="button" class="opcao" data-hospedagem="HostelWorld" aria-pressed="false" onclick="selecionarHospedagem('HostelWorld')">
                                    <i class="bi bi-people icone-opcao" aria-hidden="true"></i>
                                    <strong>HostelWorld</strong>
                                    <small>Hostels e albergues</small>
                                </button>
                                <a class="link-pesquisa-hospedagem" data-link-hospedagem="HostelWorld" href="https://www.hostelworld.com/" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir site</a>
                            </div>
                        </div>

                        <div class="campo-valor-planejamento">
                            <label for="hospedagem">Valor da hospedagem (R$)</label>
                            <input type="number" id="hospedagem" value="0" min="0" step="0.01" inputmode="decimal">
                            <p id="hospedagemSelecionada">Nenhuma hospedagem selecionada.</p>
                        </div>


                    </div>


                    <div class="botoes">


                        <button onclick="voltarEtapa(3)">
                    Voltar
                </button>


                        <button onclick="proximaEtapa(5)">
                    Continuar
                </button>


                    </div>

                </div>



                <!-- =================================================
             ETAPA 5 - RESUMO
        ================================================== -->

                <div class="etapa" id="etapa5">

                    <div class="titulo-card">

                        <i class="bi bi-check-circle"></i>

                        <h2>
                            Resumo do Planejamento
                        </h2>

                    </div>


                    <!-- EVENTO -->

                    <div class="resumo-box resumo-evento-planejado">

                        <h3>
                            <i class="bi bi-calendar-event" aria-hidden="true"></i> Evento
                        </h3>

                        <div class="linha-resumo">
                            <span>Nome</span>
                            <span id="resumoNomeEvento">Carregando...</span>
                        </div>

                        <div class="linha-resumo">
                            <span>Data e horário</span>
                            <span id="resumoDataHorarioEvento">Carregando...</span>
                        </div>

                        <div class="linha-resumo">
                            <span>Local</span>
                            <span id="resumoLocalEvento">Carregando...</span>
                        </div>

                    </div>


                    <!-- CUSTOS -->

                    <div class="resumo-box">

                        <h3>
                            Custos
                        </h3>


                        <div class="linha-resumo">

                            <span>
                        Ingresso
                    </span>

                            <span id="resumoIngresso">
                        Carregando...
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Transporte
                    </span>

                            <span id="resumoTransporte">
                        R$ 0,00
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Hospedagem
                    </span>

                            <span id="resumoHospedagem">
                        R$ 0,00
                    </span>

                        </div>


                        <hr>


                        <div class="linha-resumo total">

                            <span>
                        Total
                    </span>

                            <span id="totalFinal">
                        R$ 0,00
                    </span>

                        </div>

                    </div>


                    <!-- ORÇAMENTO -->

                    <div class="resumo-box">

                        <h3>
                            Orçamento
                        </h3>


                        <div class="linha-resumo">

                            <span>
                        Planejado
                    </span>

                            <span id="orcamentoFinal">
                        R$ 0,00
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Total de gastos
                    </span>

                            <span id="gastosFinal">
                        R$ 0,00
                    </span>

                        </div>


                        <div class="linha-resumo total">

                            <span>
                        Saldo
                    </span>

                            <span id="saldoFinal">
                        R$ 0,00
                    </span>

                        </div>

                    </div>


                    <!-- ROTA -->

                    <div class="resumo-box rota-resumo">

                        <h3>

                            <i class="bi bi-map"></i> Rota da viagem

                        </h3>


                        <div class="linha-resumo">

                            <span>
                        Origem
                    </span>

                            <span id="resumoOrigem">
                        —
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Destino
                    </span>

                            <span id="resumoDestino">

                        Carregando...

                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Transporte
                    </span>

                            <span id="resumoMeioTransporte">
                        —
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Distância
                    </span>

                            <span id="resumoDistancia">
                        —
                    </span>

                        </div>


                        <div class="linha-resumo">

                            <span>
                        Tempo estimado
                    </span>

                            <span id="resumoTempo">
                        —
                    </span>

                        </div>

                    </div>


                    <!-- DICAS -->

                    <div class="dicas">

                        <h3>

                            <i class="bi bi-info-circle"></i> Dicas para sua viagem

                        </h3>


                        <p><i class="bi bi-capsule" aria-hidden="true"></i><span>Leve medicamentos básicos e seu plano de saúde.</span></p>

                        <p><i class="bi bi-brightness-high" aria-hidden="true"></i><span>Confira a previsão do tempo e leve roupas adequadas.</span></p>

                        <p><i class="bi bi-ticket-perforated" aria-hidden="true"></i><span>Não esqueça de adquirir seus ingressos com antecedência.</span></p>

                        <p><i class="bi bi-phone" aria-hidden="true"></i><span>Consulte o canal oficial do evento para acompanhar atualizações.</span></p>

                    </div>

                    <form id="formPlanejamento" class="form-planejamento-api">
                        <input id="meioTransportePlanejamento" type="hidden" value="">

                        <p id="mensagemPlanejamento" class="mensagem-planejamento-api" aria-live="polite"></p>

                        <div id="acoesGoogleAgenda" class="acoes-google-agenda" hidden>
                            <p id="statusGoogleAgendaPlanejamento"></p>
                            <button id="btnAdicionarGoogleAgenda" type="button" hidden>
                                <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                Adicionar ao Google Agenda
                            </button>
                            <a id="btnConectarGoogleAgendaPlanejamento" href="googleCalendarConectar.php?retorno=planejamento.php%3Fid%3D<?= $idEvento ?>" hidden>
                                <i class="bi bi-google" aria-hidden="true"></i>
                                Conectar Google Agenda
                            </a>
                        </div>

                        <div class="botoes">
                            <button id="botaoVoltarResumo" type="button" onclick="voltarEtapa(4)">Voltar</button>
                            <button id="botaoImprimirPlanejamento" class="botao-imprimir-planejamento" type="button" hidden>
                                <i class="bi bi-printer" aria-hidden="true"></i>
                                <span>Imprimir / Baixar PDF</span>
                            </button>
                            <button id="botaoFinalizarPlanejamento" type="submit">Finalizar planejamento</button>
                        </div>
                    </form>


                </div>

            </section>
            </div>

            </div>
        </main>

        <dialog id="modalConflitoAgenda" class="modal-conflito-agenda" aria-labelledby="tituloConflitoAgenda">
            <form method="dialog">
                <button class="fechar-conflito-agenda" value="cancelar" aria-label="Fechar aviso">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
                <i class="bi bi-calendar-x icone-conflito-agenda" aria-hidden="true"></i>
                <h2 id="tituloConflitoAgenda">Conflito no Google Agenda</h2>
                <p>Há outro compromisso no período deste evento, considerando também o tempo de deslocamento.</p>
                <ul id="listaConflitosAgenda"></ul>
                <div class="acoes-conflito-agenda">
                    <button type="submit" value="cancelar">Cancelar</button>
                    <button type="submit" value="finalizar">Finalizar mesmo assim</button>
                </div>
            </form>
        </dialog>



        <!-- =====================================================
     FOOTER
===================================================== -->



        <!-- =====================================================
     LEAFLET
===================================================== -->

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
        </script>



        <script>
            /* =========================================================
               EVENTO CARREGADO PELA API
            ========================================================= */

            const evento = {
                id: <?= $idEvento ?>,
                nome: '',
                cidade: '',
                uf: '',
                rua: '',
                numero: '',
                cep: '',
                data: '',
                horario: '',
                endereco: '',
                gratuidade: true,
                valorMinimo: 0,
                valorMaximo: null
            };

            function atualizarLinksTransporte() {
                const destinoEvento = evento.endereco
                    || [evento.cidade, evento.uf].filter(Boolean).join(' - ')
                    || evento.nome;
                const destinoResumido = [evento.cidade, evento.uf].filter(Boolean).join(' - ')
                    || evento.nome;
                const parametrosUber = new URLSearchParams({
                    action: 'setPickup',
                    pickup: 'my_location',
                    'dropoff[formatted_address]': destinoEvento
                });
                const links = {
                    'Ônibus': `https://www.google.com/search?q=${encodeURIComponent(`passagens de ônibus para ${destinoResumido}`)}`,
                    'Carro': `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(destinoEvento)}`,
                    'Avião': `https://www.google.com/travel/flights?q=${encodeURIComponent(`voos para ${destinoResumido}`)}`,
                    'Uber': `https://m.uber.com/ul/?${parametrosUber}`
                };

                document.querySelectorAll('[data-link-transporte]').forEach((link) => {
                    link.href = links[link.dataset.linkTransporte] || '#';
                });
            }

            function atualizarLinksHospedagem() {
                const destinoBusca = [evento.cidade, evento.uf].filter(Boolean).join(', ')
                    || evento.endereco
                    || evento.nome;
                const destinoCodificado = encodeURIComponent(destinoBusca);
                const links = {
                    'Booking.com': `https://www.booking.com/searchresults.pt-br.html?ss=${destinoCodificado}`,
                    'Airbnb': `https://www.airbnb.com.br/s/${destinoCodificado}/homes`,
                    'Hotels.com': `https://www.hoteis.com/Hotel-Search?destination=${destinoCodificado}`,
                    'HostelWorld': `https://www.hostelworld.com/st/hostels/?search_keywords=${destinoCodificado}`
                };

                document.querySelectorAll('[data-link-hospedagem]').forEach((link) => {
                    link.href = links[link.dataset.linkHospedagem] || link.href;
                });
            }

            async function carregarEvento() {
                const resposta = await fetch(`api/eventos/${evento.id}`);
                const dados = await resposta.json();

                if (!resposta.ok) {
                    throw new Error(dados.erro || 'Não foi possível carregar o evento.');
                }

                const eventoApi = dados.evento;
                const enderecoBase = eventoApi.endereco_evento
                    || [eventoApi.rua_evento, eventoApi.cidade_evento, eventoApi.uf]
                        .filter(Boolean)
                        .join(', ');
                const rua = String(eventoApi.rua_evento || '').trim();
                const numero = String(eventoApi.numero_endereco || '').trim();
                let enderecoComNumero = String(enderecoBase || '').trim();
                if (rua && numero && enderecoComNumero.toLocaleLowerCase('pt-BR').startsWith(rua.toLocaleLowerCase('pt-BR'))) {
                    const complemento = enderecoComNumero.slice(rua.length).replace(/^\s*,\s*/, '');
                    enderecoComNumero = [rua, numero, complemento].filter(Boolean).join(', ');
                } else if (!enderecoComNumero) {
                    enderecoComNumero = [rua, numero, eventoApi.cidade_evento, eventoApi.uf]
                        .filter(Boolean)
                        .join(', ');
                }

                Object.assign(evento, {
                    nome: eventoApi.nome_evento || 'Evento',
                    cidade: eventoApi.cidade_evento || '',
                    uf: eventoApi.uf || '',
                    rua,
                    numero,
                    cep: eventoApi.cep_evento || '',
                    data: eventoApi.data_evento || '',
                    horario: eventoApi.horario_evento || '',
                    endereco: enderecoComNumero,
                    gratuidade: Boolean(eventoApi.gratuidade),
                    valorMinimo: Number(eventoApi.valor_ingresso_minimo || 0),
                    valorMaximo: eventoApi.valor_ingresso_maximo === null
                        ? null
                        : Number(eventoApi.valor_ingresso_maximo)
                });

                custoIngresso = evento.gratuidade ? 0 : evento.valorMinimo;
                atualizarLinksTransporte();
                atualizarLinksHospedagem();

                document.getElementById('tituloEvento').textContent = evento.nome;
                document.getElementById('enderecoEvento').textContent = evento.endereco || 'Endereço não informado';
                document.getElementById('resumoNomeEvento').textContent = evento.nome;
                document.getElementById('resumoDataHorarioEvento').textContent = formatarDataHorarioEvento(
                    evento.data,
                    evento.horario
                );
                document.getElementById('resumoLocalEvento').textContent = evento.endereco || 'Local não informado';
                document.getElementById('resumoDestino').textContent = evento.endereco || evento.nome;
                document.getElementById('textoDestinoTransporte').textContent =
                    `Pesquise opções de transporte para ${evento.cidade || 'o evento'}, ${evento.uf} e informe o valor escolhido.`;
                document.getElementById('custoMinimoEvento').textContent = evento.gratuidade
                    ? 'Entrada gratuita: não há custo de ingresso no planejamento.'
                    : `Valor mínimo informado para o ingresso: ${moeda(custoIngresso)}.`;
                document.getElementById('resumoIngresso').textContent = moeda(custoIngresso);
                atualizarResumo();
            }

            function formatarDataHorarioEvento(dataEvento, horarioEvento) {
                const partesData = String(dataEvento || '').split('-').map(Number);
                let dataFormatada = 'Data não informada';

                if (partesData.length === 3 && partesData.every(Number.isInteger)) {
                    const [ano, mes, dia] = partesData;
                    const data = new Date(ano, mes - 1, dia);

                    if (!Number.isNaN(data.getTime())) {
                        dataFormatada = new Intl.DateTimeFormat('pt-BR', {
                            day: '2-digit',
                            month: 'long',
                            year: 'numeric'
                        }).format(data);
                    }
                }

                const horario = String(horarioEvento || '').slice(0, 5);
                return horario ? `${dataFormatada}, às ${horario}` : dataFormatada;
            }


            /* =========================================================
               CONFIGURAÇÕES
            ========================================================= */

            let custoIngresso = 0;


            /* =========================================================
               ESTADO
            ========================================================= */

            let origem = null;

            let destino = null;

            let mapa = null;

            let marcadorOrigem = null;

            let marcadorDestino = null;

            let rotaLayer = null;

            let transporteSelecionado = "";

            let hospedagemSelecionada = false;

            let hospedagemDecidida = false;

            let hospedagemNome = "";

            let rotaCalculada = false;

            let idPlanejamentoExistente = null;

            /*
                Controle para impedir
                que alguma etapa seja pulada.
            */

            let etapaConcluida = {

                1: true,

                2: false,

                3: false,

                4: false

            };


            /* =========================================================
               FORMATAÇÃO DE MOEDA
            ========================================================= */

            function moeda(valor) {

                return Number(valor || 0)
                    .toLocaleString(
                        "pt-BR", {
                            style: "currency",
                            currency: "BRL"
                        }
                    );

            }


            /* =========================================================
               MENSAGEM DA ROTA
            ========================================================= */

            function mostrarMensagem(
                texto,
                erro = false
            ) {

                const mensagem =
                    document.getElementById(
                        "mensagemRota"
                    );


                mensagem.textContent = texto;


                if (erro) {

                    mensagem.classList.add(
                        "erro-rota"
                    );

                } else {

                    mensagem.classList.remove(
                        "erro-rota"
                    );

                }

            }


            /* =========================================================
               LOCALIZAR EVENTO PELO ENDEREÇO
            =========================================================

               IMPORTANTE:

               O banco não precisa possuir latitude
               nem longitude.

               O endereço é usado para descobrir
               a posição somente quando necessário.

            ========================================================= */

            function coordenadasValidas(latitude, longitude) {
                return Number.isFinite(latitude)
                    && Number.isFinite(longitude)
                    && latitude >= -90
                    && latitude <= 90
                    && longitude >= -180
                    && longitude <= 180;
            }

            async function localizarEventoPorCep() {
                const cep = String(evento.cep || '').replace(/\D/g, '');
                if (cep.length !== 8) {
                    return false;
                }

                const resposta = await fetch(`https://brasilapi.com.br/api/cep/v2/${cep}`, {
                    headers: {Accept: 'application/json'}
                });
                if (!resposta.ok) {
                    return false;
                }

                const dados = await resposta.json();
                const latitude = Number(dados?.location?.coordinates?.latitude);
                const longitude = Number(dados?.location?.coordinates?.longitude);
                if (!coordenadasValidas(latitude, longitude)) {
                    return false;
                }

                destino = {lat: latitude, lng: longitude};
                return true;
            }

            async function localizarEvento() {
                try {
                    const endereco = [
                        evento.rua,
                        evento.numero,
                        evento.cidade,
                        evento.uf,
                        'Brasil'
                    ].filter(Boolean).join(', ');
                    const url =
                        'https://nominatim.openstreetmap.org/search' +
                        '?format=json' +
                        '&limit=1' +
                        '&countrycodes=br' +
                        '&q=' +
                        encodeURIComponent(endereco);
                    const resposta = await fetch(url, {
                        headers: {Accept: 'application/json'}
                    });
                    const locais = await resposta.json();

                    if (!resposta.ok || !Array.isArray(locais) || !locais.length) {
                        throw new Error('Evento não localizado.');
                    }

                    const latitude = Number(locais[0].lat);
                    const longitude = Number(locais[0].lon);
                    if (!coordenadasValidas(latitude, longitude)) {
                        throw new Error('Coordenadas inválidas.');
                    }

                    destino = {lat: latitude, lng: longitude};
                    return true;
                } catch (erro) {
                    try {
                        if (await localizarEventoPorCep()) {
                            return true;
                        }
                    } catch (erroCep) {
                        console.error(erroCep);
                    }
                    console.error(erro);
                    mostrarMensagem(
                        'Não foi possível localizar automaticamente o endereço do evento.',
                        true
                    );
                    return false;
                }
            }


            /* =========================================================
               TROCA DE ETAPAS
            ========================================================= */

            function proximaEtapa(numero) {

                /* =====================================================
                   DESCOBRIR ETAPA ATUAL
                ===================================================== */

                const etapaAtualElemento =
                    document.querySelector(".etapa.ativa");

                if (!etapaAtualElemento) {
                    return;
                }

                const etapaAtual =
                    Number(
                        etapaAtualElemento.id.replace("etapa", "")
                    );


                /* =====================================================
                   PROTEÇÃO CONTRA PULAR ETAPA
                ===================================================== */

                if (numero !== etapaAtual + 1) {

                    if (numero > etapaAtual + 1) {

                        notificarErroPlanejamento(
                            "Conclua as etapas anteriores antes de continuar."
                        );

                    }

                    return;
                }


                /* =====================================================
                   ETAPA 1 - ORÇAMENTO
                ===================================================== */

                if (etapaAtual === 1) {

                    const campoOrcamento =
                        document.getElementById("orcamento");

                    const orcamento =
                        Number(campoOrcamento.value);

                    if (!orcamento || orcamento <= 0) {

                        notificarErroPlanejamento(
                            "Informe seu orçamento antes de continuar."
                        );

                        campoOrcamento.focus();

                        return;
                    }

                    if (orcamento < custoIngresso) {

                        notificarErroPlanejamento(
                            `O orçamento precisa ser de pelo menos R$ ${custoIngresso.toFixed(2)}.`
                        );

                        campoOrcamento.focus();

                        return;
                    }

                    /* Marca etapa 1 como concluída */
                    etapaConcluida[1] = true;
                }


                /* =====================================================
                   ETAPA 2 - TRANSPORTE
                ===================================================== */

                if (etapaAtual === 2) {

                    const campoTransporte =
                        document.getElementById("transporte");

                    const transporte =
                        Number(campoTransporte.value);

                    if (!transporteSelecionado) {

                        notificarErroPlanejamento(
                            "Escolha um meio de transporte antes de continuar."
                        );

                        return;
                    }

                    if (campoTransporte.value === '' || !Number.isFinite(transporte) || transporte < 0) {

                        notificarErroPlanejamento(
                            "Informe um valor de transporte igual ou maior que zero."
                        );

                        campoTransporte.focus();

                        return;
                    }

                    const orcamento =
                        Number(
                            document.getElementById("orcamento").value
                        ) || 0;

                    if (custoIngresso + transporte > orcamento) {

                        notificarErroPlanejamento(
                            "O valor do ingresso + transporte ultrapassa seu orçamento."
                        );

                        return;
                    }

                    /* Marca etapa 2 como concluída */
                    etapaConcluida[2] = true;
                }


                /* =====================================================
                   ETAPA 3 - ROTA
                ===================================================== */

                if (etapaAtual === 3) {

                    const mensagem =
                        document.getElementById("mensagemRota");

                    if (!origem) {

                        notificarErroPlanejamento(
                            "Defina seu ponto de partida para calcular a rota."
                        );

                        return;
                    }

                    if (!rotaCalculada) {

                        notificarErroPlanejamento(
                            "Calcule a rota antes de continuar."
                        );

                        if (mensagem) {

                            mensagem.textContent =
                                "Você precisa calcular a rota antes de continuar.";

                            mensagem.classList.add("erro-rota");

                        }

                        return;
                    }

                    const campoDistancia = document.getElementById('distanciaPlanejamento');
                    const campoTempo = document.getElementById('tempoPlanejamento');
                    const distancia = Number(campoDistancia.value);
                    const tempo = Number(campoTempo.value);

                    if (!Number.isFinite(distancia) || distancia <= 0) {
                        notificarErroPlanejamento('Informe uma distância válida para a rota.');
                        campoDistancia.focus();
                        return;
                    }

                    if (!Number.isFinite(tempo) || tempo < 1) {
                        notificarErroPlanejamento('Informe o tempo estimado da rota em minutos.');
                        campoTempo.focus();
                        return;
                    }

                    /* Marca etapa 3 como concluída */
                    etapaConcluida[3] = true;
                }


                /* =====================================================
                   ETAPA 4 - HOSPEDAGEM
                ===================================================== */

                if (etapaAtual === 4) {

                    /*
                     * O usuário precisa ter decidido:
                     *
                     * SIM → hospedagem selecionada
                     * NÃO → sem hospedagem
                     */

                    if (!hospedagemDecidida) {

                        notificarErroPlanejamento(
                            "Escolha se deseja hospedagem antes de continuar."
                        );

                        return;
                    }


                    /*
                     * Se escolheu hospedagem,
                     * precisa informar o valor.
                     */

                    if (hospedagemSelecionada === true) {

                        const campoHospedagem =
                            document.getElementById("hospedagem");

                        const hospedagem =
                            Number(campoHospedagem.value);

                        if (!hospedagem || hospedagem <= 0) {

                            notificarErroPlanejamento(
                                "Informe o valor da hospedagem antes de continuar."
                            );

                            campoHospedagem.focus();

                            return;
                        }
                    }


                    /* Marca etapa 4 como concluída */
                    etapaConcluida[4] = true;
                }


                /* =====================================================
                   ATUALIZA RESUMO
                ===================================================== */

                atualizarResumo();


                /* =====================================================
                   ESCONDE TODAS AS ETAPAS
                ===================================================== */

                document
                    .querySelectorAll(".etapa")
                    .forEach(etapa => {

                        etapa.classList.remove("ativa");

                    });


                /* =====================================================
                   MOSTRA PRÓXIMA ETAPA
                ===================================================== */

                const proxima =
                    document.getElementById(
                        `etapa${numero}`
                    );

                if (!proxima) {

                    console.error(
                        `Etapa ${numero} não encontrada.`
                    );

                    return;
                }

                proxima.classList.add("ativa");


                /* =====================================================
                   ATUALIZA TIMELINE
                ===================================================== */

                atualizarTimeline(numero);


                /* =====================================================
                   ETAPA 3 - MAPA
                ===================================================== */

                if (numero === 3) {

                    setTimeout(
                        async() => {

                            if (!destino) {

                                await localizarEvento();

                            }

                            if (destino) {

                                await iniciarMapa();

                            }

                        },
                        150
                    );
                }


                /* =====================================================
                   ETAPA 4 - HOSPEDAGEM
                ===================================================== */

                if (numero === 4 && hospedagemSelecionada) {

                    atualizarHospedagem();

                }


                /* =====================================================
                   ETAPA 5 - RESUMO FINAL
                ===================================================== */

                if (numero === 5) {

                    atualizarResumo();

                }

            }
            /* =========================================================
               VOLTAR
            ========================================================= */

            function voltarEtapa(numero) {


                const atual =
                    document.querySelector(
                        ".etapa.ativa"
                    );


                if (!atual) {

                    return;

                }


                const numeroAtual =
                    Number(
                        atual.id.replace(
                            "etapa",
                            ""
                        )
                    );


                if (
                    numero !==
                    numeroAtual - 1
                ) {

                    return;

                }


                document
                    .querySelectorAll(".etapa")
                    .forEach(
                        etapa =>
                        etapa.classList.remove(
                            "ativa"
                        )
                    );


                document
                    .getElementById(
                        `etapa${numero}`
                    )
                    .classList.add(
                        "ativa"
                    );


                atualizarTimeline(
                    numero
                );


                if (
                    numero === 3 &&
                    mapa
                ) {

                    setTimeout(
                        () =>
                        mapa.invalidateSize(),
                        100
                    );

                }

            }


            /* =========================================================
               TIMELINE
            ========================================================= */

            function atualizarTimeline(
                etapaAtual
            ) {


                document
                    .querySelectorAll(".passo")
                    .forEach(
                        (passo, index) => {


                            const numero =
                                index + 1;


                            passo.classList.remove(
                                "ativo"
                            );


                            passo.classList.remove(
                                "concluido"
                            );


                            if (
                                numero <
                                etapaAtual
                            ) {

                                passo.classList.add(
                                    "concluido"
                                );

                            }


                            if (
                                numero ===
                                etapaAtual
                            ) {

                                passo.classList.add(
                                    "ativo"
                                );

                            }

                        }
                    );

            }


            /* =========================================================
               TRANSPORTE
            ========================================================= */

            function selecionarTransporte(
                tipo
            ) {


                transporteSelecionado =
                    tipo;


                document.getElementById(
                        "transporteSelecionado"
                    ).textContent =
                    `Transporte selecionado: ${tipo}`;

                document.getElementById('meioTransportePlanejamento').value = tipo;
                document.querySelectorAll('[data-transporte]').forEach((opcao) => {
                    const selecionada = opcao.dataset.transporte === tipo;
                    opcao.classList.toggle('selecionada', selecionada);
                    opcao.setAttribute('aria-pressed', String(selecionada));
                });


                calcularEstimativaTransporte();

            }


            /* =========================================================
               ESTIMATIVA DE TRANSPORTE
            ========================================================= */

            function calcularEstimativaTransporte() {
                const box =
                    document.getElementById(
                        "estimativaTransporte"
                    );
                if (!transporteSelecionado) {
                    box.hidden = true;
                    return;
                }
                box.innerHTML = `
                    <strong>${transporteSelecionado} selecionado</strong><br>
                    <small>Informe abaixo o valor real consultado. O ShowMe não preenche preços simulados.</small>
                `;
                box.hidden = false;
            }

            function mostrarResumoPlanejamentoSalvo() {
                document.querySelectorAll('.etapa').forEach((etapa) => {
                    etapa.classList.remove('ativa');
                });
                document.getElementById('etapa5').classList.add('ativa');
                etapaConcluida = {1: true, 2: true, 3: true, 4: true};
                atualizarResumo();
                atualizarTimeline(5);
            }


            /* =========================================================
               HOSPEDAGEM
            ========================================================= */

            function atualizarEstadoHospedagem() {
                const opcaoSim = document.getElementById('opcaoHospedagemSim');
                const opcaoNao = document.getElementById('opcaoHospedagemNao');
                const simSelecionado = hospedagemDecidida && hospedagemSelecionada;
                const naoSelecionado = hospedagemDecidida && !hospedagemSelecionada;

                opcaoSim.classList.toggle('selecionada', simSelecionado);
                opcaoSim.setAttribute('aria-pressed', String(simSelecionado));
                opcaoNao.classList.toggle('selecionada', naoSelecionado);
                opcaoNao.setAttribute('aria-pressed', String(naoSelecionado));

                document.querySelectorAll('[data-hospedagem]').forEach((opcao) => {
                    const selecionada = simSelecionado && opcao.dataset.hospedagem === hospedagemNome;
                    opcao.classList.toggle('selecionada', selecionada);
                    opcao.setAttribute('aria-pressed', String(selecionada));
                });
            }

            function mostrarHospedagem() {


                hospedagemDecidida =
                    true;


                hospedagemSelecionada =
                    true;


                document.getElementById(
                        "opcoesHospedagem"
                    ).hidden = false;


                atualizarEstadoHospedagem();


                atualizarHospedagem();

            }


            function semHospedagem() {


                hospedagemDecidida =
                    true;


                hospedagemSelecionada =
                    false;


                hospedagemNome =
                    "";


                document.getElementById(
                        "opcoesHospedagem"
                    ).hidden = true;


                document.getElementById(
                        "hospedagem"
                    ).value =
                    0;


                document.getElementById(
                        "hospedagemSelecionada"
                    ).textContent =
                    "Você escolheu continuar sem hospedagem.";


                atualizarEstadoHospedagem();


                atualizarResumo();

            }


            function selecionarHospedagem(
                nome
            ) {


                hospedagemNome =
                    nome;


                hospedagemSelecionada =
                    true;


                document.getElementById(
                        "hospedagemSelecionada"
                    ).textContent =
                    `Hospedagem selecionada: ${nome}`;


                atualizarEstadoHospedagem();


                atualizarResumo();

            }


            /* =========================================================
               BUSCAR HOSPEDAGENS
            =========================================================

               Aqui usamos OpenStreetMap/Overpass para descobrir
               hospedagens próximas.

               O OpenStreetMap NÃO fornece preços ou disponibilidade;
               esses dados precisam ser consultados nos sites de reserva.

            ========================================================= */

            async function atualizarHospedagem() {


                const resultado =
                    document.getElementById(
                        "resultadoHospedagem"
                    );


                resultado.innerHTML =
                    "<p>Buscando hospedagens próximas ao evento...</p>";


                if (!destino) {

                    const localizado =
                        await localizarEvento();


                    if (!localizado) {

                        resultado.innerHTML =
                            "<p>Não foi possível localizar o evento para pesquisar hospedagens.</p>";


                        return;

                    }

                }


                try {


                    const query = `

            [out:json];

            (

                nwr[
                    "tourism"="hotel"
                ](
                    around:5000,
                    ${destino.lat},
                    ${destino.lng}
                );

                nwr[
                    "tourism"="hostel"
                ](
                    around:5000,
                    ${destino.lat},
                    ${destino.lng}
                );

                nwr[
                    "tourism"="guest_house"
                ](
                    around:5000,
                    ${destino.lat},
                    ${destino.lng}
                );

            );

            out center tags;

        `;


                    const resposta =
                        await fetch(
                            "https://overpass-api.de/api/interpreter", {
                                method: "POST",

                                body: query
                            }
                        );


                    const dados =
                        await resposta.json();


                    const locais =
                        (
                            dados.elements || []
                        )
                        .filter(
                            item =>
                            item.tags &&
                            item.tags.name
                        )
                        .slice(
                            0,
                            6
                        );


                    if (!locais.length) {


                        resultado.innerHTML = `

                <p>

                    Nenhuma hospedagem foi
                    encontrada automaticamente
                    na região do evento.

                </p>

            `;


                        return;

                    }


                    const listaHospedagens = document.createElement('div');
                    listaHospedagens.className = 'resultado-hospedagem-grid';

                    locais.forEach((item) => {
                        const nome = item.tags.name;
                        const tipo = item.tags.tourism === 'hostel'
                            ? 'Hostel'
                            : item.tags.tourism === 'guest_house'
                                ? 'Pousada'
                                : 'Hotel';
                        const endereco = [
                            item.tags['addr:street'],
                            item.tags['addr:housenumber'],
                            item.tags['addr:suburb']
                        ].filter(Boolean).join(', ');
                        const botao = document.createElement('button');
                        botao.type = 'button';
                        botao.className = 'resultado-hospedagem';
                        botao.dataset.hospedagem = nome;
                        botao.setAttribute('aria-pressed', 'false');

                        const icone = document.createElement('i');
                        icone.className = 'bi bi-building';
                        icone.setAttribute('aria-hidden', 'true');
                        const titulo = document.createElement('strong');
                        titulo.textContent = nome;
                        const descricao = document.createElement('small');
                        descricao.textContent = endereco ? `${tipo} · ${endereco}` : tipo;
                        const ajuda = document.createElement('span');
                        ajuda.textContent = 'Selecionar como referência';

                        botao.append(icone, titulo, descricao, ajuda);
                        botao.addEventListener('click', () => selecionarHospedagem(nome));
                        listaHospedagens.append(botao);
                    });

                    resultado.replaceChildren(listaHospedagens);
                    atualizarEstadoHospedagem();


                } catch (erro) {


                    console.error(
                        erro
                    );


                    resultado.innerHTML = `

            <p>

                Não foi possível buscar
                hospedagens neste momento.

            </p>

        `;

                }

            }


            /* =========================================================
               ÍCONE DO EVENTO
            ========================================================= */

            const iconeEvento =
                L.divIcon({

                    className: "pin-evento",

                    html: `

            <div class="pin-neon">

                <i
                    class="bi bi-geo-alt-fill"
                ></i>

            </div>

        `,

                    iconSize: [40, 40],

                    iconAnchor: [20, 40],

                    popupAnchor: [0, -40]

                });


            /* =========================================================
               INICIAR MAPA
            ========================================================= */

            async function iniciarMapa() {


                if (!destino) {


                    const localizado =
                        await localizarEvento();


                    if (!localizado) {

                        return;

                    }

                }


                if (
                    mapa !== null
                ) {

                    mapa.invalidateSize();

                    return;

                }


                mapa =
                    L.map(
                        "mapaRota"
                    )
                    .setView(
                        [
                            destino.lat,
                            destino.lng
                        ],
                        14
                    );


                L.tileLayer(

                    "https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png",

                    {

                        attribution: "&copy; OpenStreetMap &copy; CARTO",

                        subdomains: "abcd",

                        maxZoom: 20

                    }

                ).addTo(
                    mapa
                );


                marcadorDestino =
                    L.marker(

                        [
                            destino.lat,
                            destino.lng
                        ],

                        {
                            icon: iconeEvento
                        }

                    )
                    .addTo(
                        mapa
                    )
                    .bindPopup(

                        `

                <strong>
                    ${escapeHtml(
                        evento.local ||
                        evento.nome
                    )}
                </strong>

                <br>

                ${escapeHtml(
                    evento.cidade ||
                    ""
                )}
                -
                ${escapeHtml(
                    evento.uf ||
                    ""
                )}

            `

                    )
                    .openPopup();

            }


            /* =========================================================
               CENTRALIZAR EVENTO
            ========================================================= */

            async function centralizarEvento() {


                if (!destino) {

                    await localizarEvento();

                }


                if (!destino) {

                    return;

                }


                await iniciarMapa();


                mapa.setView(

                    [
                        destino.lat,
                        destino.lng
                    ],

                    16

                );


                if (
                    marcadorDestino
                ) {

                    marcadorDestino.openPopup();

                }

            }


            /* =========================================================
               LOCALIZAÇÃO DO USUÁRIO
            ========================================================= */

            function usarMinhaLocalizacao() {


                if (!navigator.geolocation) {


                    mostrarMensagem(

                        "Seu navegador não oferece suporte à localização.",

                        true

                    );


                    return;

                }


                mostrarMensagem(
                    "Solicitando sua localização..."
                );


                navigator.geolocation.getCurrentPosition(

                    async function(position) {


                        origem = {

                            lat: position.coords.latitude,

                            lng: position.coords.longitude

                        };


                        document.getElementById(
                                "origemTexto"
                            ).textContent =
                            "Sua localização atual";


                        document.getElementById(
                                "resumoOrigem"
                            ).textContent =
                            "Sua localização atual";


                        await iniciarMapa();


                        if (
                            marcadorOrigem
                        ) {


                            marcadorOrigem.setLatLng(

                                [
                                    origem.lat,
                                    origem.lng
                                ]

                            );


                        } else {


                            marcadorOrigem =
                                L.marker(

                                    [
                                        origem.lat,
                                        origem.lng
                                    ]

                                )
                                .addTo(
                                    mapa
                                )
                                .bindPopup(
                                    "<strong>Você está aqui</strong>"
                                );

                        }


                        calcularRota();


                    },


                    function(error) {


                        let texto =
                            "Não foi possível obter sua localização. Informe um ponto de partida manualmente.";


                        if (
                            error.code === 1
                        ) {

                            texto =
                                "Permissão de localização negada. Informe um ponto de partida manualmente ou permita o acesso à localização.";

                        }


                        mostrarMensagem(
                            texto,
                            true
                        );

                    },


                    {

                        enableHighAccuracy: true,

                        timeout: 10000,

                        maximumAge: 0

                    }

                );

            }


            /* =========================================================
               ORIGEM MANUAL
            ========================================================= */

            async function calcularRotaManual() {


                const campo =
                    document.getElementById(
                        "origemManual"
                    );


                const endereco =
                    campo.value.trim();


                if (!endereco) {


                    mostrarMensagem(

                        "Informe um ponto de partida.",

                        true

                    );


                    campo.focus();


                    return;

                }


                mostrarMensagem(
                    "Localizando o ponto de partida..."
                );


                try {


                    const url =
                        "https://nominatim.openstreetmap.org/search" +
                        "?format=json" +
                        "&limit=1" +
                        "&countrycodes=br" +
                        "&q=" +
                        encodeURIComponent(
                            endereco
                        );


                    const resposta =
                        await fetch(

                            url,

                            {

                                headers: {

                                    Accept: "application/json"

                                }

                            }

                        );


                    const locais =
                        await resposta.json();


                    if (!locais.length) {

                        throw new Error(
                            "Local não encontrado"
                        );

                    }


                    origem = {

                        lat: Number(
                            locais[0].lat
                        ),

                        lng: Number(
                            locais[0].lon
                        )

                    };


                    document.getElementById(
                            "origemTexto"
                        ).textContent =
                        endereco;


                    document.getElementById(
                            "resumoOrigem"
                        ).textContent =
                        endereco;


                    await iniciarMapa();


                    if (
                        marcadorOrigem
                    ) {


                        marcadorOrigem.setLatLng(

                            [
                                origem.lat,
                                origem.lng
                            ]

                        );


                    } else {


                        marcadorOrigem =
                            L.marker(

                                [
                                    origem.lat,
                                    origem.lng
                                ]

                            )
                            .addTo(
                                mapa
                            )
                            .bindPopup(
                                "<strong>Origem</strong>"
                            );

                    }


                    calcularRota();


                } catch (erro) {


                    console.error(
                        erro
                    );


                    mostrarMensagem(

                        "Não encontramos esse local. Tente informar uma cidade, endereço ou ponto de referência.",

                        true

                    );

                }

            }


            /* =========================================================
               CALCULAR ROTA
            ========================================================= */

            async function calcularRota() {


                if (!origem) {

                    return;

                }


                if (!destino) {


                    const localizado =
                        await localizarEvento();


                    if (!localizado) {

                        return;

                    }

                }


                /*
                    Carro e Uber:
                    rota rodoviária detalhada.

                    Ônibus e avião:
                    o Google Maps será utilizado
                    para a rota correspondente.
                */

                const usaRotaRodoviaria =

                    transporteSelecionado ===
                    "Carro" ||

                    transporteSelecionado ===
                    "Uber" ||

                    transporteSelecionado ===
                    "";


                if (!usaRotaRodoviaria) {


                    rotaCalculada =
                        true;


                    etapaConcluida[3] =
                        true;


                    document.getElementById(
                            "informacoesRota"
                        ).hidden = false;


                    document.getElementById(
                            "navegacaoBox"
                        ).hidden = false;


                    document.getElementById(
                            "distanciaRota"
                        ).textContent =
                        "Ver no mapa";


                    document.getElementById(
                            "tempoRota"
                        ).textContent =
                        "Consultar";


                    document.getElementById(
                            "resumoDistancia"
                        ).textContent =
                        "Ver no mapa";


                    document.getElementById(
                            "resumoTempo"
                        ).textContent =
                        "Consultar";


                    mostrarMensagem(

                        `Para ${transporteSelecionado}, a rota detalhada será aberta no aplicativo de navegação.`

                    );


                    return;

                }


                mostrarMensagem(
                    "Calculando rota..."
                );


                const url =

                    `https://router.project-osrm.org/route/v1/driving/` +

                    `${origem.lng},${origem.lat};` +

                    `${destino.lng},${destino.lat}` +

                    `?overview=full&geometries=geojson`;


                try {


                    const resposta =
                        await fetch(
                            url
                        );


                    const dados =
                        await resposta.json();


                    if (!dados.routes ||
                        !dados.routes.length
                    ) {

                        throw new Error(
                            "Rota não encontrada"
                        );

                    }


                    const rota =
                        dados.routes[0];


                    if (
                        rotaLayer
                    ) {

                        mapa.removeLayer(
                            rotaLayer
                        );

                    }


                    rotaLayer =
                        L.geoJSON(

                            rota.geometry,

                            {

                                style: {

                                    color: "#00ff00",

                                    weight: 5,

                                    opacity: 0.9

                                }

                            }

                        ).addTo(
                            mapa
                        );


                    mapa.fitBounds(

                        rotaLayer.getBounds(),

                        {

                            padding: [30, 30]

                        }

                    );


                    const distanciaKm =
                        rota.distance /
                        1000;

                    document.getElementById('distanciaPlanejamento').value = distanciaKm.toFixed(2);


                    document.getElementById(
                            "distanciaRota"
                        ).textContent =
                        `${distanciaKm.toFixed(1)} km`;


                    document.getElementById(
                            "resumoDistancia"
                        ).textContent =
                        `${distanciaKm.toFixed(1)} km`;


                    const minutos =
                        Math.round(
                            rota.duration /
                            60
                        );

                    document.getElementById('tempoPlanejamento').value = String(minutos);


                    let tempoTexto;


                    if (
                        minutos >= 60
                    ) {


                        const horas =
                            Math.floor(
                                minutos / 60
                            );


                        const minutosRestantes =
                            minutos % 60;


                        tempoTexto =
                            `${horas}h ${minutosRestantes}min`;


                    } else {


                        tempoTexto =
                            `${minutos} min`;

                    }


                    document.getElementById(
                            "tempoRota"
                        ).textContent =
                        tempoTexto;


                    document.getElementById(
                            "resumoTempo"
                        ).textContent =
                        tempoTexto;


                    document.getElementById(
                            "informacoesRota"
                        ).hidden = false;


                    document.getElementById(
                            "navegacaoBox"
                        ).hidden = false;


                    rotaCalculada =
                        true;


                    etapaConcluida[3] =
                        true;


                    mostrarMensagem(
                        "Rota calculada com sucesso."
                    );


                } catch (erro) {


                    console.error(
                        erro
                    );


                    mostrarMensagem(

                        "Não foi possível calcular a rota no momento.",

                        true

                    );

                }

            }


            /* =========================================================
               GOOGLE MAPS
            ========================================================= */

            function abrirGoogleMaps() {


                if (!origem ||
                    !destino
                ) {

                    notificarErroPlanejamento(
                        "Calcule uma rota primeiro."
                    );


                    return;

                }


                let modo =
                    "driving";


                if (
                    transporteSelecionado ===
                    "Ônibus"
                ) {

                    modo =
                        "transit";

                }


                if (
                    transporteSelecionado ===
                    "Avião"
                ) {

                    modo =
                        "transit";

                }


                const url =

                    `https://www.google.com/maps/dir/?api=1` +

                    `&origin=${encodeURIComponent(
            `${origem.lat},${origem.lng}`
        )}` +

        `&destination=${encodeURIComponent(
            `${destino.lat},${destino.lng}`
        )}` +

        `&travelmode=${modo}`;


    window.open(
        url,
        "_blank"
    );

}


/* =========================================================
   WAZE
========================================================= */

function abrirWaze() {


    if (
        !destino
    ) {

        notificarErroPlanejamento(
            "Calcule uma rota primeiro."
        );


        return;

    }


    const url =

        `https://www.waze.com/ul` +

        `?ll=${destino.lat},${destino.lng}` +

        `&navigate=yes`;


    window.open(
        url,
        "_blank"
    );

}


/* =========================================================
   ATUALIZAR RESUMO
========================================================= */

function atualizarResumo() {


    const orcamento =
        Number(
            document.getElementById(
                "orcamento"
            ).value
        ) || 0;


    const transporte =
        Number(
            document.getElementById(
                "transporte"
            ).value
        ) || 0;


    const hospedagem =
        Number(
            document.getElementById(
                "hospedagem"
            ).value
        ) || 0;


    const total =
        custoIngresso +
        transporte +
        hospedagem;


    const saldo =
        orcamento -
        total;


    document.getElementById(
        "valorOrcamento"
    ).textContent =
        moeda(
            orcamento
        );


    document.getElementById(
        "restante"
    ).textContent =
        moeda(

            orcamento -
            custoIngresso -
            transporte

        );


    document.getElementById(
        "resumoTransporte"
    ).textContent =
        moeda(
            transporte
        );


    document.getElementById(
        "resumoHospedagem"
    ).textContent =
        moeda(
            hospedagem
        );


    document.getElementById(
        "totalFinal"
    ).textContent =
        moeda(
            total
        );


    document.getElementById(
        "orcamentoFinal"
    ).textContent =
        moeda(
            orcamento
        );


    document.getElementById(
        "gastosFinal"
    ).textContent =
        moeda(
            total
        );


    document.getElementById(
        "saldoFinal"
    ).textContent =
        moeda(
            saldo
        );


    document.getElementById(
        "resumoMeioTransporte"
    ).textContent =

        transporteSelecionado ||
        "—";

}


/* =========================================================
   FINALIZAR
========================================================= */

let idRotaGoogleAgenda = null;
let statusGoogleAgenda = null;

function notificarErroPlanejamento(mensagem) {
    window.ShowMeUI.toast(mensagem, {variante: 'erro'});
}

function abrirEtapaPlanejamento(numero) {
    document.querySelectorAll('.etapa').forEach((etapa) => etapa.classList.remove('ativa'));
    document.getElementById(`etapa${numero}`).classList.add('ativa');
    atualizarTimeline(numero);

    if (numero === 3) {
        window.setTimeout(async () => {
            if (!destino) await localizarEvento();
            if (destino) await iniciarMapa();
            mapa?.invalidateSize();
        }, 150);
    }
}

function eventoPlanejamentoEncerrado() {
    const dataEvento = String(evento.data || '').slice(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dataEvento)) return false;

    const hoje = new Date();
    const dataHoje = [
        hoje.getFullYear(),
        String(hoje.getMonth() + 1).padStart(2, '0'),
        String(hoje.getDate()).padStart(2, '0')
    ].join('-');

    return dataEvento < dataHoje;
}

function configurarBotaoPlanejamentoFinalizado() {
    const botao = document.getElementById('botaoFinalizarPlanejamento');
    const eventoEncerrado = eventoPlanejamentoEncerrado();
    botao.type = 'button';
    botao.dataset.estado = 'finalizado';
    botao.classList.add('botao-editar-planejamento');
    botao.innerHTML = '<i class="bi bi-pencil-square" aria-hidden="true"></i><span>Editar planejamento</span>';
    botao.disabled = eventoEncerrado;
    botao.hidden = eventoEncerrado;
    document.getElementById('botaoVoltarResumo').hidden = true;
    document.getElementById('botaoImprimirPlanejamento').hidden = false;
}

async function habilitarEdicaoPlanejamento() {
    if (eventoPlanejamentoEncerrado()) {
        window.ShowMeUI.toast('O planejamento de um evento encerrado não pode ser alterado.', {
            variante: 'erro'
        });
        return;
    }

    const deveEditar = await window.ShowMeUI.confirmar({
        titulo: 'Editar planejamento',
        texto: 'Deseja reabrir este planejamento? As alterações só serão gravadas quando você salvar novamente.',
        confirmarTexto: 'Editar',
        cancelarTexto: 'Cancelar',
        variante: 'edicao'
    });
    if (!deveEditar) return;

    const botao = document.getElementById('botaoFinalizarPlanejamento');
    botao.type = 'submit';
    botao.dataset.estado = 'editando';
    botao.innerHTML = '<i class="bi bi-check2-circle" aria-hidden="true"></i><span>Salvar alterações</span>';
    document.getElementById('botaoVoltarResumo').hidden = false;
    document.getElementById('botaoImprimirPlanejamento').hidden = true;
    document.getElementById('mensagemPlanejamento').textContent = '';
    abrirEtapaPlanejamento(1);
}

async function lerRespostaJson(resposta) {
    const texto = await resposta.text();
    try {
        return texto ? JSON.parse(texto) : {};
    } catch (_) {
        throw new Error('O servidor retornou uma resposta inválida.');
    }
}

async function obterStatusGoogleAgenda() {
    const resposta = await fetch('api/google-calendar', {headers: {Accept: 'application/json'}});
    const dados = await lerRespostaJson(resposta);

    if (resposta.status === 401) {
        window.location.href = 'login.php';
        return null;
    }
    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível consultar o Google Agenda.');
    }

    statusGoogleAgenda = dados;
    return dados;
}

async function exibirAcoesGoogleAgenda(idRota) {
    idRotaGoogleAgenda = Number(idRota);
    const caixa = document.getElementById('acoesGoogleAgenda');
    const texto = document.getElementById('statusGoogleAgendaPlanejamento');
    const botaoAdicionar = document.getElementById('btnAdicionarGoogleAgenda');
    const botaoConectar = document.getElementById('btnConectarGoogleAgendaPlanejamento');
    caixa.hidden = false;

    try {
        const status = await obterStatusGoogleAgenda();
        const conectado = Boolean(status?.conectado);
        texto.textContent = conectado
            ? 'Planejamento pronto para ser adicionado à sua agenda.'
            : 'Conecte o Google Agenda para exportar este planejamento.';
        botaoAdicionar.hidden = !conectado;
        botaoConectar.hidden = conectado || !status?.configurado;

        if (!status?.configurado) {
            texto.textContent = 'Google Agenda ainda não foi configurado neste ambiente.';
        }
    } catch (erro) {
        texto.textContent = erro.message;
        botaoAdicionar.hidden = true;
        botaoConectar.hidden = true;
    }
}

async function carregarPlanejamentoExistente() {
    const resposta = await fetch('api/planejamento/', {headers: {Accept: 'application/json'}});
    const dados = await lerRespostaJson(resposta);

    if (!resposta.ok) {
        return null;
    }

    const existente = (dados.planejamentos || []).find(
        (planejamento) => Number(planejamento.id_evento) === Number(evento.id)
    );

    if (existente) {
        idPlanejamentoExistente = Number(existente.id_rota);
        custoIngresso = Number(existente.custo_ingresso ?? custoIngresso);
        document.getElementById('resumoIngresso').textContent = moeda(custoIngresso);
        document.getElementById('orcamento').value = existente.orcamento_total ?? 0;
        document.getElementById('transporte').value = existente.custo_transporte ?? 0;
        document.getElementById('hospedagem').value = existente.custo_hospedagem ?? 0;
        document.getElementById('meioTransportePlanejamento').value = existente.meio_transporte || '';
        document.getElementById('distanciaPlanejamento').value = existente.distancia_km ?? '';
        document.getElementById('tempoPlanejamento').value = existente.tempo_estimado ?? '';
        document.getElementById('origemManual').value = existente.origem || '';
        transporteSelecionado = existente.meio_transporte || '';
        hospedagemSelecionada = Boolean(existente.hospedagem_necessaria);
        hospedagemDecidida = true;
        hospedagemNome = existente.nome_hospedagem || '';
        document.getElementById('opcoesHospedagem').hidden = !hospedagemSelecionada;
        origem = existente.origem ? {lat: null, lng: null, descricao: existente.origem} : origem;
        rotaCalculada = Boolean(existente.distancia_km && existente.tempo_estimado);
        document.getElementById('transporteSelecionado').textContent = transporteSelecionado
            ? `Transporte selecionado: ${transporteSelecionado}`
            : 'Nenhum transporte selecionado.';
        document.getElementById('hospedagemSelecionada').textContent = hospedagemSelecionada
            ? `Hospedagem selecionada: ${hospedagemNome || 'não informada'}`
            : 'Você escolheu continuar sem hospedagem.';
        document.getElementById('resumoOrigem').textContent = existente.origem || '—';
        document.getElementById('resumoDistancia').textContent = existente.distancia_km !== null
            ? `${Number(existente.distancia_km).toLocaleString('pt-BR')} km`
            : '—';
        document.getElementById('resumoTempo').textContent = existente.tempo_estimado !== null
            ? `${Number(existente.tempo_estimado).toLocaleString('pt-BR')} min`
            : '—';
        if (transporteSelecionado) selecionarTransporte(transporteSelecionado);
        atualizarEstadoHospedagem();
        atualizarResumo();
        configurarBotaoPlanejamentoFinalizado();
        await exibirAcoesGoogleAgenda(existente.id_rota);
        return existente;
    }

    return null;
}

function confirmarFinalizacaoComConflito(conflitos) {
    const modal = document.getElementById('modalConflitoAgenda');
    const lista = document.getElementById('listaConflitosAgenda');
    lista.replaceChildren(...conflitos.map((conflito) => {
        const item = document.createElement('li');
        item.textContent = conflito.titulo;
        return item;
    }));

    if (typeof modal.showModal !== 'function') {
        return window.ShowMeUI.confirmar({
            titulo: 'Conflito no Google Agenda',
            texto: 'Há outro compromisso no período deste evento, considerando também o tempo de deslocamento.',
            confirmarTexto: 'Finalizar mesmo assim',
            cancelarTexto: 'Cancelar',
            variante: 'importante'
        });
    }

    modal.showModal();
    return new Promise((resolver) => {
        modal.addEventListener('close', () => resolver(modal.returnValue === 'finalizar'), {once: true});
    });
}

async function verificarConflitoGoogleAgenda(tempo) {
    const status = statusGoogleAgenda || await obterStatusGoogleAgenda();
    if (!status?.conectado) {
        return true;
    }

    const resposta = await fetch('api/google-calendar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', Accept: 'application/json'},
        body: JSON.stringify({
            acao: 'verificar_conflito',
            id_evento: evento.id,
            tempo_estimado: Number(tempo)
        })
    });
    const dados = await lerRespostaJson(resposta);

    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível verificar conflitos na agenda.');
    }

    return dados.conflito
        ? confirmarFinalizacaoComConflito(dados.conflitos || [])
        : true;
}

async function adicionarAoGoogleAgenda() {
    const botao = document.getElementById('btnAdicionarGoogleAgenda');
    const texto = document.getElementById('statusGoogleAgendaPlanejamento');
    botao.disabled = true;
    texto.textContent = 'Adicionando ao Google Agenda...';

    try {
        const resposta = await fetch('api/google-calendar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', Accept: 'application/json'},
            body: JSON.stringify({acao: 'exportar', id_rota: idRotaGoogleAgenda})
        });
        const dados = await lerRespostaJson(resposta);
        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível adicionar o evento à agenda.');
        }

        texto.textContent = dados.mensagem;
        botao.hidden = true;
    } catch (erro) {
        texto.textContent = erro.message;
        botao.disabled = false;
    }
}

async function finalizarPlanejamento(eventoSubmit) {
    eventoSubmit.preventDefault();

    if (!etapaConcluida[1] || !etapaConcluida[2] || !etapaConcluida[3] || !etapaConcluida[4]) {
        window.ShowMeUI.toast('Conclua todas as etapas antes de finalizar.', {variante: 'erro'});
        return;
    }

    const mensagem = document.getElementById('mensagemPlanejamento');
    const botao = document.getElementById('botaoFinalizarPlanejamento');
    const meioTransporte = document.getElementById('meioTransportePlanejamento').value;
    const distancia = document.getElementById('distanciaPlanejamento').value;
    const tempo = document.getElementById('tempoPlanejamento').value;
    const orcamento = Number(document.getElementById('orcamento').value) || 0;
    const custoTransporte = Number(document.getElementById('transporte').value) || 0;
    const custoHospedagem = hospedagemSelecionada
        ? Number(document.getElementById('hospedagem').value) || 0
        : 0;
    const origemInformada = document.getElementById('origemManual').value.trim()
        || document.getElementById('origemTexto').textContent.trim();

    const atualizandoPlanejamento = Number.isInteger(idPlanejamentoExistente)
        && idPlanejamentoExistente > 0;
    const deveSalvar = await window.ShowMeUI.confirmar({
        titulo: atualizandoPlanejamento ? 'Salvar alterações' : 'Finalizar planejamento',
        texto: atualizandoPlanejamento
            ? 'Confirma a substituição dos dados salvos por estas novas informações?'
            : 'Confirma este planejamento? Ele passará a aparecer em Meus Eventos e no calendário.',
        confirmarTexto: atualizandoPlanejamento ? 'Salvar' : 'Finalizar',
        cancelarTexto: 'Cancelar',
        variante: atualizandoPlanejamento ? 'edicao' : 'importante'
    });
    if (!deveSalvar) return;

    mensagem.textContent = 'Verificando sua agenda...';
    mensagem.classList.remove('erro');
    botao.disabled = true;

    try {
        const deveFinalizar = await verificarConflitoGoogleAgenda(tempo);
        if (!deveFinalizar) {
            mensagem.textContent = 'Planejamento não finalizado.';
            botao.disabled = false;
            return;
        }

        mensagem.textContent = 'Salvando planejamento...';
        const endpointPlanejamento = atualizandoPlanejamento
            ? `api/planejamento/${idPlanejamentoExistente}`
            : 'api/planejamento/';
        const resposta = await fetch(endpointPlanejamento, {
            method: atualizandoPlanejamento ? 'PUT' : 'POST',
            headers: {'Content-Type': 'application/json', Accept: 'application/json'},
            body: JSON.stringify({
                id_evento: evento.id,
                meio_transporte: meioTransporte,
                distancia_km: distancia,
                tempo_estimado: tempo,
                origem: origemInformada,
                orcamento_total: orcamento,
                custo_ingresso: custoIngresso,
                custo_transporte: custoTransporte,
                hospedagem_necessaria: hospedagemSelecionada,
                nome_hospedagem: hospedagemNome,
                custo_hospedagem: custoHospedagem
            })
        });
        const dados = await lerRespostaJson(resposta);

        if (!resposta.ok && !(resposta.status === 409 && dados.id_rota)) {
            throw new Error(dados.erro || 'Não foi possível finalizar o planejamento.');
        }

        const idRota = dados.planejamento?.id_rota || dados.id_rota;
        idPlanejamentoExistente = Number(idRota) || idPlanejamentoExistente;
        atualizarResumo();
        mensagem.textContent =
            resposta.status === 409
                ? 'Este evento já possui um planejamento finalizado.'
                : atualizandoPlanejamento
                    ? 'Planejamento atualizado com sucesso.'
                    : 'Planejamento finalizado com sucesso.';
        configurarBotaoPlanejamentoFinalizado();

        if (resposta.status !== 409) {
            const opcoesToast = {variante: 'sucesso'};
            if (!atualizandoPlanejamento) {
                opcoesToast.duracao = 8000;
                opcoesToast.acao = {
                    texto: 'Ver meus planejados',
                    href: 'meusEventos.php?aba=planejados'
                };
            }
            window.ShowMeUI.toast(
                atualizandoPlanejamento
                    ? 'Planejamento atualizado com sucesso!'
                    : 'Planejamento salvo com sucesso!',
                opcoesToast
            );
        }

        await exibirAcoesGoogleAgenda(idRota);
    } catch (erro) {
        mensagem.textContent = erro.message;
        mensagem.classList.add('erro');
        botao.disabled = false;
    }
}

document.getElementById('btnAdicionarGoogleAgenda')
    .addEventListener('click', adicionarAoGoogleAgenda);

/* =========================================================
   ESCAPAR HTML
========================================================= */

function escapeHtml(
    texto
) {

    return String(
        texto ?? ""
    )

        .replaceAll(
            "&",
            "&amp;"
        )

        .replaceAll(
            "<",
            "&lt;"
        )

        .replaceAll(
            ">",
            "&gt;"
        )

        .replaceAll(
            '"',
            "&quot;"
        )

        .replaceAll(
            "'",
            "&#039;"
        );

}


/* =========================================================
   EVENTOS DE INPUT
========================================================= */

document
    .getElementById(
        "orcamento"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );


document
    .getElementById(
        "transporte"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );


document
    .getElementById(
        "hospedagem"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );

document
    .getElementById("formPlanejamento")
    .addEventListener("submit", finalizarPlanejamento);

document.getElementById('botaoFinalizarPlanejamento').addEventListener('click', (eventoClique) => {
    if (eventoClique.currentTarget.dataset.estado !== 'finalizado') return;
    eventoClique.preventDefault();
    habilitarEdicaoPlanejamento();
});

document.getElementById('botaoImprimirPlanejamento').addEventListener('click', () => {
    window.print();
});

document.getElementById('transporte').addEventListener('keydown', (eventoTeclado) => {
    if (eventoTeclado.key !== 'Enter') return;
    eventoTeclado.preventDefault();
    proximaEtapa(3);
});

document.getElementById('hospedagem').addEventListener('keydown', (eventoTeclado) => {
    if (eventoTeclado.key !== 'Enter') return;
    eventoTeclado.preventDefault();
    proximaEtapa(5);
});


/* =========================================================
   INICIALIZAÇÃO
========================================================= */

async function inicializarPlanejamento() {
    let planejamentoExistente = null;

    try {
        await carregarEvento();
        planejamentoExistente = await carregarPlanejamentoExistente();
    } catch (erro) {
        document.getElementById("tituloEvento").textContent = erro.message;
        document.getElementById("botaoFinalizarPlanejamento").disabled = true;
        mostrarMensagem(erro.message, true);
    }

    atualizarResumo();
    if (planejamentoExistente) {
        mostrarResumoPlanejamentoSalvo();
    } else {
        atualizarTimeline(1);
    }
}

inicializarPlanejamento();
        </script>

        <script src="assets/js/main.js"></script>

    </body>

    </html>
