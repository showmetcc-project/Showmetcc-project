(function () {
    'use strict';

    const parametros = new URLSearchParams(window.location.search);
    const idEvento = Number(parametros.get('id'));
    const idUsuario = Number(document.body.dataset.userId || 0);
    const usuarioAdmin = document.body.dataset.userType === 'admin';
    const fallback = 'assets/img/bannerEventoPadrao.png';
    const categorias = ['Duvida', 'Dica', 'Transporte', 'Hospedagem', 'Companhia', 'Relato'];
    let posts = [];
    let midias = [];
    let categoriaAtual = '';
    let alvoDenunciaAtual = null;

    async function json(resposta) {
        const texto = await resposta.text();
        try {
            return texto ? JSON.parse(texto) : {};
        } catch (_) {
            return {erro: texto || 'Resposta inválida'};
        }
    }

    function caminho(valor) {
        if (!valor) return fallback;
        return /^(https?:|data:|\/)/i.test(valor) || valor.startsWith('assets/')
            ? valor
            : `assets/uploads/eventos/${valor}`;
    }

    function dataHora(valor) {
        if (!valor) return '';
        const data = new Date(valor);
        return Number.isNaN(data.getTime())
            ? valor
            : new Intl.DateTimeFormat('pt-BR', {dateStyle: 'medium', timeStyle: 'short'}).format(data);
    }

    function formatarValor(valor) {
        return Number(valor).toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
    }

    function faixaPreco(evento) {
        const minimo = Number(evento.valor_ingresso_minimo || 0);
        const maximo = Number(evento.valor_ingresso_maximo || 0);
        if (minimo <= 0) return 'Consulte valores';
        if (maximo > minimo) return `${formatarValor(minimo)} – ${formatarValor(maximo)}`;
        return `A partir de ${formatarValor(minimo)}`;
    }

    function nome(pessoa) {
        return [pessoa.nome_user, pessoa.sobrenome].filter(Boolean).join(' ') || 'Usuário';
    }

    function avatar(pessoa) {
        return pessoa.foto_perfil ? caminho(pessoa.foto_perfil) : 'assets/img/showme.png';
    }

    function botao(icone, texto, classe = '') {
        const elemento = document.createElement('button');
        elemento.type = 'button';
        elemento.className = classe;
        const iconeElemento = document.createElement('i');
        iconeElemento.className = `bi ${icone}`;
        iconeElemento.setAttribute('aria-hidden', 'true');
        elemento.append(iconeElemento);
        if (texto) elemento.append(document.createTextNode(` ${texto}`));
        return elemento;
    }

    function identidade(dados, resposta = false) {
        const topo = document.createElement('div');
        topo.className = resposta ? 'resposta-topo' : 'post-topo';
        const imagem = document.createElement('img');
        imagem.src = avatar(dados);
        imagem.alt = '';
        const info = document.createElement('div');
        info.className = 'post-identidade';
        const autor = document.createElement('strong');
        autor.textContent = nome(dados);
        const data = document.createElement('time');
        data.textContent = dataHora(dados.data_criacao);
        info.append(autor, data);
        topo.append(imagem, info);
        return topo;
    }

    async function carregarPosts() {
        const feed = document.getElementById('feedComunidade');
        feed.innerHTML = '<p class="comunidade-estado">Carregando publicações...</p>';
        const busca = new URLSearchParams({evento_id: String(idEvento)});
        if (categoriaAtual) busca.set('categoria', categoriaAtual);

        try {
            const resposta = await fetch(`api/comunidade-posts?${busca}`, {headers: {Accept: 'application/json'}});
            const dados = await json(resposta);
            if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível carregar as publicações.');
            posts = dados.posts || [];
            renderizarPosts();
        } catch (erro) {
            feed.replaceChildren();
            const aviso = document.createElement('p');
            aviso.className = 'comunidade-estado erro';
            aviso.textContent = erro.message;
            feed.append(aviso);
        }
    }

    function renderizarPosts() {
        const feed = document.getElementById('feedComunidade');
        feed.replaceChildren();
        if (!posts.length) {
            const vazio = document.createElement('p');
            vazio.className = 'comunidade-estado';
            vazio.textContent = 'Ainda não há publicações nesta categoria.';
            feed.append(vazio);
            return;
        }
        feed.append(...posts.map(criarPost));
    }

    function criarSeletorCategoria(valorAtual) {
        const seletor = document.createElement('select');
        seletor.required = true;
        seletor.setAttribute('aria-label', 'Categoria da publicação');
        categorias.forEach((categoria) => {
            const opcao = document.createElement('option');
            opcao.value = categoria;
            opcao.textContent = categoria === 'Duvida' ? 'Dúvida' : categoria;
            opcao.selected = categoria === valorAtual;
            seletor.append(opcao);
        });
        return seletor;
    }

    function abrirEdicaoPost(post, texto, acoes) {
        const formulario = document.createElement('form');
        formulario.className = 'form-edicao-comunidade';
        const categoria = criarSeletorCategoria(post.categoria);
        const campoTexto = document.createElement('textarea');
        campoTexto.value = post.texto;
        campoTexto.maxLength = 5000;
        campoTexto.required = true;
        campoTexto.rows = 4;
        campoTexto.setAttribute('aria-label', 'Texto da publicação');
        const botoes = document.createElement('div');
        botoes.className = 'form-edicao-acoes';
        const cancelar = botao('bi-x-lg', 'Cancelar', 'acao-cancelar-comunidade');
        const salvar = botao('bi-check-lg', 'Salvar', 'acao-salvar-comunidade');
        salvar.type = 'submit';
        botoes.append(cancelar, salvar);
        formulario.append(categoria, campoTexto, botoes);
        texto.hidden = true;
        acoes.hidden = true;
        texto.after(formulario);
        campoTexto.focus();

        cancelar.addEventListener('click', () => {
            formulario.remove();
            texto.hidden = false;
            acoes.hidden = false;
        });

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const valor = campoTexto.value.trim();
            if (!valor) return;
            salvar.disabled = true;
            try {
                const resposta = await fetch(`api/comunidade-posts/${post.id_post}`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({categoria: categoria.value, texto: valor})
                });
                const dados = await json(resposta);
                if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível editar a publicação.');
                await carregarPosts();
                window.ShowMeUI.toast('Publicação atualizada.', {variante: 'sucesso'});
            } catch (erro) {
                window.ShowMeUI.toast(erro.message, {variante: 'erro'});
                salvar.disabled = false;
            }
        });
    }

    async function excluirPost(id) {
        const deveExcluir = await window.ShowMeUI.confirmar({
            titulo: 'Excluir publicação',
            texto: 'Excluir esta publicação e suas respostas? Esta ação não pode ser desfeita.',
            confirmarTexto: 'Excluir',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });
        if (!deveExcluir) return;
        const resposta = await fetch(`api/comunidade-posts/${id}`, {method: 'DELETE'});
        const dados = await json(resposta);
        if (!resposta.ok) {
            window.ShowMeUI.toast(dados.erro || 'Não foi possível excluir.', {variante: 'erro'});
            return;
        }
        await carregarPosts();
        window.ShowMeUI.toast('Publicação excluída.', {variante: 'sucesso'});
    }

    function abrirEdicaoResposta(resposta, texto, acoes) {
        const formulario = document.createElement('form');
        formulario.className = 'form-edicao-comunidade form-edicao-resposta';
        const campo = document.createElement('input');
        campo.value = resposta.texto;
        campo.maxLength = 3000;
        campo.required = true;
        campo.setAttribute('aria-label', 'Texto da resposta');
        const cancelar = botao('bi-x-lg', 'Cancelar', 'acao-cancelar-comunidade');
        const salvar = botao('bi-check-lg', 'Salvar', 'acao-salvar-comunidade');
        salvar.type = 'submit';
        const botoes = document.createElement('div');
        botoes.className = 'form-edicao-acoes';
        botoes.append(cancelar, salvar);
        formulario.append(campo, botoes);
        texto.hidden = true;
        acoes.hidden = true;
        texto.after(formulario);
        campo.focus();

        cancelar.addEventListener('click', () => {
            formulario.remove();
            texto.hidden = false;
            acoes.hidden = false;
        });

        formulario.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const valor = campo.value.trim();
            if (!valor) return;
            salvar.disabled = true;
            try {
                const retorno = await fetch(`api/comunidade-respostas/${resposta.id_resposta}`, {
                    method: 'PUT',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({texto: valor})
                });
                const dados = await json(retorno);
                if (!retorno.ok) throw new Error(dados.erro || 'Não foi possível editar a resposta.');
                await carregarPosts();
                window.ShowMeUI.toast('Resposta atualizada.', {variante: 'sucesso'});
            } catch (erro) {
                window.ShowMeUI.toast(erro.message, {variante: 'erro'});
                salvar.disabled = false;
            }
        });
    }

    async function excluirResposta(id) {
        const deveExcluir = await window.ShowMeUI.confirmar({
            titulo: 'Excluir resposta',
            texto: 'Tem certeza que deseja excluir esta resposta?',
            confirmarTexto: 'Excluir',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });
        if (!deveExcluir) return;
        const retorno = await fetch(`api/comunidade-respostas/${id}`, {method: 'DELETE'});
        const dados = await json(retorno);
        if (!retorno.ok) {
            window.ShowMeUI.toast(dados.erro || 'Não foi possível excluir a resposta.', {variante: 'erro'});
            return;
        }
        await carregarPosts();
        window.ShowMeUI.toast('Resposta excluída.', {variante: 'sucesso'});
    }

    function criarResposta(item) {
        const bloco = document.createElement('div');
        bloco.className = 'resposta-comunidade';
        bloco.append(identidade(item, true));
        const texto = document.createElement('p');
        texto.textContent = item.texto;
        bloco.append(texto);

        if (!usuarioAdmin && Number(item.id_usuario) === idUsuario) {
            const acoes = document.createElement('div');
            acoes.className = 'resposta-acoes';
            const editar = botao('bi-pencil', '', 'acao-editar-comunidade acao-icone-comunidade');
            const excluir = botao('bi-trash', '', 'acao-excluir-comunidade acao-icone-comunidade');
            editar.title = 'Editar resposta';
            editar.setAttribute('aria-label', 'Editar resposta');
            excluir.title = 'Excluir resposta';
            excluir.setAttribute('aria-label', 'Excluir resposta');
            editar.addEventListener('click', () => abrirEdicaoResposta(item, texto, acoes));
            excluir.addEventListener('click', () => excluirResposta(item.id_resposta));
            acoes.append(editar, excluir);
            bloco.append(acoes);
        }
        return bloco;
    }

    function criarPost(post) {
        const artigo = document.createElement('article');
        artigo.className = 'post-comunidade';
        artigo.dataset.postId = post.id_post;
        const topo = identidade(post);
        const categoria = document.createElement('span');
        categoria.className = 'post-categoria';
        categoria.textContent = post.categoria === 'Duvida' ? 'Dúvida' : post.categoria;
        topo.append(categoria);
        const texto = document.createElement('p');
        texto.className = 'post-texto';
        texto.textContent = post.texto;
        artigo.append(topo, texto);

        const respostas = document.createElement('div');
        respostas.className = 'post-respostas';
        respostas.append(...(post.respostas || []).map(criarResposta));

        if (usuarioAdmin) {
            artigo.append(respostas);
            return artigo;
        }

        const acoes = document.createElement('div');
        acoes.className = 'post-acoes';
        const curtir = botao(
            post.curtido_usuario ? 'bi-heart-fill' : 'bi-heart',
            String(post.total_curtidas || 0),
            post.curtido_usuario ? 'ativo' : ''
        );
        curtir.setAttribute('aria-label', post.curtido_usuario ? 'Descurtir publicação' : 'Curtir publicação');
        curtir.addEventListener('click', async () => {
            curtir.disabled = true;
            try {
                const retorno = await fetch('api/comunidade-curtidas', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id_post: post.id_post})
                });
                const dados = await json(retorno);
                if (!retorno.ok) throw new Error(dados.erro);
                post.curtido_usuario = dados.curtido;
                post.total_curtidas = dados.total_curtidas;
                renderizarPosts();
            } catch (erro) {
                window.ShowMeUI.toast(erro.message || 'Falha ao curtir.', {variante: 'erro'});
                curtir.disabled = false;
            }
        });
        const responder = botao('bi-reply', 'Responder');
        acoes.append(curtir, responder);

        if (Number(post.id_usuario) === idUsuario) {
            const editar = botao('bi-pencil', '', 'acao-editar-comunidade acao-icone-comunidade');
            const excluir = botao('bi-trash', '', 'acao-excluir-comunidade acao-icone-comunidade');
            editar.title = 'Editar publicação';
            editar.setAttribute('aria-label', 'Editar publicação');
            excluir.title = 'Excluir publicação';
            excluir.setAttribute('aria-label', 'Excluir publicação');
            editar.addEventListener('click', () => abrirEdicaoPost(post, texto, acoes));
            excluir.addEventListener('click', () => excluirPost(post.id_post));
            acoes.append(editar, excluir);
        } else {
            const denunciar = botao('bi-flag', 'Denunciar');
            denunciar.addEventListener('click', () => denunciarConteudo({id_post: post.id_post}));
            acoes.append(denunciar);
        }

        const formularioResposta = document.createElement('form');
        formularioResposta.className = 'form-resposta';
        formularioResposta.hidden = true;
        const campoResposta = document.createElement('input');
        campoResposta.required = true;
        campoResposta.maxLength = 3000;
        campoResposta.placeholder = 'Escreva uma resposta...';
        campoResposta.setAttribute('aria-label', 'Resposta');
        const enviarResposta = botao('bi-send', 'Enviar');
        enviarResposta.type = 'submit';
        formularioResposta.append(campoResposta, enviarResposta);
        responder.addEventListener('click', () => {
            formularioResposta.hidden = !formularioResposta.hidden;
            if (!formularioResposta.hidden) campoResposta.focus();
        });
        formularioResposta.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const valor = campoResposta.value.trim();
            if (!valor) return;
            enviarResposta.disabled = true;
            try {
                const retorno = await fetch('api/comunidade-respostas', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id_post: post.id_post, texto: valor})
                });
                const dados = await json(retorno);
                if (!retorno.ok) throw new Error(dados.erro);
                await carregarPosts();
                window.ShowMeUI.toast('Resposta publicada.', {variante: 'sucesso'});
            } catch (erro) {
                window.ShowMeUI.toast(erro.message || 'Falha ao responder.', {variante: 'erro'});
                enviarResposta.disabled = false;
            }
        });

        artigo.append(acoes, respostas, formularioResposta);
        return artigo;
    }

    function denunciarConteudo(alvo) {
        if (usuarioAdmin) return;
        alvoDenunciaAtual = alvo;
        const formulario = document.getElementById('formDenuncia');
        formulario.reset();
        document.getElementById('campoOutroMotivo').hidden = true;
        document.getElementById('outroMotivoDenuncia').required = false;
        document.getElementById('btnEnviarDenuncia').disabled = true;
        const modal = document.getElementById('modalDenuncia');
        if (!modal.open) modal.showModal();
    }

    async function enviarDenuncia(evento) {
        evento.preventDefault();
        if (!alvoDenunciaAtual) return;
        const selecionado = document.querySelector('input[name="motivoDenuncia"]:checked');
        const outro = document.getElementById('outroMotivoDenuncia').value.trim();
        if (!selecionado || (selecionado.value === 'Outro motivo' && !outro)) return;
        const motivo = selecionado.value === 'Outro motivo' ? `Outro motivo: ${outro}` : selecionado.value;
        const enviar = document.getElementById('btnEnviarDenuncia');
        enviar.disabled = true;

        try {
            const resposta = await fetch('api/comunidade-denuncias', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({...alvoDenunciaAtual, motivo})
            });
            const dados = await json(resposta);
            if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível denunciar.');
            document.getElementById('modalDenuncia').close('enviada');
            window.ShowMeUI.toast('Denúncia registrada para revisão.', {variante: 'sucesso'});
        } catch (erro) {
            window.ShowMeUI.toast(erro.message || 'Não foi possível denunciar.', {variante: 'erro'});
            enviar.disabled = false;
        }
    }

    async function carregarGaleria() {
        const galeria = document.getElementById('galeriaComunidade');
        galeria.innerHTML = '<p class="comunidade-estado">Carregando galeria...</p>';
        try {
            const resposta = await fetch(`api/comunidade-midias?evento_id=${encodeURIComponent(idEvento)}`);
            const dados = await json(resposta);
            if (!resposta.ok) throw new Error(dados.erro);
            midias = dados.midias || [];
            renderizarGaleria();
        } catch (erro) {
            galeria.replaceChildren();
            const aviso = document.createElement('p');
            aviso.className = 'comunidade-estado erro';
            aviso.textContent = erro.message;
            galeria.append(aviso);
        }
    }

    function renderizarGaleria() {
        const galeria = document.getElementById('galeriaComunidade');
        galeria.replaceChildren();
        if (!midias.length) {
            const vazio = document.createElement('p');
            vazio.className = 'comunidade-estado';
            vazio.textContent = 'A galeria ainda não tem fotos.';
            galeria.append(vazio);
            return;
        }
        galeria.append(...midias.map((midia) => {
            const item = document.createElement('article');
            item.className = 'galeria-item';
            const abrir = document.createElement('button');
            abrir.type = 'button';
            abrir.setAttribute('aria-label', 'Ampliar foto');
            const imagem = document.createElement('img');
            imagem.src = caminho(midia.caminho_arquivo);
            imagem.alt = midia.legenda || 'Foto da comunidade';
            imagem.loading = 'lazy';
            abrir.append(imagem);
            abrir.addEventListener('click', () => abrirLightbox(midia));
            const info = document.createElement('div');
            info.className = 'galeria-item-info';
            const legenda = document.createElement('p');
            legenda.textContent = midia.legenda || `Por ${nome(midia)}`;
            info.append(legenda);
            item.append(abrir, info);
            if (!usuarioAdmin && Number(midia.id_usuario) === idUsuario) {
                const excluir = botao('bi-trash', '');
                excluir.className = 'galeria-excluir';
                excluir.setAttribute('aria-label', 'Excluir foto');
                excluir.addEventListener('click', () => excluirMidia(midia.id_midia));
                item.append(excluir);
            }
            return item;
        }));
    }

    function abrirLightbox(midia) {
        const dialog = document.getElementById('lightboxComunidade');
        const imagem = dialog.querySelector('img');
        imagem.src = caminho(midia.caminho_arquivo);
        imagem.alt = midia.legenda || 'Foto da comunidade';
        dialog.querySelector('strong').textContent = nome(midia);
        dialog.querySelector('time').textContent = dataHora(midia.data_criacao);
        dialog.querySelector('p').textContent = midia.legenda || '';
        const acoes = dialog.querySelector('.lightbox-acoes');
        acoes.replaceChildren();
        if (midia.permitir_download) {
            const baixar = document.createElement('a');
            baixar.href = caminho(midia.caminho_arquivo);
            baixar.download = '';
            baixar.textContent = 'Baixar imagem';
            acoes.append(baixar);
        }
        if (!usuarioAdmin && Number(midia.id_usuario) !== idUsuario) {
            const denunciar = botao('bi-flag', 'Denunciar');
            denunciar.addEventListener('click', () => denunciarConteudo({id_midia: midia.id_midia}));
            acoes.append(denunciar);
        }
        dialog.showModal();
    }

    async function excluirMidia(id) {
        const deveExcluir = await window.ShowMeUI.confirmar({
            titulo: 'Excluir foto',
            texto: 'Excluir esta foto permanentemente? Esta ação não pode ser desfeita.',
            confirmarTexto: 'Excluir',
            cancelarTexto: 'Cancelar',
            variante: 'destrutiva'
        });
        if (!deveExcluir) return;
        const resposta = await fetch(`api/comunidade-midias/${id}`, {method: 'DELETE'});
        const dados = await json(resposta);
        if (!resposta.ok) {
            window.ShowMeUI.toast(dados.erro || 'Não foi possível excluir.', {variante: 'erro'});
            return;
        }
        await carregarGaleria();
        window.ShowMeUI.toast('Foto excluída.', {variante: 'sucesso'});
    }

    async function carregarEvento() {
        if (!Number.isInteger(idEvento) || idEvento < 1) throw new Error('Informe um evento válido na URL.');
        const resposta = await fetch(`api/eventos/${idEvento}`);
        const dados = await json(resposta);
        if (!resposta.ok || !dados.evento) throw new Error(dados.erro || 'Evento não encontrado.');
        const evento = dados.evento;
        document.title = `Comunidade — ${evento.nome_evento} - ShowMe`;
        const titulo = document.getElementById('nomeComunidadeEvento');
        titulo.textContent = evento.nome_evento;
        if (!usuarioAdmin && titulo instanceof HTMLAnchorElement) {
            titulo.href = `detalhesEvento.php?id=${Number(evento.id_evento) || idEvento}`;
            titulo.setAttribute('aria-label', `Ver detalhes de ${evento.nome_evento}`);
        }
        document.querySelector('#dataComunidadeEvento span').textContent = evento.data_evento
            ? new Intl.DateTimeFormat('pt-BR', {dateStyle: 'long'}).format(new Date(`${evento.data_evento}T12:00:00`))
            : '';
        const local = document.getElementById('localComunidadeEvento');
        const textoLocal = [evento.cidade_evento, evento.uf].filter(Boolean).join(' - ');
        local.querySelector('span').textContent = textoLocal;
        local.hidden = !textoLocal;
        const categoria = document.getElementById('categoriaComunidadeEvento');
        categoria.querySelector('span').textContent = evento.categoria_evento || '';
        categoria.hidden = !evento.categoria_evento;
        const preco = document.getElementById('precoComunidadeEvento');
        preco.querySelector('span').textContent = faixaPreco(evento);
        preco.hidden = Boolean(evento.gratuidade);
        const imagem = document.getElementById('imagemComunidadeEvento');
        imagem.src = caminho(evento.imagem_evento);
        imagem.alt = evento.nome_evento;
        imagem.onerror = () => {
            imagem.onerror = null;
            imagem.src = fallback;
        };
        document.getElementById('estadoComunidade').hidden = true;
        document.getElementById('conteudoComunidade').hidden = false;
    }

    function iniciarInterface() {
        document.querySelectorAll('[data-aba-comunidade]').forEach((botaoAba) => {
            botaoAba.addEventListener('click', () => {
                document.querySelectorAll('[data-aba-comunidade]').forEach((item) => {
                    const ativo = item === botaoAba;
                    item.classList.toggle('ativa', ativo);
                    item.setAttribute('aria-selected', String(ativo));
                });
                const galeria = botaoAba.dataset.abaComunidade === 'galeria';
                document.getElementById('abaPublicacoes').classList.toggle('ativa', !galeria);
                document.getElementById('abaPublicacoes').hidden = galeria;
                document.getElementById('abaGaleria').classList.toggle('ativa', galeria);
                document.getElementById('abaGaleria').hidden = !galeria;
                if (galeria) carregarGaleria();
            });
        });

        document.querySelectorAll('[data-categoria]').forEach((botaoCategoria) => {
            botaoCategoria.addEventListener('click', () => {
                categoriaAtual = botaoCategoria.dataset.categoria;
                document.querySelectorAll('[data-categoria]').forEach((item) => {
                    item.classList.toggle('ativo', item === botaoCategoria);
                });
                carregarPosts();
            });
        });

        if (!usuarioAdmin) iniciarFormulariosInterativos();
        const lightbox = document.getElementById('lightboxComunidade');
        lightbox.querySelector('[data-fechar-lightbox]').addEventListener('click', () => lightbox.close());
        lightbox.addEventListener('click', (evento) => {
            if (evento.target === lightbox) lightbox.close();
        });
    }

    function iniciarFormulariosInterativos() {
        const formularioPublicacao = document.getElementById('formPublicacao');
        const categoria = document.getElementById('categoriaPublicacao');
        const texto = document.getElementById('textoPublicacao');
        const publicar = document.getElementById('btnPublicar');
        const validarPublicacao = () => {
            publicar.disabled = !(categoria.value && texto.value.trim());
        };
        categoria.addEventListener('change', validarPublicacao);
        texto.addEventListener('input', validarPublicacao);
        formularioPublicacao.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            publicar.disabled = true;
            const resposta = await fetch('api/comunidade-posts', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id_evento: idEvento, categoria: categoria.value, texto: texto.value.trim()})
            });
            const dados = await json(resposta);
            if (!resposta.ok) {
                window.ShowMeUI.toast(dados.erro || 'Não foi possível publicar.', {variante: 'erro'});
                validarPublicacao();
                return;
            }
            formularioPublicacao.reset();
            validarPublicacao();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPublicacao')).hide();
            await carregarPosts();
            window.ShowMeUI.toast('Publicação criada.', {variante: 'sucesso'});
        });

        const arquivo = document.getElementById('arquivoMidia');
        arquivo.addEventListener('change', () => {
            document.getElementById('nomeArquivoMidia').textContent = arquivo.files[0]?.name || '';
        });
        document.getElementById('formMidia').addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const formulario = evento.currentTarget;
            const dadosFormulario = new FormData(formulario);
            dadosFormulario.set('id_evento', String(idEvento));
            const enviar = formulario.querySelector('button[type="submit"]');
            enviar.disabled = true;
            const resposta = await fetch('api/comunidade-midias', {method: 'POST', body: dadosFormulario});
            const dados = await json(resposta);
            if (!resposta.ok) {
                window.ShowMeUI.toast(dados.erro || 'Não foi possível publicar a foto.', {variante: 'erro'});
                enviar.disabled = false;
                return;
            }
            formulario.reset();
            document.getElementById('nomeArquivoMidia').textContent = '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMidia')).hide();
            enviar.disabled = false;
            await carregarGaleria();
            window.ShowMeUI.toast('Foto publicada na galeria.', {variante: 'sucesso'});
        });

        const formularioDenuncia = document.getElementById('formDenuncia');
        const campoOutro = document.getElementById('campoOutroMotivo');
        const outroMotivo = document.getElementById('outroMotivoDenuncia');
        const enviar = document.getElementById('btnEnviarDenuncia');
        const validarDenuncia = () => {
            const selecionado = document.querySelector('input[name="motivoDenuncia"]:checked');
            const exigeOutro = selecionado?.value === 'Outro motivo';
            campoOutro.hidden = !exigeOutro;
            outroMotivo.required = exigeOutro;
            enviar.disabled = !selecionado || (exigeOutro && !outroMotivo.value.trim());
        };
        formularioDenuncia.addEventListener('change', validarDenuncia);
        outroMotivo.addEventListener('input', validarDenuncia);
        formularioDenuncia.addEventListener('submit', enviarDenuncia);
        const modalDenuncia = document.getElementById('modalDenuncia');
        modalDenuncia.querySelectorAll('[data-fechar-denuncia]').forEach((botaoFechar) => {
            botaoFechar.addEventListener('click', () => modalDenuncia.close('cancelada'));
        });
        modalDenuncia.addEventListener('click', (evento) => {
            if (evento.target === modalDenuncia) modalDenuncia.close('cancelada');
        });
        modalDenuncia.addEventListener('close', () => {
            alvoDenunciaAtual = null;
            formularioDenuncia.reset();
            validarDenuncia();
        });
    }

    iniciarInterface();
    carregarEvento()
        .then(() => carregarPosts())
        .catch((erro) => {
            const estado = document.getElementById('estadoComunidade');
            estado.className = 'comunidade-estado erro';
            estado.textContent = erro.message;
        });
}());
