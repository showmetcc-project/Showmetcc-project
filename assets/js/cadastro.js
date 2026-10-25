(function () {
    'use strict';

    const formularioCadastro = document.getElementById('formCadastro');
    const mensagemCadastro = document.getElementById('mensagemCadastro');
    const campoSenha = document.getElementById('senha');

    if (!formularioCadastro || !mensagemCadastro || !campoSenha) {
        return;
    }

    const MENSAGEM_SENHA_FRACA = 'Sua senha precisa ter no mínimo 6 caracteres e combinar letras e números.';

    function senhaAtendeAosRequisitos(senha) {
        return senha.length >= 6 && /[A-Za-z]/.test(senha) && /\d/.test(senha);
    }

    function limparErroSenha() {
        campoSenha.removeAttribute('aria-invalid');

        if (mensagemCadastro.dataset.campo === 'senha') {
            mensagemCadastro.textContent = '';
            mensagemCadastro.className = 'alert d-none';
            delete mensagemCadastro.dataset.campo;
        }
    }

    function exibirErroSenha() {
        campoSenha.setAttribute('aria-invalid', 'true');
        campoSenha.focus();

        if (window.ShowMeUI && typeof window.ShowMeUI.toast === 'function') {
            window.ShowMeUI.toast(MENSAGEM_SENHA_FRACA, { variante: 'erro' });
            return;
        }

        mensagemCadastro.dataset.campo = 'senha';
        mensagemCadastro.textContent = MENSAGEM_SENHA_FRACA;
        mensagemCadastro.className = 'alert alert-danger';
    }

    campoSenha.addEventListener('input', limparErroSenha);

    formularioCadastro.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        mensagemCadastro.className = 'alert d-none';
        delete mensagemCadastro.dataset.campo;

        const dadosFormulario = new FormData(formularioCadastro);
        const senha = String(dadosFormulario.get('senha') || '');

        if (!senhaAtendeAosRequisitos(senha)) {
            exibirErroSenha();
            return;
        }

        try {
            const resposta = await fetch('api/apiUsuarios.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nome: dadosFormulario.get('nome'),
                    sobrenome: dadosFormulario.get('sobrenome'),
                    email: dadosFormulario.get('email'),
                    senha: senha,
                }),
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível concluir o cadastro.');
            }

            window.location.href = 'login.php?sucesso=1';
        } catch (erro) {
            mensagemCadastro.textContent = erro.message;
            mensagemCadastro.className = 'alert alert-danger';
        }
    });
})();
