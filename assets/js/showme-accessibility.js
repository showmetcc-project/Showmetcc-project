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

    var style = document.createElement('style');
    style.textContent = [
      ':root {',
      '  --_access-icon-bottom: 80px;',
      '  --_access-icon-right: 20px;',
      '  --_access-icon-left: unset;',
      '  --_access-menu-background-color: #111111;',
      '  --_access-menu-item-button-background: #1e1e1e;',
      '  --_access-menu-item-color: rgba(255, 255, 255, 0.75);',
      '  --_access-menu-header-color: #39ff14;',
      '  --_access-menu-item-button-active-color: #000000;',
      '  --_access-menu-item-button-active-background-color: #39ff14;',
      '  --_access-menu-div-active-background-color: #39ff14;',
      '  --_access-menu-item-button-hover-color: rgba(255, 255, 255, 0.9);',
      '  --_access-menu-item-button-hover-background-color: #2a2a2a;',
      '  --_access-menu-item-icon-color: rgba(255, 255, 255, 0.6);',
      '  --_access-menu-item-hover-icon-color: rgba(255, 255, 255, 0.9);',
      '  --_access-menu-item-active-icon-color: #000000;',
      '}'
    ].join('\n');
    document.head.appendChild(style);

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
