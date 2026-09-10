(function () {
    'use strict';

    const formulario = document.getElementById('formEvento');
    const inputFoto = document.getElementById('imagemEvento');
    const mensagem = document.getElementById('mensagemEvento');
    const botaoEnviar = document.getElementById('botaoEnviarEvento');
    const tiposFotoAceitos = ['image/jpeg', 'image/png', 'image/webp'];
    const limiteFoto = 10 * 1024 * 1024;

    if (!formulario || !inputFoto || !mensagem || !botaoEnviar) {
        return;
    }

    formulario.dataset.apiConectada = 'true';

    function mostrarMensagem(texto, tipo) {
        mensagem.textContent = texto;
        mensagem.className = `alert alert-${tipo}`;
        mensagem.scrollIntoView({behavior: 'smooth', block: 'center'});
    }

    async function lerRespostaJson(resposta) {
        try {
            return await resposta.json();
        } catch (erro) {
            return {};
        }
    }

    function validarFoto() {
        const arquivos = inputFoto.files;

        if (arquivos.length !== 1) {
            throw new Error('Selecione exatamente uma foto do evento.');
        }

        const foto = arquivos[0];

        if (!tiposFotoAceitos.includes(foto.type)) {
            throw new Error('Envie somente uma foto JPG, PNG ou WebP. Vídeos não são aceitos.');
        }

        if (foto.size > limiteFoto) {
            throw new Error('A foto deve ter no máximo 10 MB.');
        }
    }

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        mensagem.className = 'alert d-none';

        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        try {
            validarFoto();
            botaoEnviar.disabled = true;
            botaoEnviar.textContent = 'Enviando...';

            const resposta = await fetch('api/eventos/', {
                method: 'POST',
                body: new FormData(formulario)
            });
            const dados = await lerRespostaJson(resposta);

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível enviar o evento.');
            }

            formulario.reset();
            mostrarMensagem('Solicitação enviada, aguardando aprovação.', 'success');

            window.setTimeout(function () {
                window.location.href = 'perfilUsuario.php';
            }, 1500);
        } catch (erro) {
            mostrarMensagem(erro.message || 'Não foi possível enviar o evento.', 'danger');
            botaoEnviar.disabled = false;
            botaoEnviar.textContent = 'Enviar para análise';
        }
    });
}());
