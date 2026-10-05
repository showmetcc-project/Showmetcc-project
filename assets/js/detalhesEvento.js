(function () {
    'use strict';
    const imagemPadrao = 'assets/img/bannerEventoPadrao.png';
    const idUsuario = Number(document.body.dataset.userId || 0);
    const parametros = new URLSearchParams(window.location.search);
    const valorId = parametros.get('id') || parametros.get('id_evento') || '';
    const idEvento = /^\d+$/.test(valorId) && Number(valorId) > 0 ? Number(valorId) : null;
    let favoritoAtual = null;
    let mapaAtual = null;
    let eventoEncerrado = false;

    function caminhoImagem(caminho) {
        const valor = String(caminho || '').trim();
        if (!valor) return imagemPadrao;
        if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/') || valor.includes('\\')) return valor;
        return `assets/img/${valor}`;
    }
    async function lerJson(resposta) { try { return await resposta.json(); } catch (_) { return {}; } }
    function formatarData(valor) {
        const partes = String(valor || '').split(/[ T]/)[0].split('-').map(Number);
        return partes.length === 3 && partes.every(Number.isInteger)
            ? new Date(partes[0], partes[1] - 1, partes[2]).toLocaleDateString('pt-BR')
            : 'Data não informada';
    }
    function terminou(dataEvento) {
        const partes = String(dataEvento || '').split('-').map(Number);
        if (partes.length !== 3 || partes.some((n) => !Number.isInteger(n))) return false;
        const evento = new Date(partes[0], partes[1] - 1, partes[2]);
        const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
        return evento < hoje;
    }
    function localidade(evento) {
        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        return cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : 'Cidade não informada';
    }
    function mostrarErro(mensagem) {
        const estado = document.getElementById('estadoPagina');
        estado.classList.add('erro'); estado.replaceChildren();
        const icone = document.createElement('i'); icone.className = 'bi bi-exclamation-triangle';
        const texto = document.createElement('p'); texto.textContent = mensagem;
        estado.append(icone, texto); document.getElementById('conteudoEvento').hidden = true;
    }
    function configurarImagem(elemento, caminho, alt) {
        elemento.src = caminhoImagem(caminho); elemento.alt = alt;
        elemento.onerror = function () { this.onerror = null; this.src = imagemPadrao; };
    }
    function estadoVazio(titulo, descricao) {
        const card = document.createElement('div'); card.className = 'card-showme';
        const h = document.createElement('h4'); h.textContent = titulo;
        const p = document.createElement('p'); p.textContent = descricao;
        card.append(h, p); return card;
    }
    function renderizarArtistas(artistas) {
        const lista = document.getElementById('listaArtistas'); lista.replaceChildren();
        if (!Array.isArray(artistas) || !artistas.length) {
            lista.append(estadoVazio('Artista não informado', 'Este evento ainda não possui artista relacionado.'));
            return;
        }
        artistas.forEach((artista) => {
            const card = document.createElement('div'); card.className = 'card-showme artista-item';
            const linha = document.createElement('div'); linha.className = 'd-flex align-items-center';
            if (artista.imagem_artista) {
                const img = document.createElement('img'); img.className = 'imagem-artista';
                configurarImagem(img, artista.imagem_artista, artista.nome_artista || 'Artista'); linha.append(img);
            }
            const bloco = document.createElement('div'); const h = document.createElement('h4');
            h.textContent = artista.nome_artista || 'Artista'; bloco.append(h);
            if (artista.genero_artista) { const p = document.createElement('p'); p.textContent = artista.genero_artista; bloco.append(p); }
            linha.append(bloco); card.append(linha); lista.append(card);
        });
    }
    function endereco(evento) {
        const cidade = String(evento.cidade_evento || '').trim(), uf = String(evento.uf || '').trim();
        return [evento.local_evento, evento.rua_evento, cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : '']
            .filter((p) => String(p || '').trim()).join(', ');
    }
    async function carregarMapa(evento) {
        const alvo = document.getElementById('mapaEventoBanco'), texto = endereco(evento);
        if (!texto || typeof window.L === 'undefined') { alvo.textContent = 'Localização não disponível.'; return; }
        alvo.textContent = 'Carregando mapa...';
        try {
            const qs = new URLSearchParams({format: 'json', limit: '1', q: texto});
            const resposta = await fetch(`https://nominatim.openstreetmap.org/search?${qs}`, {headers: {Accept: 'application/json'}});
            const dados = await resposta.json();
            if (!resposta.ok || !Array.isArray(dados) || !dados.length) throw new Error();
            alvo.replaceChildren(); if (mapaAtual) mapaAtual.remove();
            mapaAtual = window.L.map('mapaEventoBanco').setView([Number(dados[0].lat), Number(dados[0].lon)], 15);
            window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 20}).addTo(mapaAtual);
            window.L.marker([Number(dados[0].lat), Number(dados[0].lon)]).addTo(mapaAtual).bindPopup(texto).openPopup();
        } catch (_) { alvo.textContent = 'Não foi possível carregar o mapa.'; }
    }
    function configurarIngressos(evento) {
        const texto = document.getElementById('textoIngressos');
        const link = document.getElementById('linkIngressos');
        const aviso = document.getElementById('avisoIngressos');
        link.hidden = true; aviso.hidden = true;
        if (eventoEncerrado) { texto.textContent = 'As vendas foram encerradas para este evento.'; return; }
        if (evento.gratuidade) { texto.textContent = 'Este evento é gratuito.'; return; }
        texto.textContent = 'Este evento é pago. Consulte o canal oficial.';
        if (!evento.link_oficial) return;
        try {
            const url = new URL(evento.link_oficial, location.href);
            if (!['http:', 'https:'].includes(url.protocol)) return;
            link.href = url.href; link.hidden = false; aviso.hidden = false;
        } catch (_) { /* link inválido fica oculto */ }
    }
    function renderizarEvento(evento) {
        eventoEncerrado = terminou(evento.data_evento);
        document.title = `${evento.nome_evento || 'Evento'} - ShowMe`;
        const imagem = document.getElementById('imagemEvento');
        configurarImagem(imagem, evento.imagem_evento, evento.nome_evento || 'Evento');
        imagem.classList.toggle('evento-encerrado-imagem', eventoEncerrado);
        const gratuidade = document.getElementById('badgeGratuidade');
        gratuidade.className = `badge-gratuidade ${evento.gratuidade ? 'gratuito' : 'pago'}`;
        gratuidade.textContent = evento.gratuidade ? 'Gratuito' : 'Pago';
        document.getElementById('badgeEncerrado').hidden = !eventoEncerrado;
        document.getElementById('tituloEvento').textContent = evento.nome_evento || 'Evento sem nome';
        document.querySelector('#infoData span').textContent = formatarData(evento.data_evento);
        document.querySelector('#infoCidade span').textContent = localidade(evento);
        const local = document.getElementById('infoLocal'); local.querySelector('span').textContent = evento.local_evento || ''; local.hidden = !evento.local_evento;
        const categoria = document.getElementById('infoCategoria'); categoria.querySelector('span').textContent = evento.categoria_evento || ''; categoria.hidden = !evento.categoria_evento;
        const preco = document.getElementById('infoPreco'); preco.querySelector('span').textContent = String(evento.faixa_preco || '').trim() || 'Consulte valores'; preco.hidden = Boolean(evento.gratuidade);
        document.getElementById('descricaoEvento').textContent = evento.descricao_evento || 'Este evento ainda não possui uma descrição cadastrada.';
        renderizarArtistas(evento.artistas);
        document.getElementById('nomeLocal').textContent = evento.local_evento || 'Local não informado';
        document.getElementById('enderecoLocal').textContent = [evento.rua_evento, localidade(evento)].filter(Boolean).join(' — ');
        const planejar = document.getElementById('linkPlanejamento'); planejar.href = `planejamento.php?id=${idEvento}`;
        planejar.classList.toggle('acao-desabilitada', eventoEncerrado); planejar.setAttribute('aria-disabled', String(eventoEncerrado));
        if (eventoEncerrado) planejar.removeAttribute('href');
        const favorito = document.querySelector('.btn-favoritar'); favorito.disabled = eventoEncerrado; favorito.title = eventoEncerrado ? 'Evento encerrado' : '';
        document.getElementById('linkComunidade').href = `comunidadeEvento.php?id=${idEvento}`;
        configurarIngressos(evento);
        document.getElementById('estadoPagina').hidden = true; document.getElementById('conteudoEvento').hidden = false;
        carregarMapa(evento);
    }
    function atualizarFavorito() {
        const botao = document.querySelector('.btn-favoritar'), ativo = favoritoAtual !== null;
        const icone = document.createElement('i'); icone.className = ativo ? 'bi bi-heart-fill' : 'bi bi-heart';
        botao.replaceChildren(icone, document.createTextNode(ativo ? ' Favoritado' : ' Favoritar'));
        botao.setAttribute('aria-pressed', String(ativo));
    }
    async function carregarFavorito() {
        if (idUsuario < 1 || eventoEncerrado) return;
        const botao = document.querySelector('.btn-favoritar'); botao.disabled = true;
        try {
            const resposta = await fetch('api/favoritos/', {headers: {Accept: 'application/json'}});
            const dados = await lerJson(resposta); if (!resposta.ok) throw new Error(dados.erro);
            favoritoAtual = (dados.favoritos || []).find((f) => Number(f.id_evento) === idEvento) || null;
            atualizarFavorito();
        } catch (erro) { console.error(erro); } finally { botao.disabled = eventoEncerrado; }
    }
    function iniciarFavorito() {
        const botao = document.querySelector('.btn-favoritar');
        botao.addEventListener('click', async function () {
            if (eventoEncerrado) return; this.disabled = true;
            try {
                const resposta = favoritoAtual
                    ? await fetch(`api/favoritos/${favoritoAtual.id_favorito}`, {method: 'DELETE'})
                    : await fetch('api/favoritos/', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id_evento: idEvento})});
                const dados = await lerJson(resposta); if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível alterar o favorito.');
                favoritoAtual = favoritoAtual ? null : dados.favorito; atualizarFavorito();
            } catch (erro) {
                window.ShowMeUI.toast(erro.message, {variante: 'erro'});
            } finally { this.disabled = eventoEncerrado; }
        });
    }
    async function carregarPagina() {
        if (!idEvento) { mostrarErro('Informe um evento válido na URL.'); return; }
        try {
            const resposta = await fetch(`api/eventos/${idEvento}`, {headers: {Accept: 'application/json'}});
            const dados = await lerJson(resposta); if (!resposta.ok || !dados.evento) throw new Error(dados.erro || 'Evento não encontrado.');
            renderizarEvento(dados.evento); await carregarFavorito();
        } catch (erro) { mostrarErro(erro.message || 'Não foi possível carregar o evento.'); }
    }
    if (typeof window.AOS !== 'undefined') window.AOS.init();
    iniciarFavorito(); carregarPagina();
}());
