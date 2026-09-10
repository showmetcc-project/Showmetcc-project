(function () {
    'use strict';

    const botaoLogout = document.getElementById('btnLogout');

    if (!botaoLogout) {
        return;
    }

    botaoLogout.addEventListener('click', async function () {
        if (this.disabled) {
            return;
        }

        this.disabled = true;
        this.setAttribute('aria-busy', 'true');

        try {
            const resposta = await fetch('api/sessoes/', {
                method: 'DELETE',
                credentials: 'same-origin',
                headers: {Accept: 'application/json'}
            });

            if (resposta.ok || resposta.status === 401) {
                window.location.replace('index.php');
                return;
            }

            let dados = {};

            try {
                dados = await resposta.json();
            } catch (erro) {
                // Mantém a mensagem padrão se a API não devolver JSON.
            }

            throw new Error(dados.erro || 'Não foi possível encerrar a sessão.');
        } catch (erro) {
            window.alert(erro.message || 'Não foi possível encerrar a sessão.');
            this.disabled = false;
            this.removeAttribute('aria-busy');
        }
    });
}());
