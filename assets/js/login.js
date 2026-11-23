
(() => {
    'use strict';

    function iniciarLogin() {
        const formulario = document.getElementById('formLogin');
        const mensagem = document.getElementById('mensagemLogin');

        if (!formulario || !mensagem || formulario.dataset.loginInicializado) {
            return;
        }

        formulario.dataset.loginInicializado = 'true';

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const botao = formulario.querySelector('[type="submit"]');
            const textoOriginal = botao?.textContent || 'Entrar';

            const dados = new FormData(formulario);
            const email = String(dados.get('email') || '').trim();
            const senha = String(dados.get('senha') || '');

            mensagem.className = 'alert d-none';
            mensagem.textContent = '';

            if (!email || !senha) {
                mostrarMensagem('Informe seu e-mail e sua senha.', 'danger');
                return;
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                mostrarMensagem('Informe um e-mail válido.', 'danger');
                return;
            }

            if (botao) {
                botao.disabled = true;
                botao.textContent = 'Entrando...';
            }

            try {
                // Ajuste este caminho caso o nome real do arquivo seja diferente.
                const resposta = await fetch('./api/apiSessoes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: JSON.stringify({ email, senha })
                });

                const texto = await resposta.text();
                let resultado;

                try {
                    resultado = JSON.parse(texto);
                } catch {
                    console.error('Resposta não JSON da API:', {
                        status: resposta.status,
                        url: resposta.url,
                        resposta: texto
                    });

                    throw new Error(
                        'O servidor retornou uma resposta inválida. Verifique o caminho da API e os erros do PHP.'
                    );
                }

                if (!resposta.ok || resultado.sucesso === false) {
                    throw new Error(
                        resultado.erro ||
                        resultado.mensagem ||
                        'E-mail ou senha incorretos.'
                    );
                }

                if (!resultado.usuario?.id_user) {
                    throw new Error(
                        'A API não confirmou o login. Confira o retorno do PHP.'
                    );
                }

                mostrarMensagem('Login realizado! Redirecionando...', 'success');

                window.location.assign('./index.php');

            } catch (erro) {
                console.error('Erro no login ShowMe:', erro);

                mostrarMensagem(
                    erro instanceof Error
                        ? erro.message
                        : 'Não foi possível entrar. Tente novamente.',
                    'danger'
                );
            } finally {
                if (botao) {
                    botao.disabled = false;
                    botao.textContent = textoOriginal;
                }
            }
        });

        function mostrarMensagem(texto, tipo) {
            mensagem.textContent = texto;
            mensagem.className = `alert alert-${tipo}`;
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarLogin, {
            once: true
        });
    } else {
        iniciarLogin();
    }
})();
