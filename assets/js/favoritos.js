// Alterna as abas Favoritos e Planejados.
document.querySelectorAll('.aba').forEach(btn => {
    btn.addEventListener('click', () => {
        const tab = btn.dataset.tab;

        // Atualiza botões
        document.querySelectorAll('.aba').forEach(b => b.classList.remove('ativa'));
        btn.classList.add('ativa');

        // Atualiza seções
        document.querySelectorAll('.conteudo').forEach(s => s.classList.remove('ativa'));
        document.getElementById(tab).classList.add('ativa');
    });
});

const listaFavoritos = document.getElementById('favoritos');
const contadorFavoritos = document.getElementById('contadorFavoritos');

function criarCardFavorito(favorito) {
    const card = document.createElement('div');
    card.className = 'card-evento';

    const imagem = document.createElement('img');
    imagem.src = favorito.imagem_evento || 'assets/img/banner_site_565x235px.png';
    imagem.alt = favorito.nome_evento;

    const info = document.createElement('div');
    info.className = 'info';

    const titulo = document.createElement('h3');
    titulo.textContent = favorito.nome_evento;

    const local = document.createElement('p');
    local.textContent = [favorito.local_evento, favorito.cidade_evento, favorito.uf]
        .filter(Boolean)
        .join(', ');

    const data = document.createElement('p');
    data.textContent = favorito.data_evento || 'Data não informada';

    const detalhes = document.createElement('a');
    detalhes.className = 'btn-detalhes';
    detalhes.href = `detalhesEvento.php?id_evento=${favorito.id_evento}`;
    detalhes.textContent = 'Ver detalhes';

    info.append(titulo, local, data, detalhes);

    const acoes = document.createElement('div');
    acoes.className = 'acoes';

    const tipo = document.createElement('span');
    tipo.className = `tag ${favorito.gratuidade ? 'gratis' : 'pago'}`;
    tipo.textContent = favorito.gratuidade ? 'Grátis' : 'Pago';

    const excluir = document.createElement('button');
    excluir.className = 'btn-excluir';
    excluir.title = 'Remover';
    excluir.innerHTML = '<i class="bi bi-trash3"></i>';
    excluir.addEventListener('click', async () => {
        const resposta = await fetch(`api/favoritos/${favorito.id_favorito}`, {method: 'DELETE'});
        const dados = await resposta.json();

        if (!resposta.ok) {
            alert(dados.erro || 'Não foi possível remover o favorito.');
            return;
        }

        card.remove();
        if (contadorFavoritos) {
            contadorFavoritos.textContent = Math.max(0, Number(contadorFavoritos.textContent) - 1);
        }
    });

    acoes.append(tipo, excluir);
    card.append(imagem, info, acoes);
    return card;
}

async function carregarFavoritos() {
    if (!listaFavoritos) {
        return;
    }

    try {
        const resposta = await fetch('api/favoritos/');
        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar os favoritos.');
        }

        listaFavoritos.replaceChildren();
        if (contadorFavoritos) {
            contadorFavoritos.textContent = dados.favoritos.length;
        }

        if (dados.favoritos.length === 0) {
            const vazio = document.createElement('p');
            vazio.textContent = 'Você ainda não adicionou eventos aos favoritos.';
            listaFavoritos.append(vazio);
            return;
        }

        dados.favoritos.forEach((favorito) => {
            listaFavoritos.append(criarCardFavorito(favorito));
        });
    } catch (erro) {
        listaFavoritos.replaceChildren();
        if (contadorFavoritos) {
            contadorFavoritos.textContent = '0';
        }
        const mensagem = document.createElement('p');
        mensagem.textContent = erro.message;
        listaFavoritos.append(mensagem);
    }
}

const listaPlanejados = document.getElementById('planejados');
const contadorPlanejados = document.getElementById('contadorPlanejados');

function formatarDataEvento(data) {
    if (!data) {
        return 'Data não informada';
    }

    const partes = data.split('-').map(Number);
    const dataLocal = new Date(partes[0], partes[1] - 1, partes[2]);
    return dataLocal.toLocaleDateString('pt-BR');
}

function criarInformacao(icone, texto) {
    const linha = document.createElement('p');
    const elementoIcone = document.createElement('i');
    elementoIcone.className = icone;
    linha.append(elementoIcone, document.createTextNode(texto));
    return linha;
}

function textoDeslocamento(planejamento) {
    return `${planejamento.meio_transporte} · ${planejamento.distancia_km} km · ${planejamento.tempo_estimado} min`;
}

function criarCardPlanejado(planejamento) {
    const card = document.createElement('div');
    card.className = 'card-evento';

    const imagem = document.createElement('img');
    imagem.src = planejamento.imagem_evento || 'assets/img/banner_site_565x235px.png';
    imagem.alt = planejamento.nome_evento;

    const info = document.createElement('div');
    info.className = 'info';

    const titulo = document.createElement('h3');
    titulo.textContent = planejamento.nome_evento;

    const local = criarInformacao(
        'bi bi-geo-alt-fill',
        [planejamento.local_evento, planejamento.cidade_evento, planejamento.uf]
            .filter(Boolean)
            .join(', ') || 'Local não informado'
    );
    const data = criarInformacao('bi bi-calendar3', formatarDataEvento(planejamento.data_evento));
    const deslocamento = criarInformacao('bi bi-signpost-2', textoDeslocamento(planejamento));

    const botoes = document.createElement('div');
    botoes.className = 'botoes-duplos';

    const detalhes = document.createElement('a');
    detalhes.className = 'btn-detalhes';
    detalhes.href = `detalhesEvento.php?id_evento=${planejamento.id_evento}`;
    detalhes.textContent = 'Ver detalhes';

    const editar = document.createElement('button');
    editar.type = 'button';
    editar.className = 'btn-planejamento';
    editar.textContent = 'Editar transporte';
    editar.addEventListener('click', async () => {
        const novoMeio = window.prompt('Novo meio de transporte:', planejamento.meio_transporte);

        if (novoMeio === null) {
            return;
        }

        const meioTransporte = novoMeio.trim();

        if (!meioTransporte || meioTransporte.length > 30) {
            alert('Informe um meio de transporte com até 30 caracteres.');
            return;
        }

        try {
            const resposta = await fetch(`api/planejamento/${planejamento.id_rota}`, {
                method: 'PUT',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({meio_transporte: meioTransporte})
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível editar o planejamento.');
            }

            planejamento.meio_transporte = meioTransporte;
            deslocamento.lastChild.textContent = textoDeslocamento(planejamento);
        } catch (erro) {
            alert(erro.message);
        }
    });

    botoes.append(detalhes, editar);
    info.append(titulo, local, data, deslocamento, botoes);

    const acoes = document.createElement('div');
    acoes.className = 'acoes';

    const tipo = document.createElement('span');
    tipo.className = `tag ${planejamento.gratuidade ? 'gratis' : 'pago'}`;
    tipo.textContent = planejamento.gratuidade ? 'Grátis' : 'Pago';

    const excluir = document.createElement('button');
    excluir.type = 'button';
    excluir.className = 'btn-excluir';
    excluir.title = 'Desfazer planejamento';
    excluir.setAttribute('aria-label', `Desfazer planejamento de ${planejamento.nome_evento}`);
    excluir.innerHTML = '<i class="bi bi-trash3"></i>';
    excluir.addEventListener('click', async () => {
        if (!window.confirm('Deseja desfazer este planejamento?')) {
            return;
        }

        try {
            const resposta = await fetch(`api/planejamento/${planejamento.id_rota}`, {
                method: 'DELETE'
            });
            const dados = await resposta.json();

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível remover o planejamento.');
            }

            card.remove();
            contadorPlanejados.textContent = Math.max(0, Number(contadorPlanejados.textContent) - 1);

            if (!listaPlanejados.children.length) {
                const vazio = document.createElement('p');
                vazio.textContent = 'Você ainda não finalizou nenhum planejamento.';
                listaPlanejados.append(vazio);
            }
        } catch (erro) {
            alert(erro.message);
        }
    });

    acoes.append(tipo, excluir);
    card.append(imagem, info, acoes);
    return card;
}

async function carregarPlanejados() {
    if (!listaPlanejados) {
        return;
    }

    try {
        const resposta = await fetch('api/planejamento/');
        const dados = await resposta.json();

        if (!resposta.ok) {
            throw new Error(dados.erro || 'Não foi possível carregar os planejados.');
        }

        listaPlanejados.replaceChildren();
        contadorPlanejados.textContent = dados.planejamentos.length;

        if (!dados.planejamentos.length) {
            const vazio = document.createElement('p');
            vazio.textContent = 'Você ainda não finalizou nenhum planejamento.';
            listaPlanejados.append(vazio);
            return;
        }

        dados.planejamentos.forEach((planejamento) => {
            listaPlanejados.append(criarCardPlanejado(planejamento));
        });
    } catch (erro) {
        listaPlanejados.replaceChildren();
        contadorPlanejados.textContent = '0';
        const mensagem = document.createElement('p');
        mensagem.textContent = erro.message;
        listaPlanejados.append(mensagem);
    }
}

function ativarAbaInicial() {
    const abaSolicitada = new URLSearchParams(window.location.search).get('aba');

    if (abaSolicitada !== 'planejados') {
        return;
    }

    document.querySelectorAll('.aba').forEach((aba) => {
        aba.classList.toggle('ativa', aba.dataset.tab === 'planejados');
    });
    document.querySelectorAll('.conteudo').forEach((conteudo) => {
        conteudo.classList.toggle('ativa', conteudo.id === 'planejados');
    });
}

ativarAbaInicial();
carregarFavoritos();
carregarPlanejados();
