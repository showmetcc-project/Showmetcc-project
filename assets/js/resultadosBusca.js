(function () {
  'use strict';

  const grade = document.getElementById('resultadosBuscaGrid');
  const resumo = document.getElementById('resumoResultadosBusca');

  if (!grade || !resumo) return;

  const termo = new URLSearchParams(window.location.search).get('busca')?.trim() || '';
  const imagemPadrao = 'assets/img/banner_site_565x235px.png';

  function caminhoImagem(caminho) {
    const valor = String(caminho || '').trim();

    if (!valor) return imagemPadrao;
    if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/') || valor.includes('\\')) {
      return valor;
    }

    return `assets/img/${valor}`;
  }

  function formatarData(data) {
    if (!data) return 'Data não informada';
    const partes = String(data).split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : String(data);
  }

  function criarCard(evento) {
    const card = document.createElement('article');
    const link = document.createElement('a');
    const badge = document.createElement('span');
    const imagem = document.createElement('img');
    const conteudo = document.createElement('div');
    const nome = document.createElement('h4');
    const informacoes = document.createElement('div');
    const local = document.createElement('span');
    const data = document.createElement('span');
    const iconeLocal = document.createElement('i');
    const iconeData = document.createElement('i');
    const gratuito = Boolean(evento.gratuidade);
    const cidade = String(evento.cidade_evento || '').trim();
    const uf = String(evento.uf || '').trim();

    card.className = 'card-evento';
    link.href = `detalhesEvento.php?id=${encodeURIComponent(evento.id_evento)}`;
    badge.className = `badge-evento ${gratuito ? 'gratuito' : 'pago'}`;
    badge.textContent = gratuito ? 'Gratuito' : 'Pago';

    imagem.src = caminhoImagem(evento.imagem_evento);
    imagem.alt = evento.nome_evento || 'Imagem do evento';
    imagem.loading = 'lazy';
    imagem.addEventListener('error', function () {
      this.src = imagemPadrao;
    }, { once: true });

    conteudo.className = 'card-conteudo';
    nome.textContent = evento.nome_evento || 'Evento sem nome';
    informacoes.className = 'info-evento';
    iconeLocal.className = 'bi bi-geo-alt-fill';
    iconeLocal.setAttribute('aria-hidden', 'true');
    iconeData.className = 'bi bi-calendar-event';
    iconeData.setAttribute('aria-hidden', 'true');

    local.append(iconeLocal, document.createTextNode(
      cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : 'Local não informado'
    ));
    data.append(iconeData, document.createTextNode(formatarData(evento.data_evento)));
    informacoes.append(local, data);
    conteudo.append(nome, informacoes);
    link.append(badge, imagem, conteudo);

    const favorito = document.createElement('button');
    favorito.type = 'button';
    favorito.className = 'btn-toggle-favorito';
    favorito.dataset.favoritoEvento = String(evento.id_evento);
    favorito.setAttribute('aria-label', `Adicionar ${evento.nome_evento || 'evento'} aos favoritos`);
    favorito.setAttribute('aria-pressed', 'false');
    favorito.title = 'Adicionar aos favoritos';
    favorito.innerHTML = '<i class="bi bi-heart" aria-hidden="true"></i>';

    card.append(link, favorito);
    return card;
  }

  function mostrarEstado(mensagem, erro = false) {
    const estado = document.createElement('p');
    estado.className = `estado-resultados-busca${erro ? ' erro' : ''}`;
    estado.textContent = mensagem;
    grade.replaceChildren(estado);
    grade.setAttribute('aria-busy', 'false');
  }

  async function carregarResultados() {
    if (!termo) {
      resumo.textContent = 'Digite um termo no campo de busca acima.';
      mostrarEstado('Informe o nome, a cidade, a categoria ou o artista que deseja encontrar.');
      return;
    }

    const parametros = new URLSearchParams({ busca: termo });

    try {
      const resposta = await fetch(`api/eventos?${parametros.toString()}`, {
        headers: { Accept: 'application/json' }
      });
      const dados = await resposta.json();

      if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível buscar eventos');

      const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];
      resumo.textContent = `${eventos.length} ${eventos.length === 1 ? 'evento encontrado' : 'eventos encontrados'}`;

      if (eventos.length === 0) {
        mostrarEstado(`Nenhum evento encontrado para '${termo}'`);
        return;
      }

      grade.replaceChildren(...eventos.map(criarCard));
      grade.setAttribute('aria-busy', 'false');
    } catch (erro) {
      resumo.textContent = 'Não foi possível concluir a busca.';
      mostrarEstado('Ocorreu um erro ao carregar os eventos. Tente novamente.', true);
    }
  }

  carregarResultados();
})();
