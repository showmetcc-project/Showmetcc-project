(function () {
  'use strict';

  const cabecalhoFixo = document.getElementById('header');

  function sincronizarAlturaCabecalho() {
    if (!cabecalhoFixo || !cabecalhoFixo.classList.contains('fixed-top')) {
      return;
    }

    const altura = Math.ceil(cabecalhoFixo.getBoundingClientRect().height);

    if (altura > 0) {
      document.documentElement.style.setProperty('--cabecalho-altura-real', `${altura}px`);
    }
  }

  sincronizarAlturaCabecalho();

  if (cabecalhoFixo && typeof window.ResizeObserver === 'function') {
    const observadorCabecalho = new ResizeObserver(sincronizarAlturaCabecalho);
    observadorCabecalho.observe(cabecalhoFixo);
  } else {
    window.addEventListener('resize', sincronizarAlturaCabecalho, {passive: true});
  }

  window.addEventListener('load', sincronizarAlturaCabecalho, {once: true});

  document.querySelectorAll('[data-voltar-fallback]').forEach((botao) => {
    botao.addEventListener('click', (evento) => {
      evento.preventDefault();
      const fallback = botao.dataset.voltarFallback || 'inicio.php';
      const possuiPaginaAnterior = window.history.length > 1 && document.referrer !== '';

      if (possuiPaginaAnterior) {
        window.history.back();
        return;
      }

      window.location.href = fallback;
    });
  });

  const usuarioLogado = cabecalhoFixo?.dataset.usuarioLogado === '1';
  const modalEscolhaLogin = document.getElementById('modalEscolhaLogin');
  const modalLoginNecessario = document.getElementById('modalLoginNecessario');

  function abrirModal(modal) {
    if (!modal) return;

    if (typeof modal.showModal === 'function') {
      if (!modal.open) modal.showModal();
      return;
    }

    modal.setAttribute('open', '');
  }

  function fecharModal(modal) {
    if (!modal) return;

    if (typeof modal.close === 'function') {
      modal.close();
      return;
    }

    modal.removeAttribute('open');
  }

  function ehLinkDeDetalhes(link) {
    try {
      const url = new URL(link.href, window.location.href);
      return url.pathname.toLowerCase().endsWith('/detalhesevento.php');
    } catch (erro) {
      return false;
    }
  }

  document.addEventListener('click', (evento) => {
    if (!(evento.target instanceof Element)) return;

    const fechar = evento.target.closest('[data-fechar-modal]');
    if (fechar) {
      evento.preventDefault();
      fecharModal(fechar.closest('dialog'));
      return;
    }

    const escolhaLogin = evento.target.closest('[data-abrir-modal-entrada]');
    if (escolhaLogin) {
      evento.preventDefault();
      abrirModal(modalEscolhaLogin);
      return;
    }

    if (usuarioLogado) return;

    const link = evento.target.closest('a[href]');
    if (!link) return;

    if (link.matches('[data-requer-login]') || ehLinkDeDetalhes(link)) {
      evento.preventDefault();
      abrirModal(modalLoginNecessario);
    }
  });

  [modalEscolhaLogin, modalLoginNecessario].forEach((modal) => {
    modal?.addEventListener('click', (evento) => {
      if (evento.target === modal) fecharModal(modal);
    });
  });
}());
