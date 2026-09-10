(function () {
    'use strict';

    const seletor = '[data-favorito-evento]';
    const favoritos = new Map();
    let autenticado = null;
    let consultaEmAndamento = null;

    async function lerJson(resposta) {
        const texto = await resposta.text();

        if (!texto) {
            return {};
        }

        try {
            return JSON.parse(texto);
        } catch (erro) {
            throw new Error('A API retornou uma resposta inválida.');
        }
    }

    function botoesDoEvento(idEvento) {
        return Array.from(document.querySelectorAll(seletor)).filter(
            (botao) => Number(botao.dataset.favoritoEvento) === idEvento
        );
    }

    function atualizarBotao(botao) {
        const idEvento = Number(botao.dataset.favoritoEvento);
        const favoritado = favoritos.has(idEvento);
        const icone = document.createElement('i');
        const nomeEvento = botao.closest('.card-evento')?.querySelector('h4')?.textContent || 'evento';

        icone.className = favoritado ? 'bi bi-heart-fill' : 'bi bi-heart';
        icone.setAttribute('aria-hidden', 'true');
        botao.replaceChildren(icone);
        botao.setAttribute('aria-pressed', String(favoritado));
        botao.setAttribute(
            'aria-label',
            `${favoritado ? 'Remover' : 'Adicionar'} ${nomeEvento} ${favoritado ? 'dos' : 'aos'} favoritos`
        );
        botao.title = favoritado ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
    }

    function atualizarEvento(idEvento) {
        botoesDoEvento(idEvento).forEach(atualizarBotao);
    }

    async function carregarFavoritos() {
        if (consultaEmAndamento) {
            return consultaEmAndamento;
        }

        consultaEmAndamento = (async function () {
            const resposta = await fetch('api/favoritos/', {
                headers: {Accept: 'application/json'}
            });

            if (resposta.status === 401) {
                autenticado = false;
                return;
            }

            const dados = await lerJson(resposta);

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível consultar os favoritos.');
            }

            autenticado = true;
            favoritos.clear();
            (dados.favoritos || []).forEach((favorito) => {
                favoritos.set(Number(favorito.id_evento), Number(favorito.id_favorito));
            });
            document.querySelectorAll(seletor).forEach(atualizarBotao);
        }()).catch((erro) => {
            autenticado = null;
            console.error('Falha ao consultar favoritos:', erro);
        }).finally(() => {
            consultaEmAndamento = null;
        });

        return consultaEmAndamento;
    }

    async function alternarFavorito(botao) {
        const idEvento = Number(botao.dataset.favoritoEvento);

        if (!Number.isInteger(idEvento) || idEvento < 1) {
            window.alert('Não foi possível identificar este evento.');
            return;
        }

        if (autenticado === null) {
            await carregarFavoritos();
        }

        if (autenticado === false) {
            window.location.href = 'login.php';
            return;
        }

        if (autenticado !== true) {
            window.alert('Não foi possível consultar sua sessão. Tente novamente.');
            return;
        }

        const botoes = botoesDoEvento(idEvento);
        botoes.forEach((item) => { item.disabled = true; });

        try {
            const idFavorito = favoritos.get(idEvento);
            const resposta = idFavorito
                ? await fetch(`api/favoritos/${idFavorito}`, {method: 'DELETE'})
                : await fetch('api/favoritos/', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json', Accept: 'application/json'},
                    body: JSON.stringify({id_evento: idEvento})
                });
            const dados = await lerJson(resposta);

            if (resposta.status === 401) {
                autenticado = false;
                window.location.href = 'login.php';
                return;
            }

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível alterar o favorito.');
            }

            if (idFavorito) {
                favoritos.delete(idEvento);
            } else {
                favoritos.set(idEvento, Number(dados.favorito.id_favorito));
            }
            atualizarEvento(idEvento);
        } catch (erro) {
            window.alert(erro.message);
        } finally {
            botoes.forEach((item) => { item.disabled = false; });
        }
    }

    function registrarBotao(botao) {
        if (!(botao instanceof HTMLElement) || botao.dataset.favoritoInicializado === 'true') {
            return;
        }

        botao.dataset.favoritoInicializado = 'true';
        botao.addEventListener('click', function (evento) {
            evento.preventDefault();
            evento.stopPropagation();
            alternarFavorito(botao);
        });
        atualizarBotao(botao);
        carregarFavoritos();
    }

    function registrarArvore(elemento) {
        if (!(elemento instanceof HTMLElement)) {
            return;
        }

        if (elemento.matches(seletor)) {
            registrarBotao(elemento);
        }
        elemento.querySelectorAll(seletor).forEach(registrarBotao);
    }

    document.querySelectorAll(seletor).forEach(registrarBotao);
    new MutationObserver((mutacoes) => {
        mutacoes.forEach((mutacao) => {
            mutacao.addedNodes.forEach(registrarArvore);
        });
    }).observe(document.body, {childList: true, subtree: true});
}());
