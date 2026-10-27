(function () {
    'use strict';

    const imagemPadraoCard = 'assets/img/bannerEventoPadrao.png';
    const PRECO_MAXIMO_FILTRO = 2000;
    const limiteBanner = 5;
    let bannerSwiper = null;
    let requisicaoBusca = null;
    let eventosCarregados = [];
    let temporizadorRedimensionamento = null;
    const carrosseisCategorias = new Map();

    const secoesCategorias = [
        { id: 'emBreve', categoria: null, termos: [] },
        {
            id: 'musicaShows',
            categoria: 'Música e Shows',
            termos: ['musica e shows', 'musica', 'show', 'festival', 'concerto'],
        },
        {
            id: 'cinemaAudiovisual',
            categoria: 'Cinema e Audiovisual',
            termos: ['cinema e audiovisual', 'cinema', 'audiovisual', 'filme'],
        },
        { id: 'teatroDanca', categoria: 'Teatro e Dança', termos: ['teatro e danca', 'teatro', 'danca'] },
        {
            id: 'artesVisuais',
            categoria: 'Artes Visuais e Exposições',
            termos: ['artes visuais e exposicoes', 'artes visuais', 'exposicao', 'exposicoes'],
        },
        { id: 'literatura', categoria: 'Literatura', termos: ['literatura', 'literario', 'livro', 'leitura'] },
        { id: 'gastronomia', categoria: 'Gastronomia', termos: ['gastronomia', 'gastronomico'] },
        {
            id: 'oficinasWorkshops',
            categoria: 'Oficinas e Workshops',
            termos: ['oficinas e workshops', 'oficina', 'workshop', 'workshops'],
        },
        {
            id: 'culturaPopular',
            categoria: 'Tradições Regionais',
            termos: ['tradicoes regionais', 'tradicoes', 'regional'],
        },
        {
            id: 'geekCulturaPop',
            categoria: 'Geek e Cultura Pop',
            termos: ['geek e cultura pop', 'geek', 'cultura pop'],
            excluir: ['popular e regional'],
        },
    ];

    function normalizarTexto(valor) {
        return String(valor || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim()
            .toLowerCase();
    }

    function normalizarClassificacoes(valor) {
        const recebidas = Array.isArray(valor) ? valor : String(valor || '').split(',');
        const normalizadas = recebidas.map((item) => String(item || '').trim()).filter(Boolean);

        return [...new Set(normalizadas)];
    }

    function caminhoImagem(caminho, usarPadrao = true) {
        const valor = String(caminho || '').trim();

        if (!valor) {
            return usarPadrao ? imagemPadraoCard : null;
        }

        if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/') || valor.includes('\\')) {
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
            autoplay: quantidade > 1 ? { delay: 4500, disableOnInteraction: false } : false,
            pagination: {
                el: '.banner .swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.banner .swiper-button-next',
                prevEl: '.banner .swiper-button-prev',
            },
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
        imagem.alt = window.ShowMeA11y.textoAltImagemEvento(evento);
        imagem.addEventListener(
            'error',
            function () {
                slide.remove();
                const quantidadeRestante = document.querySelectorAll('#bannerEventos .swiper-slide').length;

                if (quantidadeRestante === 0) {
                    mostrarBannerVazio('Nenhum evento em destaque no momento.');
                    return;
                }

                bannerSwiper?.update();
                atualizarControlesBanner(quantidadeRestante);
            },
            { once: true },
        );

        link.append(imagem);
        slide.append(link);
        return slide;
    }

    function renderizarBanner(eventos) {
        const wrapper = document.getElementById('bannerEventos');

        if (!wrapper) {
            return;
        }

        const slides = eventos.map(criarSlideBanner).filter(Boolean).slice(0, limiteBanner);

        if (slides.length === 0) {
            mostrarBannerVazio('Nenhum evento em destaque no momento.');
            return;
        }

        wrapper.replaceChildren(...slides);
        iniciarBanner(slides.length);
        document.querySelector('.banner')?.setAttribute('aria-busy', 'false');
    }

    function formatarData(dataEvento) {
        const partes = String(dataEvento || '')
            .split('-')
            .map(Number);

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

    // Converte a classificação oficial no par usado pelo selo oficial de
    // classificação indicativa: "Livre" -> "L" (verde) e "18 anos" -> "18".
    function dadosClassificacaoIndicativa(valor) {
        const texto = String(valor || '').trim();

        if (!texto) {
            return null;
        }

        if (texto.toLowerCase() === 'livre') {
            return { chave: 'livre', rotulo: 'L' };
        }

        const numero = texto.match(/\d+/);
        return numero ? { chave: numero[0], rotulo: numero[0] } : { chave: 'livre', rotulo: texto };
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

        const selo = document.createElement('span');
        const classificacaoIndicativa = String(evento.classificacao_indicativa || '').trim();
        const dadosSelo = dadosClassificacaoIndicativa(classificacaoIndicativa);
        selo.className = 'selo-classificacao';
        selo.hidden = !dadosSelo;
        if (dadosSelo) {
            selo.dataset.classificacao = dadosSelo.chave;
            selo.textContent = dadosSelo.rotulo;
            window.ShowMeA11y.aplicarSemanticaClassificacao(selo, classificacaoIndicativa);
        }

        const imagem = document.createElement('img');
        imagem.src = caminhoImagem(evento.imagem_evento);
        imagem.alt = window.ShowMeA11y.textoAltImagemEvento(evento);
        imagem.loading = 'lazy';
        imagem.addEventListener(
            'error',
            function () {
                this.src = imagemPadraoCard;
            },
            { once: true },
        );

        const conteudo = document.createElement('div');
        conteudo.className = 'card-conteudo';

        const titulo = document.createElement('h4');
        titulo.textContent = evento.nome_evento || 'Evento sem nome';

        const informacoes = document.createElement('div');
        informacoes.className = 'info-evento';

        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        const local = cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : evento.endereco_evento || 'Endereço não informado';

        informacoes.append(
            criarLinhaInformacao('bi bi-geo-alt-fill', local, 'evento-localizacao'),
            criarLinhaInformacao(
                'bi bi-calendar-event',
                formatarData(proximaSessaoEvento(evento)?.data_evento || evento.data_evento),
                'evento-data',
            ),
        );
        conteudo.append(titulo, informacoes);
        link.append(selo, badge, imagem, conteudo);

        card.append(link);
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
            termoElemento.textContent = termo ? `“${termo}”` : 'eventos filtrados';
        }

        if (resumo) {
            resumo.textContent =
                quantidade === null
                    ? 'Buscando eventos...'
                    : `${quantidade} ${quantidade === 1 ? 'evento encontrado' : 'eventos encontrados'}`;
        }
    }

    function dataLocalISO(data = new Date()) {
        const ano = data.getFullYear();
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const dia = String(data.getDate()).padStart(2, '0');
        return `${ano}-${mes}-${dia}`;
    }

    function sessoesDoEvento(evento) {
        const sessoes = Array.isArray(evento.sessoes) ? evento.sessoes : [];

        return sessoes
            .filter((sessao) => sessao && sessao.data_evento)
            .slice()
            .sort((a, b) =>
                `${a.data_evento} ${a.hora_inicio || ''}`.localeCompare(`${b.data_evento} ${b.hora_inicio || ''}`),
            );
    }

    function datasSessoesEvento(evento) {
        const datas = sessoesDoEvento(evento)
            .map((sessao) => String(sessao.data_evento).slice(0, 10))
            .filter(Boolean);

        if (datas.length) {
            return datas;
        }

        const dataEvento = String(evento.data_evento || '').slice(0, 10);
        return dataEvento ? [dataEvento] : [];
    }

    function proximaSessaoEvento(evento) {
        const sessoes = sessoesDoEvento(evento);
        const hoje = dataLocalISO();

        return sessoes.find((sessao) => String(sessao.data_evento).slice(0, 10) >= hoje) || sessoes[0] || null;
    }

    function opcoesDeCidade(eventos) {
        const cidades = new Map();
        eventos.forEach((evento) => {
            const cidade = String(evento.cidade_evento || '').trim();
            const uf = String(evento.uf || '')
                .trim()
                .toUpperCase();
            if (!cidade) return;
            const chave = `${cidade}|${uf}`;
            cidades.set(normalizarTexto(chave), { cidade, uf });
        });
        return [...cidades.values()].sort((a, b) => a.cidade.localeCompare(b.cidade, 'pt-BR'));
    }

    function filtrarEventos(eventos, filtros) {
        const hoje = dataLocalISO();
        const base = new Date(`${hoje}T12:00:00`);
        const diaSemana = base.getDay();

        const amanhaData = new Date(base);
        amanhaData.setDate(base.getDate() + 1);
        const amanha = dataLocalISO(amanhaData);

        // Fim de semana: sábado e domingo do fim de semana corrente.
        // No domingo, considera apenas o próprio dia.
        const inicioFimDeSemanaData = new Date(base);
        const fimFimDeSemanaData = new Date(base);
        if (diaSemana === 0) {
            inicioFimDeSemanaData.setDate(base.getDate());
            fimFimDeSemanaData.setDate(base.getDate());
        } else {
            const diasAteSabado = (6 - diaSemana + 7) % 7;
            inicioFimDeSemanaData.setDate(base.getDate() + diasAteSabado);
            fimFimDeSemanaData.setDate(base.getDate() + diasAteSabado + 1);
        }
        const inicioFimDeSemana = dataLocalISO(inicioFimDeSemanaData);
        const fimFimDeSemana = dataLocalISO(fimFimDeSemanaData);

        // Nesta semana: de hoje até o próximo domingo.
        const fimNestaSemanaData = new Date(base);
        fimNestaSemanaData.setDate(base.getDate() + ((7 - diaSemana) % 7));
        const fimNestaSemana = dataLocalISO(fimNestaSemanaData);

        // Próxima semana: segunda a domingo seguintes.
        const inicioProximaSemanaData = new Date(base);
        inicioProximaSemanaData.setDate(base.getDate() + ((1 - diaSemana + 7) % 7 || 7));
        const fimProximaSemanaData = new Date(inicioProximaSemanaData);
        fimProximaSemanaData.setDate(inicioProximaSemanaData.getDate() + 6);
        const inicioProximaSemana = dataLocalISO(inicioProximaSemanaData);
        const fimProximaSemana = dataLocalISO(fimProximaSemanaData);

        // Neste mês: de hoje até o último dia do mês corrente.
        const fimNesteMes = dataLocalISO(new Date(base.getFullYear(), base.getMonth() + 1, 0));

        // Próximo mês: primeiro ao último dia do mês seguinte.
        const inicioProximoMes = dataLocalISO(new Date(base.getFullYear(), base.getMonth() + 1, 1));
        const fimProximoMes = dataLocalISO(new Date(base.getFullYear(), base.getMonth() + 2, 0));

        const cidadeSelecionada = normalizarTexto(filtros.cidade || '');
        const categoriaSelecionada = normalizarTexto(filtros.categoria || '');
        const classificacoesSelecionadas = normalizarClassificacoes(filtros.classificacoes || []).map(normalizarTexto);
        const precoMinimo = Number.isFinite(Number(filtros.precoMin)) ? Math.max(0, Number(filtros.precoMin)) : 0;
        const precoMaximo = Number.isFinite(Number(filtros.precoMax))
            ? Math.max(0, Math.min(PRECO_MAXIMO_FILTRO, Number(filtros.precoMax)))
            : PRECO_MAXIMO_FILTRO;

        return eventos.filter((evento) => {
            if (filtros.preco === 'gratuitos' && !Boolean(evento.gratuidade)) return false;
            if (filtros.preco === 'pagos' && Boolean(evento.gratuidade)) return false;

            if (classificacoesSelecionadas.length > 0) {
                const classificacaoEvento = normalizarTexto(evento.classificacao_indicativa);
                if (!classificacoesSelecionadas.includes(classificacaoEvento)) return false;
            }

            if (
                filtros.uf &&
                String(evento.uf || '')
                    .trim()
                    .toUpperCase() !== filtros.uf
            )
                return false;
            if (cidadeSelecionada && normalizarTexto(evento.cidade_evento) !== cidadeSelecionada) return false;
            if (precoMinimo > 0) {
                if (Boolean(evento.gratuidade)) return false;
                const valorMaximoEvento = Number(evento.valor_ingresso_maximo ?? evento.valor_ingresso_minimo ?? 0);
                if (!Number.isFinite(valorMaximoEvento) || valorMaximoEvento < precoMinimo) return false;
            }
            if (
                !Boolean(evento.gratuidade) &&
                precoMaximo < PRECO_MAXIMO_FILTRO &&
                Number(evento.valor_ingresso_minimo || 0) > precoMaximo
            )
                return false;

            if (categoriaSelecionada) {
                const categoriasEvento = normalizarTexto(evento.categoria_evento)
                    .split(',')
                    .map((item) => item.trim());
                if (!categoriasEvento.includes(categoriaSelecionada)) return false;
            }

            const datasEvento = datasSessoesEvento(evento);
            const possuiDataEntre = (inicio, fim) => datasEvento.some((data) => data >= inicio && data <= fim);

            if (filtros.data === 'hoje' && !datasEvento.includes(hoje)) return false;
            if (filtros.data === 'amanha' && !datasEvento.includes(amanha)) return false;
            if (filtros.data === 'fim-de-semana' && !possuiDataEntre(inicioFimDeSemana, fimFimDeSemana)) return false;
            if (filtros.data === 'nesta-semana' && !possuiDataEntre(hoje, fimNestaSemana)) return false;
            if (filtros.data === 'proxima-semana' && !possuiDataEntre(inicioProximaSemana, fimProximaSemana))
                return false;
            if (filtros.data === 'neste-mes' && !possuiDataEntre(hoje, fimNesteMes)) return false;
            if (filtros.data === 'proximo-mes' && !possuiDataEntre(inicioProximoMes, fimProximoMes)) return false;
            return true;
        });
    }

    async function buscarEventosNaHome(termoInformado, filtrosInformados = {}) {
        const termo = String(termoInformado || '').trim();
        const preco = ['todos', 'gratuitos', 'pagos'].includes(filtrosInformados.preco)
            ? filtrosInformados.preco
            : 'todos';
        const precoMaxInformado = Number(filtrosInformados.precoMax);
        const precoMax =
            preco === 'gratuitos'
                ? PRECO_MAXIMO_FILTRO
                : Number.isFinite(precoMaxInformado)
                  ? Math.max(0, Math.min(PRECO_MAXIMO_FILTRO, precoMaxInformado))
                  : PRECO_MAXIMO_FILTRO;
        const precoMinInformado = Number(filtrosInformados.precoMin);
        const precoMin =
            preco === 'gratuitos'
                ? 0
                : Number.isFinite(precoMinInformado)
                  ? Math.max(0, Math.min(precoMax, precoMinInformado))
                  : 0;
        const filtros = {
            categoria: String(filtrosInformados.categoria || ''),
            classificacoes: normalizarClassificacoes(
                filtrosInformados.classificacoes || filtrosInformados.classificacao || [],
            ),
            preco,
            cidade: String(filtrosInformados.cidade || ''),
            uf: String(filtrosInformados.uf || '').toUpperCase(),
            data: [
                'qualquer',
                'hoje',
                'amanha',
                'fim-de-semana',
                'nesta-semana',
                'proxima-semana',
                'neste-mes',
                'proximo-mes',
            ].includes(filtrosInformados.data)
                ? filtrosInformados.data
                : 'qualquer',
            precoMin,
            precoMax,
        };

        const possuiFiltros =
            filtros.categoria !== '' ||
            filtros.classificacoes.length > 0 ||
            filtros.preco !== 'todos' ||
            filtros.cidade !== '' ||
            filtros.uf !== '' ||
            filtros.data !== 'qualquer' ||
            filtros.precoMin > 0 ||
            filtros.precoMax < PRECO_MAXIMO_FILTRO;
        if (!termo && !possuiFiltros) {
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
        document.title = termo ? `Busca: ${termo} | ShowMe` : 'Eventos filtrados | ShowMe';
        grade.setAttribute('aria-busy', 'true');

        const carregando = document.createElement('div');
        carregando.className = 'resultados-busca-home-estado';
        carregando.innerHTML = '<span class="spinner-border" aria-hidden="true"></span><p>Buscando eventos...</p>';
        grade.replaceChildren(carregando);

        try {
            const parametros = new URLSearchParams();
            if (termo) parametros.set('busca', termo);
            const query = parametros.toString();
            const resposta = await fetch(`api/eventos${query ? `?${query}` : ''}`, {
                headers: { Accept: 'application/json' },
                signal: controleDaBusca.signal,
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível concluir a busca.');
            }

            const eventos = Array.isArray(dados.eventos) ? dados.eventos : [];
            window.dispatchEvent(
                new CustomEvent('showme:opcoes-filtro-cidade', {
                    detail: { opcoes: opcoesDeCidade(eventos) },
                }),
            );
            const eventosFiltrados = filtrarEventos(eventos, filtros);
            atualizarCabecalhoBusca(termo, eventosFiltrados.length);

            if (eventosFiltrados.length === 0) {
                const mensagem = termo
                    ? `Nenhum evento encontrado para “${termo}” com esses filtros. Tente ajustar a busca.`
                    : 'Nenhum evento corresponde aos filtros selecionados.';
                grade.replaceChildren(criarEstadoBusca(mensagem));
                return;
            }

            grade.replaceChildren(...eventosFiltrados.map(criarCardEvento));
        } catch (erro) {
            if (erro.name !== 'AbortError') {
                console.error('Falha na busca de eventos:', erro);
                grade.replaceChildren(
                    criarEstadoBusca('Não foi possível buscar os eventos agora. Tente novamente.', true),
                );
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
        const larguraSlide = window.innerWidth < 768 ? Math.min(window.innerWidth * 0.72, 300) : 300;
        const quantidadeQueCabe = Math.max(1, Math.floor((carrossel.clientWidth + 16) / (larguraSlide + 16)));
        const poucosItens = quantidade <= quantidadeQueCabe;

        return {
            quantidadeQueCabe,
            poucosItens,
            usarLoop: !poucosItens,
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

        const { poucosItens, usarLoop } = calcularOcupacaoCarrossel(carrossel, quantidade);
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
                onlyInViewport: true,
            },
            a11y: {
                enabled: true,
            },
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

        const { quantidadeQueCabe, usarLoop } = calcularOcupacaoCarrossel(carrossel, eventos.length);
        const quantidadeMinimaParaLoop = (quantidadeQueCabe + 1) * 2;
        const repeticoes = usarLoop ? Math.max(2, Math.ceil(quantidadeMinimaParaLoop / eventos.length)) : 1;
        const eventosVisuais = Array.from({ length: repeticoes }, () => eventos).flat();
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
            const hoje = dataLocalISO();
            return datasSessoesEvento(evento).some((data) => data >= hoje);
        }

        const categoria = normalizarTexto(evento.categoria_evento);

        if (!categoria) {
            return false;
        }

        const possuiTermo = secao.termos.some((termo) => categoria.includes(termo));
        const possuiExclusao = (secao.excluir || []).some((termo) => categoria.includes(termo));
        return possuiTermo && !possuiExclusao;
    }

    function eventosDaSecao(eventos, secao) {
        const filtrados = eventos.filter((evento) => eventoPertenceASecao(evento, secao));

        if (secao.id !== 'emBreve') {
            return filtrados;
        }

        // "Em Breve" sempre ordenado pelas datas mais próximas.
        return filtrados.slice().sort((a, b) => {
            const dataA = proximaSessaoEvento(a)?.data_evento || a.data_evento || '';
            const dataB = proximaSessaoEvento(b)?.data_evento || b.data_evento || '';
            return String(dataA).localeCompare(String(dataB));
        });
    }

    function pesoDaSecao(secao, categoriasRecomendadas) {
        if (secao.id === 'emBreve') {
            return Number.POSITIVE_INFINITY;
        }

        return categoriasRecomendadas.reduce(function (total, item) {
            const categoria = normalizarTexto(item.categoria);
            if (!categoria) return total;

            const possuiTermo = secao.termos.some((termo) => categoria.includes(termo));
            const possuiExclusao = (secao.excluir || []).some((termo) => categoria.includes(termo));

            return total + (possuiTermo && !possuiExclusao ? Number(item.peso) || 0 : 0);
        }, 0);
    }

    function reordenarSecoesPorRecomendacao(recomendacoes, eventos) {
        if (!recomendacoes?.personalizado) {
            return;
        }

        const categorias = Array.isArray(recomendacoes.categorias) ? recomendacoes.categorias : [];

        if (categorias.length === 0) {
            return;
        }

        const secoesComEventos = new Set();
        secoesCategorias.forEach(function (secao) {
            if (secao.id !== 'emBreve' && eventosDaSecao(eventos, secao).length > 0) {
                secoesComEventos.add(secao.id);
            }
        });

        const ordemOriginal = new Map(secoesCategorias.map((secao, indice) => [secao.id, indice]));
        const ordenadas = secoesCategorias.slice().sort(function (a, b) {
            if (a.id === 'emBreve') return -1;
            if (b.id === 'emBreve') return 1;

            const pesoA = secoesComEventos.has(a.id) ? pesoDaSecao(a, categorias) : 0;
            const pesoB = secoesComEventos.has(b.id) ? pesoDaSecao(b, categorias) : 0;
            const diferenca = pesoB - pesoA;
            return diferenca || ordemOriginal.get(a.id) - ordemOriginal.get(b.id);
        });
        const main = document.querySelector('main.inicio');

        if (!main) {
            return;
        }

        ordenadas.forEach(function (secao) {
            const elemento = document.getElementById(`secao${secao.id}`);
            if (elemento) main.append(elemento);
        });
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
                headers: { Accept: 'application/json' },
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível carregar os eventos.');
            }

            if (!Array.isArray(dados.eventos)) {
                throw new Error('A API retornou uma resposta inesperada.');
            }

            eventosCarregados = dados.eventos;
            window.dispatchEvent(
                new CustomEvent('showme:opcoes-filtro-cidade', {
                    detail: { opcoes: opcoesDeCidade(dados.eventos) },
                }),
            );

            renderizarBanner(dados.eventos);

            secoesCategorias.forEach((secao) => {
                const listaEventos = eventosDaSecao(dados.eventos, secao);
                renderizarCarrossel(secao.id, listaEventos, 'Nenhum evento cadastrado nesta categoria no momento.');
            });

            reordenarSecoesPorRecomendacao(dados.recomendacoes, dados.eventos);
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
        buscarEventosNaHome(evento.detail?.termo || '', evento.detail?.filtros || {});
    });

    window.addEventListener('resize', () => {
        window.clearTimeout(temporizadorRedimensionamento);
        temporizadorRedimensionamento = window.setTimeout(() => {
            if (!eventosCarregados.length) return;
            secoesCategorias.forEach((secao) => {
                renderizarCarrossel(
                    secao.id,
                    eventosDaSecao(eventosCarregados, secao),
                    'Nenhum evento cadastrado nesta categoria no momento.',
                );
            });
        }, 180);
    });

    const parametrosBusca = new URL(window.location.href).searchParams;
    buscarEventosNaHome(parametrosBusca.get('busca') || '', {
        categoria: parametrosBusca.get('categoria') || '',
        classificacao: parametrosBusca.get('classificacao') || '',
        preco: parametrosBusca.get('preco') || 'todos',
        cidade: parametrosBusca.get('cidade') || '',
        uf: parametrosBusca.get('uf') || '',
        data: parametrosBusca.get('data') || 'qualquer',
        precoMin: parametrosBusca.get('preco_min') || 0,
        precoMax: parametrosBusca.get('preco_max') || PRECO_MAXIMO_FILTRO,
    });
})();
