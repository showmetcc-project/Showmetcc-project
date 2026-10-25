(function () {
    'use strict';

    if (!window.VLibras || typeof window.VLibras.Widget !== 'function') {
        return;
    }

    new window.VLibras.Widget({
        rootPath: 'https://vlibras.gov.br/app',
        avatar: 'random',
        position: 'R',
    });
})();
