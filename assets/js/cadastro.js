
(function () {
    'use strict';

    const formulario = document.getElementById('formCadastro');
    const mensagem = document.getElementById('mensagemCadastro');

    if (!formulario || !mensagem) {
        return;
    }

    const botao = formulario.querySelector('button[type="submit"]');
    let enviando = false;

    function mostrarMensagem(texto, tipo) {
        mensagem.textContent = texto;
        mensagem.className = `alert alert-${tipo}`;
        mensagem.setAttribute('role', 'alert');
    }

    async function lerResposta(resposta) {
        const texto = await resposta.text();

        try {
            return JSON.parse(texto);
        } catch {
            console.error(
                'Resposta inválida da API de cadastro:',
                resposta.status,
                texto
            );

            throw new Error(
                'O servidor apresentou um erro ao processar o cadastro. Tente novamente.'
            );
        }
    }

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        if (enviando) {
            return;
        }

        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        const dadosFormulario = new FormData(formulario);

        const dadosCadastro = {
            nome: String(dadosFormulario.get('nome') || '').trim(),
            sobrenome: String(dadosFormulario.get('sobrenome') || '').trim(),
            email: String(dadosFormulario.get('email') || '').trim(),
            senha: String(dadosFormulario.get('senha') || '')
        };

        if (
            !dadosCadastro.nome ||
            !dadosCadastro.sobrenome ||
            !dadosCadastro.email ||
            !dadosCadastro.senha
        ) {
            mostrarMensagem('Preencha todos os campos.', 'danger');
            return;
        }

        if (dadosCadastro.senha.length < 6) {
            mostrarMensagem(
                'A senha deve ter pelo menos 6 caracteres.',
                'danger'
            );
            return;
        }

        enviando = true;

        if (botao) {
            botao.disabled = true;
            botao.textContent = 'Criando conta...';
        }

        mensagem.className = 'alert d-none';
        mensagem.textContent = '';

        try {
            const resposta = await fetch('api/apiUsuarios.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
                body: JSON.stringify(dadosCadastro)
            });

            const dados = await lerResposta(resposta);

            if (!resposta.ok) {
                throw new Error(
                    dados.erro || 'Não foi possível concluir o cadastro.'
                );
            }

            mostrarMensagem(
                dados.mensagem ||
                    'Cadastro realizado com sucesso! Redirecionando para o início...',
                'success'
            );

            formulario.reset();

            if (botao) {
                botao.textContent = 'Cadastro concluído!';
            }

            window.setTimeout(function () {
                window.location.href = 'institucional.php';
            }, 2000);

        } catch (erro) {
            mostrarMensagem(
                erro.message ||
                    'Não foi possível concluir o cadastro. Tente novamente.',
                'danger'
            );

            enviando = false;

            if (botao) {
                botao.disabled = false;
                botao.textContent = 'Criar Conta';
            }
        }
    });
})();
