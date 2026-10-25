(function () {
    'use strict';

    document.querySelectorAll('.login-alternar-senha').forEach((botao) => {
        const campo = document.getElementById(botao.getAttribute('aria-controls'));
        const icone = botao.querySelector('i');

        if (!campo || !icone) return;

        botao.addEventListener('click', () => {
            const mostrar = campo.type === 'password';
            campo.type = mostrar ? 'text' : 'password';
            botao.setAttribute('aria-pressed', String(mostrar));
            botao.setAttribute('aria-label', mostrar ? 'Ocultar senha' : 'Mostrar senha');
            botao.title = mostrar ? 'Ocultar senha' : 'Mostrar senha';
            icone.classList.toggle('bi-eye', !mostrar);
            icone.classList.toggle('bi-eye-slash', mostrar);
        });
    });
})();
