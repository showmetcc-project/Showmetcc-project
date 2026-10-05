(function () {
    'use strict';

    const formulario = document.getElementById('formEvento');
    const inputFoto = document.getElementById('imagemEvento');
    const botaoEnviar = document.getElementById('botaoEnviarEvento');
    const tiposFotoAceitos = ['image/jpeg', 'image/png', 'image/webp'];
    const limiteFoto = 10 * 1024 * 1024;

    if (!formulario || !inputFoto || !botaoEnviar) {
        return;
    }

    formulario.dataset.apiConectada = 'true';

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
            window.ShowMeUI.toast(
                'Evento enviado para moderação! Você será notificado quando for aprovado.',
                {variante: 'sucesso', duracao: 5000}
            );

            window.setTimeout(function () {
                window.location.href = 'perfilUsuario.php';
            }, 5200);
        } catch (erro) {
            window.ShowMeUI.toast(erro.message || 'Não foi possível enviar o evento.', {
                variante: 'erro'
            });
            botaoEnviar.disabled = false;
            botaoEnviar.textContent = 'Enviar para análise';
        }
    });
}());
