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

  const CHAVE_ULTIMA_PAGINA = 'showme:ultima-pagina';
  const PAGINA_VOLTAR_SEM_REGISTRO = /\/(?:termos|politicaPrivacidade)\.php$/i;

  function urlDeOutraOrigem(url) {
    try {
      return new URL(url, window.location.href).origin !== window.location.origin;
    } catch (erro) {
      return true;
    }
  }

  // Guarda a última tela do ShowMe visitada nesta sessão. As telas legais não
  // contam, para o "voltar" apontar para onde o usuário estava antes.
  function registrarUltimaPagina() {
    if (PAGINA_VOLTAR_SEM_REGISTRO.test(window.location.pathname)) {
      return;
    }

    try {
      sessionStorage.setItem(CHAVE_ULTIMA_PAGINA, window.location.href);
    } catch (erro) {
      // sessionStorage indisponível (ex.: modo privado restrito): segue sem registro.
    }
  }

  function ultimaPaginaRegistrada() {
    try {
      const pagina = sessionStorage.getItem(CHAVE_ULTIMA_PAGINA);
      if (pagina && pagina !== window.location.href && !urlDeOutraOrigem(pagina)) {
        return pagina;
      }
    } catch (erro) {
      // ignora
    }

    return '';
  }

  registrarUltimaPagina();

  document.querySelectorAll('[data-voltar-fallback]').forEach((botao) => {
    botao.addEventListener('click', (evento) => {
      evento.preventDefault();

      // Protege contra navegação dupla caso o script seja carregado duas vezes.
      if (botao.dataset.voltarEmAndamento === '1') {
        return;
      }
      botao.dataset.voltarEmAndamento = '1';
      window.setTimeout(() => {
        delete botao.dataset.voltarEmAndamento;
      }, 0);

      const fallback = botao.dataset.voltarFallback || 'index.php';
      const referrer = document.referrer;
      const veioDoShowMe = Boolean(referrer) && !urlDeOutraOrigem(referrer);

      // Veio de outra tela do ShowMe: usa o histórico quando existe (mesma aba)
      // ou abre a página de origem quando não há histórico (ex.: nova aba).
      if (veioDoShowMe) {
        if (window.history.length > 1) {
          window.history.back();
        } else {
          window.location.href = referrer;
        }
        return;
      }

      // Sem referrer (acesso direto/bookmark): recupera a última tela da sessão.
      const ultimaPagina = ultimaPaginaRegistrada();
      if (ultimaPagina) {
        window.location.href = ultimaPagina;
        return;
      }

      // Primeiro acesso já nesta página: vai para o destino padrão (Home).
      window.location.href = fallback;
    });
  });

  const usuarioLogado = cabecalhoFixo?.dataset.usuarioLogado === '1';
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

    if (usuarioLogado) return;

    const link = evento.target.closest('a[href]');
    if (!link) return;

    if (link.matches('[data-requer-login]') || ehLinkDeDetalhes(link)) {
      evento.preventDefault();
      abrirModal(modalLoginNecessario);
    }
  });

  [modalLoginNecessario].forEach((modal) => {
    modal?.addEventListener('click', (evento) => {
      if (evento.target === modal) fecharModal(modal);
    });
  });
}());
