(function () {
    'use strict';

    const imagemPadraoCard = 'assets/img/banner_site_565x235px.png';
    const limiteBanner = 5;
    let bannerSwiper = null;

    const secoesCategorias = [
        {id: 'eventosMusicais', termos: ['musica', 'musical', 'show', 'festival', 'concerto']},
        {id: 'pertoVoce', termos: ['perto de voce', 'local', 'regional']},
        {id: 'cinema', termos: ['cinema', 'filme', 'mostra cinematografica']},
        {id: 'showsInternacionais', termos: ['internacional']},
        {id: 'showsNacionais', termos: ['nacional'], excluir: ['internacional']},
        {id: 'emBreve', termos: ['em breve', 'futuro', 'proximamente']}
    ];

    function normalizarTexto(valor) {
        return String(valor || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    function caminhoImagem(caminho, usarPadrao = true) {
        const valor = String(caminho || '').trim();

        if (!valor) {
            return usarPadrao ? imagemPadraoCard : null;
        }

        if (
            /^(?:https?:)?\/\//i.test(valor)
            || valor.startsWith('/')
            || valor.includes('/')
            || valor.includes('\\')
        ) {
            return valor;
        }

        return `assets/img/${valor}`;
    }

    function atualizarControlesBanner(quantidade) {
        const exibirNavegacao = quantidade > 1;
        document.querySelector('.banner .swiper-button-prev')?.toggleAttribute('hidden', !exibirNavegacao);
        document.querySelector('.banner .swiper-button-next')?.toggleAttribute('hidden', !exibirNavegacao);
        document.querySelector('.banner .swiper-pagination')?.toggleAttribute('hidden', !exibirNavegacao);
    }

    function iniciarBanner(quantidade) {
        if (bannerSwiper) {
            bannerSwiper.destroy(true, true);
            bannerSwiper = null;
        }

        atualizarControlesBanner(quantidade);

        if (quantidade === 0 || typeof window.Swiper !== 'function') {
            return;
        }

        bannerSwiper = new window.Swiper('.banner.swiper', {
            loop: quantidade > 1,
            autoplay: quantidade > 1
                ? {delay: 4500, disableOnInteraction: false}
                : false,
            pagination: {
                el: '.banner .swiper-pagination',
                clickable: true
            },
            navigation: {
                nextEl: '.banner .swiper-button-next',
                prevEl: '.banner .swiper-button-prev'
            }
        });
    }

    function mostrarBannerVazio(mensagem) {
        const banner = document.querySelector('.banner');
        const wrapper = document.getElementById('bannerEventos');

        if (!wrapper) {
            return;
        }

        const estado = document.createElement('div');
        estado.className = 'swiper-slide banner-estado';

        const icone = document.createElement('i');
        icone.className = 'bi bi-image';
        icone.setAttribute('aria-hidden', 'true');

        const texto = document.createElement('p');
        texto.textContent = mensagem;
        estado.append(icone, texto);
        wrapper.replaceChildren(estado);

        iniciarBanner(0);
        banner?.setAttribute('aria-busy', 'false');
    }

    function criarSlideBanner(evento) {
        const caminho = caminhoImagem(evento.imagem_evento, false);

        if (!caminho) {
            return null;
        }

        const slide = document.createElement('div');
        slide.className = 'swiper-slide';

        const link = document.createElement('a');
        link.href = `detalhesEvento.php?id=${encodeURIComponent(evento.id_evento)}`;
        link.setAttribute('aria-label', `Ver detalhes de ${evento.nome_evento || 'evento'}`);

        const imagem = document.createElement('img');
        imagem.src = caminho;
        imagem.alt = evento.nome_evento || 'Evento em destaque';
        imagem.addEventListener('error', function () {
            slide.remove();
            const quantidadeRestante = document.querySelectorAll('#bannerEventos .swiper-slide').length;

            if (quantidadeRestante === 0) {
                mostrarBannerVazio('Nenhum evento em destaque no momento.');
                return;
            }

            bannerSwiper?.update();
            atualizarControlesBanner(quantidadeRestante);
        }, {once: true});

        link.append(imagem);
        slide.append(link);
        return slide;
    }

    function renderizarBanner(eventos) {
        const wrapper = document.getElementById('bannerEventos');

        if (!wrapper) {
            return;
        }

        const slides = eventos
            .map(criarSlideBanner)
            .filter(Boolean)
            .slice(0, limiteBanner);

        if (slides.length === 0) {
            mostrarBannerVazio('Nenhum evento em destaque no momento.');
            return;
        }

        wrapper.replaceChildren(...slides);
        iniciarBanner(slides.length);
        document.querySelector('.banner')?.setAttribute('aria-busy', 'false');
    }

    function formatarData(dataEvento) {
        const partes = String(dataEvento || '').split('-').map(Number);

        if (partes.length !== 3 || partes.some((parte) => !Number.isInteger(parte))) {
            return 'Data não informada';
        }

        const data = new Date(partes[0], partes[1] - 1, partes[2]);
        return data.toLocaleDateString('pt-BR');
    }

    function criarLinhaInformacao(classeIcone, texto, classeLinha) {
        const linha = document.createElement('span');
        linha.className = classeLinha;

        const icone = document.createElement('i');
        icone.className = classeIcone;
        icone.setAttribute('aria-hidden', 'true');
        linha.append(icone, document.createTextNode(texto));
        return linha;
    }

    function criarCardEvento(evento) {
        const card = document.createElement('div');
        card.className = 'card-evento';

        const link = document.createElement('a');
        link.href = `detalhesEvento.php?id=${encodeURIComponent(evento.id_evento)}`;

        const badge = document.createElement('div');
        const gratuito = Boolean(evento.gratuidade);
        badge.className = `badge-evento ${gratuito ? 'gratuito' : 'pago'}`;
        badge.textContent = gratuito ? 'Gratuito' : 'Pago';

        const imagem = document.createElement('img');
        imagem.src = caminhoImagem(evento.imagem_evento);
        imagem.alt = evento.nome_evento || 'Evento';
        imagem.loading = 'lazy';
        imagem.addEventListener('error', function () {
            this.src = imagemPadraoCard;
        }, {once: true});

        const conteudo = document.createElement('div');
        conteudo.className = 'card-conteudo';

        const titulo = document.createElement('h4');
        titulo.textContent = evento.nome_evento || 'Evento sem nome';

        const informacoes = document.createElement('div');
        informacoes.className = 'info-evento';

        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        const local = cidade
            ? `${cidade}${uf ? ` - ${uf}` : ''}`
            : (evento.local_evento || 'Local não informado');

        informacoes.append(
            criarLinhaInformacao('bi bi-geo-alt-fill', local, 'evento-localizacao'),
            criarLinhaInformacao('bi bi-calendar-event', formatarData(evento.data_evento), 'evento-data')
        );
        conteudo.append(titulo, informacoes);
        link.append(badge, imagem, conteudo);

        const favorito = document.createElement('button');
        favorito.type = 'button';
        favorito.className = 'btn-toggle-favorito';
        favorito.dataset.favoritoEvento = String(evento.id_evento);
        favorito.setAttribute('aria-label', `Adicionar ${evento.nome_evento || 'evento'} aos favoritos`);
        favorito.setAttribute('aria-pressed', 'false');
        favorito.title = 'Adicionar aos favoritos';
        favorito.innerHTML = '<i class="bi bi-heart" aria-hidden="true"></i>';

        card.append(link, favorito);
        return card;
    }

    function criarEstado(mensagem, erro = false) {
        const estado = document.createElement('div');
        estado.className = `sem-eventos estado-eventos${erro ? ' erro' : ''}`;

        const icone = document.createElement('i');
        icone.className = erro ? 'bi bi-exclamation-triangle' : 'bi bi-calendar-x';
        icone.setAttribute('aria-hidden', 'true');

        const texto = document.createElement('p');
        texto.textContent = mensagem;
        estado.append(icone, texto);
        return estado;
    }

    function atualizarNavegacao(idCarrossel, possuiEventos) {
        document.querySelectorAll(`[data-carrossel="${idCarrossel}"]`).forEach((botao) => {
            botao.hidden = !possuiEventos;
        });
    }

    function renderizarCarrossel(idCarrossel, eventos, mensagemVazia) {
        const carrossel = document.getElementById(idCarrossel);

        if (!carrossel) {
            return;
        }

        if (eventos.length === 0) {
            carrossel.replaceChildren(criarEstado(mensagemVazia));
            atualizarNavegacao(idCarrossel, false);
            return;
        }

        carrossel.replaceChildren(...eventos.map(criarCardEvento));
        atualizarNavegacao(idCarrossel, eventos.length > 1);
    }

    function eventoPertenceASecao(evento, secao) {
        const categoria = normalizarTexto(evento.categoria_evento);

        if (!categoria) {
            return false;
        }

        const possuiTermo = secao.termos.some((termo) => categoria.includes(termo));
        const possuiExclusao = (secao.excluir || []).some((termo) => categoria.includes(termo));
        return possuiTermo && !possuiExclusao;
    }

    function mostrarErro(mensagem) {
        mostrarBannerVazio(mensagem);

        secoesCategorias.forEach((secao) => {
            document.getElementById(secao.id)?.replaceChildren(criarEstado(mensagem, true));
            atualizarNavegacao(secao.id, false);
        });
    }

    async function carregarEventos() {
        try {
            const resposta = await fetch('api/eventos', {
                headers: {Accept: 'application/json'}
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível carregar os eventos.');
            }

            if (!Array.isArray(dados.eventos)) {
                throw new Error('A API retornou uma resposta inesperada.');
            }

            renderizarBanner(dados.eventos);

            secoesCategorias.forEach((secao) => {
                const eventosDaSecao = dados.eventos.filter((evento) => eventoPertenceASecao(evento, secao));
                renderizarCarrossel(
                    secao.id,
                    eventosDaSecao,
                    'Nenhum evento cadastrado nesta categoria no momento.'
                );
            });
        } catch (erro) {
            console.error('Falha ao carregar eventos na home:', erro);
            mostrarErro('Não foi possível carregar os eventos. Verifique se a API está disponível e tente novamente.');
        } finally {
            document.querySelectorAll('[data-secao-categoria]').forEach((secao) => {
                secao.setAttribute('aria-busy', 'false');
            });
        }
    }

    function iniciarBotoesCarrossel() {
        document.querySelectorAll('[data-carrossel][data-direcao]').forEach((botao) => {
            botao.addEventListener('click', function () {
                const carrossel = document.getElementById(this.dataset.carrossel);
                const direcao = Number(this.dataset.direcao);

                if (carrossel && Number.isFinite(direcao)) {
                    carrossel.scrollBy({left: 330 * direcao, behavior: 'smooth'});
                }
            });
        });
    }

    iniciarBotoesCarrossel();
    carregarEventos();
}());
