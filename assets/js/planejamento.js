/* Planejamento de viagem do evento: carregamento, custos, rota, resumo e Google Agenda. */
/* =========================================================
   EVENTO CARREGADO PELA API
========================================================= */

const evento = {
    id: Number(document.body.dataset.eventoId),
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

    const campoIngresso = document.getElementById('custoIngresso');
    const opcaoIngressoGratuito = document.getElementById('ingressoGratuito');
    const valorInicialIngresso = evento.gratuidade ? 0 : evento.valorMinimo;

    campoIngresso.min = evento.gratuidade ? '0' : String(evento.valorMinimo);
    campoIngresso.removeAttribute('max');
    campoIngresso.value = valorInicialIngresso.toFixed(2);
    campoIngresso.disabled = evento.gratuidade;
    opcaoIngressoGratuito.checked = evento.gratuidade;
    valorIngressoAntesGratuito = evento.valorMinimo;

    atualizarLinksTransporte();
    atualizarLinksHospedagem();

    document.getElementById('tituloEvento').textContent = evento.nome;
    document.getElementById('enderecoEvento').textContent = evento.endereco || 'Endereço não informado';
    document.getElementById('resumoNomeEvento').textContent = evento.nome;
    document.getElementById('nomeEventoPoster').textContent = evento.nome;
    document.getElementById('resumoDataHorarioEvento').textContent = formatarDataHorarioEvento(
        evento.data,
        evento.horario
    );
    document.getElementById('resumoLocalEvento').textContent = evento.endereco || 'Local não informado';
    document.getElementById('resumoDestino').textContent = evento.endereco || evento.nome;
    document.getElementById('textoDestinoTransporte').textContent =
        `Pesquise opções de transporte para ${evento.cidade || 'o evento'}, ${evento.uf}. Informe o valor no campo de custo do transporte.`;

    const precoMaximo = evento.valorMaximo;
    const possuiFaixa = Number.isFinite(precoMaximo) && precoMaximo > evento.valorMinimo;
    let referenciaPreco;

    if (evento.gratuidade) {
        referenciaPreco = 'Evento com entrada gratuita. A opção "Ingresso gratuito / Cortesia" já está marcada.';
    } else if (evento.valorMinimo > 0 && possuiFaixa) {
        referenciaPreco = `Preço oficial: ${moeda(evento.valorMinimo)} a ${moeda(precoMaximo)}. O ingresso é pré-preenchido com o valor mínimo; ajuste para o valor exato que você pagará (por exemplo, inteira do último lote).`;
    } else if (evento.valorMinimo > 0) {
        referenciaPreco = `Preço oficial a partir de ${moeda(evento.valorMinimo)}. Você pode ajustar o valor para inteiras, camarotes ou novos lotes.`;
    } else if (Number.isFinite(precoMaximo) && precoMaximo > 0) {
        referenciaPreco = `Preço oficial: até ${moeda(precoMaximo)}. Informe o valor exato que você pretende pagar.`;
    } else {
        referenciaPreco = 'O evento não informou valor de ingresso. Informe o valor que você pretende pagar.';
    }

    document.getElementById('custoMinimoEvento').textContent = referenciaPreco;
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

let valorIngressoAntesGratuito = 0;

function lerValorMonetario(id) {
    const campo = document.getElementById(id);
    const valor = Number(campo?.value);
    return Number.isFinite(valor) && valor >= 0 ? valor : 0;
}

function lerCustoIngresso() {
    return lerValorMonetario("custoIngresso");
}

function lerCustoTransporte() {
    return lerValorMonetario("custoTransporte");
}

function lerCustoHospedagem() {
    return hospedagemSelecionada
        ? lerValorMonetario("custoHospedagem")
        : 0;
}

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

/*
    Indica se o tempo estimado foi preenchido
    automaticamente pelo cálculo da rota.
    Quando o usuário troca para ônibus/avião,
    esse valor precisa ser informado manualmente.
*/

let tempoAutomatico = false;

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


function alternarIngressoGratuito() {
    const campo = document.getElementById("custoIngresso");
    const opcao = document.getElementById("ingressoGratuito");

    if (opcao.checked) {
        valorIngressoAntesGratuito = lerCustoIngresso() || valorIngressoAntesGratuito;
        campo.value = "0.00";
        campo.disabled = true;
    } else {
        campo.disabled = false;
        const valorRestaurado = valorIngressoAntesGratuito > 0
            ? valorIngressoAntesGratuito
            : evento.valorMinimo;
        campo.value = Number(valorRestaurado || 0).toFixed(2);
    }

    atualizarResumo();
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
        const campoIngresso =
            document.getElementById("custoIngresso");
        const opcaoIngressoGratuito =
            document.getElementById("ingressoGratuito");

        const orcamento =
            Number(campoOrcamento.value);
        const ingresso =
            campoIngresso.value === '' ? 0 : Number(campoIngresso.value);
        const ingressoGratuito =
            opcaoIngressoGratuito.checked;

        if (!orcamento || orcamento <= 0) {

            notificarErroPlanejamento(
                "Informe seu orçamento antes de continuar."
            );

            campoOrcamento.focus();

            return;
        }

        if (!Number.isFinite(ingresso) || ingresso < 0) {

            notificarErroPlanejamento(
                "Informe um valor de ingresso igual ou maior que zero."
            );

            campoIngresso.focus();

            return;
        }

        if (!ingressoGratuito && ingresso < evento.valorMinimo) {

            notificarErroPlanejamento(
                `O ingresso não pode ser menor que o valor mínimo oficial de ${moeda(evento.valorMinimo)}.`
            );

            campoIngresso.focus();

            return;
        }

        if (orcamento < ingresso) {

            notificarErroPlanejamento(
                `O orçamento precisa ser de pelo menos ${moeda(ingresso)}.`
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

        if (!transporteSelecionado) {

            notificarErroPlanejamento(
                "Escolha um meio de transporte antes de continuar."
            );

            return;
        }

        const campoCustoTransporte =
            document.getElementById("custoTransporte");
        const custoTransporte =
            Number(campoCustoTransporte.value);

        if (!Number.isFinite(custoTransporte) || custoTransporte < 0) {

            notificarErroPlanejamento(
                "Informe um custo de transporte igual ou maior que zero."
            );

            campoCustoTransporte.focus();

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

        if (hospedagemSelecionada) {

            const campoCustoHospedagem =
                document.getElementById("custoHospedagem");
            const custoHospedagem =
                Number(campoCustoHospedagem.value);

            if (!Number.isFinite(custoHospedagem) || custoHospedagem < 0) {

                notificarErroPlanejamento(
                    "Informe um custo de hospedagem igual ou maior que zero."
                );

                campoCustoHospedagem.focus();

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


    ajustarTempoParaTransporte();

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

/*
    Ajusta as instruções e o tempo estimado conforme o
    meio de transporte escolhido. Carro e Uber recebem o
    tempo automaticamente; ônibus e avião exigem que o
    usuário informe a estimativa consultada.
*/

function atualizarInstrucoesDadosRota() {
    const alvo =
        document.getElementById(
            "instrucoesDadosRota"
        );

    if (!alvo) {
        return;
    }

    if (transporteSelecionado === "Ônibus") {
        alvo.textContent = "A distância é preenchida automaticamente. Para ônibus, informe o tempo estimado consultado no seu aplicativo de transporte.";
        return;
    }

    if (transporteSelecionado === "Avião") {
        alvo.textContent = "A distância é preenchida automaticamente. Para avião, informe o tempo estimado total da viagem, incluindo o deslocamento até o aeroporto.";
        return;
    }

    alvo.textContent = "A distância e o tempo estimado são preenchidos automaticamente ao calcular a rota.";
}

function ajustarTempoParaTransporte() {
    atualizarInstrucoesDadosRota();

    const manual =

        transporteSelecionado ===
        "Ônibus" ||

        transporteSelecionado ===
        "Avião";

    if (manual && tempoAutomatico) {
        document.getElementById('tempoPlanejamento').value = '';
        tempoAutomatico = false;
        atualizarResumoRota();
    }
}

function atualizarResumoRota() {
    const campoDistancia =
        document.getElementById(
            "distanciaPlanejamento"
        );

    const campoTempo =
        document.getElementById(
            "tempoPlanejamento"
        );

    if (!campoDistancia || !campoTempo) {
        return;
    }

    const distancia =
        Number(
            campoDistancia.value
        );

    const tempo =
        Number(
            campoTempo.value
        );

    document.getElementById(
            "resumoDistancia"
        ).textContent =

        Number.isFinite(distancia) && distancia > 0 ?

        `${distancia.toLocaleString("pt-BR", {maximumFractionDigits: 1})} km` :

        "—";

    document.getElementById(
            "resumoTempo"
        ).textContent =

        Number.isFinite(tempo) && tempo > 0 ?

        `${tempo.toLocaleString("pt-BR")} min` :

        "—";
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
            "custoHospedagem"
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
        A distância física é a mesma em qualquer meio de
        transporte, então a rota é sempre calculada.
        Apenas o tempo estimado muda de comportamento:
        carro e Uber recebem o tempo automático; ônibus
        e avião exigem preenchimento manual.
    */

    const preencheTempoAutomatico =

        transporteSelecionado ===
        "" ||

        transporteSelecionado ===
        "Carro" ||

        transporteSelecionado ===
        "Uber";


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
                "resumoDistancia"
            ).textContent =
            `${distanciaKm.toLocaleString("pt-BR", {maximumFractionDigits: 1})} km`;


        if (preencheTempoAutomatico) {

            const minutos =
                Math.round(
                    rota.duration /
                    60
                );

            document.getElementById('tempoPlanejamento').value = String(minutos);


            tempoAutomatico =
                true;


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
                    "resumoTempo"
                ).textContent =
                tempoTexto;

        } else {

            tempoAutomatico =
                false;

        }


        document.getElementById(
                "navegacaoBox"
            ).hidden = false;


        rotaCalculada =
            true;


        etapaConcluida[3] =
            true;


        if (preencheTempoAutomatico) {

            mostrarMensagem(
                "Rota calculada com sucesso."
            );

        } else {

            mostrarMensagem(

                `Distância calculada. Informe o tempo estimado para ${transporteSelecionado}.`

            );

        }


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


    const ingresso =
        lerCustoIngresso();


    const transporte =
        lerCustoTransporte();


    const hospedagem =
        lerCustoHospedagem();


    const total =
        ingresso +
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
saldo
        );


    document.getElementById(
        "totalGastosEtapa"
    ).textContent =
        moeda(
total
        );


    document.getElementById(
        "saldoGastosEtapa"
    ).textContent =
        `Saldo do orçamento: ${moeda(saldo)}`;


    document.getElementById(
        "composicaoGastosEtapa"
    ).textContent =
        `Ingresso ${moeda(ingresso)} + Transporte ${moeda(transporte)} + Hospedagem ${moeda(hospedagem)}`;


    document.getElementById(
        "resumoIngresso"
    ).textContent =
        moeda(
ingresso
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

function definirEstadoGoogleAgenda(exportado, htmlLink = '') {
    const botaoAdicionar = document.getElementById('btnAdicionarGoogleAgenda');
    const linkGoogle = document.getElementById('btnVerGoogleAgenda');
    const estaExportado = Boolean(exportado);
    const url = String(htmlLink || '').trim() || 'https://calendar.google.com/calendar/u/0/r';

    botaoAdicionar.hidden = estaExportado;
    botaoAdicionar.disabled = false;
    botaoAdicionar.innerHTML = '<i class="bi bi-calendar-plus" aria-hidden="true"></i> Adicionar ao Google Agenda';
    botaoAdicionar.setAttribute('aria-label', 'Adicionar planejamento ao Google Agenda');

    linkGoogle.hidden = !estaExportado;
    if (estaExportado) {
        linkGoogle.href = url;
    } else {
        linkGoogle.removeAttribute('href');
    }
}

async function consultarGoogleAgendaExportado(idRota) {
    const resposta = await fetch('api/google-calendar', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', Accept: 'application/json'},
        body: JSON.stringify({acao: 'verificar_exportado', id_rota: Number(idRota)})
    });
    const dados = await lerRespostaJson(resposta);

    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível consultar o estado do Google Agenda.');
    }

    return dados;
}

async function exibirAcoesGoogleAgenda(idRota) {
    idRotaGoogleAgenda = Number(idRota);
    const caixa = document.getElementById('acoesGoogleAgenda');
    const texto = document.getElementById('statusGoogleAgendaPlanejamento');
    const botaoAdicionar = document.getElementById('btnAdicionarGoogleAgenda');
    const botaoConectar = document.getElementById('btnConectarGoogleAgendaPlanejamento');
    caixa.hidden = false;
    botaoAdicionar.hidden = true;
    botaoConectar.hidden = true;
    definirEstadoGoogleAgenda(false);
    botaoAdicionar.hidden = true;

    try {
        const status = await obterStatusGoogleAgenda();
        const conectado = Boolean(status?.conectado);

        if (!status?.configurado) {
texto.textContent = 'Google Agenda ainda não foi configurado neste ambiente.';
return;
        }

        if (!conectado) {
texto.textContent = 'Conecte o Google Agenda para exportar este planejamento.';
botaoConectar.hidden = false;
return;
        }

        texto.textContent = 'Planejamento pronto para ser adicionado à sua agenda.';
        botaoAdicionar.hidden = false;

        const exportacao = await consultarGoogleAgendaExportado(idRotaGoogleAgenda);
        if (exportacao.exportado) {
texto.textContent = 'Este planejamento já está na sua agenda.';
definirEstadoGoogleAgenda(true, exportacao.html_link);
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

        const ingressoExistente = Number(existente.custo_ingresso ?? evento.valorMinimo) || 0;
        const transporteExistente = Number(existente.custo_transporte) || 0;
        const hospedagemExistente = Number(existente.custo_hospedagem) || 0;
        const campoIngresso = document.getElementById('custoIngresso');
        const opcaoIngressoGratuito = document.getElementById('ingressoGratuito');

        campoIngresso.value = ingressoExistente.toFixed(2);
        document.getElementById('custoTransporte').value = transporteExistente.toFixed(2);
        document.getElementById('custoHospedagem').value = (
Boolean(existente.hospedagem_necessaria) ? hospedagemExistente : 0
        ).toFixed(2);
        opcaoIngressoGratuito.checked = ingressoExistente <= 0;
        campoIngresso.disabled = opcaoIngressoGratuito.checked;
        valorIngressoAntesGratuito = ingressoExistente > 0
? ingressoExistente
: evento.valorMinimo;

        document.getElementById('orcamento').value = existente.orcamento_total ?? 0;
        document.getElementById('meioTransportePlanejamento').value = existente.meio_transporte || '';
        document.getElementById('distanciaPlanejamento').value = existente.distancia_km ?? '';
        document.getElementById('tempoPlanejamento').value = existente.tempo_estimado ?? '';
        document.getElementById('origemManual').value = existente.origem || '';
        transporteSelecionado = existente.meio_transporte || '';
        /* O valor salvo é manual: não deve ser tratado como automático. */
        tempoAutomatico = false;
        atualizarInstrucoesDadosRota();
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
variante: 'conflito'
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
id_rota: Number.isInteger(idPlanejamentoExistente) && idPlanejamentoExistente > 0
    ? idPlanejamentoExistente
    : null,
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

        let htmlLink = dados.html_link || '';
        if (!htmlLink && dados.ja_existia) {
const exportacao = await consultarGoogleAgendaExportado(idRotaGoogleAgenda);
htmlLink = exportacao.html_link || '';
        }

        texto.textContent = 'Este planejamento já está na sua agenda.';
        botao.hidden = false;
        definirEstadoGoogleAgenda(true, htmlLink);
    } catch (erro) {
        texto.textContent = erro.message;
        botao.disabled = false;
        definirEstadoGoogleAgenda(false);
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
    const custoIngresso = lerCustoIngresso();
    const custoTransporte = lerCustoTransporte();
    const custoHospedagem = lerCustoHospedagem();
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
        "custoIngresso"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );


document
    .getElementById(
        "custoTransporte"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );


document
    .getElementById(
        "custoHospedagem"
    )
    .addEventListener(
        "input",
        atualizarResumo
    );


document
    .getElementById(
        "ingressoGratuito"
    )
    .addEventListener(
        "change",
        alternarIngressoGratuito
    );


document
    .getElementById(
        "distanciaPlanejamento"
    )
    .addEventListener(
        "input",
        atualizarResumoRota
    );


document
    .getElementById(
        "tempoPlanejamento"
    )
    .addEventListener(
        "input",
        () => {

/* Edição manual: o valor deixa de ser automático. */
tempoAutomatico =
    false;

atualizarResumoRota();

        }
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

['custoIngresso'].forEach((id) => {
    document.getElementById(id).addEventListener('keydown', (eventoTeclado) => {
        if (eventoTeclado.key !== 'Enter') return;
        eventoTeclado.preventDefault();
        proximaEtapa(2);
    });
});


/* =========================================================
   AVISO DE COMPROMISSOS NA MESMA DATA
========================================================= */

function mesmaData(valor, referencia) {
    const alvo = String(valor ?? '').slice(0, 10);
    const base = String(referencia ?? '').slice(0, 10);
    return alvo !== '' && base !== '' && alvo === base;
}

function normalizarNomeCompromisso(valor) {
    return String(valor ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
        .toLowerCase();
}

function exibirAvisoConflitoData(compromissos) {
    const vistos = new Set();
    const nomes = [];

    (compromissos || []).forEach((nome) => {
        const limpo = String(nome ?? '').trim();

        if (!limpo) {
return;
        }

        const chave = normalizarNomeCompromisso(limpo);

        if (!chave || vistos.has(chave)) {
return;
        }

        vistos.add(chave);
        nomes.push(limpo);
    });

    if (nomes.length === 0) {
        return;
    }

    if (!window.ShowMeUI || typeof window.ShowMeUI.avisar !== 'function') {
        return;
    }

    window.ShowMeUI.avisar({
        titulo: 'Compromissos nesta data',
        texto: 'Nota: Você já possui compromissos agendados nesta mesma data.',
        itens: nomes,
        icone: 'bi bi-calendar2-week',
        variante: 'conflito',
        fecharTexto: 'Entendi'
    });
}

async function verificarCompromissosDaData() {
    const compromissos = [];
    const dataEvento = String(evento.data ?? '');

    if (!dataEvento) {
        exibirAvisoConflitoData(compromissos);
        return;
    }

    /* 1) Compromissos já planejados no próprio ShowMe. */
    try {
        const resposta = await fetch('api/planejamento/', {headers: {Accept: 'application/json'}});
        const dados = await lerRespostaJson(resposta);

        if (resposta.ok) {
(dados.planejamentos || []).forEach((planejamento) => {
    if (Number(planejamento.id_evento) === Number(evento.id)) {
        return;
    }

    if (mesmaData(planejamento.data_evento, dataEvento)) {
        compromissos.push(planejamento.nome_evento);
    }
});
        }
    } catch (_) {
        /* Aviso complementar: falhas não impedem o planejamento. */
    }

    /* 2) Compromissos vindos da Google Agenda, quando conectada. */
    try {
        const status = statusGoogleAgenda || await obterStatusGoogleAgenda();

        if (status?.conectado) {
const resposta = await fetch('api/google-calendar', {
    method: 'POST',
    headers: {'Content-Type': 'application/json', Accept: 'application/json'},
    body: JSON.stringify({
        acao: 'verificar_data',
        id_evento: evento.id,
        id_rota: Number.isInteger(idPlanejamentoExistente) && idPlanejamentoExistente > 0
            ? idPlanejamentoExistente
            : null
    })
});
const dados = await lerRespostaJson(resposta);

if (resposta.ok && dados.conflito) {
    const nomeEventoAtual = normalizarNomeCompromisso(evento.nome);

    (dados.conflitos || []).forEach((conflito) => {
        /*
            O próprio evento pode já estar na agenda com o mesmo
            nome; nesse caso não é um compromisso adicional.
        */
        if (normalizarNomeCompromisso(conflito.titulo) === nomeEventoAtual) {
            return;
        }

        compromissos.push(conflito.titulo);
    });
}
        }
    } catch (_) {
        /* Integração opcional com o Google Agenda. */
    }

    exibirAvisoConflitoData(compromissos);
}


/* =========================================================
   INICIALIZAÇÃO
========================================================= */

async function inicializarPlanejamento() {
    let planejamentoExistente = null;

    try {
        await carregarEvento();
        planejamentoExistente = await carregarPlanejamentoExistente();
        atualizarInstrucoesDadosRota();

        /* Checagem complementar: um aviso nunca bloqueia o carregamento. */
        verificarCompromissosDaData().catch(() => {});
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
