(function () {
  'use strict';

  const formVisualizacao = document.getElementById('perfilForm');
  const botaoEditarPerfil = document.getElementById('btnEditarPerfil');
  const feedbackPagina = document.getElementById('perfilFeedback');
  const endpoint = formVisualizacao?.dataset.endpoint;

  if (!formVisualizacao || !botaoEditarPerfil || !endpoint) {
    return;
  }

  const camposPerfil = [
    document.getElementById('perfilNome'),
    document.getElementById('perfilSobrenome'),
    document.getElementById('perfilEmail')
  ];
  const elementosModalMidia = {
    foto_perfil: document.getElementById('modalPersonalizarPerfil'),
    foto_banner: document.getElementById('modalPersonalizarBanner')
  };
  const modaisMidia = Object.fromEntries(
    Object.entries(elementosModalMidia).map(([tipo, elemento]) => [
      tipo,
      elemento ? window.bootstrap?.Modal.getOrCreateInstance(elemento) : null
    ])
  );
  const configuracaoMidia = {
    foto_perfil: {largura: 500, altura: 500, proporcao: 1},
    foto_banner: {largura: 1600, altura: 400, proporcao: 4}
  };

  const emailPrincipal = document.getElementById('perfilEmailPrincipal');
  const avatarImagem = document.getElementById('perfilAvatarImagem');
  const avatarPlaceholder = document.getElementById('perfilAvatarPlaceholder');
  const banner = document.getElementById('perfilBanner');
  const recorteOverlay = document.getElementById('recorteOverlay');
  const recorteImagem = document.getElementById('recorteImagem');
  const confirmarRecorte = document.getElementById('confirmarRecorte');
  const cancelarRecorte = document.getElementById('cancelarRecorte');
  const cancelarRecorteTopo = document.getElementById('cancelarRecorteTopo');
  const lightbox = document.getElementById('lightboxMidia');
  const lightboxImagem = document.getElementById('lightboxMidiaImagem');
  const lightboxFallback = document.getElementById('lightboxMidiaFallback');
  const fecharLightbox = document.getElementById('fecharLightboxMidia');
  const googleAgendaStatus = document.getElementById('googleAgendaStatus');
  const botaoConectarGoogleAgenda = document.getElementById('btnConectarGoogleAgenda');
  const botaoDesconectarGoogleAgenda = document.getElementById('btnDesconectarGoogleAgenda');

  let usuarioAtual = null;
  let cropper = null;
  let alvoRecorte = null;
  let urlArquivoRecorte = null;
  let editandoPerfil = false;

  function mostrarFeedback(elemento, mensagem, tipo) {
    if (!elemento) {
      return;
    }
    elemento.textContent = mensagem;
    elemento.className = `perfil-feedback ${tipo}`;
    elemento.hidden = false;
  }

  function esconderFeedback(elemento) {
    if (!elemento) {
      return;
    }
    elemento.hidden = true;
    elemento.textContent = '';
  }

  async function lerJson(resposta) {
    const texto = await resposta.text();
    if (texto.trim() === '') {
      return {};
    }

    try {
      return JSON.parse(texto);
    } catch (_) {
      throw new Error('O servidor retornou uma resposta inválida.');
    }
  }

  function tratarSessaoExpirada(resposta) {
    if (resposta.status !== 401) {
      return false;
    }
    window.location.href = 'login.php';
    return true;
  }

  function aplicarAvatar(caminho) {
    if (caminho) {
      avatarImagem.src = caminho;
      avatarImagem.hidden = false;
      avatarPlaceholder.hidden = true;
      return;
    }

    avatarImagem.removeAttribute('src');
    avatarImagem.hidden = true;
    avatarPlaceholder.hidden = false;
  }

  function aplicarBanner(caminho) {
    banner.classList.toggle('banner-personalizado', Boolean(caminho));
    banner.style.backgroundImage = caminho ? `url("${caminho}")` : '';
  }

  function atualizarGaleriaSelecionada() {
    document.querySelectorAll('[data-galeria-midia]').forEach((opcao) => {
      const tipo = opcao.dataset.galeriaMidia;
      const selecionada = Boolean(usuarioAtual?.[tipo]) && usuarioAtual[tipo] === opcao.dataset.caminho;
      opcao.classList.toggle('selecionada', selecionada);
      opcao.setAttribute('aria-pressed', String(selecionada));
    });
  }

  function renderizarPerfil(usuario) {
    usuarioAtual = usuario;
    emailPrincipal.textContent = usuario.email_user;
    document.getElementById('perfilNome').value = usuario.nome_user;
    document.getElementById('perfilSobrenome').value = usuario.sobrenome || '';
    document.getElementById('perfilEmail').value = usuario.email_user;
    aplicarAvatar(usuario.foto_perfil || null);
    aplicarBanner(usuario.foto_banner || null);
    atualizarGaleriaSelecionada();
  }

  function renderizarEstatisticas(estatisticas) {
    const valores = estatisticas && typeof estatisticas === 'object' ? estatisticas : {};

    document.querySelectorAll('[data-estatistica]').forEach((elemento) => {
      const valor = Number(valores[elemento.dataset.estatistica]);
      elemento.textContent = Number.isInteger(valor) && valor >= 0 ? String(valor) : '0';
    });
  }

  function definirModoEdicao(ativo) {
    editandoPerfil = ativo;
    formVisualizacao.classList.toggle('modo-edicao', ativo);
    botaoEditarPerfil.classList.toggle('salvar-alteracoes', ativo);
    botaoEditarPerfil.setAttribute('aria-pressed', String(ativo));

    camposPerfil.forEach((campo) => {
      campo.readOnly = !ativo;
    });

    const icone = botaoEditarPerfil.querySelector('i');
    const texto = botaoEditarPerfil.querySelector('span');
    if (icone) {
      icone.className = ativo ? 'bi bi-check-lg' : 'bi bi-pencil';
    }
    if (texto) {
      texto.textContent = ativo ? 'Salvar alterações' : 'Editar Perfil';
    }

    if (ativo) {
      camposPerfil[0]?.focus();
      camposPerfil[0]?.select();
    }
  }

  async function salvarDadosPerfil() {
    if (!formVisualizacao.checkValidity()) {
      formVisualizacao.reportValidity();
      return;
    }

    esconderFeedback(feedbackPagina);
    botaoEditarPerfil.disabled = true;

    try {
      const resposta = await fetch(endpoint, {
        method: 'PUT',
        headers: {'Content-Type': 'application/json', Accept: 'application/json'},
        body: JSON.stringify({
          nome: camposPerfil[0].value.trim(),
          sobrenome: camposPerfil[1].value.trim(),
          email: camposPerfil[2].value.trim()
        })
      });
      const dados = await lerJson(resposta);

      if (tratarSessaoExpirada(resposta)) {
        return;
      }
      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível atualizar os dados do perfil.');
      }

      renderizarPerfil(dados.usuario);
      definirModoEdicao(false);
      esconderFeedback(feedbackPagina);
      window.ShowMeUI.toast('Perfil atualizado com sucesso.', {variante: 'sucesso'});
    } catch (erro) {
      window.ShowMeUI.toast(erro.message || 'Não foi possível atualizar o perfil.', {
        variante: 'erro'
      });
    } finally {
      botaoEditarPerfil.disabled = false;
    }
  }

  function abrirEditorMidia(tipo) {
    const input = document.querySelector(`[data-input-upload-midia="${tipo}"]`);
    if (input) {
      input.value = '';
    }
    atualizarGaleriaSelecionada();
    modaisMidia[tipo]?.show();
  }

  function definirMidiaProcessando(tipo, processando) {
    const modal = elementosModalMidia[tipo];
    if (!modal) {
      return;
    }
    modal.classList.toggle('salvando-midia', processando);
    modal.querySelectorAll('button, input').forEach((controle) => {
      if (!controle.matches('[data-bs-dismiss]')) {
        controle.disabled = processando;
      }
    });
  }

  async function salvarMidia(tipo, {blob = null, caminho = null} = {}) {
    const formulario = new FormData();
    formulario.append('acao', 'personalizar_midia');

    if (blob) {
      const extensao = blob.type === 'image/jpeg' ? 'jpg' : 'webp';
      formulario.append(tipo, blob, `${tipo}.${extensao}`);
    } else if (caminho) {
      formulario.append(`${tipo}_pronta`, caminho);
    } else {
      return;
    }

    definirMidiaProcessando(tipo, true);

    try {
      const resposta = await fetch(endpoint, {
        method: 'POST',
        headers: {Accept: 'application/json'},
        body: formulario
      });
      const dados = await lerJson(resposta);

      if (tratarSessaoExpirada(resposta)) {
        return;
      }
      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível salvar a imagem.');
      }

      renderizarPerfil(dados.usuario);
      modaisMidia[tipo]?.hide();
      window.ShowMeUI.toast(
        tipo === 'foto_perfil' ? 'Foto de perfil atualizada.' : 'Banner atualizado.',
        {variante: 'sucesso'}
      );
    } catch (erro) {
      window.ShowMeUI.toast(erro.message || 'Não foi possível salvar a imagem.', {
        variante: 'erro'
      });
    } finally {
      definirMidiaProcessando(tipo, false);
      const input = document.querySelector(`[data-input-upload-midia="${tipo}"]`);
      if (input) {
        input.value = '';
      }
    }
  }

  function fecharRecorte() {
    cropper?.destroy();
    cropper = null;
    alvoRecorte = null;
    recorteOverlay.hidden = true;
    recorteImagem.removeAttribute('src');
    document.body.classList.remove('recorte-aberto');
    if (urlArquivoRecorte) {
      URL.revokeObjectURL(urlArquivoRecorte);
      urlArquivoRecorte = null;
    }
  }

  function abrirRecorte(tipo, arquivo) {
    const tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

    if (!tiposPermitidos.includes(arquivo.type)) {
      window.ShowMeUI.toast('Use uma imagem JPG, PNG ou WebP.', {variante: 'erro'});
      return;
    }
    if (arquivo.size > 10 * 1024 * 1024) {
      window.ShowMeUI.toast('A imagem não pode ultrapassar 10 MB.', {variante: 'erro'});
      return;
    }
    if (typeof window.Cropper !== 'function') {
      window.ShowMeUI.toast('O editor de recorte não pôde ser carregado.', {variante: 'erro'});
      return;
    }

    fecharRecorte();
    alvoRecorte = tipo;
    urlArquivoRecorte = URL.createObjectURL(arquivo);
    recorteOverlay.hidden = false;
    document.body.classList.add('recorte-aberto');

    recorteImagem.onload = () => {
      cropper = new window.Cropper(recorteImagem, {
        aspectRatio: configuracaoMidia[tipo].proporcao,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        background: false
      });
    };
    recorteImagem.onerror = () => {
      fecharRecorte();
      window.ShowMeUI.toast('Não foi possível abrir a imagem selecionada.', {variante: 'erro'});
    };
    recorteImagem.src = urlArquivoRecorte;
  }

  function canvasParaBlob(canvas) {
    return new Promise((resolve, reject) => {
      canvas.toBlob((blob) => {
        if (blob) {
          resolve(blob);
        } else {
          reject(new Error('Não foi possível gerar a imagem recortada.'));
        }
      }, 'image/webp', 0.9);
    });
  }

  async function confirmarEditorRecorte() {
    if (!cropper || !alvoRecorte) {
      return;
    }

    confirmarRecorte.disabled = true;
    const tipo = alvoRecorte;

    try {
      const configuracao = configuracaoMidia[tipo];
      const canvas = cropper.getCroppedCanvas({
        width: configuracao.largura,
        height: configuracao.altura,
        fillColor: '#0a0a0a',
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
      });
      const blob = await canvasParaBlob(canvas);
      fecharRecorte();
      await salvarMidia(tipo, {blob});
    } catch (erro) {
      fecharRecorte();
      window.ShowMeUI.toast(erro.message || 'Não foi possível gerar a imagem recortada.', {
        variante: 'erro'
      });
    } finally {
      confirmarRecorte.disabled = false;
    }
  }

  function abrirLightbox(tipo) {
    const caminho = usuarioAtual?.[tipo] || null;
    lightbox.dataset.tipo = tipo;
    lightboxImagem.hidden = !caminho;
    lightboxFallback.hidden = Boolean(caminho);
    lightboxFallback.className = `lightbox-fallback ${tipo === 'foto_banner' ? 'fallback-banner' : 'fallback-avatar'}`;

    if (caminho) {
      lightboxImagem.src = caminho;
      lightboxImagem.alt = tipo === 'foto_perfil' ? 'Foto de perfil atual' : 'Banner atual do perfil';
    } else {
      lightboxImagem.removeAttribute('src');
    }

    lightbox.hidden = false;
    document.body.classList.add('lightbox-aberto');
  }

  function fecharVisualizacao() {
    lightbox.hidden = true;
    lightboxImagem.removeAttribute('src');
    document.body.classList.remove('lightbox-aberto');
  }

  async function carregarPerfil() {
    try {
      const resposta = await fetch(endpoint, {headers: {Accept: 'application/json'}});
      const dados = await lerJson(resposta);

      if (tratarSessaoExpirada(resposta)) {
        return;
      }
      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível carregar o perfil.');
      }
      renderizarPerfil(dados.usuario);
      renderizarEstatisticas(dados.estatisticas);
    } catch (erro) {
      emailPrincipal.textContent = erro.message;
      mostrarFeedback(feedbackPagina, erro.message, 'erro');
    }
  }

  function renderizarStatusGoogleAgenda(dados) {
    const conectado = Boolean(dados.conectado);
    googleAgendaStatus.textContent = conectado
      ? 'Google Agenda conectado.'
      : 'Conecte sua agenda para verificar conflitos e exportar planejamentos.';
    botaoConectarGoogleAgenda.hidden = conectado;
    botaoDesconectarGoogleAgenda.hidden = !conectado;

    if (!dados.configurado && !conectado) {
      googleAgendaStatus.textContent = 'Google Agenda ainda não foi configurado neste ambiente.';
      botaoConectarGoogleAgenda.hidden = true;
    }
  }

  async function carregarStatusGoogleAgenda() {
    if (!googleAgendaStatus) {
      return;
    }

    try {
      const resposta = await fetch('api/google-calendar', {headers: {Accept: 'application/json'}});
      const dados = await lerJson(resposta);

      if (tratarSessaoExpirada(resposta)) {
        return;
      }
      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível consultar o Google Agenda.');
      }

      renderizarStatusGoogleAgenda(dados);

      const retornoAgenda = new URLSearchParams(window.location.search).get('calendar');
      const mensagensRetorno = {
        conectado: ['Google Agenda conectado com sucesso.', 'sucesso'],
        cancelado: ['A conexão com o Google Agenda foi cancelada.', 'erro'],
        estado_invalido: ['A autorização expirou ou não pôde ser validada. Tente novamente.', 'erro'],
        erro: ['Não foi possível concluir a conexão com o Google Agenda.', 'erro'],
        configuracao: ['Configure a credencial OAuth do Google Agenda antes de conectar.', 'erro']
      };

      if (mensagensRetorno[retornoAgenda]) {
        const [mensagem, tipo] = mensagensRetorno[retornoAgenda];
        window.ShowMeUI.toast(mensagem, {variante: tipo === 'sucesso' ? 'sucesso' : 'erro'});
      }
    } catch (erro) {
      googleAgendaStatus.textContent = erro.message;
    }
  }

  async function desconectarGoogleAgenda() {
    botaoDesconectarGoogleAgenda.disabled = true;

    try {
      const resposta = await fetch('api/google-calendar', {
        method: 'DELETE',
        headers: {Accept: 'application/json'}
      });
      const dados = await lerJson(resposta);

      if (tratarSessaoExpirada(resposta)) {
        return;
      }
      if (!resposta.ok) {
        throw new Error(dados.erro || 'Não foi possível desconectar o Google Agenda.');
      }

      renderizarStatusGoogleAgenda({configurado: true, conectado: false});
      window.ShowMeUI.toast(dados.mensagem, {variante: 'sucesso'});
    } catch (erro) {
      window.ShowMeUI.toast(erro.message, {variante: 'erro'});
    } finally {
      botaoDesconectarGoogleAgenda.disabled = false;
    }
  }

  botaoEditarPerfil.addEventListener('click', () => {
    esconderFeedback(feedbackPagina);
    if (!editandoPerfil) {
      definirModoEdicao(true);
      return;
    }
    salvarDadosPerfil();
  });

  botaoDesconectarGoogleAgenda?.addEventListener('click', desconectarGoogleAgenda);

  formVisualizacao.addEventListener('submit', (evento) => {
    evento.preventDefault();
    if (editandoPerfil) {
      salvarDadosPerfil();
    }
  });

  document.querySelectorAll('[data-abrir-editor-midia]').forEach((controle) => {
    controle.addEventListener('click', (evento) => {
      evento.stopPropagation();
      abrirEditorMidia(controle.dataset.abrirEditorMidia);
    });
  });

  document.querySelectorAll('[data-visualizar-midia]').forEach((controle) => {
    controle.addEventListener('click', (evento) => {
      evento.stopPropagation();
      abrirLightbox(controle.dataset.visualizarMidia);
    });
  });

  document.querySelectorAll('[data-input-upload-midia]').forEach((input) => {
    input.addEventListener('change', () => {
      const arquivo = input.files?.[0];
      if (arquivo) {
        abrirRecorte(input.dataset.inputUploadMidia, arquivo);
      }
    });
  });

  document.querySelectorAll('[data-galeria-midia]').forEach((opcao) => {
    opcao.addEventListener('click', () => {
      salvarMidia(opcao.dataset.galeriaMidia, {caminho: opcao.dataset.caminho});
    });
  });

  cancelarRecorte.addEventListener('click', fecharRecorte);
  cancelarRecorteTopo.addEventListener('click', fecharRecorte);
  confirmarRecorte.addEventListener('click', confirmarEditorRecorte);
  recorteOverlay.addEventListener('click', (evento) => {
    if (evento.target === recorteOverlay) {
      fecharRecorte();
    }
  });

  fecharLightbox.addEventListener('click', fecharVisualizacao);
  lightbox.addEventListener('click', (evento) => {
    if (evento.target === lightbox) {
      fecharVisualizacao();
    }
  });

  document.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Escape') {
      return;
    }
    if (!lightbox.hidden) {
      fecharVisualizacao();
    }
    if (!recorteOverlay.hidden) {
      fecharRecorte();
    }
  });

  avatarImagem.addEventListener('error', () => aplicarAvatar(null));
  lightboxImagem.addEventListener('error', () => {
    lightboxImagem.hidden = true;
    lightboxFallback.hidden = false;
  });

  Object.entries(elementosModalMidia).forEach(([tipo, modal]) => {
    modal?.addEventListener('hidden.bs.modal', () => {
      if (alvoRecorte === tipo) {
        fecharRecorte();
      }
    });
  });

  carregarPerfil();
  carregarStatusGoogleAgenda();
}());
