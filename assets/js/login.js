(function () {
    'use strict';

    const formularioLogin = document.getElementById('formLogin');
    const mensagemLogin = document.getElementById('mensagemLogin');

    if (!formularioLogin || !mensagemLogin) {
        return;
    }

    formularioLogin.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        mensagemLogin.className = 'alert d-none';

        const dadosFormulario = new FormData(formularioLogin);

        try {
            const resposta = await fetch('api/sessoes/', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    email: dadosFormulario.get('email'),
                    senha: dadosFormulario.get('senha')
                })
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível entrar.');
            }

            window.location.href = 'index.php';
        } catch (erro) {
            mensagemLogin.textContent = erro.message;
            mensagemLogin.className = 'alert alert-danger';
        }
    });
}());
