(function () {
    'use strict';

    const lista = document.getElementById('listaSolicitacoes');
    const campoBusca = document.getElementById('buscaSolicitacao');
    const mensagem = document.getElementById('mensagemAdmin');
    const elementoModalEdicao = document.getElementById('modalEditarSolicitacao');
    const formularioEdicao = document.getElementById('formEditarSolicitacao');
    const mensagemEdicao = document.getElementById('mensagemEdicaoSolicitacao');
    const modalEdicao = new window.bootstrap.Modal(elementoModalEdicao);
    const imagemPadrao = 'assets/img/banner_site_565x235px.png';
    let solicitacoes = [];
    let filtroAtual = 'todas';

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

    function mostrarMensagem(texto, tipo = 'danger') {
        mensagem.textContent = texto;
        mensagem.className = `alert alert-${tipo}`;
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

        document.getElementById('totalSolicitacoes').textContent = quantidades.todas;
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
                solicitacao.local_evento,
                solicitacao.descricao_evento,
                solicitacao.descricao_artista,
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

        const verbo = status === 'aprovado' ? 'aprovar' : 'recusar';
        if (!window.confirm(`Deseja ${verbo} a solicitação de “${solicitacao.nome_evento}”?`)) {
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
            mostrarMensagem(
                `Solicitação ${status === 'aprovado' ? 'aprovada' : 'recusada'} com sucesso.`,
                'success'
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
        document.getElementById('localEventoEdicao').value = solicitacao.local_evento || '';
        document.getElementById('dataEventoEdicao').value = solicitacao.data_evento || '';
        document.getElementById('horarioEventoEdicao').value = solicitacao.horario_evento
            ? String(solicitacao.horario_evento).slice(0, 5)
            : '';
        document.getElementById('gratuidadeEdicao').value = solicitacao.gratuidade ? 'true' : 'false';
        document.getElementById('descricaoEventoEdicao').value = solicitacao.descricao_evento || '';
        document.getElementById('descricaoArtistaEdicao').value = solicitacao.descricao_artista || '';
        mensagemEdicao.className = 'alert d-none';
        mensagemEdicao.replaceChildren();
        modalEdicao.show();
    }

    async function salvarEdicaoSolicitacao() {
        const idSolicitacao = Number(document.getElementById('idSolicitacaoEdicao').value);
        const botaoSalvar = document.getElementById('btnSalvarSolicitacao');
        const dadosEdicao = {
            acao: 'editar_solicitacao',
            nome_evento: document.getElementById('nomeEventoEdicao').value.trim(),
            local_evento: document.getElementById('localEventoEdicao').value.trim(),
            data_evento: document.getElementById('dataEventoEdicao').value,
            horario_evento: document.getElementById('horarioEventoEdicao').value,
            gratuidade: document.getElementById('gratuidadeEdicao').value === 'true',
            descricao_evento: document.getElementById('descricaoEventoEdicao').value.trim(),
            descricao_artista: document.getElementById('descricaoArtistaEdicao').value.trim()
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
            mostrarMensagem('Correções salvas. A solicitação já pode ser aprovada.', 'success');
        } catch (erro) {
            mensagemEdicao.textContent = erro.message;
            mensagemEdicao.className = 'alert alert-danger';
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
        local.textContent = solicitacao.local_evento || 'Local não informado';
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
            criarCampo('Local', solicitacao.local_evento),
            criarCampo('Tipo', solicitacao.gratuidade ? 'Gratuito' : 'Pago'),
            criarCampo('Descrição do evento', solicitacao.descricao_evento),
            criarCampo('Artista / atração', solicitacao.descricao_artista)
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

    document.querySelectorAll('.status-item').forEach((botao) => {
        botao.addEventListener('click', function () {
            document.querySelectorAll('.status-item').forEach((item) => item.classList.remove('ativo'));
            this.classList.add('ativo');
            filtroAtual = this.dataset.filtro;
            renderizarSolicitacoes();
        });
    });

    campoBusca.addEventListener('input', renderizarSolicitacoes);
    formularioEdicao.addEventListener('submit', function (evento) {
        evento.preventDefault();
        salvarEdicaoSolicitacao();
    });
    carregarSolicitacoes();
}());
