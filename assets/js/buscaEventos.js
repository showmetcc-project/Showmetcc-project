(function () {
  'use strict';

  function iniciarBuscaCabecalho() {
    const formulario = document.querySelector('.busca-cabecalho');
    const campo = document.getElementById('buscaCabecalho');
    const sugestoes = document.getElementById('sugestoesBusca');

    if (!formulario || !campo || !sugestoes) {
      return;
    }

    const imagemPadrao = 'assets/img/banner_site_565x235px.png';
    let temporizador = null;
    let requisicaoAtual = null;

    function caminhoImagem(caminho) {
      const valor = String(caminho || '').trim();

      if (!valor) {
        return imagemPadrao;
      }

      if (
        /^(?:https?:)?\/\//i.test(valor)
        || valor.startsWith('/')
        || valor.includes('/')
        || valor.includes('\\')
      ) {
        return valor;
      }

      return `assets/img/${valor}`;
    }

    function ocultarSugestoes() {
      sugestoes.hidden = true;
      sugestoes.replaceChildren();
    }

    function mostrarMensagem(mensagem) {
      const item = document.createElement('p');
      item.className = 'sugestoes-busca-mensagem';
      item.textContent = mensagem;
      sugestoes.replaceChildren(item);
      sugestoes.hidden = false;
    }

    function criarSugestao(evento) {
      const link = document.createElement('a');
      link.className = 'sugestao-evento';
      link.href = `detalhesEvento.php?id=${encodeURIComponent(evento.id_evento)}`;
      link.setAttribute('role', 'option');

      const imagem = document.createElement('img');
      imagem.src = caminhoImagem(evento.imagem_evento);
      imagem.alt = '';
      imagem.loading = 'lazy';
      imagem.addEventListener('error', function () {
        this.src = imagemPadrao;
      }, { once: true });

      const textos = document.createElement('span');
      textos.className = 'sugestao-evento-textos';

      const nome = document.createElement('strong');
      nome.textContent = evento.nome_evento || 'Evento sem nome';

      const local = document.createElement('small');
      const cidade = String(evento.cidade_evento || '').trim();
      const uf = String(evento.uf || '').trim();
      local.textContent = cidade
        ? `${cidade}${uf ? ` - ${uf}` : ''}`
        : (evento.local_evento || 'Local não informado');

      textos.append(nome, local);
      link.append(imagem, textos);
      return link;
    }

    async function buscarSugestoes(termo) {
      if (requisicaoAtual) {
        requisicaoAtual.abort();
      }

      requisicaoAtual = new AbortController();
      const parametros = new URLSearchParams({ busca: termo, limite: '5' });

      try {
        const resposta = await fetch(`api/eventos?${parametros.toString()}`, {
          headers: { Accept: 'application/json' },
          signal: requisicaoAtual.signal
        });
        const dados = await resposta.json();

        if (!resposta.ok) {
          throw new Error(dados.erro || 'Não foi possível buscar eventos');
        }

        if (campo.value.trim() !== termo) {
          return;
        }

        const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];

        if (eventos.length === 0) {
          mostrarMensagem('Nenhum evento encontrado');
          return;
        }

        sugestoes.replaceChildren(...eventos.map(criarSugestao));
        sugestoes.hidden = false;
      } catch (erro) {
        if (erro.name !== 'AbortError') {
          mostrarMensagem('Não foi possível carregar as sugestões');
        }
      }
    }

    campo.addEventListener('input', function () {
      const termo = campo.value.trim();
      window.clearTimeout(temporizador);

      if (!termo) {
        if (requisicaoAtual) {
          requisicaoAtual.abort();
        }
        ocultarSugestoes();
        return;
      }

      temporizador = window.setTimeout(function () {
        buscarSugestoes(termo);
      }, 300);
    });

    campo.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') {
        ocultarSugestoes();
      }
    });

    formulario.addEventListener('submit', function (evento) {
      const termo = campo.value.trim();

      if (!termo) {
        evento.preventDefault();
        ocultarSugestoes();
        campo.focus();
        return;
      }

      campo.value = termo;
    });

    document.addEventListener('click', function (evento) {
      if (!formulario.contains(evento.target)) {
        ocultarSugestoes();
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciarBuscaCabecalho);
  } else {
    iniciarBuscaCabecalho();
  }
})();
