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

    /*
     * A biblioteca original soma valores fixos em pixels a praticamente todos os
     * elementos da página. Um único clique podia acrescentar 10px a textos, botões,
     * ícones e componentes de tamanho fixo, desmontando o layout. Estes adaptadores
     * mantêm a API e a persistência da biblioteca, mas usam passos proporcionais,
     * limites seguros e somente elementos realmente tipográficos.
     */
    var seletorTipografico = [
      'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'a', 'button', 'label',
      'input', 'textarea', 'select', 'option', 'li', 'dt', 'dd', 'blockquote',
      'figcaption', 'caption', 'th', 'td', 'small', 'strong', 'em'
    ].join(',');
    var limiteAjusteMinimo = -2;
    var limiteAjusteMaximo = 3;

    function limitarNivel(valor) {
      return Math.max(limiteAjusteMinimo, Math.min(limiteAjusteMaximo, valor));
    }

    function elementoPodeSerAjustado(elemento) {
      if (!(elemento instanceof HTMLElement)) {
        return false;
      }

      var campoComTexto = elemento.matches('input, textarea, select, option');
      var possuiTexto = Boolean((elemento.textContent || '').trim());

      return (campoComTexto || possuiTexto)
        && !elemento.closest('._access-menu, [vw], #vlibras-access-wrapper, script, style, svg')
        && !elemento.matches('.bi, [class^="bi-"], [class*=" bi-"]');
    }

    function elementosTipograficos() {
      return Array.prototype.filter.call(
        document.querySelectorAll(seletorTipografico),
        elementoPodeSerAjustado
      );
    }

    function guardarValorOriginal(elemento, propriedade, chaveBase, chaveInline, normalizador) {
      if (elemento.dataset[chaveBase] !== undefined) {
        return;
      }

      var calculado = window.getComputedStyle(elemento)[propriedade];
      elemento.dataset[chaveBase] = String(normalizador(calculado, elemento));
      elemento.dataset[chaveInline] = elemento.style[propriedade] || '';
    }

    function restaurarValorOriginal(elemento, propriedade, chaveBase, chaveInline) {
      if (elemento.dataset[chaveBase] === undefined) {
        return;
      }

      elemento.style[propriedade] = elemento.dataset[chaveInline] || '';
      delete elemento.dataset[chaveBase];
      delete elemento.dataset[chaveInline];
    }

    function aplicarTamanhoTexto(nivel) {
      var elementos = elementosTipograficos();

      if (nivel === 0) {
        document.querySelectorAll('[data-showme-a11y-font-base]').forEach(function (elemento) {
          restaurarValorOriginal(elemento, 'fontSize', 'showmeA11yFontBase', 'showmeA11yFontInline');
        });
        return;
      }

      /* Primeiro captura todos os tamanhos, depois altera. Assim um link dentro de
         um parágrafo não recebe o fator duas vezes por herança. */
      elementos.forEach(function (elemento) {
        guardarValorOriginal(
          elemento,
          'fontSize',
          'showmeA11yFontBase',
          'showmeA11yFontInline',
          function (valor) { return parseFloat(valor) || 16; }
        );
      });

      var fator = 1 + (nivel * 0.075);
      elementos.forEach(function (elemento) {
        var base = parseFloat(elemento.dataset.showmeA11yFontBase) || 16;
        elemento.style.fontSize = Math.max(11, base * fator).toFixed(2) + 'px';
      });
    }

    function aplicarEspacamento(nivel) {
      var elementos = elementosTipograficos();

      if (nivel === 0) {
        document.querySelectorAll('[data-showme-a11y-letter-base]').forEach(function (elemento) {
          restaurarValorOriginal(elemento, 'letterSpacing', 'showmeA11yLetterBase', 'showmeA11yLetterInline');
          restaurarValorOriginal(elemento, 'wordSpacing', 'showmeA11yWordBase', 'showmeA11yWordInline');
        });
        return;
      }

      elementos.forEach(function (elemento) {
        guardarValorOriginal(
          elemento,
          'letterSpacing',
          'showmeA11yLetterBase',
          'showmeA11yLetterInline',
          function (valor) { return valor === 'normal' ? 0 : (parseFloat(valor) || 0); }
        );
        guardarValorOriginal(
          elemento,
          'wordSpacing',
          'showmeA11yWordBase',
          'showmeA11yWordInline',
          function (valor) { return valor === 'normal' ? 0 : (parseFloat(valor) || 0); }
        );
      });

      elementos.forEach(function (elemento) {
        var tamanhoFonte = parseFloat(window.getComputedStyle(elemento).fontSize) || 16;
        var letras = parseFloat(elemento.dataset.showmeA11yLetterBase) || 0;
        var palavras = parseFloat(elemento.dataset.showmeA11yWordBase) || 0;
        elemento.style.letterSpacing = (letras + (tamanhoFonte * 0.025 * nivel)).toFixed(2) + 'px';
        elemento.style.wordSpacing = (palavras + (tamanhoFonte * 0.05 * nivel)).toFixed(2) + 'px';
      });
    }

    function aplicarAlturaLinha(nivel) {
      var elementos = elementosTipograficos();

      if (nivel === 0) {
        document.querySelectorAll('[data-showme-a11y-line-base]').forEach(function (elemento) {
          restaurarValorOriginal(elemento, 'lineHeight', 'showmeA11yLineBase', 'showmeA11yLineInline');
        });
        return;
      }

      elementos.forEach(function (elemento) {
        guardarValorOriginal(
          elemento,
          'lineHeight',
          'showmeA11yLineBase',
          'showmeA11yLineInline',
          function (valor, alvo) {
            var fonte = parseFloat(window.getComputedStyle(alvo).fontSize) || 16;
            return valor === 'normal' ? 1.2 : ((parseFloat(valor) || fonte * 1.2) / fonte);
          }
        );
      });

      elementos.forEach(function (elemento) {
        var base = parseFloat(elemento.dataset.showmeA11yLineBase) || 1.2;
        elemento.style.lineHeight = Math.max(1, base + (nivel * 0.1)).toFixed(2);
      });
    }

    function instalarAjustesTipograficos() {
      var prototipo = window.Accessibility.prototype;

      if (prototipo.showmeAjustesTipograficos) {
        return;
      }

      prototipo.alterTextSize = function (aumentar) {
        this._sessionState.textSize = limitarNivel(
          Number(this._sessionState.textSize || 0) + (aumentar ? 1 : -1)
        );
        aplicarTamanhoTexto(this._sessionState.textSize);
        this.onChange(true);
      };
      prototipo.alterTextSpace = function (aumentar) {
        this._sessionState.textSpace = limitarNivel(
          Number(this._sessionState.textSpace || 0) + (aumentar ? 1 : -1)
        );
        aplicarEspacamento(this._sessionState.textSpace);
        this.onChange(true);
      };
      prototipo.alterLineHeight = function (aumentar) {
        this._sessionState.lineHeight = limitarNivel(
          Number(this._sessionState.lineHeight || 0) + (aumentar ? 1 : -1)
        );
        aplicarAlturaLinha(this._sessionState.lineHeight);
        this.onChange(true);
      };
      prototipo.resetTextSize = function () {
        this._sessionState.textSize = 0;
        aplicarTamanhoTexto(0);
        this.onChange(true);
      };
      prototipo.resetTextSpace = function () {
        this._sessionState.textSpace = 0;
        aplicarEspacamento(0);
        this.onChange(true);
      };
      prototipo.resetLineHeight = function () {
        this._sessionState.lineHeight = 0;
        aplicarAlturaLinha(0);
        this.onChange(true);
      };
      prototipo.showmeAjustesTipograficos = true;
    }

    instalarAjustesTipograficos();

    // O VLibras atual usa Shadow DOM; mantenha suporte ao markup anterior.
    // Desloque somente o contêiner de 40x40; alterar o botão interno o deforma.
    var vlibrasHost = null;
    var vlibrasButton = null;
    var header = document.querySelector('#header');
    var root = document.documentElement;

    function prepararPosicaoVLibras() {
      vlibrasHost = document.getElementById('vlibras-access-wrapper');

      if (vlibrasHost && vlibrasHost.shadowRoot) {
        vlibrasButton = vlibrasHost.shadowRoot.querySelector('#vlibras-button');

        if (vlibrasButton && !vlibrasHost.shadowRoot.getElementById('showme-vlibras-offset')) {
          var estiloPosicao = document.createElement('style');
          estiloPosicao.id = 'showme-vlibras-offset';
          estiloPosicao.textContent = '#vlibras-access { top: calc(64vh - 20px) !important; }';
          vlibrasHost.shadowRoot.appendChild(estiloPosicao);
        }

        return Boolean(vlibrasButton);
      }

      vlibrasButton = document.querySelector('[vw-access-button]');
      return Boolean(vlibrasButton);
    }

    prepararPosicaoVLibras();

    function positionWidgets() {
      if (vlibrasButton) {
        var rect = vlibrasButton.getBoundingClientRect();
        // Ao abrir o VLibras/menu mobile, preserve a ultima ancora visivel.
        if (rect.width && rect.height) {
          var viewportWidth = document.documentElement.clientWidth;
          var rightOffset = Math.max(0, viewportWidth - rect.right);
          root.style.setProperty('--showme-access-right', rightOffset + 'px');
          /* Os dois widgets compartilham uma âncora fixa abaixo do centro da viewport.
             Não use o top transitório do VLibras durante as animações. */
          root.style.setProperty('--showme-access-top', 'calc(64vh + ' + (rect.height - 12) + 'px)');
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

    var tentativasVLibras = 0;
    var aguardarVLibras = window.setInterval(function () {
      tentativasVLibras += 1;
      if (prepararPosicaoVLibras()) {
        positionWidgets();
        if (typeof observer !== 'undefined') observer.observe(vlibrasButton);
        window.clearInterval(aguardarVLibras);
      } else if (tentativasVLibras >= 30) {
        window.clearInterval(aguardarVLibras);
      }
    }, 100);

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
      textPixelMode: false
    });

    function sincronizarFiltrosEmDialogos() {
      var estado = window.showMeAccessibility.sessionState || {};
      var filtro = estado.invertColors
        ? 'invert(1)'
        : (estado.grayHues ? 'grayscale(1)' : '');

      document.querySelectorAll('dialog').forEach(function (dialogo) {
        if (filtro) {
          if (dialogo.dataset.showmeFiltroOriginal === undefined) {
            dialogo.dataset.showmeFiltroOriginal = dialogo.style.filter || '';
          }
          dialogo.style.filter = filtro;
          return;
        }

        if (dialogo.dataset.showmeFiltroOriginal !== undefined) {
          dialogo.style.filter = dialogo.dataset.showmeFiltroOriginal;
          delete dialogo.dataset.showmeFiltroOriginal;
        }
      });
    }

    /* Elementos inseridos via fetch/modal depois do carregamento também recebem os
       ajustes ativos, sem observar mudanças de style e criar um ciclo de mutações. */
    var atualizacaoTipograficaPendente = false;
    var observadorConteudo = new MutationObserver(function (alteracoes) {
      var adicionouConteudo = alteracoes.some(function (alteracao) {
        return alteracao.addedNodes.length > 0;
      });

      if (!adicionouConteudo || atualizacaoTipograficaPendente) {
        return;
      }

      atualizacaoTipograficaPendente = true;
      window.requestAnimationFrame(function () {
        var estado = window.showMeAccessibility.sessionState || {};
        if (estado.textSize) aplicarTamanhoTexto(Number(estado.textSize));
        if (estado.textSpace) aplicarEspacamento(Number(estado.textSpace));
        if (estado.lineHeight) aplicarAlturaLinha(Number(estado.lineHeight));
        sincronizarFiltrosEmDialogos();
        atualizacaoTipograficaPendente = false;
      });
    });
    observadorConteudo.observe(document.body, { childList: true, subtree: true });

    /* <dialog> entra na top layer do navegador e pode escapar do filtro aplicado ao
       elemento <html>. Espelhar o estado nele mantém ODS e demais diálogos coerentes. */
    var observadorFiltro = new MutationObserver(sincronizarFiltrosEmDialogos);
    observadorFiltro.observe(document.documentElement, {
      attributes: true,
      attributeFilter: ['style']
    });
    sincronizarFiltrosEmDialogos();

    var tentativasPersonalizacao = 0;
    var aguardarMenu = window.setInterval(function () {
      tentativasPersonalizacao += 1;
      if (personalizarMenu(window.showMeAccessibility) || tentativasPersonalizacao >= 20) {
        window.clearInterval(aguardarMenu);
      }
    }, 50);
  }, false);
}());
