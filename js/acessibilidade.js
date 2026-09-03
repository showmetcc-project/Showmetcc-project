/**
 * ShowMe — Configuração de Acessibilidade
 *   1. VLibras (gov.br)
 *   2. accessibility (npm) 
 *
 * Como usar em cada página — adicione ao final do <body>, antes do </body>:
 *
 *   <!-- VLibras (sem npm, carrega do servidor do governo) -->
 *   <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
 *
 *   <!-- Biblioteca accessibility (npm) -->
 *   <script src="node_modules/accessibility/dist/main.bundle.js"></script>
 *   <!-- OU via CDN, sem npm: -->
 *   <!-- <script src="https://cdn.jsdelivr.net/npm/accessibility@6.1.0/dist/main.bundle.js"></script> -->
 *
 *   <!-- Este arquivo — sempre por último -->
 *   <script src="assets/js/showme-accessibility.js"></script>
 */
/* 1. VLIBRAS */

if (typeof window.VLibras !== 'undefined') {
    new window.VLibras.Widget({
        rootPath: 'https://vlibras.gov.br/app',
        avatar:   'random',   // icaro | hosana | guga | random
        position: 'R',        // R = direita
    });
} else {
    console.warn('ShowMe Acessibilidade: VLibras não carregado. ' +
        'Verifique se o script https://vlibras.gov.br/app/vlibras-plugin.js está incluído antes deste arquivo.');
}

/*  2. ACCESSIBILITY TOOLBAR
   Toolbar visual com aumento de texto, inverter cores, guia de leitura, text-to-speech, etc.
   npm: https://www.npmjs.com/package/accessibility
 */

window.addEventListener('load', function () {

    if (typeof Accessibility === 'undefined') {
        console.warn('ShowMe Acessibilidade: biblioteca "accessibility" não carregada. ' +
            'Verifique se o script main.bundle.js está incluído antes deste arquivo.');
        return;
    }

    // ── Textos em português 
    var labels = {
        resetTitle:           'Redefinir',
        closeTitle:           'Fechar',
        menuTitle:            'Opções de Acessibilidade',
        increaseText:         'Aumentar texto',
        decreaseText:         'Diminuir texto',
        increaseTextSpacing:  'Aumentar espaçamento',
        decreaseTextSpacing:  'Diminuir espaçamento',
        increaseLineHeight:   'Aumentar altura da linha',
        decreaseLineHeight:   'Diminuir altura da linha',
        invertColors:         'Inverter cores',
        grayHues:             'Escala de cinza',
        underlineLinks:       'Sublinhar links',
        bigCursor:            'Cursor ampliado',
        readingGuide:         'Guia de leitura',
        textToSpeech:         'Texto para fala',
        speechToText:         'Fala para texto',
        disableAnimations:    'Desativar animações',
        hotkeyPrefix:         'Atalho:',
    };

    // ── Módulos ativos 
    var modules = {
        increaseText:         true,
        decreaseText:         true,
        increaseTextSpacing:  true,
        decreaseTextSpacing:  true,
        increaseLineHeight:   true,
        decreaseLineHeight:   true,
        invertColors:         true,
        grayHues:             true,
        underlineLinks:       true,
        bigCursor:            true,
        readingGuide:         true,
        textToSpeech:         true,
        speechToText:         true,
        disableAnimations:    true,
    };

    // ── Opções gerais 
    var options = {
        labels:  labels,
        modules: modules,

        // Idioma pt-BR para text-to-speech e speech-to-text
        textToSpeechLang: 'pt-BR',
        speechToTextLang: 'pt-BR',

        // Mantém preferências após recarregar a página
        session: { persistent: true },

        // Usa px (projeto usa px fixo em vários lugares)
        textPixelMode: true,

        // Incremento por clique
        textSizeFactor: 10,
    };

    // ── Tema escuro — identidade visual do ShowMe
    var style = document.createElement('style');
    style.textContent = [
        ':root {',
        '    /* Posição: canto inferior direito, acima do VLibras (~80px) */',
        '    --_access-icon-bottom: 80px;',
        '    --_access-icon-right:  20px;',
        '    --_access-icon-left:   unset;',
        '',
        '    /* Menu — dark mode */',
        '    --_access-menu-background-color:                       #111111;',
        '    --_access-menu-item-button-background:                 #1e1e1e;',
        '    --_access-menu-item-color:                             rgba(255,255,255,0.75);',
        '    --_access-menu-header-color:                           #39ff14;',
        '',
        '    /* Item ativo: verde neon do ShowMe */',
        '    --_access-menu-item-button-active-color:               #000000;',
        '    --_access-menu-item-button-active-background-color:    #39ff14;',
        '    --_access-menu-div-active-background-color:            #39ff14;',
        '',
        '    /* Hover */',
        '    --_access-menu-item-button-hover-color:                rgba(255,255,255,0.9);',
        '    --_access-menu-item-button-hover-background-color:     #2a2a2a;',
        '',
        '    /* Ícones */',
        '    --_access-menu-item-icon-color:         rgba(255,255,255,0.6);',
        '    --_access-menu-item-hover-icon-color:   rgba(255,255,255,0.9);',
        '    --_access-menu-item-active-icon-color:  #000000;',
        '}',
    ].join('\n');
    document.head.appendChild(style);

    // ── Inicializa 
    new Accessibility(options);

}, false);