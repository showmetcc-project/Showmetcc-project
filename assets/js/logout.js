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

        const deveSair = await window.ShowMeUI.confirmar({
            titulo: 'Sair da conta',
            texto: 'Tem certeza que deseja sair?',
            confirmarTexto: 'Sair',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });

        if (!deveSair) {
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
                window.location.replace('institucional.php');
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
            window.ShowMeUI.toast(erro.message || 'Não foi possível encerrar a sessão.', {
                variante: 'erro'
            });
            this.disabled = false;
            this.removeAttribute('aria-busy');
        }
    });
}());
