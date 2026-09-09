(function () {
  'use strict';

  function iniciarFormularioContato() {
    const formulario = document.querySelector('.footer-contact-form');

    if (!formulario) {
      return;
    }

    const feedback = formulario.querySelector('.footer-contact-feedback');
    const botao = formulario.querySelector('button[type="submit"]');
    const textoBotao = botao?.querySelector('span');

    formulario.addEventListener('submit', async function (evento) {
      evento.preventDefault();

      if (!formulario.reportValidity()) {
        return;
      }

      feedback.hidden = true;
      feedback.className = 'footer-contact-feedback';
      botao.disabled = true;

      if (textoBotao) {
        textoBotao.textContent = 'Enviando...';
      }

      try {
        const resposta = await fetch(formulario.action, {
          method: 'POST',
          headers: { Accept: 'application/json' },
          body: new FormData(formulario)
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

        feedback.textContent = 'Mensagem enviada com sucesso!';
        feedback.classList.add('sucesso');
        feedback.hidden = false;
        formulario.reset();
      } catch (erro) {
        feedback.textContent = erro.message;
        feedback.classList.add('erro');
        feedback.hidden = false;
      } finally {
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
