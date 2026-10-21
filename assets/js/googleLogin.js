(function () {
    'use strict';

    let larguraRenderizada = 0;

    function mostrarMensagem(idElemento, texto, tipo) {
        const elemento = document.getElementById(idElemento);

        if (!elemento) {
            return;
        }

        elemento.textContent = texto;
        elemento.className = `alert alert-${tipo}`;
    }

    async function concluirLogin(respostaGoogle, configuracao) {
        mostrarMensagem(configuracao.errorTarget, 'Validando sua conta Google...', 'info');

        try {
            const resposta = await fetch(configuracao.endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({google_token: respostaGoogle.credential})
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível entrar com Google.');
            }

            window.location.href = configuracao.successUrl;
        } catch (erro) {
            mostrarMensagem(configuracao.errorTarget, erro.message, 'danger');
        }
    }

    function renderizarBotao() {
        const recipiente = document.querySelector('[data-google-login]');

        if (!recipiente) {
            return;
        }

        const configuracao = {
            clientId: recipiente.dataset.googleClientId || '',
            endpoint: recipiente.dataset.googleEndpoint || 'api/sessoes/',
            errorTarget: recipiente.dataset.googleErrorTarget || '',
            successUrl: recipiente.dataset.googleSuccessUrl || 'index.php',
            text: recipiente.dataset.googleText || 'continue_with'
        };

        if (!configuracao.clientId) {
            recipiente.innerHTML = '<button type="button" class="social-btn google-btn" disabled title="Configure config/google.php para habilitar"><i class="bi bi-google"></i> Continuar com Google</button>';
            return;
        }

        if (!window.google || !window.google.accounts || !window.google.accounts.id) {
            mostrarMensagem(configuracao.errorTarget, 'Não foi possível carregar o login do Google.', 'danger');
            return;
        }

        window.google.accounts.id.initialize({
            client_id: configuracao.clientId,
            callback: function (respostaGoogle) {
                concluirLogin(respostaGoogle, configuracao);
            }
        });

        const desenharBotao = () => {
            const largura = Math.max(120, Math.min(recipiente.clientWidth || 320, 400));
            if (largura === larguraRenderizada) return;

            larguraRenderizada = largura;
            recipiente.replaceChildren();
            window.google.accounts.id.renderButton(recipiente, {
                type: 'standard',
                theme: 'outline',
                size: 'large',
                text: configuracao.text,
                shape: 'rectangular',
                logo_alignment: 'left',
                width: String(largura)
            });
        };

        desenharBotao();
        if (typeof ResizeObserver === 'function') {
            new ResizeObserver(desenharBotao).observe(recipiente);
        } else {
            window.addEventListener('resize', desenharBotao);
        }
    }

    window.addEventListener('load', renderizarBotao);
}());
