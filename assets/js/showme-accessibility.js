/**
 * Configuracao da barra de acessibilidade do ShowMe.
 * A biblioteca accessibility deve ser carregada antes deste arquivo.
 */
(function () {
  'use strict';

  window.addEventListener('load', function () {
    if (typeof window.Accessibility === 'undefined') {
      console.warn(
        'ShowMe Acessibilidade: a biblioteca accessibility nao foi carregada.'
      );
      return;
    }

    if (window.showMeAccessibility) {
      return;
    }

    // O VLibras atual usa Shadow DOM; mantenha suporte ao markup anterior.
    var vlibrasHost = document.getElementById('vlibras-access-wrapper');
    var vlibrasButton = vlibrasHost && vlibrasHost.shadowRoot
      ? vlibrasHost.shadowRoot.querySelector('#vlibras-button')
      : document.querySelector('[vw-access-button]');
    var header = document.querySelector('#header');
    var root = document.documentElement;

    function positionWidgets() {
      if (vlibrasButton) {
        var rect = vlibrasButton.getBoundingClientRect();
        // Ao abrir o VLibras/menu mobile, preserve a ultima ancora visivel.
        if (rect.width && rect.height) {
          var viewportWidth = document.documentElement.clientWidth;
          var rightOffset = Math.max(0, viewportWidth - rect.right);
          root.style.setProperty('--showme-access-right', rightOffset + 'px');
          // O centro do VLibras e 50vh; nao use o top intermediario da animacao de resize.
          root.style.setProperty('--showme-access-top', 'calc(50vh + ' + (rect.height / 2 + 4) + 'px)');
          root.style.setProperty('--showme-access-panel-right', (viewportWidth - rect.left + 10) + 'px');
        }
      }
      var headerBottom = header ? header.getBoundingClientRect().bottom : 0;
      root.style.setProperty('--showme-access-panel-top', Math.max(8, headerBottom + 8) + 'px');
    }

    positionWidgets();
    window.addEventListener('resize', positionWidgets);
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', positionWidgets);
    }
    if (window.ResizeObserver) {
      var observer = new ResizeObserver(positionWidgets);
      if (vlibrasButton) observer.observe(vlibrasButton);
      if (header) observer.observe(header);
    }

    function personalizarMenu(instancia) {
      var menu = document.querySelector('._access-menu');
      if (!menu || menu.dataset.showmePersonalizado === 'true') {
        return Boolean(menu);
      }

      var cabecalho = menu.querySelector('._text-center');
      var lista = menu.querySelector('ul');
      var botaoFechar = menu.querySelector('._menu-close-btn');
      var botaoRedefinir = menu.querySelector('._menu-reset-btn');

      if (!cabecalho || !lista || !botaoFechar || !botaoRedefinir || !instancia.menuInterface) {
        return false;
      }

      function botaoDaAcao(acao) {
        return lista.querySelector('button[data-access-action="' + acao + '"]');
      }

      function aplicarPosicao(elemento, coluna, linha) {
        elemento.classList.add(
          'showme-access-coluna-' + coluna,
          'showme-access-linha-' + linha
        );
      }

      function prepararBotaoCabecalho(botao, texto, rotulo) {
        botao.classList.remove('material-icons');
        botao.removeAttribute('style');
        botao.type = 'button';
        botao.textContent = texto;
        botao.title = rotulo;
        botao.setAttribute('aria-label', rotulo);
      }

      prepararBotaoCabecalho(botaoFechar, '✕', 'Fechar painel de acessibilidade');
      prepararBotaoCabecalho(botaoRedefinir, '↺ Redefinir', 'Redefinir ajustes de acessibilidade');

      var titulo = document.createElement('span');
      titulo.className = 'showme-access-titulo';
      titulo.textContent = 'Opções de Acessibilidade';

      var areaEsquerda = document.createElement('span');
      var areaCentro = document.createElement('span');
      var areaDireita = document.createElement('span');
      areaEsquerda.className = 'showme-access-cabecalho-lado showme-access-cabecalho-esquerda';
      areaCentro.className = 'showme-access-cabecalho-centro';
      areaDireita.className = 'showme-access-cabecalho-lado showme-access-cabecalho-direita';
      areaEsquerda.appendChild(botaoRedefinir);
      areaCentro.appendChild(titulo);
      areaDireita.appendChild(botaoFechar);

      cabecalho.textContent = '';
      cabecalho.classList.add('showme-access-cabecalho');
      cabecalho.appendChild(areaEsquerda);
      cabecalho.appendChild(areaCentro);
      cabecalho.appendChild(areaDireita);

      var controles = [];

      function agruparControle(configuracao) {
        var botaoAumentar = botaoDaAcao(configuracao.aumentar);
        var botaoDiminuir = botaoDaAcao(configuracao.diminuir);

        if (!botaoAumentar || !botaoDiminuir) {
          return;
        }

        var itemPrincipal = botaoAumentar.closest('li');
        var itemSecundario = botaoDiminuir.closest('li');
        var nomeControle = document.createElement('span');
        var grupoBotoes = document.createElement('span');

        nomeControle.className = 'showme-access-controle-nome';
        nomeControle.textContent = configuracao.titulo;
        grupoBotoes.className = 'showme-access-stepper';

        botaoDiminuir.textContent = '−';
        botaoDiminuir.title = 'Diminuir ' + configuracao.titulo.toLowerCase();
        botaoDiminuir.setAttribute('aria-label', botaoDiminuir.title);
        botaoAumentar.textContent = '+';
        botaoAumentar.title = 'Aumentar ' + configuracao.titulo.toLowerCase();
        botaoAumentar.setAttribute('aria-label', botaoAumentar.title);

        botaoDiminuir.classList.add('showme-access-stepper-botao');
        botaoAumentar.classList.add('showme-access-stepper-botao');

        grupoBotoes.appendChild(botaoDiminuir);
        grupoBotoes.appendChild(botaoAumentar);

        itemPrincipal.textContent = '';
        itemPrincipal.classList.add('showme-access-card', 'showme-access-controle');
        itemPrincipal.setAttribute('role', 'group');
        itemPrincipal.setAttribute('aria-label', configuracao.titulo);
        itemPrincipal.appendChild(nomeControle);
        itemPrincipal.appendChild(grupoBotoes);
        aplicarPosicao(itemPrincipal, configuracao.coluna, configuracao.linha);

        if (itemSecundario && itemSecundario !== itemPrincipal) {
          itemSecundario.remove();
        }

        controles.push({
          elemento: itemPrincipal,
          chaveEstado: configuracao.chaveEstado
        });
      }

      agruparControle({
        titulo: 'Tamanho do Texto',
        aumentar: 'increaseText',
        diminuir: 'decreaseText',
        chaveEstado: 'textSize',
        coluna: 1,
        linha: 1
      });
      agruparControle({
        titulo: 'Espaçamento',
        aumentar: 'increaseTextSpacing',
        diminuir: 'decreaseTextSpacing',
        chaveEstado: 'textSpace',
        coluna: 1,
        linha: 2
      });
      agruparControle({
        titulo: 'Altura da Linha',
        aumentar: 'increaseLineHeight',
        diminuir: 'decreaseLineHeight',
        chaveEstado: 'lineHeight',
        coluna: 1,
        linha: 3
      });

      var chaveFonteDislexia = 'showmeFonteDislexia';
      var itemFonteDislexia = document.createElement('li');
      var botaoFonteDislexia = document.createElement('button');

      itemFonteDislexia.className = 'showme-access-card showme-access-recurso';
      itemFonteDislexia.dataset.showmeAcao = 'fonteDislexia';
      aplicarPosicao(itemFonteDislexia, 2, 5);
      botaoFonteDislexia.type = 'button';
      botaoFonteDislexia.textContent = 'Fonte para Dislexia';
      botaoFonteDislexia.setAttribute('aria-pressed', 'false');
      itemFonteDislexia.appendChild(botaoFonteDislexia);
      lista.appendChild(itemFonteDislexia);

      function lerFonteDislexia() {
        try {
          return window.localStorage.getItem(chaveFonteDislexia) === 'true';
        } catch (erro) {
          return document.documentElement.classList.contains('showme-fonte-dislexia');
        }
      }

      function salvarFonteDislexia(ativa) {
        try {
          if (ativa) {
            window.localStorage.setItem(chaveFonteDislexia, 'true');
          } else {
            window.localStorage.removeItem(chaveFonteDislexia);
          }
        } catch (erro) {
          // O recurso continua funcionando durante a pagina, mesmo sem armazenamento.
        }
      }

      function aplicarFonteDislexia(ativa) {
        document.documentElement.classList.toggle('showme-fonte-dislexia', ativa);
        itemFonteDislexia.classList.toggle('showme-access-ativo', ativa);
        botaoFonteDislexia.classList.toggle('active', ativa);
        botaoFonteDislexia.setAttribute('aria-pressed', ativa ? 'true' : 'false');
      }

      botaoFonteDislexia.addEventListener('click', function () {
        var ativa = !document.documentElement.classList.contains('showme-fonte-dislexia');
        aplicarFonteDislexia(ativa);
        salvarFonteDislexia(ativa);
      });

      aplicarFonteDislexia(lerFonteDislexia());

      var recursos = [
        { acao: 'readingGuide', coluna: 1, linha: 4 },
        { acao: 'underlineLinks', coluna: 1, linha: 5 },
        { acao: 'invertColors', coluna: 2, linha: 1 },
        { acao: 'grayHues', coluna: 2, linha: 2 },
        { acao: 'bigCursor', coluna: 2, linha: 3 },
        { acao: 'disableAnimations', coluna: 2, linha: 4 }
      ];

      recursos.forEach(function (recurso) {
        var botao = botaoDaAcao(recurso.acao);
        var item = botao && botao.closest('li');

        if (!botao || !item) {
          return;
        }

        item.classList.add('showme-access-card', 'showme-access-recurso');
        item.dataset.showmeAcao = recurso.acao;
        aplicarPosicao(item, recurso.coluna, recurso.linha);
        botao.setAttribute('aria-pressed', botao.classList.contains('active') ? 'true' : 'false');
      });

      lista.classList.add('showme-access-grid');
      menu.querySelectorAll('*').forEach(function (elemento) {
        elemento.classList.add('_access');
      });

      function atualizarEstados() {
        var estado = instancia.sessionState || {};

        controles.forEach(function (controle) {
          var ativo = Number(estado[controle.chaveEstado] || 0) !== 0;
          controle.elemento.classList.toggle('showme-access-ativo', ativo);
        });

        recursos.forEach(function (recurso) {
          var botao = botaoDaAcao(recurso.acao);
          var item = botao && botao.closest('li');
          var ativo = Boolean(botao && botao.classList.contains('active'));

          if (item) {
            item.classList.toggle('showme-access-ativo', ativo);
          }
          if (botao) {
            botao.setAttribute('aria-pressed', ativo ? 'true' : 'false');
          }
        });
      }

      lista.addEventListener('click', function () {
        window.setTimeout(atualizarEstados, 0);
      });
      lista.addEventListener('keyup', function (evento) {
        if (evento.key === 'Enter' || evento.key === ' ') {
          window.setTimeout(atualizarEstados, 0);
        }
      });
      botaoRedefinir.addEventListener('click', function () {
        aplicarFonteDislexia(false);
        salvarFonteDislexia(false);
        window.setTimeout(atualizarEstados, 0);
      });

      var observadorEstados = new MutationObserver(function (alteracoes) {
        var alterouBotao = alteracoes.some(function (alteracao) {
          return alteracao.target instanceof HTMLButtonElement;
        });
        if (alterouBotao) {
          atualizarEstados();
        }
      });
      observadorEstados.observe(lista, {
        subtree: true,
        attributes: true,
        attributeFilter: ['class']
      });

      menu.dataset.showmePersonalizado = 'true';
      atualizarEstados();
      return true;
    }

    window.showMeAccessibility = new window.Accessibility({
      labels: {
        resetTitle: 'Redefinir',
        closeTitle: 'Fechar',
        menuTitle: 'Opções de Acessibilidade',
        increaseText: 'Aumentar texto',
        decreaseText: 'Diminuir texto',
        increaseTextSpacing: 'Aumentar espaçamento',
        decreaseTextSpacing: 'Diminuir espaçamento',
        increaseLineHeight: 'Aumentar altura da linha',
        decreaseLineHeight: 'Diminuir altura da linha',
        invertColors: 'Inverter cores',
        grayHues: 'Escala de cinza',
        underlineLinks: 'Sublinhar links',
        bigCursor: 'Cursor ampliado',
        readingGuide: 'Guia de leitura',
        textToSpeech: 'Texto para fala',
        speechToText: 'Fala para texto',
        disableAnimations: 'Desativar animações',
        hotkeyPrefix: 'Atalho:'
      },
      modules: {
        increaseText: true,
        decreaseText: true,
        increaseTextSpacing: true,
        decreaseTextSpacing: true,
        increaseLineHeight: true,
        decreaseLineHeight: true,
        invertColors: true,
        grayHues: true,
        underlineLinks: true,
        bigCursor: true,
        readingGuide: true,
        textToSpeech: false,
        speechToText: false,
        disableAnimations: true
      },
      textToSpeechLang: 'pt-BR',
      speechToTextLang: 'pt-BR',
      session: { persistent: true },
      textPixelMode: true,
      textSizeFactor: 10
    });

    var tentativasPersonalizacao = 0;
    var aguardarMenu = window.setInterval(function () {
      tentativasPersonalizacao += 1;
      if (personalizarMenu(window.showMeAccessibility) || tentativasPersonalizacao >= 20) {
        window.clearInterval(aguardarMenu);
      }
    }, 50);
  }, false);
}());
