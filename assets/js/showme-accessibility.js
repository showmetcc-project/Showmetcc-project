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
          root.style.setProperty('--showme-access-left', rect.left + 'px');
          // O centro do VLibras e 50vh; nao use o top intermediario da animacao de resize.
          root.style.setProperty('--showme-access-top', 'calc(50vh + ' + (rect.height / 2 + 4) + 'px)');
          root.style.setProperty('--showme-access-panel-left', (rect.right + 10) + 'px');
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

    window.showMeAccessibility = new window.Accessibility({
      labels: {
        resetTitle: 'Redefinir',
        closeTitle: 'Fechar',
        menuTitle: 'Opcoes de acessibilidade',
        increaseText: 'Aumentar texto',
        decreaseText: 'Diminuir texto',
        increaseTextSpacing: 'Aumentar espacamento',
        decreaseTextSpacing: 'Diminuir espacamento',
        increaseLineHeight: 'Aumentar altura da linha',
        decreaseLineHeight: 'Diminuir altura da linha',
        invertColors: 'Inverter cores',
        grayHues: 'Escala de cinza',
        underlineLinks: 'Sublinhar links',
        bigCursor: 'Cursor ampliado',
        readingGuide: 'Guia de leitura',
        textToSpeech: 'Texto para fala',
        speechToText: 'Fala para texto',
        disableAnimations: 'Desativar animacoes',
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
        textToSpeech: true,
        speechToText: true,
        disableAnimations: true
      },
      textToSpeechLang: 'pt-BR',
      speechToTextLang: 'pt-BR',
      session: { persistent: true },
      textPixelMode: true,
      textSizeFactor: 10
    });
  }, false);
}());
