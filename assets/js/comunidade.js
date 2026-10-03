(function () {
    'use strict';
    const fallback = 'assets/img/bannerEventoPadrao.png';
    const alvoSeusEventos = document.getElementById('comunidadesUsuario');
    const alvoTodasComunidades = document.getElementById('todasComunidades');
    const campoBusca = document.getElementById('buscaComunidades');
    let seusEventos = [];
    let todasComunidades = [];

    function normalizar(texto) {
        return String(texto || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('pt-BR')
            .trim();
    }
    function imagem(caminho) {
        if (!caminho) return fallback;
        return /^(https?:|data:|\/)/i.test(caminho) || caminho.startsWith('assets/') ? caminho : `assets/uploads/eventos/${caminho}`;
    }
    function card(evento) {
        const coluna = document.createElement('div');
        coluna.className = 'col-12 col-md-6 col-lg-4';
        const link = document.createElement('a');
        link.className = 'comunidade-card';
        link.href = `comunidadeEvento.php?id=${encodeURIComponent(evento.id_evento)}`;
        const foto = document.createElement('img');
        foto.src = imagem(evento.imagem_evento);
        foto.alt = evento.nome_evento || 'Evento';
        foto.loading = 'lazy';
        foto.onerror = () => { foto.onerror = null; foto.src = fallback; };
        const corpo = document.createElement('div');
        corpo.className = 'comunidade-card-corpo';
        const titulo = document.createElement('h3');
        titulo.textContent = evento.nome_evento || 'Evento';
        const metrica = document.createElement('p');
        const total = Number(evento.total_posts) || 0;
        metrica.innerHTML = `<i class="bi bi-chat-dots" aria-hidden="true"></i> ${total} ${total === 1 ? 'publicação' : 'publicações'}`;
        corpo.append(titulo, metrica);
        link.append(foto, corpo);
        coluna.append(link);
        return coluna;
    }
    function renderizar(alvo, eventos, vazio) {
        alvo.replaceChildren();
        if (!eventos.length) {
            const p = document.createElement('p'); p.className = 'comunidade-estado'; p.textContent = vazio; alvo.append(p); return;
        }
        alvo.append(...eventos.map(card));
    }

    function filtrarComunidades() {
        const termo = normalizar(campoBusca?.value);
        const filtrar = (eventos) => termo === ''
            ? eventos
            : eventos.filter((evento) => normalizar(evento.nome_evento).includes(termo));

        renderizar(
            alvoSeusEventos,
            filtrar(seusEventos),
            termo ? 'Nenhum dos seus eventos corresponde à busca.' : 'Favorite ou planeje um evento para vê-lo aqui.'
        );
        renderizar(
            alvoTodasComunidades,
            filtrar(todasComunidades),
            termo ? 'Nenhuma comunidade corresponde à busca.' : 'Nenhuma outra comunidade disponível.'
        );
    }

    campoBusca?.addEventListener('input', filtrarComunidades);
    campoBusca?.closest('form')?.addEventListener('submit', (evento) => evento.preventDefault());

    fetch('api/comunidade-posts?resumo=1', {headers:{Accept:'application/json'}})
        .then(async (resposta) => { const dados = await resposta.json(); if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível carregar as comunidades.'); return dados; })
        .then((dados) => {
            seusEventos = Array.isArray(dados.seus_eventos) ? dados.seus_eventos : [];
            todasComunidades = Array.isArray(dados.todas_comunidades) ? dados.todas_comunidades : [];
            filtrarComunidades();
        })
        .catch((erro) => document.querySelectorAll('.comunidade-grid').forEach((alvo) => { alvo.innerHTML = `<p class="comunidade-estado erro"></p>`; alvo.firstElementChild.textContent = erro.message; }));
}());
