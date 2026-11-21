/* Fallback estático para mapas indisponíveis ou sem chave de tiles. */
(function () {
    'use strict';

    const TEMPO_SEM_TILES = 6000;
    const MAX_ERROS_TILES = 2;

    function limparTexto(valor) {
        return String(valor ?? '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function montarEndereco(opcoes = {}) {
        const endereco = limparTexto(opcoes.endereco);

        if (endereco) {
            return endereco;
        }

        return [
            opcoes.rua,
            opcoes.numero,
            opcoes.cidade,
            opcoes.uf,
            opcoes.cep,
        ]
            .map(limparTexto)
            .filter(Boolean)
            .join(', ');
    }

    function obterCoordenadas(opcoes = {}) {
        const latitude = Number(opcoes.latitude ?? opcoes.lat);
        const longitude = Number(opcoes.longitude ?? opcoes.lng);

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude) ||
            latitude < -90 ||
            latitude > 90 ||
            longitude < -180 ||
            longitude > 180
        ) {
            return null;
        }

        return { latitude, longitude };
    }

    function urlGoogleMaps(opcoes = {}) {
        const destinoCoordenadas = obterCoordenadas(opcoes);
        const endereco = montarEndereco(opcoes);
        const destino = destinoCoordenadas
            ? `${destinoCoordenadas.latitude},${destinoCoordenadas.longitude}`
            : endereco;

        if (!destino) {
            return 'https://www.google.com/maps';
        }

        const parametros = new URLSearchParams({
            api: '1',
            destination: destino,
        });

        const origemCoordenadas = obterCoordenadas(opcoes.origem || {});

        if (origemCoordenadas) {
            parametros.set('origin', `${origemCoordenadas.latitude},${origemCoordenadas.longitude}`);
        }

        if (opcoes.modo) {
            parametros.set('travelmode', opcoes.modo);
        }

        return `https://www.google.com/maps/dir/?${parametros.toString()}`;
    }

    function urlWaze(opcoes = {}) {
        const destinoCoordenadas = obterCoordenadas(opcoes);

        if (destinoCoordenadas) {
            return (
                `https://www.waze.com/ul?ll=${destinoCoordenadas.latitude},${destinoCoordenadas.longitude}` +
                '&navigate=yes'
            );
        }

        const endereco = montarEndereco(opcoes);

        if (!endereco) {
            return 'https://www.waze.com';
        }

        return `https://www.waze.com/ul?q=${encodeURIComponent(endereco)}&navigate=yes`;
    }

    function criarLink(href, classe, icone, rotulo) {
        const link = document.createElement('a');
        link.className = `mapa-fallback__botao ${classe}`;
        link.href = href;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';

        const iconeElemento = document.createElement('i');
        iconeElemento.className = `bi ${icone}`;
        iconeElemento.setAttribute('aria-hidden', 'true');

        const texto = document.createElement('span');
        texto.textContent = rotulo;

        link.append(iconeElemento, texto);
        return link;
    }

    function exibirFallback(container, opcoes = {}) {
        if (!container) {
            return;
        }

        const endereco = montarEndereco(opcoes);
        const bloco = document.createElement('div');
        bloco.className = 'mapa-fallback';
        bloco.setAttribute('role', 'region');
        bloco.setAttribute('aria-label', 'Localização do evento');

        const icone = document.createElement('span');
        icone.className = 'mapa-fallback__icone';
        icone.setAttribute('aria-hidden', 'true');

        const iconeInterno = document.createElement('i');
        iconeInterno.className = 'bi bi-geo-alt-fill';
        icone.append(iconeInterno);

        const titulo = document.createElement('strong');
        titulo.className = 'mapa-fallback__titulo';
        titulo.textContent = 'Local do evento';

        const textoEndereco = document.createElement('p');
        textoEndereco.className = 'mapa-fallback__endereco';
        textoEndereco.textContent = endereco || 'Endereço não informado.';

        const acoes = document.createElement('div');
        acoes.className = 'mapa-fallback__acoes';
        acoes.append(
            criarLink(
                urlGoogleMaps(opcoes),
                'mapa-fallback__botao--principal',
                'bi-map-fill',
                'Abrir rota no Google Maps',
            ),
            criarLink(urlWaze(opcoes), 'mapa-fallback__botao--secundario', 'bi-car-front-fill', 'Abrir no Waze'),
        );

        bloco.append(icone, titulo, textoEndereco, acoes);
        container.replaceChildren(bloco);
        container.classList.add('mapa-fallback-container');
    }

    function removerFallback(container) {
        if (!container) {
            return;
        }

        container.classList.remove('mapa-fallback-container');
        container.replaceChildren();
    }

    function monitorarTiles(mapa, container, opcoes, aoFalhar) {
        if (!mapa || typeof mapa.on !== 'function') {
            return { parar: () => {}, falhar: () => {} };
        }

        let tilesCarregados = 0;
        let erros = 0;
        let encerrado = false;
        let temporizador = null;

        const limparTemporizador = () => {
            if (temporizador !== null) {
                window.clearTimeout(temporizador);
                temporizador = null;
            }
        };

        const encerrar = () => {
            encerrado = true;
            limparTemporizador();
        };

        const falhar = () => {
            if (encerrado) {
                return;
            }

            encerrar();

            if (typeof aoFalhar === 'function') {
                aoFalhar();
            }
        };

        mapa.on('tileload', () => {
            tilesCarregados += 1;

            /* Ao menos um tile carregou: o mapa está operacional. */
            if (tilesCarregados > 0) {
                encerrar();
            }
        });

        mapa.on('tileerror', () => {
            erros += 1;

            if (erros >= MAX_ERROS_TILES) {
                falhar();
            }
        });

        temporizador = window.setTimeout(() => {
            if (tilesCarregados === 0) {
                falhar();
            }
        }, TEMPO_SEM_TILES);

        return {
            parar: encerrar,
            falhar,
        };
    }

    window.ShowMeMapa = {
        exibirFallback,
        removerFallback,
        monitorarTiles,
        urlGoogleMaps,
        urlWaze,
    };
})();
