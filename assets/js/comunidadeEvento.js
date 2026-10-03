(function () {
    'use strict';
    const parametros = new URLSearchParams(window.location.search);
    const idEvento = Number(parametros.get('id'));
    const idUsuario = Number(document.body.dataset.userId || 0);
    const fallback = 'assets/img/bannerEventoPadrao.png';
    let posts = [];
    let midias = [];
    let categoriaAtual = '';

    async function json(resposta) {
        const texto = await resposta.text();
        try { return texto ? JSON.parse(texto) : {}; } catch (_) { return {erro: texto || 'Resposta inválida'}; }
    }
    function caminho(valor) {
        if (!valor) return fallback;
        return /^(https?:|data:|\/)/i.test(valor) || valor.startsWith('assets/') ? valor : `assets/uploads/eventos/${valor}`;
    }
    function dataHora(valor) {
        if (!valor) return '';
        const data = new Date(valor);
        return Number.isNaN(data.getTime()) ? valor : new Intl.DateTimeFormat('pt-BR', {dateStyle:'medium', timeStyle:'short'}).format(data);
    }
    function nome(pessoa) { return [pessoa.nome_user, pessoa.sobrenome].filter(Boolean).join(' ') || 'Usuário'; }
    function avatar(pessoa) { return pessoa.foto_perfil ? caminho(pessoa.foto_perfil) : 'assets/img/showme.png'; }
    function botao(icone, texto, classe = '') {
        const b = document.createElement('button'); b.type = 'button'; b.className = classe;
        const i = document.createElement('i'); i.className = `bi ${icone}`; i.setAttribute('aria-hidden','true');
        b.append(i, document.createTextNode(` ${texto}`)); return b;
    }
    function identidade(dados, resposta = false) {
        const topo = document.createElement('div'); topo.className = resposta ? 'resposta-topo' : 'post-topo';
        const img = document.createElement('img'); img.src = avatar(dados); img.alt = '';
        const info = document.createElement('div'); info.className = 'post-identidade';
        const strong = document.createElement('strong'); strong.textContent = nome(dados);
        const time = document.createElement('time'); time.textContent = dataHora(dados.data_criacao);
        info.append(strong, time); topo.append(img, info); return topo;
    }
    async function carregarPosts() {
        const feed = document.getElementById('feedComunidade');
        feed.innerHTML = '<p class="comunidade-estado">Carregando publicações...</p>';
        const busca = new URLSearchParams({evento_id:String(idEvento)});
        if (categoriaAtual) busca.set('categoria', categoriaAtual);
        try {
            const resposta = await fetch(`api/comunidade-posts?${busca}`, {headers:{Accept:'application/json'}});
            const dados = await json(resposta);
            if (!resposta.ok) throw new Error(dados.erro || 'Não foi possível carregar as publicações.');
            posts = dados.posts || [];
            renderizarPosts();
        } catch (erro) { feed.replaceChildren(); const p=document.createElement('p'); p.className='comunidade-estado erro'; p.textContent=erro.message; feed.append(p); }
    }
    function renderizarPosts() {
        const feed = document.getElementById('feedComunidade'); feed.replaceChildren();
        if (!posts.length) { const p=document.createElement('p'); p.className='comunidade-estado'; p.textContent='Ainda não há publicações nesta categoria.'; feed.append(p); return; }
        feed.append(...posts.map(criarPost));
    }
    function criarPost(post) {
        const artigo = document.createElement('article'); artigo.className='post-comunidade'; artigo.dataset.postId=post.id_post;
        const topo = identidade(post);
        const categoria = document.createElement('span'); categoria.className='post-categoria'; categoria.textContent=post.categoria === 'Duvida' ? 'Dúvida' : post.categoria;
        topo.append(categoria);
        const texto = document.createElement('p'); texto.className='post-texto'; texto.textContent=post.texto;
        const acoes = document.createElement('div'); acoes.className='post-acoes';
        const curtir = botao(post.curtido_usuario ? 'bi-heart-fill':'bi-heart', String(post.total_curtidas || 0), post.curtido_usuario ? 'ativo':'');
        curtir.setAttribute('aria-label', post.curtido_usuario ? 'Descurtir publicação':'Curtir publicação');
        curtir.onclick = async () => {
            curtir.disabled=true;
            try { const r=await fetch('api/comunidade-curtidas',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_post:post.id_post})}); const d=await json(r); if(!r.ok) throw new Error(d.erro); post.curtido_usuario=d.curtido; post.total_curtidas=d.total_curtidas; renderizarPosts(); } catch(e){alert(e.message||'Falha ao curtir.'); curtir.disabled=false;}
        };
        const responder = botao('bi-reply','Responder');
        acoes.append(curtir, responder);
        if (Number(post.id_usuario) === idUsuario) {
            const excluir=botao('bi-trash','Excluir'); excluir.onclick=()=>excluirPost(post.id_post); acoes.append(excluir);
        } else {
            const denunciar=botao('bi-flag','Denunciar'); denunciar.onclick=()=>denunciarConteudo({id_post:post.id_post}); acoes.append(denunciar);
        }
        const respostas=document.createElement('div'); respostas.className='post-respostas';
        (post.respostas||[]).forEach((item)=>{ const bloco=document.createElement('div'); bloco.className='resposta-comunidade'; bloco.append(identidade(item,true)); const p=document.createElement('p'); p.textContent=item.texto; bloco.append(p); respostas.append(bloco); });
        const form=document.createElement('form'); form.className='form-resposta'; form.hidden=true;
        const input=document.createElement('input'); input.required=true; input.maxLength=3000; input.placeholder='Escreva uma resposta...'; input.setAttribute('aria-label','Resposta');
        const enviar=botao('bi-send','Enviar'); enviar.type='submit'; form.append(input,enviar);
        responder.onclick=()=>{ form.hidden=!form.hidden; if(!form.hidden) input.focus(); };
        form.onsubmit=async(evento)=>{evento.preventDefault(); const valor=input.value.trim(); if(!valor)return; enviar.disabled=true; try{const r=await fetch('api/comunidade-respostas',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_post:post.id_post,texto:valor})});const d=await json(r);if(!r.ok)throw new Error(d.erro);await carregarPosts();}catch(e){alert(e.message||'Falha ao responder.');enviar.disabled=false;}};
        artigo.append(topo,texto,acoes,respostas,form); return artigo;
    }
    async function excluirPost(id) {
        if (!confirm('Excluir esta publicação e suas respostas?')) return;
        const resposta=await fetch(`api/comunidade-posts/${id}`,{method:'DELETE'}); const dados=await json(resposta);
        if(!resposta.ok){alert(dados.erro||'Não foi possível excluir.');return;} await carregarPosts();
    }
    async function denunciarConteudo(alvo) {
        const motivo=prompt('Informe o motivo da denúncia:'); if(!motivo||!motivo.trim())return;
        const resposta=await fetch('api/comunidade-denuncias',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({...alvo,motivo:motivo.trim()})});
        const dados=await json(resposta); alert(resposta.ok?'Denúncia registrada para revisão.':(dados.erro||'Não foi possível denunciar.'));
    }
    async function carregarGaleria() {
        const alvo=document.getElementById('galeriaComunidade'); alvo.innerHTML='<p class="comunidade-estado">Carregando galeria...</p>';
        try{const r=await fetch(`api/comunidade-midias?evento_id=${encodeURIComponent(idEvento)}`);const d=await json(r);if(!r.ok)throw new Error(d.erro);midias=d.midias||[];renderizarGaleria();}catch(e){alvo.innerHTML='';const p=document.createElement('p');p.className='comunidade-estado erro';p.textContent=e.message;alvo.append(p);}
    }
    function renderizarGaleria() {
        const alvo=document.getElementById('galeriaComunidade'); alvo.replaceChildren();
        if(!midias.length){const p=document.createElement('p');p.className='comunidade-estado';p.textContent='A galeria ainda não tem fotos.';alvo.append(p);return;}
        alvo.append(...midias.map((midia)=>{
            const item=document.createElement('article');item.className='galeria-item';
            const abrir=document.createElement('button');abrir.type='button';abrir.setAttribute('aria-label','Ampliar foto');
            const img=document.createElement('img');img.src=caminho(midia.caminho_arquivo);img.alt=midia.legenda||'Foto da comunidade';img.loading='lazy';abrir.append(img);abrir.onclick=()=>abrirLightbox(midia);
            const info=document.createElement('div');info.className='galeria-item-info';const p=document.createElement('p');p.textContent=midia.legenda||`Por ${nome(midia)}`;info.append(p);item.append(abrir,info);
            if(Number(midia.id_usuario)===idUsuario){const excluir=botao('bi-trash','');excluir.className='galeria-excluir';excluir.setAttribute('aria-label','Excluir foto');excluir.onclick=()=>excluirMidia(midia.id_midia);item.append(excluir);}
            return item;
        }));
    }
    function abrirLightbox(midia) {
        const dialog=document.getElementById('lightboxComunidade'); dialog.querySelector('img').src=caminho(midia.caminho_arquivo);dialog.querySelector('img').alt=midia.legenda||'Foto da comunidade';dialog.querySelector('strong').textContent=nome(midia);dialog.querySelector('time').textContent=dataHora(midia.data_criacao);dialog.querySelector('p').textContent=midia.legenda||'';
        const acoes=dialog.querySelector('.lightbox-acoes');acoes.replaceChildren();
        if(midia.permitir_download){const baixar=document.createElement('a');baixar.href=caminho(midia.caminho_arquivo);baixar.download='';baixar.append(document.createTextNode('Baixar imagem'));acoes.append(baixar);}
        if(Number(midia.id_usuario)!==idUsuario){const denunciar=botao('bi-flag','Denunciar');denunciar.onclick=()=>denunciarConteudo({id_midia:midia.id_midia});acoes.append(denunciar);}
        dialog.showModal();
    }
    async function excluirMidia(id) { if(!confirm('Excluir esta foto permanentemente?'))return;const r=await fetch(`api/comunidade-midias/${id}`,{method:'DELETE'});const d=await json(r);if(!r.ok){alert(d.erro||'Não foi possível excluir.');return;}await carregarGaleria(); }
    async function carregarEvento() {
        if(!Number.isInteger(idEvento)||idEvento<1)throw new Error('Informe um evento válido na URL.');
        const r=await fetch(`api/eventos/${idEvento}`);const d=await json(r);if(!r.ok||!d.evento)throw new Error(d.erro||'Evento não encontrado.');const evento=d.evento;
        document.title=`Comunidade — ${evento.nome_evento} - ShowMe`;document.getElementById('nomeComunidadeEvento').textContent=evento.nome_evento;document.getElementById('dataComunidadeEvento').textContent=evento.data_evento?new Intl.DateTimeFormat('pt-BR',{dateStyle:'long'}).format(new Date(`${evento.data_evento}T12:00:00`)):'';
        const img=document.getElementById('imagemComunidadeEvento');img.src=caminho(evento.imagem_evento);img.alt=evento.nome_evento;img.onerror=()=>{img.onerror=null;img.src=fallback;};
        const titulo=document.querySelector('.cabecalho-titulo');if(titulo){titulo.setAttribute('aria-label',`Comunidade ${evento.nome_evento}`);titulo.innerHTML='';const a=document.createElement('span');a.className='cabecalho-titulo-verde parte-1';a.textContent='Comunidade';const b=document.createElement('span');b.className='cabecalho-titulo-rosa parte-2';b.textContent=evento.nome_evento;titulo.append(a,b);}
        document.getElementById('estadoComunidade').hidden=true;document.getElementById('conteudoComunidade').hidden=false;
    }
    function iniciarInterface() {
        document.querySelectorAll('[data-aba-comunidade]').forEach((botaoAba)=>botaoAba.addEventListener('click',()=>{document.querySelectorAll('[data-aba-comunidade]').forEach((b)=>{const ativo=b===botaoAba;b.classList.toggle('ativa',ativo);b.setAttribute('aria-selected',String(ativo));});const galeria=botaoAba.dataset.abaComunidade==='galeria';document.getElementById('abaPublicacoes').classList.toggle('ativa',!galeria);document.getElementById('abaPublicacoes').hidden=galeria;document.getElementById('abaGaleria').classList.toggle('ativa',galeria);document.getElementById('abaGaleria').hidden=!galeria;if(galeria)carregarGaleria();}));
        document.querySelectorAll('[data-categoria]').forEach((b)=>b.addEventListener('click',()=>{categoriaAtual=b.dataset.categoria;document.querySelectorAll('[data-categoria]').forEach((x)=>x.classList.toggle('ativo',x===b));carregarPosts();}));
        const form=document.getElementById('formPublicacao'),categoria=document.getElementById('categoriaPublicacao'),texto=document.getElementById('textoPublicacao'),publicar=document.getElementById('btnPublicar');const validar=()=>{publicar.disabled=!(categoria.value&&texto.value.trim());};categoria.addEventListener('change',validar);texto.addEventListener('input',validar);
        form.addEventListener('submit',async(e)=>{e.preventDefault();publicar.disabled=true;const r=await fetch('api/comunidade-posts',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id_evento:idEvento,categoria:categoria.value,texto:texto.value.trim()})});const d=await json(r);if(!r.ok){document.getElementById('mensagemPublicacao').textContent=d.erro||'Não foi possível publicar.';validar();return;}form.reset();validar();bootstrap.Modal.getOrCreateInstance(document.getElementById('modalPublicacao')).hide();await carregarPosts();});
        const arquivo=document.getElementById('arquivoMidia');arquivo.addEventListener('change',()=>document.getElementById('nomeArquivoMidia').textContent=arquivo.files[0]?.name||'');document.getElementById('formMidia').addEventListener('submit',async(e)=>{e.preventDefault();const form=e.currentTarget,data=new FormData(form);data.set('id_evento',String(idEvento));const botaoSubmit=form.querySelector('button[type=submit]');botaoSubmit.disabled=true;const r=await fetch('api/comunidade-midias',{method:'POST',body:data});const d=await json(r);if(!r.ok){document.getElementById('mensagemMidia').textContent=d.erro||'Não foi possível publicar a foto.';botaoSubmit.disabled=false;return;}form.reset();document.getElementById('nomeArquivoMidia').textContent='';bootstrap.Modal.getOrCreateInstance(document.getElementById('modalMidia')).hide();botaoSubmit.disabled=false;await carregarGaleria();});
        const lightbox=document.getElementById('lightboxComunidade');lightbox.querySelector('[data-fechar-lightbox]').onclick=()=>lightbox.close();lightbox.addEventListener('click',(e)=>{if(e.target===lightbox)lightbox.close();});
    }
    iniciarInterface();
    carregarEvento().then(()=>carregarPosts()).catch((erro)=>{const estado=document.getElementById('estadoComunidade');estado.className='comunidade-estado erro';estado.textContent=erro.message;});
}());
