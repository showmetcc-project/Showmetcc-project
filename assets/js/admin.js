(function () {
    'use strict';

    const lista = document.getElementById('listaSolicitacoes');
    const campoBusca = document.getElementById('buscaAdmin');
    const listaDenuncias = document.getElementById('listaDenuncias');
    const campoBuscaDenuncia = campoBusca;
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

    function nomeSolicitante(solicitacao) {
        return [solicitacao.nome_user, solicitacao.sobrenome].filter(Boolean).join(' ') || 'Usuário';
    }

    function normalizarBusca(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('pt-BR');
    }

    function ativarFiltroBusca(seletor, valor) {
        document.querySelectorAll(seletor).forEach((botao) => {
            botao.classList.toggle('ativo', botao.dataset[valor.chave] === valor.status);
        });
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

    function atualizarContadores() {
        const quantidades = {
            todas: solicitacoes.length,
            pendente: solicitacoes.filter((item) => item.status_solicitacao === 'pendente').length,
            aprovado: solicitacoes.filter((item) => item.status_solicitacao === 'aprovado').length,
            recusado: solicitacoes.filter((item) => item.status_solicitacao === 'recusado').length
        };

        document.getElementById('contadorTodas').textContent = quantidades.todas;
        document.getElementById('contadorPendentes').textContent = quantidades.pendente;
        document.getElementById('contadorAprovadas').textContent = quantidades.aprovado;
        document.getElementById('contadorRecusadas').textContent = quantidades.recusado;
    }

    function filtrarSolicitacoes() {
        const termo = normalizarBusca(campoBusca.value.trim());

        return solicitacoes.filter((solicitacao) => {
            if (filtroAtual !== 'todas' && solicitacao.status_solicitacao !== filtroAtual) {
                return false;
            }

            if (!termo) {
                return true;
            }

            const conteudo = normalizarBusca([
                solicitacao.nome_evento,
                solicitacao.endereco_evento,
                solicitacao.rua_evento,
                solicitacao.cidade_evento,
                solicitacao.uf,
                solicitacao.categoria_evento,
                solicitacao.descricao_evento,
                solicitacao.descricao_artista,
                solicitacao.nome_artista_solicitado,
                nomeSolicitante(solicitacao),
                solicitacao.email_user
            ].filter(Boolean).join(' '));
            return conteudo.includes(termo);
        });
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
                    await carregarSolicitacoes(false);
                }
                throw new Error(dados.erro || 'Não foi possível analisar a solicitação.');
            }

            solicitacao.status_solicitacao = dados.solicitacao.status_solicitacao;
            solicitacao.id_evento = dados.solicitacao.id_evento;
            atualizarContadores();
            renderizarSolicitacoes();
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

    function abrirEdicaoSolicitacao(solicitacao) {
        if (solicitacao.status_solicitacao !== 'pendente') {
            mostrarMensagem('Somente solicitações pendentes podem ser editadas.');
            return;
        }

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
        modalEdicao.show();
    }

    async function salvarEdicaoSolicitacao() {
        const idSolicitacao = Number(document.getElementById('idSolicitacaoEdicao').value);
        const botaoSalvar = document.getElementById('btnSalvarSolicitacao');
        const dadosEdicao = {
            acao: 'editar_solicitacao',
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
            descricao_evento: document.getElementById('descricaoEventoEdicao').value.trim(),
            descricao_artista: document.getElementById('descricaoArtistaEdicao').value.trim(),
            nome_artista_solicitado: document.getElementById('nomeArtistaSolicitadoEdicao').value.trim()
        };

        botaoSalvar.disabled = true;

        try {
            const resposta = await fetch(`api/eventos/${idSolicitacao}`, {
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
                    await carregarSolicitacoes(false);
                }
                throw new Error(dados.erro || 'Não foi possível salvar as correções.');
            }

            const indice = solicitacoes.findIndex(
                (item) => Number(item.id_solicitacao) === idSolicitacao
            );

            if (indice >= 0) {
                solicitacoes[indice] = dados.solicitacao;
            }

            modalEdicao.hide();
            renderizarSolicitacoes();
            window.ShowMeUI.toast(
                'Alterações salvas. A solicitação já pode ser aprovada.',
                {variante: 'sucesso'}
            );
        } catch (erro) {
            window.ShowMeUI.toast(erro.message, {variante: 'erro'});
        } finally {
            botaoSalvar.disabled = false;
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
        card.className = `evento-card status-${solicitacao.status_solicitacao}${expandido ? ' expandido' : ''}`;
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
        badge.className = `badge-solicitacao badge-${solicitacao.status_solicitacao}`;
        badge.textContent = statusLegivel(solicitacao.status_solicitacao);
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
            criarCampo('Situação', statusLegivel(solicitacao.status_solicitacao))
        );
        linha.append(colunaEvento, colunaSolicitante);
        corpo.append(linha);

        const rodape = document.createElement('div');
        rodape.className = 'evento-footer';
        const editar = document.createElement('button');
        editar.type = 'button';
        editar.className = 'btn-editar';
        editar.disabled = solicitacao.status_solicitacao !== 'pendente';
        editar.innerHTML = '<i class="bi bi-pencil" aria-hidden="true"></i> Editar informações';
        editar.addEventListener('click', () => abrirEdicaoSolicitacao(solicitacao));
        rodape.append(
            editar,
            criarBotaoModeracao(solicitacao, 'aprovado'),
            criarBotaoModeracao(solicitacao, 'recusado')
        );

        if (solicitacao.id_evento) {
            const verEvento = document.createElement('a');
            verEvento.className = 'btn-ver-evento';
            verEvento.href = `detalhesEvento.php?id=${encodeURIComponent(solicitacao.id_evento)}`;
            verEvento.textContent = 'Ver evento publicado';
            rodape.prepend(verEvento);
        }

        card.append(cabecalho, corpo, rodape);
        return card;
    }

    function renderizarSolicitacoes() {
        const filtradas = filtrarSolicitacoes();
        lista.setAttribute('aria-busy', 'false');

        if (filtradas.length === 0) {
            const vazio = document.createElement('div');
            vazio.className = 'estado-solicitacoes';
            vazio.textContent = campoBusca.value.trim()
                ? 'Nenhuma solicitação corresponde à busca.'
                : 'Nenhuma solicitação encontrada neste status.';
            lista.replaceChildren(vazio);
            return;
        }

        lista.replaceChildren(...filtradas.map((solicitacao, indice) => criarCard(solicitacao, indice === 0)));
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

    function atualizarContadoresDenuncias() {
        const quantidades = {
            todas: denuncias.length,
            pendente: denuncias.filter((item) => item.status_denuncia === 'pendente').length,
            mantido: denuncias.filter((item) => item.status_denuncia === 'mantido').length,
            removido: denuncias.filter((item) => item.status_denuncia === 'removido').length
        };

        document.getElementById('contadorDenunciasAba').textContent = quantidades.pendente;
        document.getElementById('contadorDenunciasTodas').textContent = quantidades.todas;
        document.getElementById('contadorDenunciasPendentes').textContent = quantidades.pendente;
        document.getElementById('contadorDenunciasMantidas').textContent = quantidades.mantido;
        document.getElementById('contadorDenunciasRemovidas').textContent = quantidades.removido;
    }

    function filtrarDenuncias() {
        const termo = normalizarBusca(campoBuscaDenuncia.value.trim());

        return denuncias.filter((denuncia) => {
            if (filtroDenunciaAtual !== 'todas' && denuncia.status_denuncia !== filtroDenunciaAtual) {
                return false;
            }

            if (!termo) {
                return true;
            }

            return normalizarBusca([
                denuncia.texto_post,
                denuncia.categoria_post,
                denuncia.motivo,
                denuncia.comunidade_nome,
                denuncia.autor_nome,
                denuncia.autor_sobrenome,
                denuncia.autor_email,
                denuncia.denunciante_nome,
                denuncia.denunciante_sobrenome
            ].filter(Boolean).join(' ')).includes(termo);
        });
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
            titulo: removendo ? 'Remover publicação' : 'Manter publicação',
            texto: removendo
                ? 'A publicação deixará de aparecer na comunidade. O histórico da denúncia será preservado.'
                : 'A publicação continuará visível e a denúncia será marcada como mantida.',
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
                    await carregarDenuncias(false);
                }
                throw new Error(dados.erro || 'Não foi possível moderar a denúncia.');
            }

            denuncias.forEach((item) => {
                if (Number(item.id_post) === Number(dados.moderacao.id_post)
                    && item.status_denuncia === 'pendente') {
                    item.status_denuncia = dados.moderacao.status_denuncia;
                    item.status_post = dados.moderacao.status_post;
                    item.data_moderacao = new Date().toISOString();
                }
            });
            atualizarContadoresDenuncias();
            renderizarDenuncias();
            window.ShowMeUI.toast(
                removendo ? 'Publicação removida.' : 'Publicação mantida.',
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
        dataPost.dateTime = denuncia.data_post || '';
        dataPost.textContent = formatarDataHora(denuncia.data_post);
        autorTexto.append(autorNome, dataPost);
        autor.append(avatar, autorTexto);
        const categoria = document.createElement('span');
        categoria.className = 'denuncia-categoria';
        categoria.textContent = denuncia.categoria_post || 'Sem categoria';
        const textoPost = document.createElement('p');
        textoPost.className = 'denuncia-texto';
        textoPost.textContent = denuncia.texto_post;
        publicacao.append(autor, categoria, textoPost);

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
            manter.innerHTML = '<i class="bi bi-check-circle" aria-hidden="true"></i> Manter publicação';
            manter.addEventListener('click', () => moderarDenuncia(denuncia, 'manter'));
            const remover = document.createElement('button');
            remover.type = 'button';
            remover.className = 'btn-remover-post';
            remover.innerHTML = '<i class="bi bi-trash" aria-hidden="true"></i> Remover publicação';
            remover.addEventListener('click', () => moderarDenuncia(denuncia, 'remover'));
            acoes.append(manter, remover);
            card.append(acoes);
        }

        return card;
    }

    function renderizarDenuncias() {
        const filtradas = filtrarDenuncias();
        listaDenuncias.setAttribute('aria-busy', 'false');

        if (filtradas.length === 0) {
            const vazio = document.createElement('div');
            vazio.className = 'estado-solicitacoes';
            vazio.textContent = campoBuscaDenuncia.value.trim()
                ? 'Nenhuma denúncia corresponde à busca.'
                : 'Nenhuma denúncia encontrada neste status.';
            listaDenuncias.replaceChildren(vazio);
            return;
        }

        listaDenuncias.replaceChildren(...filtradas.map(criarCardDenuncia));
    }

    async function carregarDenuncias(exibirCarregamento = true) {
        if (exibirCarregamento) {
            listaDenuncias.setAttribute('aria-busy', 'true');
            const carregando = document.createElement('div');
            carregando.className = 'estado-solicitacoes';
            carregando.textContent = 'Carregando denúncias...';
            listaDenuncias.replaceChildren(carregando);
        }

        try {
            const resposta = await fetch('api/comunidade-denuncias?status=todas', {
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

            denuncias = dados.denuncias;
            denunciasCarregadas = true;
            atualizarContadoresDenuncias();
            renderizarDenuncias();
        } catch (erro) {
            listaDenuncias.setAttribute('aria-busy', 'false');
            const falha = document.createElement('div');
            falha.className = 'estado-solicitacoes erro';
            falha.textContent = erro.message;
            listaDenuncias.replaceChildren(falha);
        }
    }

    async function carregarSolicitacoes(exibirCarregamento = true) {
        if (exibirCarregamento) {
            lista.setAttribute('aria-busy', 'true');
            const carregando = document.createElement('div');
            carregando.className = 'estado-solicitacoes';
            carregando.textContent = 'Carregando solicitações...';
            lista.replaceChildren(carregando);
        }

        try {
            const resposta = await fetch('api/eventos?solicitacoes=todas', {
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

            solicitacoes = dados.solicitacoes;
            atualizarContadores();
            renderizarSolicitacoes();
        } catch (erro) {
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
            renderizarSolicitacoes();
        });
    });

    document.querySelectorAll('[data-filtro-denuncia]').forEach((botao) => {
        botao.addEventListener('click', function () {
            document.querySelectorAll('[data-filtro-denuncia]').forEach((item) => item.classList.remove('ativo'));
            this.classList.add('ativo');
            filtroDenunciaAtual = this.dataset.filtroDenuncia;
            renderizarDenuncias();
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

            if (campoBusca.value.trim()) {
                if (mostrarDenuncias) {
                    filtroDenunciaAtual = 'todas';
                    ativarFiltroBusca('[data-filtro-denuncia]', {chave: 'filtroDenuncia', status: 'todas'});
                } else {
                    filtroAtual = 'todas';
                    ativarFiltroBusca('[data-filtro-evento]', {chave: 'filtroEvento', status: 'todas'});
                }
            }

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
        const temBusca = Boolean(campoBusca.value.trim());
        const painelDenunciasAtivo = !document.getElementById('painelDenunciasAdmin').hidden;

        if (painelDenunciasAtivo) {
            if (temBusca) {
                filtroDenunciaAtual = 'todas';
                ativarFiltroBusca('[data-filtro-denuncia]', {chave: 'filtroDenuncia', status: 'todas'});
            }
            renderizarDenuncias();
        } else {
            if (temBusca) {
                filtroAtual = 'todas';
                ativarFiltroBusca('[data-filtro-evento]', {chave: 'filtroEvento', status: 'todas'});
            }
            renderizarSolicitacoes();
        }
    });
    formularioEdicao.addEventListener('submit', function (evento) {
        evento.preventDefault();
        salvarEdicaoSolicitacao();
    });
    carregarSolicitacoes();
    carregarDenuncias();
}());
