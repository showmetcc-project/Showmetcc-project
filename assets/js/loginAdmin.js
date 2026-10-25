
(function () {
    'use strict';

    const formularioLoginAdmin = document.getElementById('formLoginAdmin');
    const mensagemLoginAdmin = document.getElementById('mensagemLoginAdmin');

    if (!formularioLoginAdmin || !mensagemLoginAdmin) {
        console.error('Formulário ou área de mensagem do login administrativo não encontrados.');
        return;
    }

    formularioLoginAdmin.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        const botaoEnviar = formularioLoginAdmin.querySelector(
            'button[type="submit"], input[type="submit"]'
        );

        mensagemLoginAdmin.textContent = '';
        mensagemLoginAdmin.className = 'alert d-none';

        if (botaoEnviar) {
            botaoEnviar.disabled = true;
        }

        try {
            const dadosFormulario = new FormData(formularioLoginAdmin);
            const email = String(dadosFormulario.get('email') || '').trim();
            const senha = String(dadosFormulario.get('senha') || '');

            if (!email || !senha) {
                throw new Error('Informe seu e-mail e sua senha.');
            }

            const resposta = await fetch('./api/apiSessoes.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email, senha })
            });

            const texto = await resposta.text();
            let dados;

            try {
                dados = JSON.parse(texto);
            } catch {
                console.error('Resposta não JSON da API de sessão:', texto);
                throw new Error(
                    'O servidor retornou uma resposta inválida. Verifique os erros PHP.'
                );
            }

            if (!resposta.ok) {
                throw new Error(
                    dados.erro || dados.mensagem || 'Não foi possível entrar.'
                );
            }

            if (!dados.usuario || !dados.usuario.tipo_usuario) {
                console.error('Formato inesperado da resposta:', dados);
                throw new Error('A resposta do servidor não contém os dados do usuário.');
            }

            if (dados.usuario.tipo_usuario !== 'admin') {
                throw new Error('Esta conta não possui perfil de administrador.');
            }

            window.location.assign('./admin.php');

        } catch (erro) {
            console.error('Erro no login administrativo:', erro);

            mensagemLoginAdmin.textContent =
                erro.message || 'Ocorreu um erro ao tentar entrar.';

            mensagemLoginAdmin.className = 'alert alert-danger';
        } finally {
            if (botaoEnviar) {
                botaoEnviar.disabled = false;
            }
        }
    });
})();
