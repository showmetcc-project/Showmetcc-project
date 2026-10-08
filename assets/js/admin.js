(function () {
    'use strict';

    const lista = document.getElementById('listaSolicitacoes');
    const campoBusca = document.getElementById('buscaAdmin');
    const listaDenuncias = document.getElementById('listaDenuncias');
    const campoBuscaDenuncia = campoBusca;
    const campoPeriodo = document.getElementById('filtroPeriodoAdmin');
    const elementoModalEdicao = document.getElementById('modalEditarSolicitacao');
    const formularioEdicao = document.getElementById('formEditarSolicitacao');
    const modalEdicao = new window.bootstrap.Modal(elementoModalEdicao);
    const imagemPadrao = 'assets/img/bannerEventoPadrao.png';
    const avatarPadrao = 'assets/img/showme.png';
    let solicitacoes = [];
    let denuncias = [];
    let filtroAtual = 'pendente';
    let filtroDenunciaAtual = 'pendente';
    let denunciasCarregadas = false;
    const tamanhoLote = 10;
    let temMaisSolicitacoes = false;
    let temMaisDenuncias = false;
    let sequenciaSolicitacoes = 0;
    let sequenciaDenuncias = 0;
    let temporizadorBusca = null;

    async function lerJson(resposta) {
        try {
            return await resposta.json();
        } catch (erro) {
            return {};
        }
    }

    function caminhoImagem(caminho) {
        const valor = String(caminho || '').trim();

        if (!valor) {
            return imagemPadrao;
        }

        if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/')) {
            return valor;
        }

        return `assets/img/${valor}`;
    }

    function formatarData(data) {
        const partes = String(data || '').split('-').map(Number);

        if (partes.length !== 3 || partes.some((parte) => !Number.isInteger(parte))) {
            return 'Data não informada';
        }

        return new Date(partes[0], partes[1] - 1, partes[2]).toLocaleDateString('pt-BR');
    }

    function formatarDataHora(dataHora) {
        const correspondencia = String(dataHora || '').match(
            /^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/
        );

        if (!correspondencia) {
            return 'Data não informada';
        }

        const data = new Date(
            Number(correspondencia[1]),
            Number(correspondencia[2]) - 1,
            Number(correspondencia[3]),
            Number(correspondencia[4]),
            Number(correspondencia[5]),
            Number(correspondencia[6] || 0)
        );
        return data.toLocaleString('pt-BR');
    }

    function statusLegivel(status) {
        return {
            pendente: 'Pendente',
            aprovado: 'Aprovada',
            recusado: 'Recusada'
        }[status] || status;
    }

    function situacaoSolicitacao(solicitacao) {
        return solicitacao.status_solicitacao === 'aprovado' && !solicitacao.id_evento
            ? 'Evento removido'
            : statusLegivel(solicitacao.status_solicitacao);
    }

    function nomeSolicitante(solicitacao) {
        return [solicitacao.nome_user, solicitacao.sobrenome].filter(Boolean).join(' ') || 'Usuário';
    }

    function mostrarMensagem(texto, tipo = 'danger') {
        window.ShowMeUI.toast(texto, {
            variante: tipo === 'success' ? 'sucesso' : 'erro'
        });
    }

    function criarCampo(rotulo, valor) {
        const campo = document.createElement('div');
        campo.className = 'campo-info';
        const label = document.createElement('span');
        label.className = 'campo-info-label';
        label.textContent = rotulo;
        const texto = document.createElement('p');
        texto.textContent = valor || 'Não informado';
        campo.append(label, texto);
        return campo;
    }

    function atualizarContadores(quantidades = {}) {

        document.getElementById('contadorTodas').textContent = quantidades.todas;
        document.getElementById('contadorPendentes').textContent = quantidades.pendente;
        document.getElementById('contadorAprovadas').textContent = quantidades.aprovado;
        document.getElementById('contadorRecusadas').textContent = quantidades.recusado;
        document.getElementById('contadorRemovidas').textContent = quantidades.removido;
    }

    async function moderarSolicitacao(solicitacao, status) {
        if (solicitacao.status_solicitacao !== 'pendente') {
            mostrarMensagem('Esta solicitação já foi analisada.');
            return;
        }

        const deveModerar = await window.ShowMeUI.confirmar({
            titulo: status === 'aprovado' ? 'Aprovar evento' : 'Reprovar evento',
            texto: status === 'aprovado'
                ? `Deseja aprovar “${solicitacao.nome_evento}” e publicá-lo como evento?`
                : `Deseja reprovar “${solicitacao.nome_evento}”? A solicitação deixará de ficar pendente.`,
            confirmarTexto: status === 'aprovado' ? 'Aprovar' : 'Reprovar',
            cancelarTexto: 'Cancelar',
            variante: status === 'aprovado' ? 'importante' : 'destrutiva'
        });

        if (!deveModerar) {
            return;
        }

        const card = lista.querySelector(`[data-solicitacao-id="${solicitacao.id_solicitacao}"]`);
        card?.querySelectorAll('button').forEach((botao) => { botao.disabled = true; });

        try {
            const resposta = await fetch(`api/eventos/${solicitacao.id_solicitacao}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({
                    acao: 'moderar',
                    status_solicitacao: status
                })
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }

            if (!resposta.ok) {
                if (resposta.status === 409 || resposta.status === 404) {
                    await carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
                }
                throw new Error(dados.erro || 'Não foi possível analisar a solicitação.');
            }

            await carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
            window.ShowMeUI.toast(
                status === 'aprovado'
                    ? 'Evento aprovado com sucesso.'
                    : 'Evento reprovado com sucesso.',
                {variante: 'sucesso'}
            );
        } catch (erro) {
            mostrarMensagem(erro.message);
            card?.querySelectorAll('button').forEach((botao) => { botao.disabled = false; });
        }
    }

    function preencherFormularioEvento(solicitacao) {
        document.getElementById('idSolicitacaoEdicao').value = String(solicitacao.id_solicitacao);
        document.getElementById('nomeEventoEdicao').value = solicitacao.nome_evento || '';
        document.getElementById('cepEventoEdicao').value = solicitacao.cep_evento || '';
        document.getElementById('enderecoEventoEdicao').value = solicitacao.endereco_evento || '';
        document.getElementById('numeroEnderecoEdicao').value = solicitacao.numero_endereco || '';
        document.getElementById('ruaEventoEdicao').value = solicitacao.rua_evento || '';
        document.getElementById('cidadeEventoEdicao').value = solicitacao.cidade_evento || '';
        document.getElementById('ufEventoEdicao').value = solicitacao.uf || '';
        document.getElementById('categoriaEventoEdicao').value = solicitacao.categoria_evento || '';
        document.getElementById('linkOficialEdicao').value = solicitacao.link_oficial || '';
        document.getElementById('dataEventoEdicao').value = solicitacao.data_evento || '';
        document.getElementById('horarioEventoEdicao').value = solicitacao.horario_evento
            ? String(solicitacao.horario_evento).slice(0, 5)
            : '';
        document.getElementById('gratuidadeEdicao').value = solicitacao.gratuidade ? 'true' : 'false';
        document.getElementById('valorMinimoEdicao').value = solicitacao.valor_ingresso_minimo || '';
        document.getElementById('valorMaximoEdicao').value = solicitacao.valor_ingresso_maximo || '';
        document.getElementById('descricaoEventoEdicao').value = solicitacao.descricao_evento || '';
        document.getElementById('descricaoArtistaEdicao').value = solicitacao.descricao_artista || '';
        document.getElementById('nomeArtistaSolicitadoEdicao').value = solicitacao.nome_artista_solicitado || '';
    }

    function configurarModalEdicao(solicitacao, modo) {
        const editandoEvento = modo === 'evento';
        formularioEdicao.dataset.modo = modo;
        formularioEdicao.dataset.idAlvo = String(
            editandoEvento ? solicitacao.id_evento : solicitacao.id_solicitacao
        );
        preencherFormularioEvento(solicitacao);
        document.getElementById('tituloModalEditarSolicitacao').textContent = editandoEvento
            ? 'Editar evento publicado'
            : 'Corrigir solicitação';
        document.getElementById('btnSalvarSolicitacao').innerHTML = editandoEvento
            ? '<i class="bi bi-pencil" aria-hidden="true"></i> Salvar evento'
            : '<i class="bi bi-check-lg" aria-hidden="true"></i> Salvar correções';
        document.querySelectorAll('.campo-apenas-solicitacao').forEach((campo) => {
            campo.hidden = editandoEvento;
        });
        modalEdicao.show();
    }

    function abrirEdicaoSolicitacao(solicitacao) {
        if (solicitacao.status_solicitacao !== 'pendente') {
            mostrarMensagem('Somente solicitações pendentes podem ser editadas.');
            return;
        }
        configurarModalEdicao(solicitacao, 'solicitacao');
    }

    function abrirEdicaoEvento(solicitacao) {
        if (solicitacao.status_solicitacao !== 'aprovado' || !solicitacao.id_evento) {
            mostrarMensagem('O evento publicado não foi encontrado.');
            return;
        }
        configurarModalEdicao(solicitacao, 'evento');
    }

    async function salvarEdicaoSolicitacao() {
        const modo = formularioEdicao.dataset.modo || 'solicitacao';
        const editandoEvento = modo === 'evento';
        const idAlvo = Number(formularioEdicao.dataset.idAlvo);
        const botaoSalvar = document.getElementById('btnSalvarSolicitacao');
        const dadosEdicao = {
            acao: editandoEvento ? 'editar' : 'editar_solicitacao',
            nome_evento: document.getElementById('nomeEventoEdicao').value.trim(),
            cep_evento: document.getElementById('cepEventoEdicao').value.trim(),
            endereco_evento: document.getElementById('enderecoEventoEdicao').value.trim(),
            numero_endereco: document.getElementById('numeroEnderecoEdicao').value.trim(),
            rua_evento: document.getElementById('ruaEventoEdicao').value.trim(),
            cidade_evento: document.getElementById('cidadeEventoEdicao').value.trim(),
            uf: document.getElementById('ufEventoEdicao').value.trim(),
            categoria_evento: document.getElementById('categoriaEventoEdicao').value.trim(),
            link_oficial: document.getElementById('linkOficialEdicao').value.trim(),
            data_evento: document.getElementById('dataEventoEdicao').value,
            horario_evento: document.getElementById('horarioEventoEdicao').value,
            gratuidade: document.getElementById('gratuidadeEdicao').value === 'true',
            valor_ingresso_minimo: document.getElementById('valorMinimoEdicao').value,
            valor_ingresso_maximo: document.getElementById('valorMaximoEdicao').value,
            descricao_evento: document.getElementById('descricaoEventoEdicao').value.trim()
        };

        if (!editandoEvento) {
            dadosEdicao.descricao_artista = document.getElementById('descricaoArtistaEdicao').value.trim();
            dadosEdicao.nome_artista_solicitado = document.getElementById('nomeArtistaSolicitadoEdicao').value.trim();
        }

        if (editandoEvento) {
            const confirmou = await window.ShowMeUI.confirmar({
                titulo: 'Salvar alterações no evento',
                texto: 'As informações publicadas serão alteradas e o usuário que cadastrou o evento será avisado por e-mail.',
                confirmarTexto: 'Salvar alterações',
                cancelarTexto: 'Cancelar',
                variante: 'edicao'
            });
            if (!confirmou) return;
        }

        botaoSalvar.disabled = true;

        try {
            const resposta = await fetch(`api/eventos/${idAlvo}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify(dadosEdicao)
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }

            if (!resposta.ok) {
                if (resposta.status === 409 || resposta.status === 404) {
                    await carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
                }
                throw new Error(dados.erro || 'Não foi possível salvar as correções.');
            }

            modalEdicao.hide();
            await carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
            window.ShowMeUI.toast(
                editandoEvento
                    ? 'Evento atualizado. O responsável será notificado por e-mail.'
                    : 'Alterações salvas. A solicitação já pode ser aprovada.',
                {variante: 'sucesso'}
            );
        } catch (erro) {
            window.ShowMeUI.toast(erro.message, {variante: 'erro'});
        } finally {
            botaoSalvar.disabled = false;
        }
    }

    async function removerEventoPublicado(solicitacao) {
        if (!solicitacao.id_evento) {
            mostrarMensagem('O evento publicado não foi encontrado.');
            return;
        }

        const confirmou = await window.ShowMeUI.confirmar({
            titulo: 'Remover evento publicado',
            texto: `Tem certeza que deseja remover “${solicitacao.nome_evento}”? Favoritos, planejamentos e a comunidade associados também serão removidos. O responsável será avisado por e-mail.`,
            confirmarTexto: 'Remover evento',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });
        if (!confirmou) return;

        const card = lista.querySelector(`[data-solicitacao-id="${solicitacao.id_solicitacao}"]`);
        card?.querySelectorAll('button, a').forEach((controle) => {
            controle.setAttribute('aria-disabled', 'true');
            if ('disabled' in controle) controle.disabled = true;
        });

        try {
            const resposta = await fetch(`api/eventos/${solicitacao.id_evento}`, {
                method: 'DELETE',
                headers: {Accept: 'application/json'}
            });
            const dados = await lerJson(resposta);
            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }
            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível remover o evento.');
            }
            await carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
            window.ShowMeUI.toast(
                'Evento removido. O responsável será notificado por e-mail.',
                {variante: 'sucesso'}
            );
        } catch (erro) {
            mostrarMensagem(erro.message);
            card?.querySelectorAll('button, a').forEach((controle) => {
                controle.removeAttribute('aria-disabled');
                if ('disabled' in controle) controle.disabled = false;
            });
        }
    }

    function criarBotaoModeracao(solicitacao, status) {
        const aprovar = status === 'aprovado';
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = aprovar ? 'btn-aprovar' : 'btn-recusar';
        botao.disabled = solicitacao.status_solicitacao !== 'pendente';
        botao.innerHTML = aprovar
            ? '<i class="bi bi-check-circle" aria-hidden="true"></i> Aprovar'
            : '<i class="bi bi-x-circle" aria-hidden="true"></i> Recusar';
        botao.addEventListener('click', () => moderarSolicitacao(solicitacao, status));
        return botao;
    }

    function criarCard(solicitacao, expandido) {
        const card = document.createElement('article');
        const statusVisual = solicitacao.status_solicitacao === 'aprovado' && !solicitacao.id_evento
            ? 'removido'
            : solicitacao.status_solicitacao;
        card.className = `evento-card status-${statusVisual}${expandido ? ' expandido' : ''}`;
        card.dataset.solicitacaoId = String(solicitacao.id_solicitacao);

        const cabecalho = document.createElement('button');
        cabecalho.type = 'button';
        cabecalho.className = 'evento-header';
        cabecalho.setAttribute('aria-expanded', String(expandido));

        const info = document.createElement('span');
        info.className = 'evento-info';
        const imagem = document.createElement('img');
        imagem.className = 'evento-foto';
        imagem.src = caminhoImagem(solicitacao.foto);
        imagem.alt = `Foto de ${solicitacao.nome_evento}`;
        imagem.loading = 'lazy';
        imagem.addEventListener('error', function () {
            this.src = imagemPadrao;
        }, {once: true});

        const resumo = document.createElement('span');
        const titulo = document.createElement('h3');
        titulo.textContent = solicitacao.nome_evento;
        const local = document.createElement('small');
        local.textContent = solicitacao.endereco_evento || 'Endereço não informado';
        const data = document.createElement('small');
        data.className = 'd-block';
        const horario = solicitacao.horario_evento
            ? ` às ${String(solicitacao.horario_evento).slice(0, 5)}`
            : '';
        data.textContent = `${formatarData(solicitacao.data_evento)}${horario} · ${solicitacao.gratuidade ? 'Gratuito' : 'Pago'}`;
        resumo.append(titulo, local, data);
        info.append(imagem, resumo);

        const ladoDireito = document.createElement('span');
        ladoDireito.className = 'header-direito';
        const badge = document.createElement('span');
        const eventoRemovido = solicitacao.status_solicitacao === 'aprovado' && !solicitacao.id_evento;
        badge.className = `badge-solicitacao badge-${eventoRemovido ? 'removido' : solicitacao.status_solicitacao}`;
        badge.textContent = situacaoSolicitacao(solicitacao);
        const chevron = document.createElement('i');
        chevron.className = `bi ${expandido ? 'bi-chevron-up' : 'bi-chevron-down'} chevron`;
        chevron.setAttribute('aria-hidden', 'true');
        ladoDireito.append(badge, chevron);
        cabecalho.append(info, ladoDireito);

        cabecalho.addEventListener('click', function () {
            const aberto = card.classList.toggle('expandido');
            this.setAttribute('aria-expanded', String(aberto));
            chevron.classList.toggle('bi-chevron-up', aberto);
            chevron.classList.toggle('bi-chevron-down', !aberto);
        });

        const corpo = document.createElement('div');
        corpo.className = 'evento-body';
        const linha = document.createElement('div');
        linha.className = 'row g-4';
        const colunaEvento = document.createElement('div');
        colunaEvento.className = 'col-md-6';
        colunaEvento.append(
            criarCampo('Endereço', [solicitacao.endereco_evento || solicitacao.rua_evento,
                solicitacao.numero_endereco, solicitacao.cidade_evento, solicitacao.uf]
                .filter(Boolean).join(' · ')),
            criarCampo('Categoria', solicitacao.categoria_evento),
            criarCampo('Link oficial', solicitacao.link_oficial),
            criarCampo('Tipo', solicitacao.gratuidade ? 'Gratuito' : 'Pago'),
            criarCampo('Faixa de valor', solicitacao.gratuidade
                ? 'Entrada gratuita'
                : [solicitacao.valor_ingresso_minimo, solicitacao.valor_ingresso_maximo]
                    .filter((valor) => valor !== null && valor !== '').join(' – ')),
            criarCampo('Descrição do evento', solicitacao.descricao_evento),
            criarCampo('Nome do artista / atração', solicitacao.nome_artista_solicitado),
            criarCampo('Descrição do artista / atração', solicitacao.descricao_artista)
        );
        const colunaSolicitante = document.createElement('div');
        colunaSolicitante.className = 'col-md-6';
        colunaSolicitante.append(
            criarCampo('Data e hora', `${formatarData(solicitacao.data_evento)}${horario}`),
            criarCampo('Solicitado por', `${nomeSolicitante(solicitacao)} · ${solicitacao.email_user}`),
            criarCampo('Enviado em', formatarDataHora(solicitacao.data_solicitacao)),
            criarCampo('Situação', situacaoSolicitacao(solicitacao))
        );
        linha.append(colunaEvento, colunaSolicitante);
        corpo.append(linha);

        const rodape = document.createElement('div');
        rodape.className = 'evento-footer';
        if (solicitacao.status_solicitacao === 'pendente') {
            const editar = document.createElement('button');
            editar.type = 'button';
            editar.className = 'btn-editar';
            editar.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Editar informações';
            editar.addEventListener('click', () => abrirEdicaoSolicitacao(solicitacao));
            rodape.append(
                editar,
                criarBotaoModeracao(solicitacao, 'aprovado'),
                criarBotaoModeracao(solicitacao, 'recusado')
            );
        } else if (solicitacao.status_solicitacao === 'aprovado' && solicitacao.id_evento) {
            const editarEvento = document.createElement('button');
            editarEvento.type = 'button';
            editarEvento.className = 'btn-editar';
            editarEvento.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Editar evento publicado';
            editarEvento.addEventListener('click', () => abrirEdicaoEvento(solicitacao));

            const removerEvento = document.createElement('button');
            removerEvento.type = 'button';
            removerEvento.className = 'btn-remover-evento';
            removerEvento.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i> Remover evento';
            removerEvento.addEventListener('click', () => removerEventoPublicado(solicitacao));
            rodape.append(editarEvento, removerEvento);
        }

        card.append(cabecalho, corpo, rodape);
        return card;
    }

    function renderizarSolicitacoes(adicionar = false, novosItens = solicitacoes) {
        lista.setAttribute('aria-busy', 'false');

        if (solicitacoes.length === 0) {
            const vazio = document.createElement('div');
            vazio.className = 'estado-solicitacoes';
            vazio.textContent = campoBusca.value.trim()
                ? 'Nenhuma solicitação corresponde à busca.'
                : 'Nenhuma solicitação encontrada neste status.';
            lista.replaceChildren(vazio);
            return;
        }

        if (adicionar) {
            lista.querySelector('.admin-ver-mais-container')?.remove();
            lista.append(...novosItens.map((solicitacao) => criarCard(solicitacao, false)));
            if (temMaisSolicitacoes) {
                lista.append(criarBotaoVerMais('solicitacoes'));
            }
            return;
        }

        const elementos = solicitacoes.map((solicitacao, indice) => criarCard(solicitacao, indice === 0));
        if (temMaisSolicitacoes) {
            elementos.push(criarBotaoVerMais('solicitacoes'));
        }
        lista.replaceChildren(...elementos);
    }

    function statusDenunciaLegivel(status) {
        return {
            pendente: 'Pendente',
            mantido: 'Mantido',
            removido: 'Removido'
        }[status] || status;
    }

    function nomePessoa(nome, sobrenome, fallback = 'Usuário') {
        return [nome, sobrenome].filter(Boolean).join(' ') || fallback;
    }

    function atualizarContadoresDenuncias(quantidades = {}) {

        document.getElementById('contadorDenunciasAba').textContent = quantidades.pendente;
        document.getElementById('contadorDenunciasTodas').textContent = quantidades.todas;
        document.getElementById('contadorDenunciasPendentes').textContent = quantidades.pendente;
        document.getElementById('contadorDenunciasMantidas').textContent = quantidades.mantido;
        document.getElementById('contadorDenunciasRemovidas').textContent = quantidades.removido;
    }

    function criarDetalheDenuncia(rotulo, valor, classe = '') {
        const detalhe = document.createElement('div');
        detalhe.className = `denuncia-detalhe${classe ? ` ${classe}` : ''}`;
        const titulo = document.createElement('span');
        titulo.textContent = rotulo;
        const texto = document.createElement('p');
        texto.textContent = valor || 'Não informado';
        detalhe.append(titulo, texto);
        return detalhe;
    }

    async function moderarDenuncia(denuncia, acao) {
        if (denuncia.status_denuncia !== 'pendente') {
            mostrarMensagem('Esta denúncia já foi analisada.');
            return;
        }

        const removendo = acao === 'remover';
        const deveModerar = await window.ShowMeUI.confirmar({
            titulo: removendo ? 'Remover conteúdo' : 'Manter conteúdo',
            texto: removendo
                ? 'O conteúdo deixará de aparecer na comunidade. O histórico da denúncia será preservado e as pessoas envolvidas serão notificadas por e-mail.'
                : 'O conteúdo continuará visível, a denúncia será marcada como mantida e o denunciante será notificado por e-mail.',
            confirmarTexto: removendo ? 'Remover' : 'Manter',
            cancelarTexto: 'Cancelar',
            variante: removendo ? 'destrutiva' : 'importante'
        });

        if (!deveModerar) return;

        const card = listaDenuncias.querySelector(`[data-denuncia-id="${denuncia.id_denuncia}"]`);
        card?.querySelectorAll('button').forEach((botao) => { botao.disabled = true; });

        try {
            const resposta = await fetch(`api/comunidade-denuncias/${denuncia.id_denuncia}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({acao})
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }

            if (!resposta.ok) {
                if (resposta.status === 404 || resposta.status === 409) {
                    await carregarDenuncias({reiniciar: true, exibirCarregamento: false});
                }
                throw new Error(dados.erro || 'Não foi possível moderar a denúncia.');
            }

            await carregarDenuncias({reiniciar: true, exibirCarregamento: false});
            window.ShowMeUI.toast(
                removendo ? 'Conteúdo removido.' : 'Conteúdo mantido.',
                {variante: 'sucesso'}
            );
        } catch (erro) {
            mostrarMensagem(erro.message);
            card?.querySelectorAll('button').forEach((botao) => { botao.disabled = false; });
        }
    }

    function criarCardDenuncia(denuncia) {
        const card = document.createElement('article');
        card.className = `denuncia-card status-${denuncia.status_denuncia}`;
        card.dataset.denunciaId = String(denuncia.id_denuncia);

        const cabecalho = document.createElement('header');
        cabecalho.className = 'denuncia-card-cabecalho';
        const comunidade = document.createElement('div');
        comunidade.className = 'denuncia-comunidade';
        const imagem = document.createElement('img');
        imagem.src = caminhoImagem(denuncia.comunidade_imagem);
        imagem.alt = `Imagem de ${denuncia.comunidade_nome}`;
        imagem.loading = 'lazy';
        imagem.addEventListener('error', function () {
            this.src = imagemPadrao;
        }, {once: true});
        const comunidadeTexto = document.createElement('div');
        const titulo = document.createElement('h3');
        titulo.textContent = denuncia.comunidade_nome || 'Comunidade do evento';
        const link = document.createElement('a');
        link.href = `comunidadeEvento.php?id=${encodeURIComponent(denuncia.id_evento)}`;
        link.textContent = 'Abrir comunidade';
        comunidadeTexto.append(titulo, link);
        comunidade.append(imagem, comunidadeTexto);
        const status = document.createElement('span');
        status.className = 'denuncia-status';
        status.textContent = statusDenunciaLegivel(denuncia.status_denuncia);
        cabecalho.append(comunidade, status);

        const corpo = document.createElement('div');
        corpo.className = 'denuncia-card-corpo';
        const publicacao = document.createElement('section');
        publicacao.className = 'denuncia-publicacao';
        const autor = document.createElement('div');
        autor.className = 'denuncia-autor';
        const avatar = document.createElement('img');
        avatar.src = denuncia.autor_foto ? caminhoImagem(denuncia.autor_foto) : avatarPadrao;
        avatar.alt = '';
        avatar.addEventListener('error', function () {
            this.src = avatarPadrao;
        }, {once: true});
        const autorTexto = document.createElement('div');
        const autorNome = document.createElement('strong');
        autorNome.textContent = nomePessoa(denuncia.autor_nome, denuncia.autor_sobrenome, 'Autor não encontrado');
        const dataPost = document.createElement('time');
        const dataConteudo = denuncia.tipo_alvo === 'midia' ? denuncia.data_midia : denuncia.data_post;
        dataPost.dateTime = dataConteudo || '';
        dataPost.textContent = formatarDataHora(dataConteudo);
        autorTexto.append(autorNome, dataPost);
        autor.append(avatar, autorTexto);
        const categoria = document.createElement('span');
        categoria.className = 'denuncia-categoria';
        categoria.textContent = denuncia.tipo_alvo === 'midia'
            ? 'Foto da galeria'
            : (denuncia.categoria_post || 'Sem categoria');
        const textoPost = document.createElement('p');
        textoPost.className = 'denuncia-texto';
        textoPost.textContent = denuncia.tipo_alvo === 'midia'
            ? (denuncia.legenda_midia || 'Imagem sem legenda.')
            : denuncia.texto_post;
        publicacao.append(autor, categoria);
        if (denuncia.tipo_alvo === 'midia') {
            const imagemDenunciada = document.createElement('img');
            imagemDenunciada.className = 'denuncia-midia-imagem';
            imagemDenunciada.src = caminhoImagem(denuncia.caminho_midia);
            imagemDenunciada.alt = denuncia.legenda_midia || 'Foto denunciada da galeria';
            imagemDenunciada.loading = 'lazy';
            imagemDenunciada.addEventListener('error', function () {
                this.src = imagemPadrao;
            }, {once: true});
            publicacao.append(imagemDenunciada);
        }
        publicacao.append(textoPost);

        const detalhes = document.createElement('aside');
        detalhes.className = 'denuncia-detalhes';
        detalhes.append(
            criarDetalheDenuncia('Motivo da denúncia', denuncia.motivo, 'denuncia-motivo'),
            criarDetalheDenuncia('Autor', `${nomePessoa(denuncia.autor_nome, denuncia.autor_sobrenome)} · ${denuncia.autor_email || 'e-mail não informado'}`),
            criarDetalheDenuncia('Denunciado em', formatarDataHora(denuncia.data_denuncia)),
            criarDetalheDenuncia('Denunciado por', nomePessoa(denuncia.denunciante_nome, denuncia.denunciante_sobrenome))
        );

        if (denuncia.data_moderacao) {
            detalhes.append(criarDetalheDenuncia('Analisado em', formatarDataHora(denuncia.data_moderacao)));
        }

        corpo.append(publicacao, detalhes);
        card.append(cabecalho, corpo);

        if (denuncia.status_denuncia === 'pendente') {
            const acoes = document.createElement('footer');
            acoes.className = 'denuncia-card-acoes';
            const manter = document.createElement('button');
            manter.type = 'button';
            manter.className = 'btn-manter-post';
            manter.innerHTML = '<i class="bi bi-check-circle" aria-hidden="true"></i> Manter conteúdo';
            manter.addEventListener('click', () => moderarDenuncia(denuncia, 'manter'));
            const remover = document.createElement('button');
            remover.type = 'button';
            remover.className = 'btn-remover-post';
            remover.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i> Remover conteúdo';
            remover.addEventListener('click', () => moderarDenuncia(denuncia, 'remover'));
            acoes.append(manter, remover);
            card.append(acoes);
        }

        return card;
    }

    function renderizarDenuncias(adicionar = false, novosItens = denuncias) {
        listaDenuncias.setAttribute('aria-busy', 'false');

        if (denuncias.length === 0) {
            const vazio = document.createElement('div');
            vazio.className = 'estado-solicitacoes';
            vazio.textContent = campoBuscaDenuncia.value.trim()
                ? 'Nenhuma denúncia corresponde à busca.'
                : 'Nenhuma denúncia encontrada neste status.';
            listaDenuncias.replaceChildren(vazio);
            return;
        }

        if (adicionar) {
            listaDenuncias.querySelector('.admin-ver-mais-container')?.remove();
            listaDenuncias.append(...novosItens.map(criarCardDenuncia));
            if (temMaisDenuncias) {
                listaDenuncias.append(criarBotaoVerMais('denuncias'));
            }
            return;
        }

        const elementos = denuncias.map(criarCardDenuncia);
        if (temMaisDenuncias) {
            elementos.push(criarBotaoVerMais('denuncias'));
        }
        listaDenuncias.replaceChildren(...elementos);
    }

    function criarBotaoVerMais(tipo) {
        const envoltorio = document.createElement('div');
        envoltorio.className = 'admin-ver-mais-container';
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.className = 'admin-ver-mais';
        botao.innerHTML = '<i class="bi bi-plus-circle" aria-hidden="true"></i> Ver mais';
        botao.addEventListener('click', async () => {
            botao.disabled = true;
            botao.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Carregando...';
            if (tipo === 'denuncias') {
                await carregarDenuncias({reiniciar: false, exibirCarregamento: false});
            } else {
                await carregarSolicitacoes({reiniciar: false, exibirCarregamento: false});
            }
        });
        envoltorio.append(botao);
        return envoltorio;
    }

    function parametrosListagem(status, offset) {
        const parametros = new URLSearchParams({
            status,
            busca: campoBusca.value.trim(),
            dias: campoPeriodo.value,
            limite: String(tamanhoLote),
            offset: String(offset)
        });
        return parametros;
    }

    async function carregarDenuncias({reiniciar = true, exibirCarregamento = true} = {}) {
        const sequenciaAtual = ++sequenciaDenuncias;
        const offset = reiniciar ? 0 : denuncias.length;
        if (exibirCarregamento) {
            listaDenuncias.setAttribute('aria-busy', 'true');
            const carregando = document.createElement('div');
            carregando.className = 'estado-solicitacoes';
            carregando.textContent = 'Carregando denúncias...';
            listaDenuncias.replaceChildren(carregando);
        }

        try {
            const parametros = parametrosListagem(filtroDenunciaAtual, offset);
            const resposta = await fetch(`api/comunidade-denuncias?${parametros}`, {
                headers: {Accept: 'application/json'}
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }

            if (!resposta.ok || !Array.isArray(dados.denuncias)) {
                throw new Error(dados.erro || 'Não foi possível carregar as denúncias.');
            }

            if (sequenciaAtual !== sequenciaDenuncias) return;

            const novosItens = dados.denuncias;
            denuncias = reiniciar ? novosItens : denuncias.concat(novosItens);
            temMaisDenuncias = Boolean(dados.tem_mais);
            denunciasCarregadas = true;
            atualizarContadoresDenuncias(dados.contadores);
            renderizarDenuncias(!reiniciar, novosItens);
        } catch (erro) {
            if (sequenciaAtual !== sequenciaDenuncias) return;
            listaDenuncias.setAttribute('aria-busy', 'false');
            const falha = document.createElement('div');
            falha.className = 'estado-solicitacoes erro';
            falha.textContent = erro.message;
            listaDenuncias.replaceChildren(falha);
        }
    }

    async function carregarSolicitacoes({reiniciar = true, exibirCarregamento = true} = {}) {
        const sequenciaAtual = ++sequenciaSolicitacoes;
        const offset = reiniciar ? 0 : solicitacoes.length;
        if (exibirCarregamento) {
            lista.setAttribute('aria-busy', 'true');
            const carregando = document.createElement('div');
            carregando.className = 'estado-solicitacoes';
            carregando.textContent = 'Carregando solicitações...';
            lista.replaceChildren(carregando);
        }

        try {
            const parametros = parametrosListagem(filtroAtual, offset);
            parametros.delete('status');
            parametros.set('solicitacoes', filtroAtual);
            const resposta = await fetch(`api/eventos?${parametros}`, {
                headers: {Accept: 'application/json'}
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401 || resposta.status === 403) {
                window.location.href = 'loginAdmin.php';
                return;
            }

            if (!resposta.ok || !Array.isArray(dados.solicitacoes)) {
                throw new Error(dados.erro || 'Não foi possível carregar as solicitações.');
            }

            if (sequenciaAtual !== sequenciaSolicitacoes) return;

            const novosItens = dados.solicitacoes;
            solicitacoes = reiniciar ? novosItens : solicitacoes.concat(novosItens);
            temMaisSolicitacoes = Boolean(dados.tem_mais);
            atualizarContadores(dados.contadores);
            renderizarSolicitacoes(!reiniciar, novosItens);
        } catch (erro) {
            if (sequenciaAtual !== sequenciaSolicitacoes) return;
            lista.setAttribute('aria-busy', 'false');
            const falha = document.createElement('div');
            falha.className = 'estado-solicitacoes erro';
            falha.textContent = erro.message;
            lista.replaceChildren(falha);
        }
    }

    document.querySelectorAll('[data-filtro-evento]').forEach((botao) => {
        botao.addEventListener('click', function () {
            document.querySelectorAll('[data-filtro-evento]').forEach((item) => item.classList.remove('ativo'));
            this.classList.add('ativo');
            filtroAtual = this.dataset.filtroEvento;
            carregarSolicitacoes({reiniciar: true});
        });
    });

    document.querySelectorAll('[data-filtro-denuncia]').forEach((botao) => {
        botao.addEventListener('click', function () {
            document.querySelectorAll('[data-filtro-denuncia]').forEach((item) => item.classList.remove('ativo'));
            this.classList.add('ativo');
            filtroDenunciaAtual = this.dataset.filtroDenuncia;
            carregarDenuncias({reiniciar: true});
        });
    });

    document.querySelectorAll('[data-aba-admin]').forEach((botao) => {
        botao.addEventListener('click', function () {
            const aba = this.dataset.abaAdmin;
            document.querySelectorAll('[data-aba-admin]').forEach((item) => {
                const ativa = item === this;
                item.classList.toggle('ativa', ativa);
                item.setAttribute('aria-selected', String(ativa));
            });

            const painelEventos = document.getElementById('painelEventosAdmin');
            const painelDenuncias = document.getElementById('painelDenunciasAdmin');
            const mostrarDenuncias = aba === 'denuncias';
            campoBusca.placeholder = mostrarDenuncias
                ? 'Buscar post, autor ou comunidade...'
                : 'Buscar evento ou solicitante...';
            painelEventos.classList.toggle('ativa', !mostrarDenuncias);
            painelEventos.hidden = mostrarDenuncias;
            painelDenuncias.classList.toggle('ativa', mostrarDenuncias);
            painelDenuncias.hidden = !mostrarDenuncias;

            if (mostrarDenuncias && !denunciasCarregadas) {
                carregarDenuncias();
            } else if (mostrarDenuncias) {
                renderizarDenuncias();
            } else {
                renderizarSolicitacoes();
            }
        });
    });

    campoBusca.addEventListener('input', function () {
        window.clearTimeout(temporizadorBusca);
        temporizadorBusca = window.setTimeout(() => {
            carregarSolicitacoes({reiniciar: true, exibirCarregamento: false});
            carregarDenuncias({reiniciar: true, exibirCarregamento: false});
        }, 300);
    });

    campoPeriodo.addEventListener('change', function () {
        carregarSolicitacoes({reiniciar: true});
        carregarDenuncias({reiniciar: true});
    });
    formularioEdicao.addEventListener('submit', function (evento) {
        evento.preventDefault();
        salvarEdicaoSolicitacao();
    });
    carregarSolicitacoes();
    carregarDenuncias();
}());
