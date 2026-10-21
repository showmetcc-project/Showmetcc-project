(function () {
  'use strict';

  const PRECO_MAXIMO_SLIDER = 2000;
  const LIMITE_RESULTADOS = 20;
  const PERIODOS_VALIDOS = [
    'qualquer',
    'hoje',
    'amanha',
    'fim-de-semana',
    'nesta-semana',
    'proxima-semana',
    'neste-mes',
    'proximo-mes'
  ];
  const PRECOS_VALIDOS = ['todos', 'gratuitos', 'pagos'];

  function iniciarBuscaCabecalho() {
    const formulario = document.querySelector('.busca-cabecalho');
    const campo = document.getElementById('buscaCabecalho');
    const containerFlutuante = document.getElementById('containerBuscaFlutuante');
    const resultados = document.getElementById('resultadosBuscaTempoReal');
    const botaoLimpar = formulario?.querySelector('[data-limpar-busca]');
    const backdropBusca = document.querySelector('[data-backdrop-busca]');
    const filtroCategoria = document.getElementById('filtroCategoriaBusca');
    const filtroClassificacao = document.getElementById('filtroClassificacaoBusca');
    const filtroUf = document.getElementById('filtroUfBusca');
    const filtroCidade = document.getElementById('filtroCidadeBusca');
    const sliderPreco = document.getElementById('filtroPrecoMaximo');
    const saidaPreco = document.getElementById('filtroPrecoValor');
    const estaNaHome = Boolean(document.querySelector('main.inicio'));
    const classificacoesValidas = new Set(
      filtroClassificacao
        ? [...filtroClassificacao.options].map((opcao) => opcao.value).filter(Boolean)
        : []
    );

    if (!formulario || !campo || !resultados || !containerFlutuante) {
      return;
    }

    const imagemPadrao = 'assets/img/bannerEventoPadrao.png';
    const parametrosIniciais = new URL(window.location.href).searchParams;
    const cidadeInicial = parametrosIniciais.get('cidade') || '';
    const precoMaxInicial = Number(parametrosIniciais.get('preco_max') || PRECO_MAXIMO_SLIDER);

    let temporizador = null;
    let requisicaoAtual = null;
    let opcoesLocalidade = [];
    let requisicaoLocais = null;
    let locaisCarregados = false;

    function normalizarClassificacao(valor) {
      const classificacao = String(valor || '').trim();
      return classificacoesValidas.has(classificacao) ? classificacao : '';
    }

    const filtros = {
      categoria: parametrosIniciais.get('categoria') || '',
      classificacao: normalizarClassificacao(parametrosIniciais.get('classificacao') || ''),
      preco: PRECOS_VALIDOS.includes(parametrosIniciais.get('preco'))
        ? parametrosIniciais.get('preco')
        : 'todos',
      data: PERIODOS_VALIDOS.includes(parametrosIniciais.get('data'))
        ? parametrosIniciais.get('data')
        : 'qualquer',
      uf: (parametrosIniciais.get('uf') || (cidadeInicial.includes('|') ? cidadeInicial.split('|')[1] : '')).toUpperCase(),
      cidade: cidadeInicial.includes('|') ? cidadeInicial.split('|')[0] : cidadeInicial,
      precoMax: Number.isFinite(precoMaxInicial)
        ? Math.max(0, Math.min(PRECO_MAXIMO_SLIDER, precoMaxInicial))
        : PRECO_MAXIMO_SLIDER
    };

    if (!PRECOS_VALIDOS.includes(filtros.preco)) {
      filtros.preco = 'todos';
    }

    function filtrosAtivos() {
      return filtros.categoria !== ''
        || filtros.classificacao !== ''
        || filtros.preco !== 'todos'
        || filtros.data !== 'qualquer'
        || filtros.uf !== ''
        || filtros.cidade !== ''
        || filtros.precoMax < PRECO_MAXIMO_SLIDER;
    }

    function periodoDaData(data) {
      if (data === 'hoje') return 'hoje';
      if (data === 'amanha') return 'amanha';
      if (data === 'fim-de-semana') return 'fim_de_semana';
      if (data === 'nesta-semana') return 'nesta_semana';
      if (data === 'proxima-semana') return 'proxima_semana';
      if (data === 'neste-mes') return 'neste_mes';
      if (data === 'proximo-mes') return 'proximo_mes';
      return '';
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

    function atualizarBotaoLimpar() {
      if (botaoLimpar) {
        botaoLimpar.hidden = campo.value.length === 0;
      }
    }

    function emitirBusca(termo) {
      window.dispatchEvent(new CustomEvent('showme:busca-home', {
        detail: {termo, filtros: {...filtros}}
      }));
    }

    function urlDaBusca(termo) {
      const url = new URL('index.php', window.location.href);

      if (termo) url.searchParams.set('busca', termo);
      if (filtros.categoria) url.searchParams.set('categoria', filtros.categoria);
      if (filtros.classificacao) url.searchParams.set('classificacao', filtros.classificacao);
      if (filtros.preco !== 'todos') url.searchParams.set('preco', filtros.preco);
      if (filtros.cidade) url.searchParams.set('cidade', filtros.cidade);
      if (filtros.uf) url.searchParams.set('uf', filtros.uf);
      if (filtros.data !== 'qualquer') url.searchParams.set('data', filtros.data);
      if (filtros.precoMax < PRECO_MAXIMO_SLIDER) url.searchParams.set('preco_max', String(filtros.precoMax));

      return url;
    }

    function aplicarBuscaNaHome(termo, registrarHistorico = true) {
      if (!estaNaHome) {
        return;
      }

      if (!termo && !filtrosAtivos()) {
        const urlAtual = new URL(window.location.href);
        const urlInicial = new URL('index.php', window.location.href);
        if (registrarHistorico && urlAtual.href !== urlInicial.href) {
          window.history.pushState({}, '', urlInicial);
        }
        emitirBusca('');
        return;
      }

      const urlAtual = new URL(window.location.href);
      const urlNova = urlDaBusca(termo);

      if (registrarHistorico && urlAtual.href !== urlNova.href) {
        window.history.pushState({busca: termo, filtros: {...filtros}}, '', urlNova);
      }

      emitirBusca(termo);
    }

    function atualizarControlesFiltros() {
      formulario.querySelectorAll('[data-filtro-preco]').forEach((botao) => {
        const ativo = botao.dataset.filtroPreco === filtros.preco;
        botao.classList.toggle('ativo', ativo);
        botao.setAttribute('aria-pressed', String(ativo));
      });
      formulario.querySelectorAll('[data-filtro-data]').forEach((botao) => {
        const ativo = botao.dataset.filtroData === filtros.data;
        botao.classList.toggle('ativo', ativo);
        botao.setAttribute('aria-pressed', String(ativo));
      });
      if (filtroClassificacao) filtroClassificacao.value = filtros.classificacao;
      if (filtroCategoria) filtroCategoria.value = filtros.categoria;
      if (filtroUf) filtroUf.value = filtros.uf;
      if (filtroCidade) filtroCidade.value = filtros.cidade;
      if (sliderPreco) sliderPreco.value = String(filtros.precoMax);
      if (saidaPreco) {
        saidaPreco.value = filtros.precoMax >= PRECO_MAXIMO_SLIDER
          ? 'R$ 2.000+'
          : Number(filtros.precoMax).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
      }
    }

    function atualizarOpcoesCidade() {
      if (!filtroCidade) return;
      const cidades = opcoesLocalidade.filter((opcao) => !filtros.uf || opcao.uf === filtros.uf);
      filtroCidade.replaceChildren(new Option('Todas as cidades', ''));
      cidades.forEach((opcao) => filtroCidade.add(new Option(opcao.cidade, opcao.cidade)));
      if (filtros.cidade && !cidades.some((opcao) => opcao.cidade === filtros.cidade)) {
        filtroCidade.add(new Option(filtros.cidade, filtros.cidade));
      }
      filtroCidade.value = filtros.cidade;
    }

    function popularLocais(dados) {
      if (Array.isArray(dados?.cidades)) {
        opcoesLocalidade = dados.cidades.map((local) => ({
          cidade: String(local.cidade || '').trim(),
          uf: String(local.uf || '').trim().toUpperCase()
        })).filter((local) => local.cidade && local.uf);
      }

      if (filtroUf && Array.isArray(dados?.ufs)) {
        const ufAtual = filtros.uf;
        const ufs = [...new Set(dados.ufs.map((uf) => String(uf || '').trim().toUpperCase()).filter(Boolean))].sort();
        filtroUf.replaceChildren(new Option('Todas as UFs', ''));
        ufs.forEach((uf) => filtroUf.add(new Option(uf, uf)));
        filtroUf.value = ufs.includes(ufAtual) ? ufAtual : '';
        filtros.uf = filtroUf.value;
      }

      if (filtroCategoria && Array.isArray(dados?.categorias)) {
        const categoriaAtual = filtros.categoria;
        const categorias = [...new Set(
          dados.categorias.map((categoria) => String(categoria || '').trim()).filter(Boolean)
        )].sort((a, b) => a.localeCompare(b, 'pt-BR'));
        filtroCategoria.replaceChildren(new Option('Todas as categorias', ''));
        categorias.forEach((categoria) => filtroCategoria.add(new Option(categoria, categoria)));
        filtroCategoria.value = categorias.includes(categoriaAtual) ? categoriaAtual : '';
        filtros.categoria = filtroCategoria.value;
      }

      atualizarOpcoesCidade();
    }

    async function carregarLocais() {
      if (locaisCarregados || requisicaoLocais) return requisicaoLocais;
      requisicaoLocais = fetch('api/eventos?listar_locais=1', {
        headers: {Accept: 'application/json'}
      })
        .then(async (resposta) => {
          const dados = await resposta.json();
          if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível carregar os locais.');
          popularLocais(dados);
          locaisCarregados = true;
        })
        .catch((erro) => {
          console.error('Falha ao carregar locais e categorias dos eventos:', erro);
          requisicaoLocais = null;
        });
      return requisicaoLocais;
    }

    function abrirContainerBusca() {
      containerFlutuante.classList.remove('d-none');
      backdropBusca?.classList.remove('d-none');
      document.body.classList.add('busca-aberta');
      campo.setAttribute('aria-expanded', 'true');
      carregarLocais();
      executarBusca();
    }

    function fecharContainerBusca() {
      containerFlutuante.classList.add('d-none');
      backdropBusca?.classList.add('d-none');
      document.body.classList.remove('busca-aberta');
      campo.setAttribute('aria-expanded', 'false');
    }

    function mostrarMensagem(mensagem) {
      const item = document.createElement('p');
      item.className = 'resultados-busca-vazio';
      item.textContent = mensagem;
      resultados.replaceChildren(item);
    }

    function criarSugestao(evento) {
      const link = document.createElement('a');
      link.className = 'resultado-evento-compacto';
      link.href = `detalhesEvento.php?id=${encodeURIComponent(evento.id_evento)}`;
      link.setAttribute('role', 'option');

      const imagem = document.createElement('img');
      imagem.src = caminhoImagem(evento.imagem_evento);
      imagem.alt = window.ShowMeA11y.textoAltImagemEvento(evento);
      imagem.loading = 'lazy';
      imagem.addEventListener('error', function () {
        this.src = imagemPadrao;
      }, {once: true});

      const textos = document.createElement('span');
      textos.className = 'resultado-evento-info';

      const nome = document.createElement('strong');
      nome.textContent = evento.nome_evento || 'Evento sem nome';

      const local = document.createElement('small');
      const cidade = String(evento.cidade_evento || '').trim();
      const uf = String(evento.uf || '').trim();
      const data = String(evento.data_evento || '').slice(0, 10);
      const dataFormatada = data
        ? new Intl.DateTimeFormat('pt-BR', {dateStyle: 'medium'}).format(new Date(`${data}T12:00:00`))
        : 'Data não informada';
      local.textContent = `${cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : 'Local não informado'} · ${dataFormatada}`;

      const preco = document.createElement('span');
      preco.className = 'resultado-evento-preco';
      preco.textContent = Boolean(evento.gratuidade)
        ? 'Gratuito'
        : Number(evento.valor_ingresso_minimo || 0).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});

      textos.append(nome, local);
      link.append(imagem, textos, preco);
      return link;
    }

    async function executarBusca() {
      const termo = campo.value.trim();

      if (!termo && !filtrosAtivos()) {
        requisicaoAtual?.abort();
        requisicaoAtual = null;
        mostrarMensagem('Digite para buscar eventos ou selecione filtros.');
        return;
      }

      requisicaoAtual?.abort();
      requisicaoAtual = new AbortController();
      const controleAtual = requisicaoAtual;
      resultados.setAttribute('aria-busy', 'true');

      const parametros = new URLSearchParams();
      if (termo) parametros.set('busca', termo);
      if (filtros.categoria) parametros.set('categoria', filtros.categoria);
      if (filtros.classificacao) parametros.set('classificacao', filtros.classificacao);
      if (filtros.preco !== 'todos') parametros.set('ingresso', filtros.preco);
      const periodo = periodoDaData(filtros.data);
      if (periodo) parametros.set('periodo', periodo);
      if (filtros.uf) parametros.set('uf', filtros.uf);
      if (filtros.cidade) parametros.set('cidade', filtros.cidade);
      if (filtros.precoMax < PRECO_MAXIMO_SLIDER) parametros.set('valor_maximo', String(filtros.precoMax));
      parametros.set('limite', String(LIMITE_RESULTADOS));

      try {
        const resposta = await fetch(`api/eventos?${parametros.toString()}`, {
          headers: {Accept: 'application/json'},
          signal: controleAtual.signal
        });
        const dados = await resposta.json();

        if (!resposta.ok) {
          throw new Error(dados.erro || 'Não foi possível buscar eventos');
        }

        if (requisicaoAtual !== controleAtual) {
          return;
        }

        const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];

        if (eventos.length === 0) {
          mostrarMensagem(termo
            ? 'Nenhum evento encontrado com esses critérios.'
            : 'Nenhum evento corresponde aos filtros selecionados.');
          return;
        }

        resultados.replaceChildren(...eventos.map(criarSugestao));
      } catch (erro) {
        if (erro.name !== 'AbortError') {
          mostrarMensagem('Não foi possível carregar as sugestões');
        }
      } finally {
        if (requisicaoAtual === controleAtual) resultados.setAttribute('aria-busy', 'false');
      }
    }

    function agendarBusca() {
      window.clearTimeout(temporizador);
      atualizarBotaoLimpar();
      temporizador = window.setTimeout(function () {
        executarBusca();
      }, 300);
    }

    function aplicarFiltros() {
      atualizarControlesFiltros();
      aplicarBuscaNaHome(campo.value.trim());
      agendarBusca();
    }

    // Abre o painel ao focar/clicar na busca principal.
    campo.addEventListener('focus', abrirContainerBusca);
    campo.addEventListener('click', abrirContainerBusca);

    // Atualiza em tempo real enquanto o usuário digita.
    campo.addEventListener('input', function () {
      agendarBusca();
    });

    formulario.querySelectorAll('[data-filtro-preco]').forEach((botao) => {
      botao.addEventListener('click', () => {
        filtros.preco = botao.dataset.filtroPreco;
        aplicarFiltros();
      });
    });

    formulario.querySelectorAll('[data-filtro-data]').forEach((botao) => {
      botao.addEventListener('click', () => {
        filtros.data = botao.dataset.filtroData;
        aplicarFiltros();
      });
    });

    filtroClassificacao?.addEventListener('change', () => {
      filtros.classificacao = normalizarClassificacao(filtroClassificacao.value);
      aplicarFiltros();
    });

    filtroCategoria?.addEventListener('change', () => {
      filtros.categoria = filtroCategoria.value;
      aplicarFiltros();
    });

    filtroUf?.addEventListener('change', () => {
      filtros.uf = filtroUf.value;
      filtros.cidade = '';
      atualizarOpcoesCidade();
      aplicarFiltros();
    });

    filtroCidade?.addEventListener('change', () => {
      filtros.cidade = filtroCidade.value;
      aplicarFiltros();
    });

    sliderPreco?.addEventListener('input', () => {
      filtros.precoMax = Number(sliderPreco.value);
      atualizarControlesFiltros();
      aplicarBuscaNaHome(campo.value.trim(), false);
      window.clearTimeout(temporizador);
      temporizador = window.setTimeout(executarBusca, 250);
    });

    formulario.querySelector('[data-limpar-filtros]')?.addEventListener('click', () => {
      filtros.categoria = '';
      filtros.classificacao = '';
      filtros.preco = 'todos';
      filtros.cidade = '';
      filtros.uf = '';
      filtros.data = 'qualquer';
      filtros.precoMax = PRECO_MAXIMO_SLIDER;
      atualizarOpcoesCidade();
      aplicarFiltros();
    });

    campo.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') {
        fecharContainerBusca();
      }
    });

    // Botão X do painel flutuante.
    formulario.querySelector('[data-fechar-busca]')?.addEventListener('click', fecharContainerBusca);

    formulario.addEventListener('submit', function (evento) {
      const termo = campo.value.trim();
      campo.value = termo;
      atualizarBotaoLimpar();
      fecharContainerBusca();

      if (!estaNaHome) {
        return;
      }

      evento.preventDefault();
      aplicarBuscaNaHome(termo);
      executarBusca();
    });

    botaoLimpar?.addEventListener('click', function () {
      campo.value = '';
      requisicaoAtual?.abort();
      requisicaoAtual = null;
      atualizarBotaoLimpar();

      if (estaNaHome) {
        aplicarBuscaNaHome('');
      }

      campo.focus();
      executarBusca();
    });

    window.addEventListener('popstate', function () {
      if (!estaNaHome) {
        return;
      }

      const parametros = new URL(window.location.href).searchParams;
      const termo = parametros.get('busca')?.trim() || '';
      filtros.categoria = parametros.get('categoria') || '';
      filtros.classificacao = normalizarClassificacao(parametros.get('classificacao') || '');
      filtros.preco = PRECOS_VALIDOS.includes(parametros.get('preco')) ? parametros.get('preco') : 'todos';
      filtros.cidade = parametros.get('cidade') || '';
      filtros.uf = (parametros.get('uf') || '').toUpperCase();
      filtros.data = PERIODOS_VALIDOS.includes(parametros.get('data')) ? parametros.get('data') : 'qualquer';
      filtros.precoMax = Number(parametros.get('preco_max') || PRECO_MAXIMO_SLIDER);
      campo.value = termo;
      atualizarControlesFiltros();
      atualizarBotaoLimpar();
      aplicarBuscaNaHome(termo, false);
      if (!containerFlutuante.classList.contains('d-none')) executarBusca();
    });

    document.addEventListener('click', function (evento) {
      if (!formulario.contains(evento.target)) {
        fecharContainerBusca();
      }
    });

    atualizarControlesFiltros();
    atualizarBotaoLimpar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarBuscaCabecalho);
  } else {
    iniciarBuscaCabecalho();
  }
})();
