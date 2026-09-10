(function () {
    'use strict';

    const idAvaliacao = Number(document.body.dataset.avaliacaoId || 0);
    const idUsuario = Number(document.body.dataset.userId || 0);
    const usuarioAdmin = document.body.dataset.userAdmin === 'true';
    const estado = document.getElementById('estadoEditarAvaliacao');
    const card = document.getElementById('cardEditarAvaliacao');
    const formulario = document.getElementById('formEditarAvaliacao');
    const campoNota = document.getElementById('notaEdicao');
    const campoComentario = document.getElementById('comentarioEdicao');
    const mensagem = document.getElementById('mensagemEditarAvaliacao');
    const botaoSalvar = document.getElementById('btnSalvarAvaliacao');
    const botaoExcluir = document.getElementById('btnExcluirAvaliacao');
    let avaliacaoAtual = null;

    async function lerJson(resposta) {
        try {
            return await resposta.json();
        } catch (erro) {
            return {};
        }
    }

    function mostrarEstado(texto, erro = false) {
        estado.textContent = texto;
        estado.classList.toggle('erro', erro);
        estado.hidden = false;
        card.hidden = true;
    }

    function mostrarMensagem(texto, tipo) {
        mensagem.textContent = texto;
        mensagem.className = `alert alert-${tipo}`;
    }

    function atualizarEstrelas(nota) {
        const valor = Number(nota);
        campoNota.value = String(valor);

        document.querySelectorAll('#estrelasEdicao .estrela').forEach((estrela) => {
            const ativa = Number(estrela.dataset.valor) <= valor;
            estrela.classList.toggle('bi-star-fill', ativa);
            estrela.classList.toggle('bi-star', !ativa);
            estrela.classList.toggle('ativa', ativa);
            estrela.setAttribute('aria-pressed', String(Number(estrela.dataset.valor) === valor));
        });
    }

    function caminhoMidia(caminho) {
        const valor = String(caminho || '').trim();

        if (!valor) {
            return '';
        }

        if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/')) {
            return valor;
        }

        return `assets/uploads/avaliacoes/${valor}`;
    }

    function renderizarMidias(midias) {
        const grade = document.getElementById('midiasAvaliacaoEdicao');
        const itens = Array.isArray(midias) ? midias : [];

        if (itens.length === 0) {
            const vazio = document.createElement('p');
            vazio.className = 'sem-midias-edicao';
            vazio.textContent = 'Esta avaliação não possui mídias.';
            grade.replaceChildren(vazio);
            return;
        }

        grade.replaceChildren(...itens.map((midia) => {
            const elemento = midia.tipo_midia === 'video'
                ? document.createElement('video')
                : document.createElement('img');

            elemento.src = caminhoMidia(midia.caminho_arquivo);

            if (elemento instanceof HTMLVideoElement) {
                elemento.controls = true;
                elemento.preload = 'metadata';
            } else {
                elemento.alt = 'Foto da avaliação';
                elemento.loading = 'lazy';
            }

            return elemento;
        }));
    }

    function renderizarAvaliacao(avaliacao) {
        avaliacaoAtual = avaliacao;
        document.getElementById('nomeEventoAvaliacao').textContent = avaliacao.nome_evento || 'Evento';
        campoComentario.value = avaliacao.comentario || '';
        atualizarEstrelas(avaliacao.nota);
        renderizarMidias(avaliacao.midias);

        const destino = `detalhesEvento.php?id=${encodeURIComponent(avaliacao.id_evento)}#avaliacoes`;
        document.getElementById('linkCancelarEdicao').href = destino;
        estado.hidden = true;
        card.hidden = false;
    }

    async function carregarAvaliacao() {
        if (!Number.isInteger(idAvaliacao) || idAvaliacao < 1) {
            mostrarEstado('Informe uma avaliação válida na URL.', true);
            return;
        }

        try {
            const resposta = await fetch(`api/avaliacoes/${idAvaliacao}`, {
                headers: {Accept: 'application/json'}
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            if (resposta.status === 404) {
                mostrarEstado('Avaliação não encontrada.', true);
                return;
            }

            if (!resposta.ok || !dados.avaliacao) {
                throw new Error(dados.erro || 'Não foi possível carregar a avaliação.');
            }

            if (Number(dados.avaliacao.id_user) !== idUsuario && !usuarioAdmin) {
                mostrarEstado('Você não tem permissão para editar esta avaliação.', true);
                return;
            }

            renderizarAvaliacao(dados.avaliacao);
        } catch (erro) {
            mostrarEstado(erro.message, true);
        }
    }

    document.querySelectorAll('#estrelasEdicao .estrela').forEach((estrela) => {
        estrela.addEventListener('click', function () {
            atualizarEstrelas(Number(this.dataset.valor));
        });
    });

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();

        const nota = Number(campoNota.value);
        const comentario = campoComentario.value.trim();

        if (!Number.isInteger(nota) || nota < 1 || nota > 5) {
            mostrarMensagem('Selecione uma nota de 1 a 5.', 'danger');
            return;
        }

        if (!comentario) {
            mostrarMensagem('O comentário é obrigatório.', 'danger');
            return;
        }

        botaoSalvar.disabled = true;

        try {
            const resposta = await fetch(`api/avaliacoes/${idAvaliacao}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                body: JSON.stringify({nota, comentario})
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível atualizar a avaliação.');
            }

            mostrarMensagem('Avaliação atualizada com sucesso.', 'success');
            window.location.href = `detalhesEvento.php?id=${encodeURIComponent(avaliacaoAtual.id_evento)}#avaliacoes`;
        } catch (erro) {
            mostrarMensagem(erro.message, 'danger');
        } finally {
            botaoSalvar.disabled = false;
        }
    });

    botaoExcluir.addEventListener('click', async function () {
        if (!avaliacaoAtual || !window.confirm('Deseja excluir definitivamente esta avaliação?')) {
            return;
        }

        this.disabled = true;

        try {
            const resposta = await fetch(`api/avaliacoes/${idAvaliacao}`, {
                method: 'DELETE',
                headers: {Accept: 'application/json'}
            });
            const dados = await lerJson(resposta);

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível excluir a avaliação.');
            }

            window.location.href = `detalhesEvento.php?id=${encodeURIComponent(avaliacaoAtual.id_evento)}#avaliacoes`;
        } catch (erro) {
            mostrarMensagem(erro.message, 'danger');
            this.disabled = false;
        }
    });

    carregarAvaliacao();
}());
