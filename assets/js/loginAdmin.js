(function () {
    'use strict';

    const formularioLoginAdmin = document.getElementById('formLoginAdmin');
    const mensagemLoginAdmin = document.getElementById('mensagemLoginAdmin');

    if (!formularioLoginAdmin || !mensagemLoginAdmin) {
        return;
    }

    formularioLoginAdmin.addEventListener('submit', async (evento) => {
        evento.preventDefault();
        mensagemLoginAdmin.className = 'alert d-none';

        const dadosFormulario = new FormData(formularioLoginAdmin);

        try {
            const resposta = await fetch('api/apiSessoes.php', {
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

            if (dados.usuario.tipo_usuario !== 'admin') {
                await fetch('api/sessoes/', {method: 'DELETE'});
                throw new Error('Esta conta não possui perfil de administrador.');
            }

            window.location.href = 'admin.php';
        } catch (erro) {
            mensagemLoginAdmin.textContent = erro.message;
            mensagemLoginAdmin.className = 'alert alert-danger';
        }
    });
}());
