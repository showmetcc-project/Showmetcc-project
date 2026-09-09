(function () {
  'use strict';

  const form = document.getElementById('perfilForm');
  const botaoEditar = document.getElementById('btnEditarPerfil');
  const feedback = document.getElementById('perfilFeedback');

  if (!form || !botaoEditar || !feedback) {
    return;
  }

  const campos = Array.from(form.querySelectorAll('input'));
  const emailPrincipal = document.getElementById('perfilEmailPrincipal');
  const endpoint = form.dataset.endpoint;
  let editando = false;

  function mostrarFeedback(mensagem, tipo) {
    feedback.textContent = mensagem;
    feedback.className = `perfil-feedback ${tipo}`;
    feedback.hidden = false;
  }

  function alternarEdicao(ativa) {
    editando = ativa;
    campos.forEach((campo) => {
      campo.readOnly = !ativa;
    });

    botaoEditar.innerHTML = ativa
      ? '<i class="bi bi-check-lg"></i> Salvar alterações'
      : '<i class="bi bi-pencil"></i> Editar Perfil';

    if (ativa) {
      feedback.hidden = true;
      campos[0]?.focus();
    }
  }

  async function carregarPerfil() {
    try {
      const resposta = await fetch(endpoint);
      const dados = await resposta.json();

      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível carregar o perfil.');
      }

      const usuario = dados.usuario;
      emailPrincipal.textContent = usuario.email_user;
      document.getElementById('perfilNome').value = usuario.nome_user;
      document.getElementById('perfilSobrenome').value = usuario.sobrenome || '';
      document.getElementById('perfilEmail').value = usuario.email_user;
    } catch (erro) {
      emailPrincipal.textContent = erro.message;
      mostrarFeedback(erro.message, 'erro');
    }
  }

  botaoEditar.addEventListener('click', () => {
    if (!editando) {
      alternarEdicao(true);
      return;
    }

    form.requestSubmit();
  });

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    botaoEditar.disabled = true;
    feedback.hidden = true;

    try {
      const resposta = await fetch(endpoint, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          nome: document.getElementById('perfilNome').value.trim(),
          sobrenome: document.getElementById('perfilSobrenome').value.trim(),
          email: document.getElementById('perfilEmail').value.trim()
        })
      });
      const dados = await resposta.json();

      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível atualizar o perfil.');
      }

      emailPrincipal.textContent = dados.usuario.email_user;
      alternarEdicao(false);
      mostrarFeedback(dados.mensagem || 'Perfil atualizado com sucesso.', 'sucesso');
    } catch (erro) {
      mostrarFeedback(erro.message, 'erro');
    } finally {
      botaoEditar.disabled = false;
    }
  });

  carregarPerfil();
}());
