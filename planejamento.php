<?php
require_once __DIR__ . '/config/verificaUsuarioComum.php';

$idInformado = $_GET['id'] ?? ($_GET['id_evento'] ?? null);

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

        <link href="assets/img/showme.png" rel="icon">

        <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">

        <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

        <link rel="stylesheet" href="assets/css/planejamento.css">

        <!-- CSS compartilhado por último para preservar o cabeçalho -->

        <link rel="stylesheet" href="assets/css/main.css">

        <!-- FOLHA DE IMPRESSÃO DO RESUMO (CARTAZ URBANO) -->

        <link rel="stylesheet" href="assets/css/impressao.css" media="print">
    </head>

    <body class="com-cabecalho-padrao cabecalho-tipo-c pagina-planejamento" data-evento-id="<?= $idEvento ?>">
        <?php
        $tipoCabecalho = 'C';
        $configuracaoCabecalho = [
            'titulo_primario' => 'Planejamento',
            'titulo_secundario' => 'do Evento',
            'icone' => 'bi-map-fill',
            'fallback' => 'meusEventos.php',
        ];
        require __DIR__ . '/cabecalho.php';
        ?>

        <p id="tituloEvento" class="planejamento-evento-atual">
            Carregando evento...
        </p>

        <main class="planejamento-container container">
            <div class="row g-4 g-xl-5 align-items-start">
            <aside class="progresso col-12 col-lg-3">
                <h3>
                    Progresso
                </h3>

                <div class="timeline">
                    <div class="passo ativo" id="passo1">
                        <div class="numero">
                            1
                        </div>

                        <span>
                    Orçamento
                </span>
                    </div>

                    <div class="passo" id="passo2">
                        <div class="numero">
                            2
                        </div>

                        <span>
                    Transporte
                </span>
                    </div>

                    <div class="passo" id="passo3">
                        <div class="numero">
                            3
                        </div>

                        <span>
                    Rota
                </span>
                    </div>

                    <div class="passo" id="passo4">
                        <div class="numero">
                            4
                        </div>

                        <span>
                    Hospedagem
                </span>
                    </div>

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

            <div class="col-12 col-lg-9">
            <section class="card-planejamento">
                <div class="etapa ativa" id="etapa1">
                    <div class="titulo-card">
                        <i class="bi bi-currency-dollar"></i>

                        <h2>
                            Orçamento e Custos
                        </h2>
                    </div>

                    <p>
                        Defina o orçamento total da viagem e os gastos com ingresso e extras.
                        Transporte e hospedagem serão informados nas etapas seguintes.
                    </p>

                    <div class="gastos-planejamento">
                        <h3>
                            <i class="bi bi-wallet2" aria-hidden="true"></i> Gastos do Evento
                        </h3>

                        <label for="orcamento">
                            Orçamento planejado total (R$)
                        </label>

                        <input type="number" id="orcamento" placeholder="500" min="0" step="0.01" inputmode="decimal" required>

                        <div class="campos-gastos-planejamento">
                            <div class="campo-gasto">
                                <label for="custoIngresso">Valor do Ingresso / Entrada (R$)</label>
                                <input type="number" id="custoIngresso" value="0" min="0" step="0.01" inputmode="decimal" aria-describedby="custoMinimoEvento">
                                <div class="alerta alerta-custo-evento" id="custoMinimoEvento" role="status" aria-live="polite">
                                    Carregando o valor de acesso ao evento...
                                </div>
                                <label class="opcao-ingresso-gratuito" for="ingressoGratuito">
                                    <input type="checkbox" id="ingressoGratuito">
                                    <span>Ingresso gratuito / Cortesia</span>
                                </label>
                            </div>
                        </div>

                        <div class="orcamento-restante resumo-gastos-etapa">
                            <span>Total de gastos</span>
                            <strong id="totalGastosEtapa" aria-live="polite">R$ 0,00</strong>
                            <small id="composicaoGastosEtapa">Ingresso + Transporte + Hospedagem</small>
                            <small id="saldoGastosEtapa">Saldo do orçamento: R$ 0,00</small>
                        </div>
                    </div>

                    <button onclick="proximaEtapa(2)">
                Continuar
            </button>
                </div>

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

                    <div id="estimativaTransporte" hidden>
                    </div>

                    <p id="transporteSelecionado">Nenhum transporte selecionado.</p>

                    <div class="campo-gasto campo-gasto-etapa">
                        <label for="custoTransporte">Custo do Transporte (R$)</label>
                        <input type="number" id="custoTransporte" value="0" min="0" step="0.01" inputmode="decimal" aria-describedby="ajudaCustoTransporte">
                        <small id="ajudaCustoTransporte" class="ajuda-campo">
                            Informe o valor total do transporte, incluindo ida, volta e taxas.
                        </small>
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

                <div class="etapa" id="etapa3">
                    <div class="titulo-card">
                        <i class="bi bi-map-fill"></i>

                        <h2>
                            Planeje sua rota
                        </h2>
                    </div>

                    <p>
                        Veja a localização do evento e calcule a melhor rota a partir do seu ponto de partida.
                    </p>

                    <div class="localizacao-box">
                        <strong>

                    <i class="bi bi-geo-alt-fill"></i>

                    Local do evento
                </strong>

                        <p id="enderecoEvento">
                            Carregando endereço...
                        </p>

                        <button type="button" onclick="centralizarEvento()">
                    <i class="bi bi-map-fill"></i>

                    Ver localização
                </button>
                    </div>

                    <div id="mapaRota"></div>

                    <div class="origem-rota">
                        <h3>
                            <i class="bi bi-map-fill"></i> De onde você vai sair?
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

                    <p id="mensagemRota" class="mensagem-rota" role="status" aria-live="polite" aria-atomic="true">
                    </p>

                    <div class="dados-rota-planejamento">
                        <h3><i class="bi bi-map-fill" aria-hidden="true"></i> Dados da rota</h3>
                        <p id="instrucoesDadosRota">A distância e o tempo estimado são preenchidos automaticamente ao calcular a rota.</p>
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

                    <div class="navegacao-box" id="navegacaoBox" hidden>
                        <h3>
                            <i class="bi bi-compass"></i> Continuar navegação
                        </h3>

                        <p>
                            Você pode continuar sua rota utilizando um aplicativo de navegação.
                        </p>

                        <div class="botoes-navegacao">
                            <button type="button" onclick="abrirGoogleMaps()">
                        <i class="bi bi-map-fill"></i>

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

                    <div class="botoes">
                        <button onclick="voltarEtapa(2)">
                    Voltar
                </button>

                        <button onclick="proximaEtapa(4)">
                    Continuar
                </button>
                    </div>
                </div>

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

                        <p id="hospedagemSelecionada">Nenhuma hospedagem selecionada.</p>

                        <div class="campo-gasto campo-gasto-etapa">
                            <label for="custoHospedagem">Custo da Hospedagem (R$)</label>
                            <input type="number" id="custoHospedagem" value="0" min="0" step="0.01" inputmode="decimal" aria-describedby="ajudaCustoHospedagem">
                            <small id="ajudaCustoHospedagem" class="ajuda-campo">
                                Informe o valor total das diárias e taxas da hospedagem.
                            </small>
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

                <div class="etapa" id="etapa5">
                    <div class="titulo-card">
                        <i class="bi bi-check-circle"></i>

                        <h2>
                            Resumo do Planejamento
                        </h2>
                    </div>

                    <div class="nome-evento-poster">
                        <span class="nome-evento-poster-rotulo">Atração principal</span>
                        <strong id="nomeEventoPoster">Carregando...</strong>
                    </div>

                    <div class="resumo-box resumo-evento-planejado">
                        <h3>
                            <i class="bi bi-calendar-event" aria-hidden="true"></i> Evento
                        </h3>

                        <div class="linha-resumo linha-nome-evento">
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

                    <div class="resumo-box resumo-custos">
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
                            <span>Total</span>

                            <span id="totalFinal">
                        R$ 0,00
                    </span>
                        </div>
                    </div>

                    <div class="resumo-box resumo-orcamento">
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
                            <span>Saldo</span>

                            <span id="saldoFinal">
                        R$ 0,00
                    </span>
                        </div>
                    </div>

                    <div class="resumo-box rota-resumo">
                        <h3>
                            <i class="bi bi-map-fill icone-rota-impressao"></i> Rota da viagem
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

                    <div class="dicas">
                        <h3>
                            <i class="bi bi-info-circle"></i> Dicas de Viagem
                        </h3>

                        <p><i class="bi bi-capsule" aria-hidden="true"></i><span>Leve medicamentos básicos e seu plano de saúde.</span></p>

                        <p><i class="bi bi-brightness-high" aria-hidden="true"></i><span>Confira a previsão do tempo e leve roupas adequadas.</span></p>

                        <p><i class="bi bi-ticket-perforated" aria-hidden="true"></i><span>Não esqueça de adquirir seus ingressos com antecedência.</span></p>

                        <p><i class="bi bi-phone" aria-hidden="true"></i><span>Consulte o canal oficial do evento para acompanhar atualizações.</span></p>
                    </div>

                    <div class="rodape-impressao">
                        SHOWME • PLATAFORMA DE EVENTOS E PLANEJAMENTO DE VIAGENS
                    </div>

                    <form id="formPlanejamento" class="form-planejamento-api">
                        <input id="meioTransportePlanejamento" type="hidden" value="">

                        <p id="mensagemPlanejamento" class="mensagem-planejamento-api" aria-live="polite"></p>

                        <div id="acoesGoogleAgenda" class="acoes-google-agenda" hidden>
                            <p id="statusGoogleAgendaPlanejamento" role="status" aria-live="polite" aria-atomic="true"></p>
                            <button id="btnAdicionarGoogleAgenda" type="button" hidden>
                                <i class="bi bi-calendar-plus" aria-hidden="true"></i>
                                Adicionar ao Google Agenda
                            </button>
                            <a id="btnVerGoogleAgenda" class="ver-google-agenda" href="#" target="_blank" rel="noopener noreferrer" hidden>
                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                Ver no Google Agenda
                            </a>
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

        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
        </script>

        <script src="assets/js/planejamento.js"></script>

        <script src="assets/js/main.js"></script>
    </body>
    </html>
