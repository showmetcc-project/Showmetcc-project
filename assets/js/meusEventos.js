// Alterna as abas Favoritos, Planejados e Calendário.
document.querySelectorAll('.aba').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        // Atualiza botões
        document.querySelectorAll('.aba').forEach(b => {
            const ativa = b === btn;
            b.classList.toggle('ativa', ativa);
            b.setAttribute('aria-selected', String(ativa));
        });

        // Atualiza seções
        document.querySelectorAll('.conteudo').forEach(s => s.classList.remove('ativa'));
        document.getElementById(tab).classList.add('ativa');

        if (tab === 'calendario') {
            renderizarCalendario();
        }
    });
});

const listaFavoritos = document.getElementById('favoritos');
const contadorFavoritos = document.getElementById('contadorFavoritos');
const imagemPadrao = 'assets/img/bannerEventoPadrao.png';

async function lerRespostaJson(resposta) {
    const texto = await resposta.text();

    if (!texto) {
        return {};
    }

    try {
        return JSON.parse(texto);
    } catch (erro) {
        throw new Error('A API retornou uma resposta inválida.');
    }
}

function caminhoImagem(caminho) {
    const valor = String(caminho || '').trim();

    if (!valor) {
        return imagemPadrao;
    }

    if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/') || valor.includes('\\')) {
        return valor;
    }

    return `assets/img/${valor}`;
}

function mostrarFavoritosVazios() {
    if (!listaFavoritos) {
        return;
    }

    const vazio = document.createElement('p');
    vazio.textContent = 'Você ainda não adicionou eventos aos favoritos.';
    listaFavoritos.replaceChildren(vazio);
}

function criarCardFavorito(favorito) {
    const coluna = document.createElement('div');
    const card = document.createElement('div');
    coluna.className = 'col-12';
    card.className = 'card-evento';

    const imagem = document.createElement('img');
    imagem.src = caminhoImagem(favorito.imagem_evento);
    imagem.alt = favorito.nome_evento || 'Evento';
    imagem.loading = 'lazy';
    imagem.addEventListener('error', function () {
        this.src = imagemPadrao;
    }, {once: true});

    const info = document.createElement('div');
    info.className = 'info';

    const titulo = document.createElement('h3');
    titulo.textContent = favorito.nome_evento;

    const local = criarInformacao(
        'bi bi-geo-alt-fill',
        [favorito.endereco_evento, favorito.numero_endereco, favorito.cidade_evento, favorito.uf]
            .filter(Boolean)
            .join(', ') || 'Local não informado'
    );
    const data = criarInformacao('bi bi-calendar3', formatarDataEvento(favorito.data_evento));

    const detalhes = document.createElement('a');
    detalhes.className = 'btn-detalhes';
    detalhes.href = `detalhesEvento.php?id=${favorito.id_evento}`;
    detalhes.textContent = 'Ver detalhes';

    info.append(titulo, local, data, detalhes);

    const acoes = document.createElement('div');
    acoes.className = 'acoes';

    const tipo = document.createElement('span');
    tipo.className = `tag ${favorito.gratuidade ? 'gratis' : 'pago'}`;
    tipo.textContent = favorito.gratuidade ? 'Grátis' : 'Pago';

    const excluir = document.createElement('button');
    excluir.type = 'button';
    excluir.className = 'btn-excluir';
    excluir.title = 'Remover dos favoritos';
    excluir.setAttribute('aria-label', `Remover ${favorito.nome_evento || 'evento'} dos favoritos`);
    excluir.innerHTML = '<i class="bi bi-trash3"></i>';
    excluir.addEventListener('click', async () => {
        const deveRemover = await window.ShowMeUI.confirmar({
            titulo: 'Remover favorito',
            texto: 'Deseja remover este evento dos favoritos?',
            confirmarTexto: 'Remover',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });

        if (!deveRemover) {
            return;
        }

        excluir.disabled = true;

        try {
            const resposta = await fetch(`api/favoritos/${favorito.id_favorito}`, {method: 'DELETE'});

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            const dados = await lerRespostaJson(resposta);

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível remover o favorito.');
            }

            coluna.remove();
            if (contadorFavoritos) {
                contadorFavoritos.textContent = Math.max(0, Number(contadorFavoritos.textContent) - 1);
            }

            if (!listaFavoritos.querySelector('.card-evento')) {
                mostrarFavoritosVazios();
            }
            window.ShowMeUI.toast('Evento removido dos favoritos.', {variante: 'sucesso'});
        } catch (erro) {
            window.ShowMeUI.toast(erro.message, {variante: 'erro'});
        } finally {
            excluir.disabled = false;
        }
    });

    acoes.append(tipo, excluir);
    card.append(imagem, info, acoes);
    coluna.append(card);
    return coluna;
}

async function carregarFavoritos() {
    if (!listaFavoritos) {
        return;
    }

    try {
        const resposta = await fetch('api/favoritos/');

        if (resposta.status === 401) {
            window.location.href = 'login.php';
            return;
        }

        const dados = await lerRespostaJson(resposta);

        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar os favoritos.');
        }

        listaFavoritos.replaceChildren();
        if (contadorFavoritos) {
            contadorFavoritos.textContent = dados.favoritos.length;
        }

        if (dados.favoritos.length === 0) {
            mostrarFavoritosVazios();
            return;
        }

        dados.favoritos.forEach((favorito) => {
            listaFavoritos.append(criarCardFavorito(favorito));
        });
    } catch (erro) {
        listaFavoritos.replaceChildren();
        if (contadorFavoritos) {
            contadorFavoritos.textContent = '0';
        }
        const mensagem = document.createElement('p');
        mensagem.textContent = erro.message;
        listaFavoritos.append(mensagem);
    }
}

const listaPlanejados = document.getElementById('planejados');
const contadorPlanejados = document.getElementById('contadorPlanejados');
let planejamentosCarregados = [];

function formatarDataEvento(data) {
    if (!data) {
        return 'Data não informada';
    }

    const partes = data.split('-').map(Number);
    const dataLocal = new Date(partes[0], partes[1] - 1, partes[2]);
    return dataLocal.toLocaleDateString('pt-BR');
}

function formatarMoeda(valor) {
    const numero = Number(valor);
    return (Number.isFinite(numero) ? numero : 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function chaveHojeLocal() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

function planejamentoEstaAtivo(planejamento) {
    const dataEvento = String(planejamento.data_evento || '').slice(0, 10);
    return /^\d{4}-\d{2}-\d{2}$/.test(dataEvento) && dataEvento >= chaveHojeLocal();
}

function criarInformacao(icone, texto) {
    const linha = document.createElement('p');
    const elementoIcone = document.createElement('i');
    elementoIcone.className = icone;
    linha.append(elementoIcone, document.createTextNode(texto));
    return linha;
}

function textoDeslocamento(planejamento) {
    return `${planejamento.meio_transporte} · ${planejamento.distancia_km} km · ${planejamento.tempo_estimado} min`;
}

async function excluirPlanejamento(planejamento) {
    const deveRemover = await window.ShowMeUI.confirmar({
        titulo: 'Remover planejamento',
        texto: 'Tem certeza que deseja remover esse planejamento? Essa ação não pode ser desfeita.',
        confirmarTexto: 'Remover',
        cancelarTexto: 'Cancelar',
        variante: 'destrutiva'
    });

    if (!deveRemover) {
        return false;
    }

    const resposta = await fetch(`api/planejamento/${planejamento.id_rota}`, {
        method: 'DELETE'
    });
    const dados = await lerRespostaJson(resposta);

    if (resposta.status === 401) {
        window.location.href = 'login.php';
        return false;
    }

    if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível remover o planejamento.');
    }

    await carregarPlanejados();
    window.ShowMeUI.toast('Planejamento removido.', {variante: 'sucesso'});
    return true;
}

function criarCardPlanejado(planejamento) {
    const coluna = document.createElement('div');
    const card = document.createElement('div');
    const eventoEncerrado = !planejamentoEstaAtivo(planejamento);
    coluna.className = 'col-12';
    card.className = `card-evento${eventoEncerrado ? ' evento-encerrado' : ''}`;

    const imagem = document.createElement('img');
    imagem.src = planejamento.imagem_evento || 'assets/img/bannerEventoPadrao.png';
    imagem.alt = planejamento.nome_evento;

    const info = document.createElement('div');
    info.className = 'info';

    const titulo = document.createElement('h3');
    titulo.textContent = planejamento.nome_evento;

    const local = criarInformacao(
        'bi bi-geo-alt-fill',
        [planejamento.endereco_evento, planejamento.numero_endereco, planejamento.cidade_evento, planejamento.uf]
            .filter(Boolean)
            .join(', ') || 'Local não informado'
    );
    const data = criarInformacao('bi bi-calendar3', formatarDataEvento(planejamento.data_evento));
    const deslocamento = criarInformacao('bi bi-signpost-2', textoDeslocamento(planejamento));

    const botoes = document.createElement('div');
    botoes.className = 'botoes-duplos';

    const detalhes = document.createElement('a');
    detalhes.className = 'btn-detalhes';
    detalhes.href = `planejamento.php?id=${planejamento.id_evento}&resumo=1`;
    detalhes.textContent = 'Ver planejamento';

    const verCalendario = document.createElement('a');
    verCalendario.className = 'btn-planejamento';
    verCalendario.href = `meusEventos.php?aba=calendario&data=${encodeURIComponent(chaveDoPlanejamento(planejamento))}`;
    verCalendario.textContent = 'Ver no calendário';

    botoes.append(detalhes, verCalendario);
    info.append(titulo, local, data, deslocamento, botoes);

    const acoes = document.createElement('div');
    acoes.className = 'acoes';

    const tipo = document.createElement('span');
    tipo.className = `tag ${planejamento.gratuidade ? 'gratis' : 'pago'}`;
    tipo.textContent = planejamento.gratuidade ? 'Grátis' : 'Pago';

    const excluir = document.createElement('button');
    excluir.type = 'button';
    excluir.className = 'btn-excluir';
    excluir.title = 'Desfazer planejamento';
    excluir.setAttribute('aria-label', `Desfazer planejamento de ${planejamento.nome_evento}`);
    excluir.innerHTML = '<i class="bi bi-trash3"></i>';
    excluir.addEventListener('click', async () => {
        excluir.disabled = true;
        try {
            await excluirPlanejamento(planejamento);
        } catch (erro) {
            window.ShowMeUI.toast(erro.message, {variante: 'erro'});
        } finally {
            excluir.disabled = false;
        }
    });

    acoes.append(tipo, excluir);

    if (eventoEncerrado) {
        const encerrado = document.createElement('span');
        encerrado.className = 'badge-evento-encerrado';
        encerrado.textContent = 'Evento encerrado';
        card.append(encerrado);
    }

    card.append(imagem, info, acoes);
    coluna.append(card);
    return coluna;
}

async function carregarPlanejados() {
    if (!listaPlanejados) {
        return;
    }

    try {
        const resposta = await fetch('api/planejamento/');
        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar os planejados.');
        }

        planejamentosCarregados = Array.isArray(dados.planejamentos) ? dados.planejamentos : [];
        listaPlanejados.replaceChildren();
        contadorPlanejados.textContent = planejamentosCarregados.filter(planejamentoEstaAtivo).length;

        if (!planejamentosCarregados.length) {
            const vazio = document.createElement('p');
            vazio.textContent = 'Você ainda não finalizou nenhum planejamento.';
            listaPlanejados.append(vazio);
            renderizarCalendario();
            return;
        }

        planejamentosCarregados.forEach((planejamento) => {
            listaPlanejados.append(criarCardPlanejado(planejamento));
        });
        renderizarCalendario();
    } catch (erro) {
        planejamentosCarregados = [];
        listaPlanejados.replaceChildren();
        contadorPlanejados.textContent = '0';
        const mensagem = document.createElement('p');
        mensagem.textContent = erro.message;
        listaPlanejados.append(mensagem);
        renderizarCalendario();
    }
}

const calendarioGrid = document.getElementById('calendarioGrid');
const calendarioTitulo = document.getElementById('calendarioTitulo');
const calendarioDetalhes = document.getElementById('calendarioDetalhes');
const resumoMesConteudo = document.getElementById('resumoMesConteudo');
const calendarioAnterior = document.getElementById('calendarioAnterior');
const calendarioProximo = document.getElementById('calendarioProximo');
const calendarioHoje = document.getElementById('calendarioHoje');
const dataAtual = new Date();
let mesCalendario = new Date(dataAtual.getFullYear(), dataAtual.getMonth(), 1);
let dataSelecionada = null;

function doisDigitos(numero) {
    return String(numero).padStart(2, '0');
}

function chaveDaData(data) {
    return `${data.getFullYear()}-${doisDigitos(data.getMonth() + 1)}-${doisDigitos(data.getDate())}`;
}

function chaveDoPlanejamento(planejamento) {
    const data = String(planejamento.data_evento || '').slice(0, 10);
    return /^\d{4}-\d{2}-\d{2}$/.test(data) ? data : '';
}

function dataDaChave(chave) {
    const partes = String(chave).split('-').map(Number);
    return new Date(partes[0], partes[1] - 1, partes[2]);
}

function capitalizar(texto) {
    return texto ? texto.charAt(0).toUpperCase() + texto.slice(1) : '';
}

function formatarMesAno(data) {
    return capitalizar(data.toLocaleDateString('pt-BR', {
        month: 'long',
        year: 'numeric'
    }));
}

function formatarDataExtenso(chave) {
    return capitalizar(dataDaChave(chave).toLocaleDateString('pt-BR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    }));
}

function agruparPlanejamentosPorData() {
    const grupos = new Map();

    planejamentosCarregados.forEach((planejamento) => {
        const chave = chaveDoPlanejamento(planejamento);

        if (!chave) {
            return;
        }

        if (!grupos.has(chave)) {
            grupos.set(chave, []);
        }

        grupos.get(chave).push(planejamento);
    });

    return grupos;
}

function localPlanejamento(planejamento) {
    return [planejamento.endereco_evento, planejamento.numero_endereco, planejamento.cidade_evento, planejamento.uf]
        .filter(Boolean)
        .join(', ') || 'Local não informado';
}

function criarDetalhePlanejamento(planejamento) {
    const card = document.createElement('article');
    card.className = 'calendario-evento-detalhe';

    const cabecalho = document.createElement('div');
    cabecalho.className = 'calendario-evento-cabecalho';

    const titulo = document.createElement('h4');
    titulo.textContent = planejamento.nome_evento || 'Evento';

    const excluir = document.createElement('button');
    excluir.type = 'button';
    excluir.className = 'calendario-excluir';
    excluir.title = 'Remover planejamento';
    excluir.setAttribute('aria-label', `Remover planejamento de ${planejamento.nome_evento || 'evento'}`);
    excluir.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i>';
    excluir.addEventListener('click', async () => {
        excluir.disabled = true;

        try {
            await excluirPlanejamento(planejamento);
        } catch (erro) {
            window.ShowMeUI.toast(erro.message, {variante: 'erro'});
        } finally {
            excluir.disabled = false;
        }
    });

    cabecalho.append(titulo, excluir);

    const local = document.createElement('p');
    local.className = 'calendario-evento-info';
    local.innerHTML = '<i class="bi bi-geo-alt-fill" aria-hidden="true"></i>';
    local.append(document.createTextNode(localPlanejamento(planejamento)));

    const investimento = document.createElement('p');
    investimento.className = 'calendario-evento-info';
    investimento.innerHTML = '<i class="bi bi-wallet2" aria-hidden="true"></i>';
    investimento.append(document.createTextNode(
        `Investimento total: ${formatarMoeda(planejamento.investimento_total)}`
    ));

    const link = document.createElement('a');
    link.className = 'calendario-link-planejamento';
    link.href = `planejamento.php?id=${planejamento.id_evento}&resumo=1`;
    link.textContent = 'Ver planejamento completo →';

    card.append(cabecalho, local, investimento, link);
    return card;
}

function renderizarDetalhesCalendario(grupos) {
    if (!calendarioDetalhes) {
        return;
    }

    calendarioDetalhes.replaceChildren();

    if (!dataSelecionada) {
        const neutro = document.createElement('p');
        neutro.className = 'calendario-estado-neutro';
        neutro.textContent = 'Selecione um dia para ver seus planejamentos.';
        calendarioDetalhes.append(neutro);
        return;
    }

    const titulo = document.createElement('h3');
    titulo.textContent = formatarDataExtenso(dataSelecionada);
    calendarioDetalhes.append(titulo);

    const eventos = grupos.get(dataSelecionada) || [];

    if (!eventos.length) {
        const neutro = document.createElement('p');
        neutro.className = 'calendario-estado-neutro';
        neutro.textContent = 'Nenhum evento planejado nesse dia.';
        calendarioDetalhes.append(neutro);
        return;
    }

    const lista = document.createElement('div');
    lista.className = 'calendario-eventos-dia';
    eventos.forEach((planejamento) => lista.append(criarDetalhePlanejamento(planejamento)));
    calendarioDetalhes.append(lista);
}

function renderizarResumoMes() {
    if (!resumoMesConteudo) {
        return;
    }

    const ano = mesCalendario.getFullYear();
    const mes = mesCalendario.getMonth();
    const eventosMes = planejamentosCarregados.filter((planejamento) => {
        const chave = chaveDoPlanejamento(planejamento);

        if (!chave) {
            return false;
        }

        const data = dataDaChave(chave);
        return data.getFullYear() === ano && data.getMonth() === mes;
    });

    resumoMesConteudo.replaceChildren();

    if (!eventosMes.length) {
        const vazio = document.createElement('div');
        vazio.className = 'resumo-mes-vazio';

        const mensagem = document.createElement('p');
        mensagem.textContent = 'Nenhum evento planejado neste mês.';

        const link = document.createElement('a');
        link.href = 'inicio.php';
        link.textContent = 'Explorar eventos →';

        vazio.append(mensagem, link);
        resumoMesConteudo.append(vazio);
        return;
    }

    const quantidade = document.createElement('div');
    quantidade.className = 'resumo-mes-metrica';
    quantidade.innerHTML = '<span>Planejados no mês</span>';
    const total = document.createElement('strong');
    total.textContent = String(eventosMes.length);
    quantidade.append(total);

    const investimento = document.createElement('div');
    investimento.className = 'resumo-mes-metrica resumo-investimento';
    investimento.innerHTML = '<span>Investimento total no mês</span>';
    const totalInvestimento = eventosMes.reduce((soma, planejamento) => {
        const totalSalvo = Number(planejamento.investimento_total);
        if (Number.isFinite(totalSalvo)) {
            return soma + totalSalvo;
        }

        return soma
            + Number(planejamento.custo_ingresso || 0)
            + Number(planejamento.custo_transporte || 0)
            + Number(planejamento.custo_hospedagem || 0);
    }, 0);
    const valorInvestimento = document.createElement('strong');
    valorInvestimento.textContent = formatarMoeda(totalInvestimento);
    investimento.append(valorInvestimento);

    resumoMesConteudo.append(quantidade, investimento);
}

function renderizarCalendario() {
    if (!calendarioGrid || !calendarioTitulo) {
        return;
    }

    const grupos = agruparPlanejamentosPorData();
    const ano = mesCalendario.getFullYear();
    const mes = mesCalendario.getMonth();
    const primeiroDia = new Date(ano, mes, 1);
    const ultimoDia = new Date(ano, mes + 1, 0);
    const inicioGrade = new Date(ano, mes, 1 - primeiroDia.getDay());
    const totalCelulas = Math.ceil((primeiroDia.getDay() + ultimoDia.getDate()) / 7) * 7;
    const chaveHoje = chaveDaData(new Date());

    calendarioTitulo.textContent = formatarMesAno(mesCalendario);
    calendarioGrid.replaceChildren();

    for (let indice = 0; indice < totalCelulas; indice += 1) {
        const data = new Date(
            inicioGrade.getFullYear(),
            inicioGrade.getMonth(),
            inicioGrade.getDate() + indice
        );
        const chave = chaveDaData(data);
        const eventos = grupos.get(chave) || [];
        const foraDoMes = data.getMonth() !== mes;

        const dia = document.createElement('button');
        dia.type = 'button';
        dia.className = 'calendario-dia';
        dia.setAttribute('role', 'gridcell');
        dia.setAttribute('aria-selected', String(chave === dataSelecionada));
        dia.setAttribute(
            'aria-label',
            `${formatarDataExtenso(chave)}${eventos.length ? `, ${eventos.length} evento(s) planejado(s)` : ''}`
        );
        dia.classList.toggle('fora-mes', foraDoMes);
        dia.classList.toggle('dia-hoje', chave === chaveHoje);
        dia.classList.toggle('dia-selecionado', chave === dataSelecionada);
        dia.classList.toggle('dia-com-evento', eventos.length > 0);

        const numero = document.createElement('span');
        numero.className = 'calendario-numero';
        numero.textContent = String(data.getDate());
        dia.append(numero);

        if (eventos.length) {
            const chip = document.createElement('span');
            chip.className = 'calendario-evento-chip';
            chip.textContent = eventos[0].nome_evento || 'Evento';
            chip.title = eventos[0].nome_evento || 'Evento';
            dia.append(chip);

            if (eventos.length > 1) {
                const adicionais = document.createElement('span');
                adicionais.className = 'calendario-eventos-adicionais';
                adicionais.textContent = `+${eventos.length - 1}`;
                dia.append(adicionais);
            }
        }

        dia.addEventListener('click', () => {
            dataSelecionada = chave;
            renderizarCalendario();
        });

        calendarioGrid.append(dia);
    }

    renderizarDetalhesCalendario(grupos);
    renderizarResumoMes();
}

function mudarMesCalendario(deslocamento) {
    mesCalendario = new Date(
        mesCalendario.getFullYear(),
        mesCalendario.getMonth() + deslocamento,
        1
    );
    dataSelecionada = null;
    renderizarCalendario();
}

calendarioAnterior?.addEventListener('click', () => mudarMesCalendario(-1));
calendarioProximo?.addEventListener('click', () => mudarMesCalendario(1));
calendarioHoje?.addEventListener('click', () => {
    const hoje = new Date();
    mesCalendario = new Date(hoje.getFullYear(), hoje.getMonth(), 1);
    dataSelecionada = chaveDaData(hoje);
    renderizarCalendario();
});

function ativarAbaInicial() {
    const parametros = new URLSearchParams(window.location.search);
    const abaSolicitada = parametros.get('aba');

    if (!['favoritos', 'planejados', 'calendario'].includes(abaSolicitada)) {
        return;
    }

    document.querySelectorAll('.aba').forEach((aba) => {
        const ativa = aba.dataset.tab === abaSolicitada;
        aba.classList.toggle('ativa', ativa);
        aba.setAttribute('aria-selected', String(ativa));
    });
    document.querySelectorAll('.conteudo').forEach((conteudo) => {
        conteudo.classList.toggle('ativa', conteudo.id === abaSolicitada);
    });

    if (abaSolicitada === 'calendario') {
        const dataSolicitada = parametros.get('data') || '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dataSolicitada)) {
            const data = dataDaChave(dataSolicitada);
            if (!Number.isNaN(data.getTime()) && chaveDaData(data) === dataSolicitada) {
                mesCalendario = new Date(data.getFullYear(), data.getMonth(), 1);
                dataSelecionada = dataSolicitada;
            }
        }
    }
}

ativarAbaInicial();
renderizarCalendario();
carregarFavoritos();
carregarPlanejados();
