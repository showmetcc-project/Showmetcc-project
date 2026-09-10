(function () {
    'use strict';

    const imagemPadrao = 'assets/img/banner_site_565x235px.png';
    const limiteRecomendados = 5;

    function iniciarBanner() {
        if (typeof window.Swiper !== 'function') {
            return;
        }

        new window.Swiper('.banner.swiper', {
            loop: true,
            autoplay: {
                delay: 4500,
                disableOnInteraction: false
            },
            pagination: {
                el: '.swiper-pagination'
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev'
            }
        });
    }

    function caminhoImagem(caminho) {
        const valor = String(caminho || '').trim();

        if (!valor) {
            return imagemPadrao;
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

    function formatarData(dataEvento) {
        const partes = String(dataEvento || '').split('-').map(Number);

        if (partes.length !== 3 || partes.some((parte) => !Number.isInteger(parte))) {
            return 'Data não informada';
        }

        const data = new Date(partes[0], partes[1] - 1, partes[2]);
        return data.toLocaleDateString('pt-BR');
    }

    function criarLinhaInformacao(classeIcone, texto) {
        const linha = document.createElement('span');
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
            this.src = imagemPadrao;
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
            criarLinhaInformacao('bi bi-geo-alt-fill', local),
            criarLinhaInformacao('bi bi-calendar-event', formatarData(evento.data_evento))
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

    function mostrarErro(mensagem) {
        const recomendados = document.getElementById('recomendados');
        const outrosEventos = document.getElementById('outrosEventos');

        if (recomendados) {
            recomendados.replaceChildren(criarEstado(mensagem, true));
            atualizarNavegacao('recomendados', false);
        }

        if (outrosEventos) {
            outrosEventos.replaceChildren(criarEstado(mensagem, true));
            atualizarNavegacao('outrosEventos', false);
        }
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

            const recomendados = dados.eventos.slice(0, limiteRecomendados);
            const outrosEventos = dados.eventos.slice(limiteRecomendados);

            renderizarCarrossel(
                'recomendados',
                recomendados,
                'Nenhum evento cadastrado no momento.'
            );
            renderizarCarrossel(
                'outrosEventos',
                outrosEventos,
                'Nenhum outro evento cadastrado no momento.'
            );
        } catch (erro) {
            console.error('Falha ao carregar eventos na home:', erro);
            mostrarErro('Não foi possível carregar os eventos. Verifique se a API está disponível e tente novamente.');
        } finally {
            document.getElementById('secaoRecomendados')?.setAttribute('aria-busy', 'false');
            document.getElementById('secaoOutrosEventos')?.setAttribute('aria-busy', 'false');
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

    iniciarBanner();
    iniciarBotoesCarrossel();
    carregarEventos();
}());
