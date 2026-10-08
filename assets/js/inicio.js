(function () {
    'use strict';

    const imagemPadraoCard = 'assets/img/bannerEventoPadrao.png';
    const limiteBanner = 5;
    let bannerSwiper = null;
    let requisicaoBusca = null;
    let eventosCarregados = [];
    let temporizadorRedimensionamento = null;
    const carrosseisCategorias = new Map();

    const secoesCategorias = [
        {id: 'eventosMusicais', termos: ['musica', 'musical', 'show', 'festival', 'concerto']},
        {id: 'pertoVoce', termos: ['perto de voce', 'local', 'regional']},
        {id: 'cinema', termos: ['cinema', 'filme', 'mostra cinematografica']},
        {id: 'workshops', termos: ['workshop']},
        {id: 'oficinas', termos: ['oficina']},
        {id: 'gastronomicos', termos: ['gastronomico', 'gastronomia']},
        {id: 'literatura', termos: ['literatura', 'literario', 'livro', 'leitura']},
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
            : (evento.endereco_evento || 'Endereço não informado');

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

    function criarEstadoBusca(mensagem, erro = false) {
        const estado = criarEstado(mensagem, erro);
        estado.classList.add('resultados-busca-home-estado');
        return estado;
    }

    function alternarVisaoHome(exibirBusca) {
        document.querySelectorAll('[data-visao-home="normal"]').forEach((elemento) => {
            elemento.hidden = exibirBusca;
        });

        const resultados = document.getElementById('resultadosBuscaHome');

        if (resultados) {
            resultados.hidden = !exibirBusca;
        }
    }

    function mostrarVisaoNormal() {
        requisicaoBusca?.abort();
        requisicaoBusca = null;
        alternarVisaoHome(false);
        document.title = 'ShowMe';
    }

    function atualizarCabecalhoBusca(termo, quantidade = null) {
        const termoElemento = document.getElementById('termoBuscaHome');
        const resumo = document.getElementById('resumoBuscaHome');

        if (termoElemento) {
            termoElemento.textContent = `“${termo}”`;
        }

        if (resumo) {
            resumo.textContent = quantidade === null
                ? 'Buscando eventos...'
                : `${quantidade} ${quantidade === 1 ? 'evento encontrado' : 'eventos encontrados'}`;
        }
    }

    async function buscarEventosNaHome(termoInformado) {
        const termo = String(termoInformado || '').trim();

        if (!termo) {
            mostrarVisaoNormal();
            return;
        }

        const grade = document.getElementById('resultadosBuscaHomeGrid');

        if (!grade) {
            return;
        }

        requisicaoBusca?.abort();
        requisicaoBusca = new AbortController();
        const controleDaBusca = requisicaoBusca;
        alternarVisaoHome(true);
        atualizarCabecalhoBusca(termo);
        document.title = `Busca: ${termo} | ShowMe`;
        grade.setAttribute('aria-busy', 'true');

        const carregando = document.createElement('div');
        carregando.className = 'resultados-busca-home-estado';
        carregando.innerHTML = '<span class="spinner-border" aria-hidden="true"></span><p>Buscando eventos...</p>';
        grade.replaceChildren(carregando);

        try {
            const parametros = new URLSearchParams({busca: termo});
            const resposta = await fetch(`api/eventos?${parametros.toString()}`, {
                headers: {Accept: 'application/json'},
                signal: controleDaBusca.signal
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível concluir a busca.');
            }

            const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];
            atualizarCabecalhoBusca(termo, eventos.length);

            if (eventos.length === 0) {
                grade.replaceChildren(criarEstadoBusca(`Nenhum evento encontrado para “${termo}”. Tente outra busca.`));
                return;
            }

            grade.replaceChildren(...eventos.map(criarCardEvento));
        } catch (erro) {
            if (erro.name !== 'AbortError') {
                console.error('Falha na busca de eventos:', erro);
                grade.replaceChildren(criarEstadoBusca('Não foi possível buscar os eventos agora. Tente novamente.', true));
                atualizarCabecalhoBusca(termo, 0);
            }
        } finally {
            if (requisicaoBusca === controleDaBusca) {
                grade.setAttribute('aria-busy', 'false');
            }
        }
    }

    function atualizarNavegacao(idCarrossel, possuiEventos) {
        document.querySelectorAll(`[data-carrossel="${idCarrossel}"]`).forEach((botao) => {
            botao.hidden = !possuiEventos;
        });
    }

    function destruirCarrosselCategoria(idCarrossel) {
        const instancia = carrosseisCategorias.get(idCarrossel);
        if (instancia) {
            instancia.destroy(true, true);
            carrosseisCategorias.delete(idCarrossel);
        }
    }

    function calcularOcupacaoCarrossel(carrossel, quantidade) {
        const larguraSlide = window.innerWidth < 768
            ? Math.min(window.innerWidth * 0.72, 300)
            : 300;
        const quantidadeQueCabe = Math.max(
            1,
            Math.floor((carrossel.clientWidth + 16) / (larguraSlide + 16))
        );
        const poucosItens = quantidade <= quantidadeQueCabe;

        return {
            quantidadeQueCabe,
            poucosItens,
            usarLoop: !poucosItens
        };
    }

    function navegarCarrosselCategoria(instancia, direcao) {
        if (!instancia || instancia.destroyed || instancia.slides.length < 2) return;

        if (instancia.params.loop) {
            if (direcao > 0) {
                instancia.slideNext();
            } else {
                instancia.slidePrev();
            }
            return;
        }

        const trilho = instancia.wrapperEl;
        const estaNoFim = direcao > 0 && instancia.isEnd;
        const estaNoInicio = direcao < 0 && instancia.isBeginning;

        if (estaNoFim) {
            const primeiroSlide = trilho.firstElementChild;
            if (!primeiroSlide) return;

            const indiceAtual = instancia.activeIndex;
            trilho.append(primeiroSlide);
            instancia.update();
            instancia.slideTo(Math.max(0, indiceAtual - 1), 0, false);

            window.requestAnimationFrame(() => instancia.slideNext());
            return;
        }

        if (estaNoInicio) {
            const ultimoSlide = trilho.lastElementChild;
            if (!ultimoSlide) return;

            const indiceAtual = instancia.activeIndex;
            trilho.prepend(ultimoSlide);
            instancia.update();
            instancia.slideTo(Math.min(instancia.slides.length - 1, indiceAtual + 1), 0, false);

            window.requestAnimationFrame(() => instancia.slidePrev());
            return;
        }

        if (direcao > 0) {
            instancia.slideNext();
        } else {
            instancia.slidePrev();
        }
    }

    function iniciarCarrosselCategoria(idCarrossel, quantidade) {
        const carrossel = document.getElementById(idCarrossel);
        if (!carrossel) return;

        const {poucosItens, usarLoop} = calcularOcupacaoCarrossel(carrossel, quantidade);
        carrossel.classList.toggle('carrossel-poucos-itens', poucosItens);

        if (quantidade < 2 || typeof window.Swiper !== 'function') return;

        const botaoAnterior = document.querySelector(`[data-carrossel="${idCarrossel}"][data-direcao="-1"]`);
        const botaoProximo = document.querySelector(`[data-carrossel="${idCarrossel}"][data-direcao="1"]`);

        const instancia = new window.Swiper(carrossel, {
            slidesPerView: 'auto',
            centeredSlides: !poucosItens,
            centerInsufficientSlides: poucosItens,
            spaceBetween: 16,
            grabCursor: true,
            loop: usarLoop,
            rewind: false,
            loopPreventsSliding: false,
            watchSlidesProgress: true,
            keyboard: {
                enabled: true,
                onlyInViewport: true
            },
            a11y: {
                enabled: true
            }
        });

        /* Os controles ficam fora do elemento Swiper para permanecerem visíveis
           mesmo quando a biblioteca entende que todos os slides cabem na tela. */
        if (botaoAnterior) {
            botaoAnterior.onclick = () => navegarCarrosselCategoria(instancia, -1);
        }
        if (botaoProximo) {
            botaoProximo.onclick = () => navegarCarrosselCategoria(instancia, 1);
        }

        carrosseisCategorias.set(idCarrossel, instancia);
    }

    function renderizarCarrossel(idCarrossel, eventos, mensagemVazia, erro = false) {
        const carrossel = document.getElementById(idCarrossel);

        if (!carrossel) {
            return;
        }

        destruirCarrosselCategoria(idCarrossel);
        carrossel.classList.remove('carrossel-poucos-itens');
        carrossel.classList.remove('carrossel-item-unico');
        let trilho = carrossel.querySelector('.swiper-wrapper');
        if (!trilho) {
            trilho = document.createElement('div');
            trilho.className = 'swiper-wrapper';
            carrossel.replaceChildren(trilho);
        }

        if (eventos.length === 0) {
            const slideEstado = document.createElement('div');
            slideEstado.className = 'swiper-slide estado-carrossel';
            slideEstado.append(criarEstado(mensagemVazia, erro));
            trilho.replaceChildren(slideEstado);
            atualizarNavegacao(idCarrossel, false);
            return;
        }

        carrossel.classList.toggle('carrossel-item-unico', eventos.length === 1);

        const {quantidadeQueCabe, usarLoop} = calcularOcupacaoCarrossel(carrossel, eventos.length);
        const quantidadeMinimaParaLoop = (quantidadeQueCabe + 1) * 2;
        const repeticoes = usarLoop
            ? Math.max(2, Math.ceil(quantidadeMinimaParaLoop / eventos.length))
            : 1;
        const eventosVisuais = Array.from(
            {length: repeticoes},
            () => eventos
        ).flat();
        const slides = eventosVisuais.map((evento, indice) => {
            const slide = document.createElement('div');
            slide.className = 'swiper-slide';
            if (indice >= eventos.length) {
                slide.dataset.carrosselCopia = 'true';
            }
            slide.append(criarCardEvento(evento));
            return slide;
        });

        trilho.replaceChildren(...slides);
        atualizarNavegacao(idCarrossel, eventos.length > 1);
        window.requestAnimationFrame(() => iniciarCarrosselCategoria(idCarrossel, eventos.length));
    }

    function eventoPertenceASecao(evento, secao) {
        if (secao.id === 'emBreve') {
            const partes = String(evento.data_evento || '').split('-').map(Number);
            if (partes.length !== 3 || partes.some((parte) => !Number.isInteger(parte))) return false;
            const dataEvento = new Date(partes[0], partes[1] - 1, partes[2]);
            const hoje = new Date();
            hoje.setHours(0, 0, 0, 0);
            const limite = new Date(hoje);
            limite.setDate(limite.getDate() + 90);
            return dataEvento >= hoje && dataEvento <= limite;
        }

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
            renderizarCarrossel(secao.id, [], mensagem, true);
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

            eventosCarregados = dados.eventos;

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

    carregarEventos();
    window.addEventListener('showme:busca-home', function (evento) {
        buscarEventosNaHome(evento.detail?.termo || '');
    });

    window.addEventListener('resize', () => {
        window.clearTimeout(temporizadorRedimensionamento);
        temporizadorRedimensionamento = window.setTimeout(() => {
            if (!eventosCarregados.length) return;
            secoesCategorias.forEach((secao) => {
                renderizarCarrossel(
                    secao.id,
                    eventosCarregados.filter((evento) => eventoPertenceASecao(evento, secao)),
                    'Nenhum evento cadastrado nesta categoria no momento.'
                );
            });
        }, 180);
    });

    buscarEventosNaHome(new URL(window.location.href).searchParams.get('busca') || '');
}());
