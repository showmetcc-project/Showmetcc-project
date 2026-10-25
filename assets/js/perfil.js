
(function () {
  'use strict';

  const form = document.getElementById('perfilForm');
  const botaoEditar = document.getElementById('btnEditarPerfil');
  const feedback = document.getElementById('perfilFeedback');
  const endpoint = form?.dataset.endpoint;

  if (!form || !botaoEditar || !endpoint) {
    console.error('[ShowMe] Formulário do perfil ou endpoint não encontrado.');
    return;
  }

  const campos = [
    document.getElementById('perfilNome'),
    document.getElementById('perfilSobrenome'),
    document.getElementById('perfilEmail')
  ];

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
  const fecharLightboxBotao = document.getElementById('fecharLightboxMidia');

  const googleAgendaStatus = document.getElementById('googleAgendaStatus');
  const botaoDesconectarGoogleAgenda =
    document.getElementById('btnDesconectarGoogleAgenda');

  const elementosModalMidia = {
    foto_perfil: document.getElementById('modalPersonalizarPerfil'),
    foto_banner: document.getElementById('modalPersonalizarBanner')
  };

  const modaisMidia = {};

  Object.entries(elementosModalMidia).forEach(([tipo, elemento]) => {
    if (elemento && window.bootstrap?.Modal) {
      modaisMidia[tipo] =
        window.bootstrap.Modal.getOrCreateInstance(elemento);
    }
  });

  const configuracaoMidia = {
    foto_perfil: {
      largura: 500,
      altura: 500,
      proporcao: 1
    },
    foto_banner: {
      largura: 1600,
      altura: 400,
      proporcao: 4
    }
  };

  let usuarioAtual = null;
  let cropper = null;
  let alvoRecorte = null;
  let urlArquivoRecorte = null;
  let editandoPerfil = false;
  let salvandoPerfil = false;
  let salvandoMidia = false;

  // --------------------------------------------------
  // Mensagens
  // --------------------------------------------------

  function notificar(mensagem, variante = 'erro') {
    if (typeof window.ShowMeUI?.toast === 'function') {
      window.ShowMeUI.toast(mensagem, { variante });
    } else {
      console.log(`[ShowMe] ${mensagem}`);
    }
  }

  function mostrarFeedback(mensagem, tipo = 'erro') {
    if (!feedback) return;

    feedback.textContent = mensagem;
    feedback.className = `perfil-feedback ${tipo}`;
    feedback.hidden = false;
    feedback.setAttribute(
      'role',
      tipo === 'erro' ? 'alert' : 'status'
    );
  }

  function esconderFeedback() {
    if (!feedback) return;

    feedback.textContent = '';
    feedback.hidden = true;
  }

  function mensagemErro(dados, padrao) {
    return dados?.mensagem || dados?.erro || padrao;
  }

  // --------------------------------------------------
  // Comunicação com a API
  // --------------------------------------------------

  async function lerJson(resposta) {
    const texto = await resposta.text();

    if (!texto.trim()) return {};

    try {
      return JSON.parse(texto);
    } catch {
      throw new Error(
        'O servidor retornou uma resposta inválida. Confira o PHP e a API.'
      );
    }
  }

  function tratarSessaoExpirada(resposta) {
    if (resposta.status !== 401) return false;

    window.location.href = 'login.php';
    return true;
  }

  async function requisicao(url, opcoes = {}) {
    const resposta = await fetch(url, {
      credentials: 'same-origin',
      ...opcoes,
      headers: {
        Accept: 'application/json',
        ...(opcoes.headers || {})
      }
    });

    const dados = await lerJson(resposta);

    if (tratarSessaoExpirada(resposta)) {
      throw new Error('Sua sessão expirou. Entre novamente.');
    }

    if (!resposta.ok) {
      throw new Error(
        mensagemErro(
          dados,
          `Não foi possível concluir a operação (${resposta.status}).`
        )
      );
    }

    return dados;
  }

  // --------------------------------------------------
  // Perfil e estatísticas
  // --------------------------------------------------

  function aplicarAvatar(caminho) {
    if (!avatarImagem || !avatarPlaceholder) return;

    if (caminho) {
      avatarImagem.src = caminho;
      avatarImagem.hidden = false;
      avatarPlaceholder.hidden = true;
    } else {
      avatarImagem.removeAttribute('src');
      avatarImagem.hidden = true;
      avatarPlaceholder.hidden = false;
    }
  }

  function aplicarBanner(caminho) {
    if (!banner) return;

    banner.classList.toggle(
      'banner-personalizado',
      Boolean(caminho)
    );

    banner.style.backgroundImage = caminho
      ? `url("${String(caminho).replace(/["\\]/g, '\\$&')}")`
      : '';
  }

  function atualizarGaleriaSelecionada() {
    document.querySelectorAll('[data-galeria-midia]').forEach(opcao => {
      const tipo = opcao.dataset.galeriaMidia;
      const caminho = opcao.dataset.caminho;

      const selecionada = Boolean(
        usuarioAtual?.[tipo] &&
        caminho &&
        usuarioAtual[tipo] === caminho
      );

      opcao.classList.toggle('selecionada', selecionada);
      opcao.setAttribute('aria-pressed', String(selecionada));
    });
  }

  function renderizarPerfil(usuario) {
    if (!usuario || typeof usuario !== 'object') {
      throw new Error('A API não retornou os dados do usuário.');
    }

    usuarioAtual = usuario;

    if (emailPrincipal) {
      emailPrincipal.textContent =
        usuario.email_user || usuario.email || '';
    }

    if (campos[0]) {
      campos[0].value = usuario.nome_user || usuario.nome || '';
    }

    if (campos[1]) {
      campos[1].value = usuario.sobrenome || '';
    }

    if (campos[2]) {
      campos[2].value = usuario.email_user || usuario.email || '';
    }

    aplicarAvatar(usuario.foto_perfil || null);
    aplicarBanner(usuario.foto_banner || null);
    atualizarGaleriaSelecionada();
  }

  function renderizarEstatisticas(estatisticas) {
    const valores =
      estatisticas && typeof estatisticas === 'object'
        ? estatisticas
        : {};

    document.querySelectorAll('[data-estatistica]').forEach(elemento => {
      const valor = Number(valores[elemento.dataset.estatistica]);

      elemento.textContent =
        Number.isInteger(valor) && valor >= 0
          ? String(valor)
          : '0';
    });
  }

  async function carregarPerfil() {
    try {
      const dados = await requisicao(endpoint);

      renderizarPerfil(dados.usuario);
      renderizarEstatisticas(dados.estatisticas);
      esconderFeedback();
    } catch (erro) {
      mostrarFeedback(
        erro.message || 'Não foi possível carregar o perfil.'
      );
    }
  }

  // --------------------------------------------------
  // Editar e salvar dados pessoais
  // --------------------------------------------------

  function definirModoEdicao(ativo) {
    editandoPerfil = ativo;

    form.classList.toggle('modo-edicao', ativo);
    botaoEditar.classList.toggle('salvar-alteracoes', ativo);
    botaoEditar.setAttribute('aria-pressed', String(ativo));

    campos.forEach(campo => {
      if (campo) campo.readOnly = !ativo;
    });

    const icone = botaoEditar.querySelector('i');
    const texto = botaoEditar.querySelector('span');

    if (icone) {
      icone.className = ativo
        ? 'bi bi-check-lg'
        : 'bi bi-pencil';
    }

    if (texto) {
      texto.textContent = ativo
        ? 'Salvar alterações'
        : 'Editar Perfil';
    }

    if (ativo) {
      campos[0]?.focus();
    }
  }

  async function salvarDadosPerfil() {
    if (salvandoPerfil) return;

    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }

    const dadosFormulario = {
      nome: campos[0]?.value.trim() || '',
      sobrenome: campos[1]?.value.trim() || '',
      email: campos[2]?.value.trim() || ''
    };

    if (!dadosFormulario.nome) {
      notificar('Informe seu nome.');
      campos[0]?.focus();
      return;
    }

    if (!dadosFormulario.sobrenome) {
      notificar('Informe seu sobrenome.');
      campos[1]?.focus();
      return;
    }

    if (!dadosFormulario.email) {
      notificar('Informe seu e-mail.');
      campos[2]?.focus();
      return;
    }

    salvandoPerfil = true;
    botaoEditar.disabled = true;
    esconderFeedback();

    try {
      const dados = await requisicao(endpoint, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(dadosFormulario)
      });

      if (dados.usuario) {
        renderizarPerfil(dados.usuario);
      } else {
        usuarioAtual = {
          ...usuarioAtual,
          nome_user: dadosFormulario.nome,
          sobrenome: dadosFormulario.sobrenome,
          email_user: dadosFormulario.email
        };

        renderizarPerfil(usuarioAtual);
      }

      definirModoEdicao(false);

      notificar(
        dados.mensagem || 'Perfil atualizado com sucesso.',
        'sucesso'
      );
    } catch (erro) {
      mostrarFeedback(
        erro.message || 'Não foi possível atualizar o perfil.'
      );
    } finally {
      salvandoPerfil = false;
      botaoEditar.disabled = false;
    }
  }

  botaoEditar.addEventListener('click', () => {
    esconderFeedback();

    if (!editandoPerfil) {
      definirModoEdicao(true);
      return;
    }

    salvarDadosPerfil();
  });

  form.addEventListener('submit', evento => {
    evento.preventDefault();

    if (editandoPerfil) {
      salvarDadosPerfil();
    }
  });

  // --------------------------------------------------
  // Abrir os modais de avatar e banner
  // --------------------------------------------------

  function abrirEditorMidia(tipo) {
    if (!configuracaoMidia[tipo]) return;

    const modal = elementosModalMidia[tipo];

    if (!modal || !modaisMidia[tipo]) {
      notificar('Não foi possível abrir a galeria de imagens.');
      return;
    }

    const input = modal.querySelector(
      `[data-input-upload-midia="${tipo}"]`
    );

    if (input) input.value = '';

    atualizarGaleriaSelecionada();
    modaisMidia[tipo].show();
  }

  document.querySelectorAll('[data-abrir-editor-midia]').forEach(controle => {
    controle.addEventListener('click', evento => {
      evento.stopPropagation();
      abrirEditorMidia(controle.dataset.abrirEditorMidia);
    });
  });

  // --------------------------------------------------
  // Salvar imagem escolhida na galeria ou enviada
  // --------------------------------------------------

  function definirMidiaProcessando(tipo, processando) {
    const modal = elementosModalMidia[tipo];

    if (!modal) return;

    modal.classList.toggle('salvando-midia', processando);

    modal.querySelectorAll('button, input').forEach(controle => {
      if (!controle.matches('[data-bs-dismiss]')) {
        controle.disabled = processando;
      }
    });
  }

  async function salvarMidia(tipo, { blob = null, caminho = null } = {}) {
    if (salvandoMidia) return;

    if (!configuracaoMidia[tipo]) {
      notificar('Tipo de imagem inválido.');
      return;
    }

    if (!blob && !caminho) return;

    const formulario = new FormData();
    formulario.append('acao', 'personalizar_midia');

    if (blob) {
      const extensao =
        blob.type === 'image/jpeg' ? 'jpg' : 'webp';

      formulario.append(
        tipo,
        blob,
        `${tipo}-${Date.now()}.${extensao}`
      );
    } else {
      formulario.append(`${tipo}_pronta`, caminho);
    }

    salvandoMidia = true;
    definirMidiaProcessando(tipo, true);

    try {
      const dados = await requisicao(endpoint, {
        method: 'POST',
        body: formulario
      });

      if (dados.usuario) {
        renderizarPerfil(dados.usuario);
      } else {
        await carregarPerfil();
      }

      modaisMidia[tipo]?.hide();

      notificar(
        dados.mensagem ||
          (tipo === 'foto_perfil'
            ? 'Foto de perfil atualizada.'
            : 'Banner atualizado.'),
        'sucesso'
      );
    } catch (erro) {
      notificar(
        erro.message || 'Não foi possível salvar a imagem.'
      );
    } finally {
      salvandoMidia = false;
      definirMidiaProcessando(tipo, false);

      const input = document.querySelector(
        `[data-input-upload-midia="${tipo}"]`
      );

      if (input) input.value = '';
    }
  }

  // No HTML enviado, data-galeria-midia contém o tipo,
  // e data-caminho contém o caminho da imagem.
  document.querySelectorAll('[data-galeria-midia]').forEach(opcao => {
    opcao.addEventListener('click', evento => {
      evento.preventDefault();

      const tipo = opcao.dataset.galeriaMidia;
      const caminho = opcao.dataset.caminho;

      if (!['foto_perfil', 'foto_banner'].includes(tipo) || !caminho) {
        notificar('Confira o tipo e o caminho da imagem na galeria.');
        return;
      }

      salvarMidia(tipo, { caminho });
    });
  });

  // --------------------------------------------------
  // Upload e recorte de imagens com CropperJS
  // --------------------------------------------------

  function fecharRecorte() {
    cropper?.destroy();
    cropper = null;
    alvoRecorte = null;

    if (recorteOverlay) recorteOverlay.hidden = true;
    if (recorteImagem) recorteImagem.removeAttribute('src');

    document.body.classList.remove('recorte-aberto');

    if (urlArquivoRecorte) {
      URL.revokeObjectURL(urlArquivoRecorte);
      urlArquivoRecorte = null;
    }
  }

  function abrirRecorte(tipo, arquivo) {
    if (
      !configuracaoMidia[tipo] ||
      !recorteOverlay ||
      !recorteImagem
    ) {
      notificar('O painel de recorte não foi encontrado no HTML.');
      return;
    }

    const tiposPermitidos = [
      'image/jpeg',
      'image/png',
      'image/webp'
    ];

    if (!tiposPermitidos.includes(arquivo.type)) {
      notificar('Use uma imagem JPG, PNG ou WebP.');
      return;
    }

    if (arquivo.size > 10 * 1024 * 1024) {
      notificar('A imagem não pode ultrapassar 10 MB.');
      return;
    }

    if (typeof window.Cropper !== 'function') {
      notificar('O CropperJS não foi carregado.');
      return;
    }

    fecharRecorte();

    alvoRecorte = tipo;
    urlArquivoRecorte = URL.createObjectURL(arquivo);

    recorteOverlay.hidden = false;
    document.body.classList.add('recorte-aberto');

    recorteImagem.onload = () => {
      cropper?.destroy();

      cropper = new window.Cropper(recorteImagem, {
        aspectRatio: configuracaoMidia[tipo].proporcao,
        viewMode: 1,
        dragMode: 'move',
        autoCropArea: 1,
        responsive: true,
        background: false,
        guides: true,
        movable: true,
        zoomable: true
      });
    };

    recorteImagem.onerror = () => {
      fecharRecorte();
      notificar('Não foi possível abrir a imagem selecionada.');
    };

    recorteImagem.src = urlArquivoRecorte;
  }

  function canvasParaBlob(canvas) {
    return new Promise((resolve, reject) => {
      canvas.toBlob(blob => {
        if (blob) {
          resolve(blob);
        } else {
          reject(
            new Error('Não foi possível gerar a imagem recortada.')
          );
        }
      }, 'image/webp', 0.9);
    });
  }

  async function confirmarEditorRecorte() {
    if (!cropper || !alvoRecorte) return;

    if (confirmarRecorte) {
      confirmarRecorte.disabled = true;
    }

    const tipo = alvoRecorte;

    try {
      const configuracao = configuracaoMidia[tipo];

      const canvas = cropper.getCroppedCanvas({
        width: configuracao.largura,
        height: configuracao.altura,
        imageSmoothingEnabled: true,
        imageSmoothingQuality: 'high'
      });

      if (!canvas) {
        throw new Error('Não foi possível processar o recorte.');
      }

      const blob = await canvasParaBlob(canvas);

      fecharRecorte();
      await salvarMidia(tipo, { blob });
    } catch (erro) {
      fecharRecorte();

      notificar(
        erro.message || 'Não foi possível recortar a imagem.'
      );
    } finally {
      if (confirmarRecorte) {
        confirmarRecorte.disabled = false;
      }
    }
  }

  document.querySelectorAll('[data-input-upload-midia]').forEach(input => {
    input.addEventListener('change', () => {
      const arquivo = input.files?.[0];

      if (arquivo) {
        abrirRecorte(input.dataset.inputUploadMidia, arquivo);
      }
    });
  });

  cancelarRecorte?.addEventListener('click', fecharRecorte);
  cancelarRecorteTopo?.addEventListener('click', fecharRecorte);
  confirmarRecorte?.addEventListener('click', confirmarEditorRecorte);

  recorteOverlay?.addEventListener('click', evento => {
    if (evento.target === recorteOverlay) {
      fecharRecorte();
    }
  });

  // --------------------------------------------------
  // Visualização ampliada de avatar e banner
  // --------------------------------------------------

  function abrirLightbox(tipo) {
    if (!lightbox || !lightboxImagem || !lightboxFallback) {
      return;
    }

    const caminho = usuarioAtual?.[tipo] || null;

    lightboxImagem.hidden = !caminho;
    lightboxFallback.hidden = Boolean(caminho);

    lightboxFallback.className =
      `lightbox-fallback ${
        tipo === 'foto_banner'
          ? 'fallback-banner'
          : 'fallback-avatar'
      }`;

    if (caminho) {
      lightboxImagem.src = caminho;
      lightboxImagem.alt =
        tipo === 'foto_perfil'
          ? 'Foto de perfil atual'
          : 'Banner atual do perfil';
    } else {
      lightboxImagem.removeAttribute('src');
    }

    lightbox.hidden = false;
    document.body.classList.add('lightbox-aberto');
  }

  function fecharVisualizacao() {
    if (!lightbox) return;

    lightbox.hidden = true;
    lightboxImagem?.removeAttribute('src');

    document.body.classList.remove('lightbox-aberto');
  }

  document.querySelectorAll('[data-visualizar-midia]').forEach(controle => {
    controle.addEventListener('click', evento => {
      evento.stopPropagation();
      abrirLightbox(controle.dataset.visualizarMidia);
    });
  });

  fecharLightboxBotao?.addEventListener('click', fecharVisualizacao);

  lightbox?.addEventListener('click', evento => {
    if (evento.target === lightbox) {
      fecharVisualizacao();
    }
  });

  avatarImagem?.addEventListener('error', () => {
    aplicarAvatar(null);
  });

  lightboxImagem?.addEventListener('error', () => {
    lightboxImagem.hidden = true;

    if (lightboxFallback) {
      lightboxFallback.hidden = false;
    }
  });

  // --------------------------------------------------
  // Google Agenda
  // --------------------------------------------------
  // O HTML já possui o link para googleCalendarConectar.php.
  // A conexão permanece disponível pelo link original.
  // A desconexão aguarda a confirmação da rota real da API.

  botaoDesconectarGoogleAgenda?.addEventListener('click', () => {
    notificar(
      'A desconexão ainda precisa ser conectada à API do Google Agenda.'
    );
  });

  // --------------------------------------------------
  // Teclado, modais e inicialização
  // --------------------------------------------------

  document.addEventListener('keydown', evento => {
    if (evento.key !== 'Escape') return;

    if (lightbox && !lightbox.hidden) {
      fecharVisualizacao();
    }

    if (recorteOverlay && !recorteOverlay.hidden) {
      fecharRecorte();
    }
  });

  Object.entries(elementosModalMidia).forEach(([tipo, modal]) => {
    modal?.addEventListener('hidden.bs.modal', () => {
      if (alvoRecorte === tipo) {
        fecharRecorte();
      }
    });
  });

  definirModoEdicao(false);
  carregarPerfil();

})();
