(function () {
    'use strict';

    const formularioCadastro = document.getElementById('formCadastro');
    const mensagemCadastro = document.getElementById('mensagemCadastro');

    if (!formularioCadastro || !mensagemCadastro) {
        return;
    }

    formularioCadastro.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        mensagemCadastro.className = 'alert d-none';

        const dadosFormulario = new FormData(formularioCadastro);

        try {
            const resposta = await fetch('api/apiUsuarios.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    nome: dadosFormulario.get('nome'),
                    sobrenome: dadosFormulario.get('sobrenome'),
                    email: dadosFormulario.get('email'),
                    senha: dadosFormulario.get('senha')
                })
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
}());
