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
}());
