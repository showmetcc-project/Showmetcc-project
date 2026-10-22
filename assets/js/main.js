/**
* Template Name: Arsha
* Template URL: https://bootstrapmade.com/arsha-free-bootstrap-html-template-corporate/
* Updated: Feb 22 2025 with Bootstrap v5.3.3
* Author: BootstrapMade.com
* License: https://bootstrapmade.com/license/
*/

(function() {
  "use strict";

  /**
   * Apply .scrolled class to the body as the page is scrolled down
   */
  function toggleScrolled() {
    const selectBody = document.querySelector('body');
    const selectHeader = document.querySelector('#header');
    if (!selectHeader) return;
    if (!selectHeader.classList.contains('scroll-up-sticky') && !selectHeader.classList.contains('sticky-top') && !selectHeader.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? selectBody.classList.add('scrolled') : selectBody.classList.remove('scrolled');
  }

  document.addEventListener('scroll', toggleScrolled);
  window.addEventListener('load', toggleScrolled);

  /**
   * Mobile nav toggle
   */
  const mobileNavToggleBtn = document.querySelector('.mobile-nav-toggle');

  function mobileNavToogle() {
    document.querySelector('body').classList.toggle('mobile-nav-active');
    mobileNavToggleBtn.classList.toggle('bi-list');
    mobileNavToggleBtn.classList.toggle('bi-x');
  }
  if (mobileNavToggleBtn) {
    mobileNavToggleBtn.addEventListener('click', mobileNavToogle);
  }

  /**
   * Hide mobile nav on same-page/hash links
   */
  document.querySelectorAll('#navmenu a').forEach(navmenu => {
    navmenu.addEventListener('click', () => {
      if (document.querySelector('.mobile-nav-active')) {
        mobileNavToogle();
      }
    });

  });

  /**
   * Toggle mobile nav dropdowns
   */
  document.querySelectorAll('.navmenu .toggle-dropdown').forEach(navmenu => {
    navmenu.addEventListener('click', function(e) {
      e.preventDefault();
      this.parentNode.classList.toggle('active');
      this.parentNode.nextElementSibling.classList.toggle('dropdown-active');
      e.stopImmediatePropagation();
    });
  });

  /**
   * Preloader
   */
  const preloader = document.querySelector('#preloader');
  if (preloader) {
    window.addEventListener('load', () => {
      preloader.remove();
    });
  }

  /**
   * Scroll top button
   */
  let scrollTop = document.querySelector('.scroll-top');
  if (!scrollTop) {
    scrollTop = document.createElement('a');
    scrollTop.href = '#';
    scrollTop.id = 'scroll-top';
    scrollTop.className = 'scroll-top d-flex align-items-center justify-content-center';
    scrollTop.setAttribute('aria-label', 'Voltar ao topo');
    scrollTop.innerHTML = '<i class="bi bi-arrow-up-short" aria-hidden="true"></i>';
    document.body.append(scrollTop);
  }

  function toggleScrollTop() {
    if (scrollTop) {
      window.scrollY > 100 ? scrollTop.classList.add('active') : scrollTop.classList.remove('active');
    }
  }
  if (scrollTop) {
    scrollTop.addEventListener('click', (e) => {
      e.preventDefault();
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    });
  }

  window.addEventListener('load', toggleScrollTop);
  document.addEventListener('scroll', toggleScrollTop);

  /**
   * Animation on scroll function and init
   */
  function aosInit() {
    if (typeof AOS === 'undefined') return;
    AOS.init({
      duration: 600,
      easing: 'ease-in-out',
      once: true,
      mirror: false
    });
  }
  window.addEventListener('load', aosInit);

  /**
   * Frequently Asked Questions Toggle
   */
  document.querySelectorAll('.faq-item h3, .faq-item .faq-toggle').forEach((faqItem) => {
    faqItem.addEventListener('click', () => {
      faqItem.parentNode.classList.toggle('faq-active');
    });
  });

  /**
   * Correct scrolling position upon page load for URLs containing hash links.
   */
  window.addEventListener('load', function(e) {
    if (window.location.hash) {
      if (document.querySelector(window.location.hash)) {
        setTimeout(() => {
          let section = document.querySelector(window.location.hash);
          let scrollMarginTop = getComputedStyle(section).scrollMarginTop;
          window.scrollTo({
            top: section.offsetTop - parseInt(scrollMarginTop),
            behavior: 'smooth'
          });
        }, 100);
      }
    }
  });

  /**
   * Navmenu Scrollspy
   */
  let navmenulinks = document.querySelectorAll('.navmenu a');

  function navmenuScrollspy() {
    navmenulinks.forEach(navmenulink => {
      if (!navmenulink.hash) return;
      let section = document.querySelector(navmenulink.hash);
      if (!section) return;
      let position = window.scrollY + 200;
      if (position >= section.offsetTop && position <= (section.offsetTop + section.offsetHeight)) {
        document.querySelectorAll('.navmenu a.active').forEach(link => link.classList.remove('active'));
        navmenulink.classList.add('active');
      } else {
        navmenulink.classList.remove('active');
      }
    })
  }
  window.addEventListener('load', navmenuScrollspy);
  document.addEventListener('scroll', navmenuScrollspy);

})();

/**
 * Feedback compartilhado do ShowMe.
 * confirmar() resolve true/false; toast() exibe uma notificação temporária.
 */
(function () {
  'use strict';

  let modalAtual = null;
  let resolverModal = null;
  let focoAnterior = null;

  function garantirContainerToasts(destino) {
    let container = document.getElementById('showmeToastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'showmeToastContainer';
      container.className = 'showme-toast-container';
      container.setAttribute('aria-live', 'polite');
      container.setAttribute('aria-atomic', 'false');
    }

    const recipiente = destino instanceof HTMLElement ? destino : document.body;
    if (container.parentElement !== recipiente) recipiente.append(container);
    return container;
  }

  function toast(mensagem, opcoes = {}) {
    const variante = opcoes.variante === 'erro' ? 'erro' : 'sucesso';
    const duracao = Math.max(1500, Number(opcoes.duracao) || 4000);
    const notificacao = document.createElement('div');
    notificacao.className = `showme-toast showme-toast-${variante}`;
    notificacao.setAttribute('role', variante === 'erro' ? 'alert' : 'status');

    const icone = document.createElement('i');
    icone.className = variante === 'erro'
      ? 'bi bi-exclamation-circle-fill'
      : 'bi bi-check-circle-fill';
    icone.setAttribute('aria-hidden', 'true');

    const texto = document.createElement('p');
    texto.textContent = String(mensagem || '');

    const conteudo = document.createElement('div');
    conteudo.className = 'showme-toast-conteudo';
    conteudo.append(texto);

    if (opcoes.acao && opcoes.acao.texto && opcoes.acao.href) {
      const acao = document.createElement('a');
      acao.className = 'showme-toast-acao';
      acao.href = String(opcoes.acao.href);
      acao.textContent = String(opcoes.acao.texto);
      conteudo.append(acao);
    }

    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'showme-toast-fechar';
    fechar.setAttribute('aria-label', 'Fechar notificação');
    fechar.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';

    let temporizador = null;
    const remover = () => {
      if (!notificacao.isConnected) return;
      window.clearTimeout(temporizador);
      notificacao.classList.add('showme-toast-saindo');
      notificacao.addEventListener('animationend', () => notificacao.remove(), {once: true});
    };

    fechar.addEventListener('click', remover);
    notificacao.append(icone, conteudo, fechar);
    garantirContainerToasts(opcoes.container).append(notificacao);
    temporizador = window.setTimeout(remover, duracao);
    return notificacao;
  }

  function finalizarConfirmacao(resultado) {
    if (!modalAtual) return;
    const modal = modalAtual;
    const resolver = resolverModal;
    modalAtual = null;
    resolverModal = null;
    modal.classList.add('showme-confirmacao-saindo');
    modal.addEventListener('animationend', () => modal.remove(), {once: true});
    document.body.classList.remove('showme-modal-aberto');
    focoAnterior?.focus?.();
    resolver?.(resultado);
  }

  function confirmar(opcoes = {}) {
    if (modalAtual) finalizarConfirmacao(false);

    const variante = ['importante', 'edicao', 'conflito'].includes(opcoes.variante)
      ? opcoes.variante
      : 'destrutiva';
    const tituloId = `showmeConfirmacaoTitulo-${Date.now()}`;
    focoAnterior = document.activeElement;

    const overlay = document.createElement('div');
    overlay.className = `showme-confirmacao-overlay showme-confirmacao-${variante}`;
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', tituloId);

    const caixa = document.createElement('div');
    caixa.className = 'showme-confirmacao';

    const icone = document.createElement('i');
    icone.className = variante === 'destrutiva'
      ? 'bi bi-exclamation-triangle-fill showme-confirmacao-icone'
      : variante === 'edicao'
        ? 'bi bi-pencil-square showme-confirmacao-icone'
        : variante === 'conflito'
          ? 'bi bi-calendar-x showme-confirmacao-icone'
          : 'bi bi-question-circle-fill showme-confirmacao-icone';
    icone.setAttribute('aria-hidden', 'true');

    const titulo = document.createElement('h2');
    titulo.id = tituloId;
    titulo.textContent = String(opcoes.titulo || 'Confirmar ação');

    const texto = document.createElement('p');
    texto.textContent = String(opcoes.texto || 'Deseja continuar?');

    const acoes = document.createElement('div');
    acoes.className = 'showme-confirmacao-acoes';

    const cancelar = document.createElement('button');
    cancelar.type = 'button';
    cancelar.className = 'showme-confirmacao-cancelar';
    cancelar.textContent = String(opcoes.cancelarTexto || 'Cancelar');

    const confirmarBotao = document.createElement('button');
    confirmarBotao.type = 'button';
    confirmarBotao.className = 'showme-confirmacao-confirmar';
    confirmarBotao.textContent = String(opcoes.confirmarTexto || 'Confirmar');

    cancelar.addEventListener('click', () => finalizarConfirmacao(false));
    confirmarBotao.addEventListener('click', () => finalizarConfirmacao(true));
    overlay.addEventListener('click', (evento) => {
      if (evento.target === overlay) finalizarConfirmacao(false);
    });
    overlay.addEventListener('keydown', (evento) => {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        finalizarConfirmacao(false);
      }
      if (evento.key === 'Tab') {
        const controles = [cancelar, confirmarBotao];
        const indice = controles.indexOf(document.activeElement);
        const proximo = evento.shiftKey
          ? (indice <= 0 ? controles.length - 1 : indice - 1)
          : (indice >= controles.length - 1 ? 0 : indice + 1);
        evento.preventDefault();
        controles[proximo].focus();
      }
    });

    acoes.append(cancelar, confirmarBotao);
    caixa.append(icone, titulo, texto, acoes);
    overlay.append(caixa);
    document.body.append(overlay);
    document.body.classList.add('showme-modal-aberto');
    modalAtual = overlay;

    return new Promise((resolver) => {
      resolverModal = resolver;
      window.requestAnimationFrame(() => cancelar.focus());
    });
  }

  /**
   * Aviso informativo: mesmo visual da confirmação, com um único botão.
   * Aceita opcoes.itens (array) para listar detalhes.
   */
  function avisar(opcoes = {}) {
    if (modalAtual) finalizarConfirmacao(false);

    const variante = ['edicao', 'conflito'].includes(opcoes.variante)
      ? opcoes.variante
      : 'importante';
    const tituloId = `showmeAvisoTitulo-${Date.now()}`;
    focoAnterior = document.activeElement;

    const overlay = document.createElement('div');
    overlay.className = `showme-confirmacao-overlay showme-confirmacao-${variante}`;
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-labelledby', tituloId);

    const caixa = document.createElement('div');
    caixa.className = 'showme-confirmacao showme-aviso';

    const icone = document.createElement('i');
    icone.className = `${opcoes.icone || 'bi bi-info-circle-fill'} showme-confirmacao-icone`;
    icone.setAttribute('aria-hidden', 'true');

    const titulo = document.createElement('h2');
    titulo.id = tituloId;
    titulo.textContent = String(opcoes.titulo || 'Aviso');

    const texto = document.createElement('p');
    texto.textContent = String(opcoes.texto || '');

    const itens = Array.isArray(opcoes.itens)
      ? opcoes.itens.map((item) => String(item || '').trim()).filter(Boolean)
      : [];

    const lista = document.createElement('ul');
    lista.className = 'showme-aviso-lista';
    itens.forEach((item) => {
      const linha = document.createElement('li');
      linha.textContent = item;
      lista.append(linha);
    });

    const acoes = document.createElement('div');
    acoes.className = 'showme-confirmacao-acoes';

    const fechar = document.createElement('button');
    fechar.type = 'button';
    fechar.className = 'showme-confirmacao-confirmar';
    fechar.textContent = String(opcoes.fecharTexto || 'Entendi');

    fechar.addEventListener('click', () => finalizarConfirmacao(true));
    overlay.addEventListener('click', (evento) => {
      if (evento.target === overlay) finalizarConfirmacao(true);
    });
    overlay.addEventListener('keydown', (evento) => {
      if (evento.key === 'Escape') {
        evento.preventDefault();
        finalizarConfirmacao(true);
      }
      if (evento.key === 'Tab') {
        evento.preventDefault();
        fechar.focus();
      }
    });

    acoes.append(fechar);
    caixa.append(icone, titulo, texto);
    if (itens.length) caixa.append(lista);
    caixa.append(acoes);
    overlay.append(caixa);
    document.body.append(overlay);
    document.body.classList.add('showme-modal-aberto');
    modalAtual = overlay;

    return new Promise((resolver) => {
      resolverModal = resolver;
      window.requestAnimationFrame(() => fechar.focus());
    });
  }

  window.ShowMeUI = Object.freeze({confirmar, avisar, toast});
})();

/**
 * Acessibilidade compartilhada.
 * Usa o texto alternativo cadastrado para a imagem do evento e, quando ele
 * está vazio, gera a descrição padrão com o nome do evento e a cidade.
 */
(function () {
  'use strict';

  function textoAltImagemEvento(evento = {}) {
    const altCadastrado = String(evento.alt_imagem ?? '').trim();

    if (altCadastrado) {
      return altCadastrado;
    }

    const nome = String(evento.nome_evento ?? '').trim() || 'evento sem nome';
    const cidade = String(evento.cidade_evento ?? '').trim();

    return `Imagem de divulgação para o evento ${nome}${cidade ? ` - ${cidade}` : ''}`;
  }

  const rotulosClassificacaoIndicativa = Object.freeze({
    livre: 'Livre',
    '6': '6 anos',
    '10': '10 anos',
    '12': '12 anos',
    '14': '14 anos',
    '16': '16 anos',
    '18': '18 anos'
  });

  /**
   * Gera o nome acessível completo do selo, independentemente de o banco
   * armazenar "Livre"/"6 anos" ou apenas a chave numérica "6".
   */
  function ariaLabelClassificacaoIndicativa(valor) {
    const texto = String(valor ?? '').trim();

    if (!texto) {
      return '';
    }

    const normalizado = texto.toLocaleLowerCase('pt-BR');
    const chave = normalizado === 'livre' || normalizado === 'l'
      ? 'livre'
      : (texto.match(/\d+/) || [])[0];
    const rotulo = rotulosClassificacaoIndicativa[chave] || texto;

    return `Classificação indicativa: ${rotulo}`;
  }

  /**
   * Aplica a semântica única dos selos: role="img", aria-label completo e
   * nenhum title isolado. Também limpa os atributos quando o valor é vazio.
   */
  function aplicarSemanticaClassificacao(selo, valor) {
    if (!(selo instanceof HTMLElement)) {
      return;
    }

    const rotulo = ariaLabelClassificacaoIndicativa(valor);

    if (!rotulo) {
      selo.removeAttribute('role');
      selo.removeAttribute('aria-label');
      selo.removeAttribute('title');
      return;
    }

    selo.setAttribute('role', 'img');
    selo.setAttribute('aria-label', rotulo);
    selo.removeAttribute('title');
  }

  window.ShowMeA11y = Object.freeze({
    textoAltImagemEvento,
    ariaLabelClassificacaoIndicativa,
    aplicarSemanticaClassificacao
  });
})();
