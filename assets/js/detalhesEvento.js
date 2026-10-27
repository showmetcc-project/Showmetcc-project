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
    let planejamentoDoEvento = null;
    let sessoesEvento = [];

    function caminhoImagem(caminho) {
        const valor = String(caminho || '').trim();
        if (!valor) return imagemPadrao;
        if (/^(?:https?:)?\/\//i.test(valor) || valor.startsWith('/') || valor.includes('/') || valor.includes('\\'))
            return valor;
        return `assets/img/${valor}`;
    }
    async function lerJson(resposta) {
        try {
            return await resposta.json();
        } catch (_) {
            return {};
        }
    }
    function formatarData(valor) {
        const partes = String(valor || '')
            .split(/[ T]/)[0]
            .split('-')
            .map(Number);
        return partes.length === 3 && partes.every(Number.isInteger)
            ? new Date(partes[0], partes[1] - 1, partes[2]).toLocaleDateString('pt-BR')
            : 'Data não informada';
    }
    function terminou(dataEvento) {
        const partes = String(dataEvento || '')
            .split('-')
            .map(Number);
        if (partes.length !== 3 || partes.some((n) => !Number.isInteger(n))) return false;
        const evento = new Date(partes[0], partes[1] - 1, partes[2]);
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        return evento < hoje;
    }
    function formatarHora(valor) {
        const partes = String(valor || '').split(':');
        const hora = Number(partes[0]);
        const minuto = Number(partes[1] || 0);

        if (!Number.isInteger(hora)) return '';

        return minuto > 0 ? `${hora}h${String(minuto).padStart(2, '0')}` : `${hora}h`;
    }
    function formatarSessao(sessao) {
        const data = formatarData(sessao?.data_evento);
        const inicio = formatarHora(sessao?.hora_inicio);
        const fim = formatarHora(sessao?.hora_fim);

        if (!inicio) return data;
        return fim ? `${data} — ${inicio} às ${fim}` : `${data} — ${inicio}`;
    }
    function formatarHorarioSessao(sessao) {
        const inicio = formatarHora(sessao?.hora_inicio);
        const fim = formatarHora(sessao?.hora_fim);

        if (!inicio) return 'Horário não informado';
        return fim ? `${inicio} às ${fim}` : inicio;
    }
    function normalizarSessoes(evento) {
        const sessoes = Array.isArray(evento.sessoes) ? evento.sessoes : [];
        const normalizadas = sessoes
            .filter((sessao) => sessao && sessao.data_evento)
            .map((sessao) => ({
                id: Number(sessao.id) || null,
                data_evento: sessao.data_evento,
                hora_inicio: sessao.hora_inicio || '',
                hora_fim: sessao.hora_fim || '',
            }))
            .sort((a, b) => `${a.data_evento} ${a.hora_inicio}`.localeCompare(`${b.data_evento} ${b.hora_inicio}`));

        if (normalizadas.length) return normalizadas;

        return evento.data_evento
            ? [
                  {
                      id: null,
                      data_evento: evento.data_evento,
                      hora_inicio: evento.horario_evento || '',
                      hora_fim: '',
                  },
              ]
            : [];
    }
    function localidade(evento) {
        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        return cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : 'Cidade não informada';
    }
    function formatarValor(valor) {
        return Number(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
    function faixaPreco(evento) {
        const minimo = Number(evento.valor_ingresso_minimo || 0);
        const maximo = Number(evento.valor_ingresso_maximo || 0);
        if (minimo <= 0) return 'Consulte valores';
        if (maximo > minimo) return `${formatarValor(minimo)} – ${formatarValor(maximo)}`;
        return `A partir de ${formatarValor(minimo)}`;
    }
    function mostrarErro(mensagem) {
        const estado = document.getElementById('estadoPagina');
        estado.classList.add('erro');
        estado.replaceChildren();
        const icone = document.createElement('i');
        icone.className = 'bi bi-exclamation-triangle';
        const texto = document.createElement('p');
        texto.textContent = mensagem;
        estado.append(icone, texto);
        document.getElementById('conteudoEvento').hidden = true;
    }
    function configurarImagem(elemento, caminho, alt) {
        elemento.src = caminhoImagem(caminho);
        elemento.alt = alt;
        elemento.onerror = function () {
            this.onerror = null;
            this.src = imagemPadrao;
        };
    }
    function estadoVazio(titulo, descricao) {
        const card = document.createElement('div');
        card.className = 'card-showme';
        const h = document.createElement('h4');
        h.textContent = titulo;
        const p = document.createElement('p');
        p.textContent = descricao;
        card.append(h, p);
        return card;
    }
    function renderizarArtistas(artistas) {
        const lista = document.getElementById('listaArtistas');
        lista.replaceChildren();
        if (!Array.isArray(artistas) || !artistas.length) {
            lista.append(
                estadoVazio(
                    'Quem estará lá ainda não foi informado',
                    'Este evento ainda não possui programação ou participantes cadastrados.',
                ),
            );
            return;
        }
        artistas.forEach((artista) => {
            const card = document.createElement('div');
            card.className = 'card-showme artista-item';
            const bloco = document.createElement('div');
            const h = document.createElement('h4');
            h.textContent = artista.nome_artista || 'Participante';
            bloco.append(h);
            if (artista.genero_artista) {
                const p = document.createElement('p');
                p.textContent = artista.genero_artista;
                bloco.append(p);
            }
            card.append(bloco);
            lista.append(card);
        });
    }
    function partesData(valor) {
        const correspondencia = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(valor || ''));
        if (!correspondencia) return null;

        const ano = Number(correspondencia[1]);
        const mes = Number(correspondencia[2]);
        const dia = Number(correspondencia[3]);
        const data = new Date(ano, mes - 1, dia);

        if (data.getFullYear() !== ano || data.getMonth() !== mes - 1 || data.getDate() !== dia) return null;

        return { ano, mes, dia, timestamp: Date.UTC(ano, mes - 1, dia) };
    }
    function juntarComConjuncao(itens) {
        if (!itens.length) return '';
        if (itens.length === 1) return itens[0];
        if (itens.length === 2) return `${itens[0]} e ${itens[1]}`;
        return `${itens.slice(0, -1).join(', ')} e ${itens[itens.length - 1]}`;
    }
    function textoDatasSessoes(sessoes) {
        const datasPorTimestamp = new Map();

        sessoes.forEach(function (sessao) {
            const data = partesData(sessao.data_evento);
            if (data && !datasPorTimestamp.has(data.timestamp)) datasPorTimestamp.set(data.timestamp, data);
        });

        const datas = Array.from(datasPorTimestamp.values()).sort(function (a, b) {
            return a.timestamp - b.timestamp;
        });

        if (!datas.length) return 'Data não informada';

        const mesmoAno = datas.every((data) => data.ano === datas[0].ano);
        const mesmoMes = mesmoAno && datas.every((data) => data.mes === datas[0].mes);
        const formatarDia = function (data) {
            const dia = String(data.dia).padStart(2, '0');
            if (mesmoMes) return dia;
            const diaMes = `${dia}/${String(data.mes).padStart(2, '0')}`;
            return mesmoAno ? diaMes : `${diaMes}/${data.ano}`;
        };
        const blocos = [];
        let indice = 0;

        while (indice < datas.length) {
            let fim = indice;

            while (fim + 1 < datas.length && datas[fim + 1].timestamp - datas[fim].timestamp === 24 * 60 * 60 * 1000) {
                fim += 1;
            }

            if (fim - indice + 1 >= 3) {
                blocos.push(`${formatarDia(datas[indice])} a ${formatarDia(datas[fim])}`);
            } else {
                for (let atual = indice; atual <= fim; atual += 1) blocos.push(formatarDia(datas[atual]));
            }

            indice = fim + 1;
        }

        const texto = juntarComConjuncao(blocos);

        if (mesmoMes) return `${texto}/${String(datas[0].mes).padStart(2, '0')}/${datas[0].ano}`;
        if (mesmoAno) return `${texto}/${datas[0].ano}`;
        return texto;
    }
    function textoHorariosSessoes(sessoes) {
        const horarios = new Map();

        sessoes.forEach(function (sessao) {
            const horario = formatarHora(sessao.hora_inicio);
            if (horario && !horarios.has(horario)) horarios.set(horario, String(sessao.hora_inicio || ''));
        });

        const ordenados = Array.from(horarios.entries()).sort(function (a, b) {
            return a[1].localeCompare(b[1]);
        });

        return ordenados.length ? ` • ${juntarComConjuncao(ordenados.map(([horario]) => horario))}` : '';
    }
    function resumoSessoes() {
        return `${textoDatasSessoes(sessoesEvento)}${textoHorariosSessoes(sessoesEvento)}`;
    }
    function descricaoSessoes() {
        return sessoesEvento.map(formatarSessao).join('; ');
    }
    function endereco(evento) {
        const cidade = String(evento.cidade_evento || '').trim(),
            uf = String(evento.uf || '').trim();
        if (evento.endereco_evento) {
            const enderecoCompleto = String(evento.endereco_evento).trim();
            const rua = String(evento.rua_evento || '').trim();
            const numero = String(evento.numero_endereco || '').trim();
            if (
                rua &&
                numero &&
                enderecoCompleto.toLocaleLowerCase('pt-BR').startsWith(rua.toLocaleLowerCase('pt-BR'))
            ) {
                const complemento = enderecoCompleto.slice(rua.length).replace(/^\s*,\s*/, '');
                return [rua, numero, complemento].filter(Boolean).join(', ');
            }
            return enderecoCompleto;
        }
        return [evento.rua_evento, evento.numero_endereco, cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : '']
            .filter((p) => String(p || '').trim())
            .join(', ');
    }

    function coordenadasValidas(latitude, longitude) {
        return (
            Number.isFinite(latitude) &&
            Number.isFinite(longitude) &&
            latitude >= -90 &&
            latitude <= 90 &&
            longitude >= -180 &&
            longitude <= 180
        );
    }

    async function localizarPorCep(cep) {
        const cepNumerico = String(cep || '').replace(/\D/g, '');
        if (cepNumerico.length !== 8) return null;

        const resposta = await fetch(`https://brasilapi.com.br/api/cep/v2/${cepNumerico}`, {
            headers: { Accept: 'application/json' },
        });
        if (!resposta.ok) return null;

        const dados = await resposta.json();
        const latitude = Number(dados?.location?.coordinates?.latitude);
        const longitude = Number(dados?.location?.coordinates?.longitude);
        return coordenadasValidas(latitude, longitude) ? { latitude, longitude } : null;
    }

    async function localizarPorEndereco(evento) {
        const consulta = [evento.rua_evento, evento.numero_endereco, evento.cidade_evento, evento.uf, 'Brasil']
            .filter((parte) => String(parte || '').trim())
            .join(', ');
        if (!consulta) return null;

        const qs = new URLSearchParams({ format: 'json', limit: '1', countrycodes: 'br', q: consulta });
        const resposta = await fetch(`https://nominatim.openstreetmap.org/search?${qs}`, {
            headers: { Accept: 'application/json' },
        });
        if (!resposta.ok) return null;

        const dados = await resposta.json();
        if (!Array.isArray(dados) || !dados.length) return null;
        const latitude = Number(dados[0].lat);
        const longitude = Number(dados[0].lon);
        return coordenadasValidas(latitude, longitude) ? { latitude, longitude } : null;
    }

    async function carregarMapa(evento) {
        const alvo = document.getElementById('mapaEventoBanco'),
            texto = endereco(evento);
        if (!texto) {
            alvo.textContent = 'Localização não disponível.';
            return;
        }
        if (typeof window.L === 'undefined') {
            alvo.textContent = 'Não foi possível iniciar o mapa.';
            return;
        }
        alvo.textContent = 'Carregando mapa...';
        try {
            const coordenadas = (await localizarPorEndereco(evento)) || (await localizarPorCep(evento.cep_evento));
            if (!coordenadas) throw new Error('Endereço não localizado.');

            alvo.replaceChildren();
            if (mapaAtual) mapaAtual.remove();
            mapaAtual = window.L.map('mapaEventoBanco').setView([coordenadas.latitude, coordenadas.longitude], 15);
            window.L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 20,
            }).addTo(mapaAtual);
            window.L.marker([coordenadas.latitude, coordenadas.longitude])
                .addTo(mapaAtual)
                .bindPopup(texto)
                .openPopup();
            window.setTimeout(() => mapaAtual?.invalidateSize(), 0);
        } catch (erro) {
            console.error('Falha ao carregar o mapa do evento:', erro);
            alvo.textContent = 'Não foi possível localizar este endereço no mapa.';
        }
    }
    function sessoesDisponiveisParaPlanejamento() {
        return sessoesEvento.filter((sessao) => sessao.id && !terminou(sessao.data_evento));
    }

    function irParaPlanejamentoComSessao(sessao) {
        if (!sessao?.id) return false;
        window.location.href = `planejamento.php?id=${idEvento}&sessao_id=${sessao.id}`;
        return true;
    }

    function abrirSelecaoOuContinuar() {
        const disponiveis = sessoesDisponiveisParaPlanejamento();

        if (disponiveis.length === 1 && irParaPlanejamentoComSessao(disponiveis[0])) return;

        abrirModalSelecaoSessao();
    }

    function abrirModalSelecaoSessao() {
        const modal = document.getElementById('modalSelecaoSessao');
        const opcoes = document.getElementById('opcoesSessoesEvento');
        const erro = document.getElementById('erroSelecaoSessao');
        const confirmar = document.getElementById('botaoConfirmarSessao');
        const titulo = document.getElementById('tituloModalSelecaoSessao');
        const descricao = document.getElementById('descricaoModalSelecaoSessao');
        const legenda = document.getElementById('legendaSessoesEvento');
        const disponiveis = sessoesDisponiveisParaPlanejamento();
        const datasUnicas = new Set(disponiveis.map((sessao) => sessao.data_evento));
        const possuiDataUnica = datasUnicas.size === 1;

        if (!modal || !opcoes || !erro || !confirmar) return;
        if (modal.open) return;

        if (typeof modal.showModal !== 'function') {
            if (disponiveis[0]) {
                irParaPlanejamentoComSessao(disponiveis[0]);
            }
            return;
        }

        if (titulo) {
            titulo.textContent = !disponiveis.length
                ? 'Escolha a sessão do evento'
                : possuiDataUnica
                  ? 'Escolha o horário da sessão'
                  : 'Escolha a data e o horário';
        }

        if (descricao) {
            descricao.textContent = possuiDataUnica
                ? 'Este evento tem apenas uma data. Selecione o horário da sessão que você vai usar no planejamento.'
                : 'Selecione a data e o horário da sessão que você vai usar no planejamento.';
        }

        if (legenda) {
            legenda.textContent = possuiDataUnica ? 'Horários disponíveis' : 'Sessões disponíveis';
        }

        opcoes.replaceChildren();
        erro.textContent = '';
        confirmar.disabled = true;

        if (!disponiveis.length) {
            const mensagem = document.createElement('p');
            mensagem.className = 'sem-sessoes-disponiveis';
            mensagem.textContent = 'Não há sessões futuras disponíveis para planejamento.';
            opcoes.append(mensagem);
        }

        disponiveis.forEach(function (sessao) {
            const rotulo = document.createElement('label');
            rotulo.className = 'opcao-sessao-evento';

            const radio = document.createElement('input');
            radio.type = 'radio';
            radio.name = 'sessaoSelecionada';
            radio.value = String(sessao.id);

            const texto = document.createElement('span');
            texto.textContent = possuiDataUnica ? formatarHorarioSessao(sessao) : formatarSessao(sessao);

            rotulo.append(radio, texto);
            opcoes.append(rotulo);
        });

        opcoes.querySelectorAll('input[name="sessaoSelecionada"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                confirmar.disabled = false;
                erro.textContent = '';
            });
        });

        modal.showModal();
    }

    function iniciarSelecaoSessao() {
        const modal = document.getElementById('modalSelecaoSessao');
        const formulario = document.getElementById('formSelecaoSessao');
        const erro = document.getElementById('erroSelecaoSessao');

        if (!modal || !formulario) return;

        modal.querySelectorAll('[data-fechar-selecao-sessao]').forEach(function (botao) {
            botao.addEventListener('click', function () {
                modal.close();
            });
        });

        formulario.addEventListener('submit', function (evento) {
            evento.preventDefault();

            const selecionada = formulario.querySelector('input[name="sessaoSelecionada"]:checked');

            if (!selecionada) {
                if (erro) erro.textContent = 'Selecione uma sessão para continuar.';
                formulario.querySelector('input[name="sessaoSelecionada"]')?.focus();
                return;
            }

            window.location.href = `planejamento.php?id=${idEvento}&sessao_id=${selecionada.value}`;
        });
    }

    function iniciarPlanejamento() {
        const link = document.getElementById('linkPlanejamento');

        if (!link) return;

        link.addEventListener('click', function (evento) {
            if (link.getAttribute('aria-disabled') === 'true' || planejamentoDoEvento) return;
            if (!sessoesEvento.length) return;

            evento.preventDefault();
            abrirSelecaoOuContinuar();
        });
    }

    function configurarBotaoPlanejamento() {
        const link = document.getElementById('linkPlanejamento');
        if (!link) return;

        const possuiPlanejamento = Boolean(planejamentoDoEvento);
        const possuiSessaoDisponivel = sessoesDisponiveisParaPlanejamento().length > 0;
        const desabilitado = !possuiPlanejamento && (eventoEncerrado || !possuiSessaoDisponivel);

        link.classList.toggle('btn-planejamento-concluido', possuiPlanejamento);
        link.classList.toggle('acao-desabilitada', desabilitado);
        link.setAttribute('aria-disabled', String(desabilitado));

        if (desabilitado) {
            link.removeAttribute('href');
        } else {
            const parametros = new URLSearchParams({ id: String(idEvento) });

            if (possuiPlanejamento && planejamentoDoEvento?.sessao_id) {
                parametros.set('sessao_id', String(planejamentoDoEvento.sessao_id));
            }

            link.href = `planejamento.php?${parametros}`;
        }

        if (possuiPlanejamento) {
            link.innerHTML =
                '<i class="bi bi-map-fill" aria-hidden="true"></i> Ver meu planejamento';
            link.setAttribute('aria-label', 'Ver meu planejamento deste evento, já planejado');
        } else {
            link.innerHTML = '<i class="bi bi-map-fill" aria-hidden="true"></i> Planejar Viagem';
            link.setAttribute('aria-label', 'Planejar viagem para este evento');
        }
    }

    async function carregarPlanejamentoDoEvento() {
        if (idUsuario < 1 || !idEvento) return;

        try {
            const resposta = await fetch('api/planejamento/', { headers: { Accept: 'application/json' } });
            const dados = await lerJson(resposta);
            if (!resposta.ok) return;

            planejamentoDoEvento =
                (dados.planejamentos || []).find((planejamento) => Number(planejamento.id_evento) === idEvento) || null;
            configurarBotaoPlanejamento();
        } catch (erro) {
            console.error('Falha ao consultar o planejamento do evento:', erro);
        }
    }

    function configurarAcessoEvento(evento) {
        const texto = document.getElementById('textoAcessoEvento');
        const link = document.getElementById('linkAcessoEvento');
        const aviso = document.getElementById('avisoAcessoEvento');
        const gratuito = Boolean(evento.gratuidade);

        link.hidden = true;
        link.removeAttribute('href');
        aviso.hidden = true;

        if (eventoEncerrado) {
            texto.textContent = gratuito
                ? 'Este evento teve entrada gratuita, mas já foi encerrado.'
                : 'As vendas foram encerradas para este evento.';
            return;
        }

        if (gratuito) {
            texto.textContent = 'A entrada para este evento é gratuita.';
            link.textContent = 'Mais informações';
        } else {
            texto.textContent = `Ingressos: ${faixaPreco(evento)}`;
            link.textContent = 'Comprar ingressos';
        }

        if (!evento.link_oficial) return;
        try {
            const url = new URL(evento.link_oficial, location.href);
            if (!['http:', 'https:'].includes(url.protocol)) return;
            link.href = url.href;
            link.hidden = false;
            aviso.hidden = gratuito;
        } catch {
            // URL inválida: mantém o link oculto.
        }
    }
    function renderizarEvento(evento) {
        sessoesEvento = normalizarSessoes(evento);
        const ultimaData = sessoesEvento.length
            ? sessoesEvento[sessoesEvento.length - 1].data_evento
            : evento.data_evento;
        eventoEncerrado = terminou(ultimaData);
        document.title = `${evento.nome_evento || 'Evento'} - ShowMe`;
        const imagem = document.getElementById('imagemEvento');
        configurarImagem(imagem, evento.imagem_evento, window.ShowMeA11y.textoAltImagemEvento(evento));
        imagem.classList.toggle('evento-encerrado-imagem', eventoEncerrado);
        const gratuidade = document.getElementById('badgeGratuidade');
        gratuidade.className = `badge-gratuidade ${evento.gratuidade ? 'gratuito' : 'pago'}`;
        gratuidade.textContent = evento.gratuidade ? 'Gratuito' : 'Pago';
        const badgeClassificacao = document.getElementById('badgeClassificacao');
        const valorClassificacao = String(evento.classificacao_indicativa || '').trim();
        if (valorClassificacao) {
            const chave =
                valorClassificacao.toLocaleLowerCase('pt-BR') === 'livre'
                    ? 'livre'
                    : (valorClassificacao.match(/\d+/) || ['livre'])[0];
            badgeClassificacao.textContent = chave === 'livre' ? 'L' : chave;
            badgeClassificacao.dataset.classificacao = chave;
        }
        window.ShowMeA11y.aplicarSemanticaClassificacao(badgeClassificacao, valorClassificacao);
        badgeClassificacao.hidden = !valorClassificacao;
        document.getElementById('badgeEncerrado').hidden = !eventoEncerrado;
        document.getElementById('tituloEvento').textContent = evento.nome_evento || 'Evento sem nome';
        const infoData = document.getElementById('infoData');
        infoData.querySelector('span').textContent = resumoSessoes();
        const descricaoDatas = descricaoSessoes();
        if (descricaoDatas) {
            infoData.title = descricaoDatas;
            infoData.setAttribute('aria-label', `Datas e horários: ${descricaoDatas}`);
        }
        document.querySelector('#infoCidade span').textContent = localidade(evento);
        const local = document.getElementById('infoLocal');
        local.hidden = true;
        const categoria = document.getElementById('infoCategoria');
        categoria.querySelector('span').textContent = evento.categoria_evento || '';
        categoria.hidden = !evento.categoria_evento;
        const preco = document.getElementById('infoPreco');
        preco.querySelector('span').textContent = faixaPreco(evento);
        preco.hidden = Boolean(evento.gratuidade);
        document.getElementById('descricaoEvento').textContent =
            evento.descricao_evento || 'Este evento ainda não possui uma descrição cadastrada.';
        renderizarArtistas(evento.artistas);
        document.getElementById('nomeLocal').textContent = evento.endereco_evento || 'Endereço não informado';
        document.getElementById('enderecoLocal').textContent = endereco(evento);
        configurarBotaoPlanejamento();
        definirFavoritosHabilitados(eventoEncerrado);
        if (eventoEncerrado)
            botoesFavorito().forEach((botao) => {
                botao.title = 'Evento encerrado';
            });
        document.getElementById('linkComunidade').href = `comunidadeEvento.php?id=${idEvento}`;
        configurarAcessoEvento(evento);
        document.getElementById('estadoPagina').hidden = true;
        document.getElementById('conteudoEvento').hidden = false;
        carregarMapa(evento);
    }
    function botoesFavorito() {
        return Array.from(document.querySelectorAll('.btn-favoritar'));
    }
    function definirFavoritosHabilitados(desabilitado) {
        botoesFavorito().forEach((botao) => {
            botao.disabled = desabilitado;
        });
    }
    function atualizarFavorito() {
        const ativo = favoritoAtual !== null;
        const rotulo = ativo ? 'Remover dos favoritos' : 'Adicionar aos favoritos';
        botoesFavorito().forEach((botao) => {
            const icone = document.createElement('i');
            icone.className = ativo ? 'bi bi-heart-fill' : 'bi bi-heart';
            icone.setAttribute('aria-hidden', 'true');
            botao.replaceChildren(icone, document.createTextNode(ativo ? ' Favoritado' : ' Favoritar'));
            botao.setAttribute('aria-pressed', String(ativo));
            botao.setAttribute('aria-label', rotulo);
            botao.title = rotulo;
        });
    }
    async function carregarFavorito() {
        if (idUsuario < 1 || eventoEncerrado) return;
        definirFavoritosHabilitados(true);
        try {
            const resposta = await fetch('api/favoritos/', { headers: { Accept: 'application/json' } });
            const dados = await lerJson(resposta);
            if (!resposta.ok) throw new Error(dados.erro);
            favoritoAtual = (dados.favoritos || []).find((f) => Number(f.id_evento) === idEvento) || null;
            atualizarFavorito();
        } catch (erro) {
            console.error(erro);
        } finally {
            definirFavoritosHabilitados(eventoEncerrado);
        }
    }
    function iniciarFavorito() {
        botoesFavorito().forEach((botao) => {
            botao.addEventListener('click', async function () {
                if (eventoEncerrado) return;
                definirFavoritosHabilitados(true);
                try {
                    const resposta = favoritoAtual
                        ? await fetch(`api/favoritos/${favoritoAtual.id_favorito}`, { method: 'DELETE' })
                        : await fetch('api/favoritos/', {
                              method: 'POST',
                              headers: { 'Content-Type': 'application/json' },
                              body: JSON.stringify({ id_evento: idEvento }),
                          });
                    const dados = await lerJson(resposta);
                    if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível alterar o favorito.');
                    favoritoAtual = favoritoAtual ? null : dados.favorito;
                    atualizarFavorito();
                } catch (erro) {
                    window.ShowMeUI.toast(erro.message, { variante: 'erro' });
                } finally {
                    definirFavoritosHabilitados(eventoEncerrado);
                }
            });
        });
    }
    async function carregarPagina() {
        if (!idEvento) {
            mostrarErro('Informe um evento válido na URL.');
            return;
        }
        try {
            const resposta = await fetch(`api/eventos/${idEvento}`, { headers: { Accept: 'application/json' } });
            const dados = await lerJson(resposta);
            if (!resposta.ok || !dados.evento) throw new Error(dados.erro || 'Evento não encontrado.');
            renderizarEvento(dados.evento);
            await Promise.all([carregarFavorito(), carregarPlanejamentoDoEvento()]);

            const solicitarSelecao =
                parametros.get('selecionarSessao') === '1' || parametros.get('selecionar_sessao') === '1';

            if (solicitarSelecao && !eventoEncerrado) {
                abrirSelecaoOuContinuar();
            }
        } catch (erro) {
            mostrarErro(erro.message || 'Não foi possível carregar o evento.');
        }
    }
    if (typeof window.AOS !== 'undefined') window.AOS.init();
    iniciarFavorito();
    iniciarSelecaoSessao();
    iniciarPlanejamento();
    carregarPagina();
})();
