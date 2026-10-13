/* Proteção CSRF compartilhada para as chamadas mutáveis da API ShowMe. */
(function () {
  'use strict';

  const fetchNativo = window.fetch.bind(window);
  const urlToken = new URL('api/csrf', window.location.href).toString();
  let tokenCsrf = null;
  let carregamentoToken = null;

  function urlDaApi(entrada) {
    const valor = entrada instanceof Request ? entrada.url : entrada;

    try {
      const url = new URL(valor, window.location.href);
      return url.origin === window.location.origin && /\/api(?:\/|$)/.test(url.pathname);
    } catch (_) {
      return false;
    }
  }

  async function carregarToken(forcar = false) {
    if (tokenCsrf && !forcar) {
      return tokenCsrf;
    }

    if (carregamentoToken && !forcar) {
      return carregamentoToken;
    }

    carregamentoToken = fetchNativo(urlToken, {
      method: 'GET',
      credentials: 'same-origin',
      headers: {Accept: 'application/json'}
    })
      .then(async (resposta) => {
        const dados = await resposta.json().catch(() => ({}));

        if (!resposta.ok || typeof dados.csrf_token !== 'string' || !dados.csrf_token) {
          throw new Error(dados.erro || 'Não foi possível preparar a proteção da sessão.');
        }

        tokenCsrf = dados.csrf_token;
        window.csrfToken = tokenCsrf;
        return tokenCsrf;
      })
      .finally(() => {
        carregamentoToken = null;
      });

    return carregamentoToken;
  }

  async function atualizarTokenDaResposta(resposta) {
    if (!resposta || !resposta.headers.get('content-type')?.includes('application/json')) {
      return;
    }

    const dados = await resposta.clone().json().catch(() => null);
    if (dados && typeof dados.csrf_token === 'string' && dados.csrf_token) {
      tokenCsrf = dados.csrf_token;
      window.csrfToken = tokenCsrf;
    }
  }

  window.ShowMeCsrf = {
    pronto: carregarToken(),
    atualizar: () => carregarToken(true)
  };

  window.fetch = async function (entrada, opcoes = {}) {
    const metodo = String(opcoes.method || (entrada instanceof Request ? entrada.method : 'GET')).toUpperCase();
    const mutavel = ['POST', 'PUT', 'DELETE'].includes(metodo);

    if (!mutavel || !urlDaApi(entrada)) {
      return fetchNativo(entrada, opcoes);
    }

    const token = await carregarToken();
    const cabecalhos = new Headers(opcoes.headers || (entrada instanceof Request ? entrada.headers : undefined));
    cabecalhos.set('X-CSRF-Token', token);

    const resposta = await fetchNativo(entrada, {
      ...opcoes,
      headers: cabecalhos,
      credentials: opcoes.credentials || 'same-origin'
    });

    await atualizarTokenDaResposta(resposta);
    return resposta;
  };
}());
