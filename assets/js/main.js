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

  function garantirContainerToasts() {
    let container = document.getElementById('showmeToastContainer');
    if (container) return container;

    container = document.createElement('div');
    container.id = 'showmeToastContainer';
    container.className = 'showme-toast-container';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');
    document.body.append(container);
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
    garantirContainerToasts().append(notificacao);
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

    const variante = ['importante', 'edicao'].includes(opcoes.variante)
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

  window.ShowMeUI = Object.freeze({confirmar, toast});
})();
