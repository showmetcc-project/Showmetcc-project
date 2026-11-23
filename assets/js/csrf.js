
/* Proteção CSRF compartilhada — ShowMe */
(() => {
    'use strict';

    const fetchNativo = window.fetch.bind(window);

    const urlToken = new URL(
        'api/apiCsrf.php',
        document.baseURI
    ).href;

    let tokenCsrf = null;
    let carregamentoToken = null;

    function pertenceApi(entrada) {
        try {
            const url = entrada instanceof URL
                ? entrada
                : new URL(
                    typeof entrada === 'string'
                        ? entrada
                        : entrada.url,
                    document.baseURI
                );

            return (
                url.origin === window.location.origin &&
                /\/api\/[^/]+\.php$/.test(url.pathname)
            );
        } catch {
            return false;
        }
    }

    async function carregarToken(forcar = false) {
        if (tokenCsrf && !forcar) return tokenCsrf;
        if (carregamentoToken && !forcar) return carregamentoToken;

        carregamentoToken = (async () => {
            const resposta = await fetchNativo(urlToken, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    Accept: 'application/json'
                }
            });

            const texto = await resposta.text();

            let dados;

            try {
                dados = JSON.parse(texto);
            } catch {
                throw new Error(
                    'A API CSRF não retornou JSON válido.'
                );
            }

            if (!resposta.ok || !dados.csrf_token) {
                throw new Error(
                    dados.erro || 'Não foi possível obter o token CSRF.'
                );
            }

            tokenCsrf = dados.csrf_token;
            window.csrfToken = tokenCsrf;

            return tokenCsrf;
        })().finally(() => {
            carregamentoToken = null;
        });

        return carregamentoToken;
    }

    async function atualizarToken(resposta) {
        const tipo = resposta.headers.get('content-type') || '';

        if (!tipo.includes('application/json')) return;

        const dados = await resposta.clone()
            .json()
            .catch(() => null);

        if (typeof dados?.csrf_token === 'string' &&
            dados.csrf_token) {
            tokenCsrf = dados.csrf_token;
            window.csrfToken = tokenCsrf;
        }
    }

    window.ShowMeCsrf = {
        pronto: carregarToken(),
        atualizar: () => carregarToken(true)
    };

    window.fetch = async function (entrada, opcoes = {}) {
        const requisicaoOriginal =
            typeof Request !== 'undefined' &&
            entrada instanceof Request
                ? entrada
                : null;

        const metodo = String(
            opcoes.method ||
            requisicaoOriginal?.method ||
            'GET'
        ).toUpperCase();

        const mutavel = [
            'POST',
            'PUT',
            'PATCH',
            'DELETE'
        ].includes(metodo);

        
if (!mutavel || !pertenceApi(entrada)) {
    try {
        return await fetchNativo(entrada, opcoes);
    } catch (erro) {
        console.error('[CSRF] Erro na requisição:', erro);
        throw erro;
    }
}

        const token = await carregarToken();

        const cabecalhos = new Headers(
            opcoes.headers ||
            requisicaoOriginal?.headers ||
            undefined
        );

        cabecalhos.set('X-CSRF-Token', token);

        const resposta = await fetchNativo(entrada, {
            ...opcoes,
            headers: cabecalhos,
            credentials: opcoes.credentials || 'same-origin'
        });

        await atualizarToken(resposta);

        return resposta;
    };
})();
