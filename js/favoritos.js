// Troca de abas: Favoritos <-> Planejados
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

carregarFavoritos();
