(function () {
  'use strict';

  const botoesOds = document.querySelectorAll('[data-ods-modal]');
  const modaisOds = document.querySelectorAll('.ods-modal');

  function fecharModal(modal) {
    if (modal && modal.open) {
      modal.close();
    }
  }

  botoesOds.forEach((botao) => {
    botao.addEventListener('click', () => {
      const modal = document.getElementById(botao.dataset.odsModal || '');

      if (modal instanceof HTMLDialogElement) {
        modal.showModal();
        document.body.classList.add('ods-modal-aberto');
      }
    });
  });

  modaisOds.forEach((modal) => {
    modal.querySelectorAll('[data-fechar-ods]').forEach((botao) => {
      botao.addEventListener('click', () => fecharModal(modal));
    });

    modal.addEventListener('click', (evento) => {
      const limites = modal.getBoundingClientRect();
      const clicouFora = evento.clientX < limites.left
        || evento.clientX > limites.right
        || evento.clientY < limites.top
        || evento.clientY > limites.bottom;

      if (clicouFora) {
        fecharModal(modal);
      }
    });

    modal.addEventListener('close', () => {
      if (!document.querySelector('.ods-modal[open]')) {
        document.body.classList.remove('ods-modal-aberto');
      }
    });
  });
}());
