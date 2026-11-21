(function () {
    'use strict';

    function iniciarFormularioContato() {
        const formulario = document.querySelector('.footer-contact-form');

        if (!formulario) {
            return;
        }

        const botao = formulario.querySelector('button[type="submit"]');
        const textoBotao = botao?.querySelector('span');

        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            if (!formulario.reportValidity()) {
                return;
            }

            botao.disabled = true;

            if (textoBotao) {
                textoBotao.textContent = 'Enviando...';
            }

            const controlador = new AbortController();
            const temporizador = window.setTimeout(() => controlador.abort(), 15000);

            try {
                const resposta = await fetch(formulario.action, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body: new FormData(formulario),
                    signal: controlador.signal,
                });

                let dados;

                try {
                    dados = await resposta.json();
                } catch (erro) {
                    throw new Error('O servidor retornou uma resposta inválida.');
                }

                if (!resposta.ok || !dados.sucesso) {
                    throw new Error(dados.erro || 'Não foi possível enviar a mensagem.');
                }

                formulario.reset();
                window.ShowMeUI.toast('Mensagem enviada com sucesso!', { variante: 'sucesso' });
            } catch (erro) {
                const mensagem =
                    erro.name === 'AbortError'
                        ? 'O envio demorou demais. Tente novamente em instantes.'
                        : erro.message || 'Não foi possível enviar a mensagem.';

                window.ShowMeUI.toast(mensagem, { variante: 'erro' });
            } finally {
                window.clearTimeout(temporizador);
                botao.disabled = false;

                if (textoBotao) {
                    textoBotao.textContent = 'Enviar';
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarFormularioContato);
    } else {
        iniciarFormularioContato();
    }
})();
