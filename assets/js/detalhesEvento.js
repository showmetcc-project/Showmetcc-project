(function () {
    'use strict';

    const imagemPadrao = 'assets/img/banner_site_565x235px.png';
    const idUsuario = Number(document.body.dataset.userId || 0);
    const usuarioAdmin = document.body.dataset.userAdmin === 'true';
    const parametros = new URLSearchParams(window.location.search);
    const idInformado = parametros.get('id') || parametros.get('id_evento') || '';
    const idEvento = /^\d+$/.test(idInformado) && Number(idInformado) > 0
        ? Number(idInformado)
        : null;

    let favoritoAtual = null;
    let mapaAtual = null;

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

    async function lerRespostaJson(resposta) {
        try {
            return await resposta.json();
        } catch (erro) {
            return {};
        }
    }

    function formatarData(dataInformada) {
        const dataTexto = String(dataInformada || '').split(/[ T]/)[0];
        const partes = dataTexto.split('-').map(Number);

        if (partes.length !== 3 || partes.some((parte) => !Number.isInteger(parte))) {
            return 'Data não informada';
        }

        return new Date(partes[0], partes[1] - 1, partes[2]).toLocaleDateString('pt-BR');
    }

    function localidade(evento) {
        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        return cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : 'Cidade não informada';
    }

    function mostrarErroPagina(mensagem) {
        const estado = document.getElementById('estadoPagina');
        estado.classList.add('erro');
        estado.replaceChildren();

        const icone = document.createElement('i');
        icone.className = 'bi bi-exclamation-triangle';
        icone.setAttribute('aria-hidden', 'true');

        const texto = document.createElement('p');
        texto.textContent = mensagem;
        estado.append(icone, texto);
        document.getElementById('conteudoEvento').hidden = true;
    }

    function configurarImagem(elemento, caminho, textoAlternativo) {
        elemento.src = caminhoImagem(caminho);
        elemento.alt = textoAlternativo;
        elemento.addEventListener('error', function () {
            this.src = imagemPadrao;
        }, {once: true});
    }

    function criarEstadoVazio(titulo, descricao) {
        const card = document.createElement('div');
        card.className = 'card-showme';
        const heading = document.createElement('h4');
        heading.textContent = titulo;
        const texto = document.createElement('p');
        texto.textContent = descricao;
        card.append(heading, texto);
        return card;
    }

    function renderizarArtistas(artistas) {
        const lista = document.getElementById('listaArtistas');
        lista.replaceChildren();

        if (!Array.isArray(artistas) || artistas.length === 0) {
            lista.append(criarEstadoVazio(
                'Artista não informado',
                'Este evento ainda não possui artista relacionado no banco.'
            ));
            return;
        }

        artistas.forEach((artista) => {
            const card = document.createElement('div');
            card.className = 'card-showme artista-item';
            const linha = document.createElement('div');
            linha.style.display = 'flex';
            linha.style.alignItems = 'center';

            if (artista.imagem_artista) {
                const imagem = document.createElement('img');
                imagem.className = 'imagem-artista';
                configurarImagem(imagem, artista.imagem_artista, artista.nome_artista || 'Artista');
                linha.append(imagem);
            }

            const textos = document.createElement('div');
            const nome = document.createElement('h4');
            nome.textContent = artista.nome_artista || 'Artista não informado';
            textos.append(nome);

            if (artista.genero_artista) {
                const genero = document.createElement('p');
                genero.textContent = artista.genero_artista;
                textos.append(genero);
            }

            linha.append(textos);
            card.append(linha);
            lista.append(card);
        });
    }

    function montarEndereco(evento) {
        const cidade = String(evento.cidade_evento || '').trim();
        const uf = String(evento.uf || '').trim();
        const cidadeUf = cidade ? `${cidade}${uf ? ` - ${uf}` : ''}` : '';

        return [evento.local_evento, evento.rua_evento, cidadeUf]
            .filter((parte) => String(parte || '').trim())
            .join(', ');
    }

    async function carregarMapa(evento) {
        const mapaElemento = document.getElementById('mapaEventoBanco');
        const endereco = montarEndereco(evento);

        if (!endereco || typeof window.L === 'undefined') {
            mapaElemento.textContent = 'Localização não disponível.';
            return;
        }

        mapaElemento.textContent = 'Carregando mapa...';

        try {
            const parametrosMapa = new URLSearchParams({format: 'json', limit: '1', q: endereco});
            const resposta = await fetch(
                `https://nominatim.openstreetmap.org/search?${parametrosMapa.toString()}`,
                {headers: {Accept: 'application/json'}}
            );
            const dados = await resposta.json();

            if (!resposta.ok || !Array.isArray(dados) || dados.length === 0) {
                throw new Error('Localização não encontrada.');
            }

            mapaElemento.replaceChildren();

            if (mapaAtual) {
                mapaAtual.remove();
            }

            mapaAtual = window.L.map('mapaEventoBanco').setView(
                [Number(dados[0].lat), Number(dados[0].lon)],
                15
            );
            window.L.tileLayer(
                'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
                {attribution: '&copy; OpenStreetMap &copy; CARTO', subdomains: 'abcd', maxZoom: 20}
            ).addTo(mapaAtual);

            const popup = document.createElement('div');
            const nome = document.createElement('strong');
            nome.textContent = evento.nome_evento || 'Evento';
            const enderecoPopup = document.createElement('span');
            enderecoPopup.textContent = endereco;
            popup.append(nome, document.createElement('br'), enderecoPopup);

            window.L.marker([Number(dados[0].lat), Number(dados[0].lon)])
                .addTo(mapaAtual)
                .bindPopup(popup)
                .openPopup();
        } catch (erro) {
            mapaElemento.textContent = erro.message || 'Não foi possível carregar o mapa.';
        }
    }

    function configurarIngressos(evento) {
        const texto = document.getElementById('textoIngressos');
        const link = document.getElementById('linkIngressos');
        const aviso = document.getElementById('avisoIngressos');

        texto.textContent = evento.gratuidade
            ? 'Este evento é gratuito.'
            : 'Este evento é pago. Adquira seus ingressos através dos canais oficiais.';
        link.hidden = true;
        aviso.hidden = true;

        if (!evento.link_oficial) {
            texto.append(document.createElement('br'), document.createTextNode('Link oficial não informado.'));
            return;
        }

        try {
            const url = new URL(evento.link_oficial, window.location.href);

            if (!['http:', 'https:'].includes(url.protocol)) {
                throw new Error('Protocolo inválido');
            }

            link.href = url.href;
            link.hidden = false;
            aviso.hidden = false;
        } catch (erro) {
            texto.append(document.createElement('br'), document.createTextNode('Link oficial inválido.'));
        }
    }

    function renderizarEvento(evento) {
        document.title = `${evento.nome_evento || 'Evento'} - ShowMe`;
        configurarImagem(
            document.getElementById('imagemEvento'),
            evento.imagem_evento,
            evento.nome_evento || 'Evento'
        );

        const badgeGratuidade = document.getElementById('badgeGratuidade');
        badgeGratuidade.className = `badge-gratuidade ${evento.gratuidade ? 'gratuito' : 'pago'}`;
        badgeGratuidade.textContent = evento.gratuidade ? 'Gratuito' : 'Pago';

        const badgeCategoria = document.getElementById('badgeCategoria');
        badgeCategoria.textContent = evento.categoria_evento || '';
        badgeCategoria.hidden = !evento.categoria_evento;

        document.getElementById('tituloEvento').textContent = evento.nome_evento || 'Evento sem nome';
        document.querySelector('#infoData span').textContent = formatarData(evento.data_evento);
        document.querySelector('#infoCidade span').textContent = localidade(evento);

        const infoLocal = document.getElementById('infoLocal');
        infoLocal.querySelector('span').textContent = evento.local_evento || '';
        infoLocal.hidden = !evento.local_evento;
        document.getElementById('descricaoEvento').textContent = evento.descricao_evento
            || 'Este evento ainda não possui uma descrição cadastrada.';
        renderizarArtistas(evento.artistas);

        document.getElementById('nomeLocal').textContent = evento.local_evento || 'Local não informado';
        document.getElementById('enderecoLocal').textContent = [evento.rua_evento, localidade(evento)]
            .filter(Boolean)
            .join(' — ');
        document.getElementById('idEventoAvaliacao')?.setAttribute('value', String(idEvento));
        document.getElementById('linkPlanejamento').href = `planejamento.php?id=${encodeURIComponent(idEvento)}`;
        configurarIngressos(evento);

        document.getElementById('estadoPagina').hidden = true;
        document.getElementById('conteudoEvento').hidden = false;
        carregarMapa(evento);
    }

    function criarMidiaAvaliacao(midia) {
        if (midia.tipo_midia === 'video') {
            const video = document.createElement('video');
            video.className = 'midia-avaliacao';
            video.src = caminhoImagem(midia.caminho_arquivo);
            video.controls = true;
            video.preload = 'metadata';
            return video;
        }

        const imagem = document.createElement('img');
        imagem.className = 'midia-avaliacao';
        configurarImagem(imagem, midia.caminho_arquivo, 'Foto enviada na avaliação');
        imagem.loading = 'lazy';
        return imagem;
    }

    function podeAlterarAvaliacao(avaliacao) {
        return idUsuario > 0 && (Number(avaliacao.id_user) === idUsuario || usuarioAdmin);
    }

    function criarAvaliacao(avaliacao) {
        const item = document.createElement('div');
        item.className = 'avaliacao-item';
        const topo = document.createElement('div');
        topo.className = 'avaliacao-topo';
        const corpo = document.createElement('div');
        corpo.className = 'avaliacao-corpo';

        const autor = document.createElement('strong');
        autor.textContent = [avaliacao.nome_user, avaliacao.sobrenome].filter(Boolean).join(' ') || 'Usuário';
        const comentario = document.createElement('p');
        comentario.textContent = avaliacao.comentario || '';
        const data = document.createElement('small');
        data.textContent = formatarData(avaliacao.data_avaliacao);
        corpo.append(autor, comentario, data);

        const nota = Math.max(0, Math.min(5, Number(avaliacao.nota) || 0));
        const estrelas = document.createElement('div');
        estrelas.className = 'estrelas-exibir';
        estrelas.textContent = `${'★'.repeat(nota)}${'☆'.repeat(5 - nota)}`;
        estrelas.setAttribute('aria-label', `Nota ${nota} de 5`);
        topo.append(corpo, estrelas);
        item.append(topo);

        if (Array.isArray(avaliacao.midias) && avaliacao.midias.length > 0) {
            const midias = document.createElement('div');
            midias.className = 'midias-avaliacao';
            midias.append(...avaliacao.midias.map(criarMidiaAvaliacao));
            item.append(midias);
        }

        if (podeAlterarAvaliacao(avaliacao)) {
            const acoes = document.createElement('div');
            acoes.className = 'acoes-avaliacao';
            const editar = document.createElement('a');
            editar.href = `editarAvaliacoes.php?id=${encodeURIComponent(avaliacao.id_avaliacao)}`;
            editar.textContent = 'Editar';
            const apagar = document.createElement('button');
            apagar.type = 'button';
            apagar.textContent = 'Apagar';
            apagar.addEventListener('click', async function () {
                if (!window.confirm('Deseja apagar esta avaliação?')) {
                    return;
                }

                this.disabled = true;

                try {
                    const resposta = await fetch(`api/avaliacoes/${avaliacao.id_avaliacao}`, {method: 'DELETE'});
                    const dados = await lerRespostaJson(resposta);

                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível apagar a avaliação.');
                    }

                    await carregarAvaliacoes();
                } catch (erro) {
                    window.alert(erro.message);
                    this.disabled = false;
                }
            });
            acoes.append(editar, apagar);
            item.append(acoes);
        }

        return item;
    }

    function atualizarFormularioAvaliacao(avaliacoes) {
        const formulario = document.getElementById('formAvaliacao');
        const mensagem = document.getElementById('mensagemAvaliacao');

        if (!formulario || !mensagem || idUsuario < 1) {
            return;
        }

        const avaliacaoDoUsuario = avaliacoes.find(
            (avaliacao) => Number(avaliacao.id_user) === idUsuario
        );

        if (!avaliacaoDoUsuario) {
            formulario.hidden = false;
            mensagem.className = 'alert d-none';
            mensagem.replaceChildren();
            return;
        }

        formulario.hidden = true;
        const linkEditar = document.createElement('a');
        linkEditar.href = `editarAvaliacoes.php?id=${encodeURIComponent(avaliacaoDoUsuario.id_avaliacao)}`;
        linkEditar.className = 'alert-link';
        linkEditar.textContent = 'Edite sua avaliação existente.';
        mensagem.replaceChildren(
            document.createTextNode('Você já avaliou este evento. '),
            linkEditar
        );
        mensagem.className = 'alert alert-info';
    }

    async function carregarAvaliacoes() {
        const lista = document.getElementById('listaAvaliacoes');
        lista.replaceChildren();
        const carregando = document.createElement('p');
        carregando.textContent = 'Carregando avaliações...';
        lista.append(carregando);

        try {
            const resposta = await fetch(`api/avaliacoes?evento_id=${encodeURIComponent(idEvento)}`, {
                headers: {Accept: 'application/json'}
            });
            const dados = await lerRespostaJson(resposta);

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível carregar as avaliações.');
            }

            const avaliacoes = Array.isArray(dados.avaliacoes) ? dados.avaliacoes : [];
            lista.replaceChildren();
            atualizarFormularioAvaliacao(avaliacoes);

            if (avaliacoes.length === 0) {
                const vazio = document.createElement('p');
                vazio.textContent = 'Nenhuma avaliação cadastrada para este evento.';
                lista.append(vazio);
                return;
            }

            lista.append(...avaliacoes.map(criarAvaliacao));
        } catch (erro) {
            lista.replaceChildren();
            const mensagem = document.createElement('p');
            mensagem.textContent = erro.message;
            mensagem.className = 'text-danger';
            lista.append(mensagem);
        }
    }

    function atualizarBotaoFavorito() {
        const botao = document.querySelector('.btn-favoritar');
        const favoritado = favoritoAtual !== null;
        const icone = document.createElement('i');
        icone.className = favoritado ? 'bi bi-heart-fill' : 'bi bi-heart';
        icone.setAttribute('aria-hidden', 'true');
        botao.replaceChildren(icone, document.createTextNode(favoritado ? 'Favoritado' : 'Favoritar'));
        botao.setAttribute('aria-pressed', String(favoritado));
    }

    async function carregarEstadoFavorito() {
        if (idUsuario < 1) {
            return;
        }

        const botao = document.querySelector('.btn-favoritar');
        botao.disabled = true;

        try {
            const resposta = await fetch('api/favoritos/', {headers: {Accept: 'application/json'}});

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            const dados = await lerRespostaJson(resposta);

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível consultar os favoritos.');
            }

            favoritoAtual = dados.favoritos.find(
                (favorito) => Number(favorito.id_evento) === idEvento
            ) || null;
            atualizarBotaoFavorito();
        } catch (erro) {
            console.error('Falha ao consultar favorito:', erro);
        } finally {
            botao.disabled = false;
        }
    }

    function iniciarFavorito() {
        const botao = document.querySelector('.btn-favoritar');
        botao.addEventListener('click', async function () {
            if (idUsuario < 1) {
                window.location.href = 'login.php';
                return;
            }

            this.disabled = true;

            try {
                const resposta = favoritoAtual
                    ? await fetch(`api/favoritos/${favoritoAtual.id_favorito}`, {method: 'DELETE'})
                    : await fetch('api/favoritos/', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({id_evento: idEvento})
                    });

                if (resposta.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                const dados = await lerRespostaJson(resposta);

                if (!resposta.ok) {
                    throw new Error(dados.erro || 'Não foi possível alterar o favorito.');
                }

                favoritoAtual = favoritoAtual ? null : dados.favorito;
                atualizarBotaoFavorito();
            } catch (erro) {
                window.alert(erro.message);
            } finally {
                this.disabled = false;
            }
        });
    }

    function iniciarEstrelas() {
        const estrelas = document.querySelectorAll('.estrela');
        const campoNota = document.getElementById('nota');
        let notaAtual = 0;

        function iluminar(nota) {
            estrelas.forEach((estrela) => {
                const selecionada = Number(estrela.dataset.valor) <= nota;
                estrela.classList.toggle('bi-star-fill', selecionada);
                estrela.classList.toggle('bi-star', !selecionada);
            });
        }

        function selecionar(estrela) {
            notaAtual = Number(estrela.dataset.valor);
            campoNota.value = String(notaAtual);
            iluminar(notaAtual);
        }

        estrelas.forEach((estrela) => {
            estrela.addEventListener('mouseover', () => iluminar(Number(estrela.dataset.valor)));
            estrela.addEventListener('mouseout', () => iluminar(notaAtual));
            estrela.addEventListener('click', () => selecionar(estrela));
            estrela.addEventListener('keydown', (evento) => {
                if (evento.key === 'Enter' || evento.key === ' ') {
                    evento.preventDefault();
                    selecionar(estrela);
                }
            });
        });
    }

    function iniciarFormularioAvaliacao() {
        const formulario = document.getElementById('formAvaliacao');

        if (!formulario) {
            return;
        }

        iniciarEstrelas();
        formulario.addEventListener('submit', async function (evento) {
            evento.preventDefault();

            const mensagem = document.getElementById('mensagemAvaliacao');
            const botao = document.getElementById('btnEnviarAvaliacao');
            mensagem.className = 'alert d-none';

            try {
                const arquivos = formulario.elements['midias[]'].files;

                if (!formulario.elements.nota.value) {
                    throw new Error('Selecione uma nota de 1 a 5.');
                }

                if (arquivos.length < 1 || arquivos.length > 5) {
                    throw new Error('Selecione de 1 a 5 fotos ou vídeos.');
                }

                botao.disabled = true;
                const resposta = await fetch('api/avaliacoes/', {method: 'POST', body: new FormData(formulario)});
                const dados = await lerRespostaJson(resposta);

                if (resposta.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                if (resposta.status === 409) {
                    await carregarAvaliacoes();
                    throw new Error(
                        dados.erro || 'Você já avaliou este evento; edite sua avaliação existente.'
                    );
                }

                if (!resposta.ok) {
                    throw new Error(dados.erro || 'Não foi possível enviar a avaliação.');
                }

                formulario.reset();
                document.querySelectorAll('.estrela').forEach((estrela) => {
                    estrela.classList.remove('bi-star-fill');
                    estrela.classList.add('bi-star');
                });
                mensagem.textContent = 'Avaliação enviada com sucesso.';
                mensagem.className = 'alert alert-success';
                await carregarAvaliacoes();
            } catch (erro) {
                mensagem.textContent = erro.message;
                mensagem.className = 'alert alert-danger';
            } finally {
                botao.disabled = false;
            }
        });
    }

    async function carregarPagina() {
        if (!idEvento) {
            mostrarErroPagina('Informe um evento válido na URL.');
            return;
        }

        try {
            const resposta = await fetch(`api/eventos/${encodeURIComponent(idEvento)}`, {
                headers: {Accept: 'application/json'}
            });
            const dados = await lerRespostaJson(resposta);

            if (resposta.status === 404) {
                mostrarErroPagina('Evento não encontrado.');
                return;
            }

            if (!resposta.ok || !dados.evento) {
                throw new Error(dados.erro || 'Não foi possível carregar o evento.');
            }

            renderizarEvento(dados.evento);
            await Promise.all([carregarAvaliacoes(), carregarEstadoFavorito()]);
        } catch (erro) {
            mostrarErroPagina(erro.message || 'Não foi possível carregar o evento.');
        }
    }

    if (typeof window.AOS !== 'undefined') {
        window.AOS.init();
    }

    iniciarFavorito();
    iniciarFormularioAvaliacao();
    carregarPagina();
}());
