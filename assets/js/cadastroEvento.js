(function () {
    'use strict';

    const formulario = document.getElementById('formEvento');
    const inputFoto = document.getElementById('imagemEvento');
    const dropzoneFoto = document.getElementById('dropzoneFotoEvento');
    const nomeFoto = document.getElementById('nomeFotoEvento');
    const botaoEnviar = document.getElementById('botaoEnviarEvento');
    const inputCep = document.getElementById('cepEvento');
    const inputEndereco = document.getElementById('enderecoEvento');
    const inputRua = document.getElementById('ruaEvento');
    const inputCidade = document.getElementById('cidadeEvento');
    const inputUf = document.getElementById('ufEvento');
    const statusCep = document.getElementById('statusCepEvento');
    const camposValor = document.getElementById('camposValorIngresso');
    const valorMinimo = document.getElementById('valorIngressoMinimo');
    const valorMaximo = document.getElementById('valorIngressoMaximo');
    const campoGeneroArtista = document.getElementById('campoGeneroArtista');
    const inputGeneroArtista = document.getElementById('generoArtistaSolicitado');
    const categorias = formulario ? [...formulario.querySelectorAll('input[name="categoria_evento[]"]')] : [];
    const seletorCategorias = document.getElementById('seletorCategoriasEvento');
    const botaoCategorias = document.getElementById('botaoCategoriasEvento');
    const painelCategorias = document.getElementById('painelCategoriasEvento');
    const resumoCategorias = document.getElementById('resumoCategoriasEvento');
    const erroCategorias = document.getElementById('erroCategoriasEvento');
    const listaSessoes = document.getElementById('listaSessoesEvento');
    const templateSessao = document.getElementById('templateSessaoEvento');
    const botaoAdicionarSessao = document.getElementById('botaoAdicionarSessao');
    const erroSessoes = document.getElementById('erroSessoesEvento');
    const tiposFotoAceitos = ['image/jpeg', 'image/png', 'image/webp'];
    const limiteFoto = 10 * 1024 * 1024;
    const limiteSessoes = 30;
    let cepConsultado = '';
    let consultaCepAtual = 0;

    if (!formulario || !inputFoto || !dropzoneFoto || !botaoEnviar) {
        return;
    }

    formulario.dataset.apiConectada = 'true';

    function definirPainelCategoriasAberto(aberto) {
        if (!botaoCategorias || !painelCategorias) return;
        botaoCategorias.setAttribute('aria-expanded', String(aberto));
        painelCategorias.hidden = !aberto;
    }

    function atualizarResumoCategorias() {
        if (!resumoCategorias) return;

        const selecionadas = categorias.filter((categoria) => categoria.checked);
        resumoCategorias.replaceChildren();

        if (!selecionadas.length) {
            const placeholder = document.createElement('span');
            placeholder.className = 'placeholder-categorias';
            placeholder.textContent = 'Selecione uma ou mais categorias';
            resumoCategorias.appendChild(placeholder);
        } else {
            selecionadas.forEach(function (categoria) {
                const marcador = document.createElement('span');
                marcador.className = 'categoria-selecionada';
                marcador.textContent = categoria.value;
                resumoCategorias.appendChild(marcador);
            });
        }

        categorias.forEach(function (categoria) {
            categoria.closest('.opcao-categoria')?.classList.toggle('selecionada', categoria.checked);
        });
    }

    function normalizarCategoria(valor) {
        return String(valor || '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function atualizarCampoGeneroArtista() {
        if (!campoGeneroArtista || !inputGeneroArtista) return;

        const musical = categorias.some(function (categoria) {
            if (!categoria.checked) return false;
            const valor = normalizarCategoria(categoria.value);
            return valor.includes('musica') || valor.includes('musical');
        });

        campoGeneroArtista.hidden = !musical;
        inputGeneroArtista.disabled = !musical;

        if (!musical) {
            inputGeneroArtista.value = '';
        }
    }

    botaoCategorias?.addEventListener('click', function () {
        definirPainelCategoriasAberto(botaoCategorias.getAttribute('aria-expanded') !== 'true');
    });

    document.addEventListener('click', function (evento) {
        if (seletorCategorias && !seletorCategorias.contains(evento.target)) {
            definirPainelCategoriasAberto(false);
        }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape' && botaoCategorias?.getAttribute('aria-expanded') === 'true') {
            definirPainelCategoriasAberto(false);
            botaoCategorias.focus();
        }
    });

    async function lerRespostaJson(resposta) {
        try {
            return await resposta.json();
        } catch (erro) {
            return {};
        }
    }

    function validarFoto() {
        const arquivos = inputFoto.files;

        if (arquivos.length !== 1) {
            throw new Error('Selecione exatamente uma foto do evento.');
        }

        const foto = arquivos[0];

        if (!tiposFotoAceitos.includes(foto.type)) {
            throw new Error('Envie somente uma foto JPG, PNG ou WebP. Vídeos não são aceitos.');
        }

        if (foto.size > limiteFoto) {
            throw new Error('A foto deve ter no máximo 10 MB.');
        }
    }

    function atualizarFotoSelecionada() {
        const foto = inputFoto.files[0];
        nomeFoto.textContent = foto ? foto.name : '';
        dropzoneFoto.classList.toggle('arquivo-selecionado', Boolean(foto));
    }

    function abrirSeletorFoto(evento) {
        if (evento.target === inputFoto) return;
        inputFoto.click();
    }

    dropzoneFoto.addEventListener('click', abrirSeletorFoto);
    dropzoneFoto.addEventListener('keydown', function (evento) {
        if (evento.key === 'Enter' || evento.key === ' ') {
            evento.preventDefault();
            inputFoto.click();
        }
    });
    ['dragenter', 'dragover'].forEach(function (tipo) {
        dropzoneFoto.addEventListener(tipo, function (evento) {
            evento.preventDefault();
            dropzoneFoto.classList.add('arrastando');
        });
    });
    ['dragleave', 'drop'].forEach(function (tipo) {
        dropzoneFoto.addEventListener(tipo, function (evento) {
            evento.preventDefault();
            dropzoneFoto.classList.remove('arrastando');
        });
    });
    dropzoneFoto.addEventListener('drop', function (evento) {
        const arquivos = evento.dataTransfer?.files;
        if (!arquivos?.length) return;
        if (arquivos.length !== 1) {
            window.ShowMeUI.toast('Envie somente uma foto do evento.', { variante: 'erro' });
            return;
        }

        const transferencia = new DataTransfer();
        transferencia.items.add(arquivos[0]);
        inputFoto.files = transferencia.files;
        atualizarFotoSelecionada();
    });
    inputFoto.addEventListener('change', atualizarFotoSelecionada);

    function linhasSessoes() {
        return listaSessoes ? [...listaSessoes.querySelectorAll('[data-sessao]')] : [];
    }

    function reindexarSessoes() {
        const linhas = linhasSessoes();

        linhas.forEach(function (linha, indice) {
            const campos = [...linha.querySelectorAll('[data-campo]')];

            campos.forEach(function (campo) {
                const sufixo = campo.dataset.campo || 'campo';
                const identificador = `sessaoEvento${indice}-${sufixo}`;
                const rotulo = campo.closest('.campo-sessao')?.querySelector('label');

                campo.id = identificador;
                campo.name = `sessoes[${indice}][${sufixo}]`;

                if (rotulo) {
                    rotulo.htmlFor = identificador;
                }
            });

            const remover = linha.querySelector('[data-remover-sessao]');

            if (remover) {
                remover.hidden = linhas.length === 1;
                remover.disabled = linhas.length === 1;
            }
        });
    }

    function atualizarErroSessoes(mensagem) {
        if (erroSessoes) {
            erroSessoes.textContent = mensagem;
        }
    }

    function adicionarSessao() {
        if (!listaSessoes || !templateSessao) return;
        if (linhasSessoes().length >= limiteSessoes) {
            atualizarErroSessoes(`Adicione no máximo ${limiteSessoes} datas/sessões.`);
            return;
        }

        const novaSessao = templateSessao.content.firstElementChild.cloneNode(true);
        listaSessoes.appendChild(novaSessao);
        reindexarSessoes();
        atualizarErroSessoes('');
        novaSessao.querySelector('[data-campo="data_evento"]')?.focus();
    }

    botaoAdicionarSessao?.addEventListener('click', adicionarSessao);

    listaSessoes?.addEventListener('click', function (evento) {
        const botaoRemover = evento.target.closest('[data-remover-sessao]');

        if (!botaoRemover) return;

        const linhas = linhasSessoes();

        if (linhas.length <= 1) return;

        botaoRemover.closest('[data-sessao]')?.remove();
        reindexarSessoes();
        atualizarErroSessoes('');
    });

    function validarSessoes() {
        const linhas = linhasSessoes();

        if (!linhas.length) {
            throw new Error('Informe ao menos uma data e um horário para o evento.');
        }

        const horarios = new Set();

        for (const linha of linhas) {
            const data = linha.querySelector('[data-campo="data_evento"]')?.value || '';
            const inicio = linha.querySelector('[data-campo="hora_inicio"]')?.value || '';
            const fim = linha.querySelector('[data-campo="hora_fim"]')?.value || '';

            if (!data || !inicio || !fim) {
                throw new Error('Preencha a data, o início e o fim de todas as sessões.');
            }

            if (fim <= inicio) {
                throw new Error('O horário de fim precisa ser posterior ao horário de início.');
            }

            const chave = `${data} ${inicio}`;

            if (horarios.has(chave)) {
                throw new Error('Não repita a mesma data com o mesmo horário de início.');
            }

            horarios.add(chave);
        }
    }

    function limparSessoesExtras() {
        const linhas = linhasSessoes();

        linhas.slice(1).forEach(function (linha) {
            linha.remove();
        });

        reindexarSessoes();
        atualizarErroSessoes('');
    }

    reindexarSessoes();

    function limparEndereco(mensagem) {
        inputEndereco.value = '';
        inputRua.value = '';
        inputCidade.value = '';
        inputUf.value = '';
        statusCep.textContent = mensagem;
        statusCep.classList.remove('sucesso');
    }

    function formatarCep(valor) {
        const numeros = String(valor || '')
            .replace(/\D/g, '')
            .slice(0, 8);
        return numeros.length > 5 ? `${numeros.slice(0, 5)}-${numeros.slice(5)}` : numeros;
    }

    async function consultarCep(cep) {
        const identificadorConsulta = ++consultaCepAtual;
        statusCep.textContent = 'Buscando endereço...';
        statusCep.classList.remove('sucesso', 'erro');

        try {
            const resposta = await fetch(`https://brasilapi.com.br/api/cep/v2/${encodeURIComponent(cep)}`, {
                headers: { Accept: 'application/json' },
            });
            const dados = await lerRespostaJson(resposta);
            if (!resposta.ok) throw new Error('CEP não encontrado.');
            if (identificadorConsulta !== consultaCepAtual) return;

            const cidadeUf = [dados.city, dados.state].filter(Boolean).join(' - ');
            const enderecoCompleto = [
                dados.street,
                dados.neighborhood,
                cidadeUf,
                `CEP ${formatarCep(dados.cep || cep)}`,
            ]
                .filter(Boolean)
                .join(', ');

            if (!enderecoCompleto || !dados.city || !dados.state) {
                throw new Error('O CEP não retornou um endereço completo.');
            }

            inputEndereco.value = enderecoCompleto;
            inputRua.value = String(dados.street || '').trim();
            inputCidade.value = String(dados.city || '').trim();
            inputUf.value = String(dados.state || '')
                .trim()
                .toUpperCase();
            cepConsultado = cep;
            statusCep.textContent = 'Endereço preenchido pela BrasilAPI.';
            statusCep.classList.add('sucesso');
        } catch (erro) {
            if (identificadorConsulta !== consultaCepAtual) return;
            cepConsultado = '';
            limparEndereco(erro.message || 'Não foi possível consultar o CEP.');
            statusCep.classList.add('erro');
        }
    }

    inputCep.addEventListener('input', function () {
        inputCep.value = formatarCep(inputCep.value);
        const cep = inputCep.value.replace(/\D/g, '');
        if (cep.length !== 8) {
            cepConsultado = '';
            limparEndereco('Digite os 8 números do CEP.');
            return;
        }
        if (cep !== cepConsultado) consultarCep(cep);
    });

    function atualizarCamposValor() {
        const pago = document.getElementById('pago').checked;
        camposValor.hidden = !pago;
        camposValor.setAttribute('aria-hidden', String(!pago));
        formulario.dataset.gratuidade = String(!pago);
        valorMinimo.required = pago;
        valorMinimo.disabled = !pago;
        valorMaximo.disabled = !pago;
        if (!pago) {
            valorMinimo.value = '';
            valorMaximo.value = '';
        }
    }

    formulario.querySelectorAll('input[name="gratuidade"]').forEach(function (radio) {
        radio.addEventListener('change', atualizarCamposValor);
    });
    atualizarCamposValor();

    categorias.forEach(function (categoria) {
        categoria.addEventListener('change', function () {
            atualizarResumoCategorias();
            atualizarCampoGeneroArtista();
            const possuiCategoria = categorias.some((item) => item.checked);
            botaoCategorias?.setAttribute('aria-invalid', String(!possuiCategoria));
            erroCategorias.textContent = possuiCategoria ? '' : 'Selecione pelo menos uma categoria.';
        });
    });
    atualizarResumoCategorias();
    atualizarCampoGeneroArtista();

    formulario.addEventListener('submit', async function (evento) {
        evento.preventDefault();
        if (!formulario.checkValidity()) {
            formulario.reportValidity();
            return;
        }

        try {
            validarSessoes();
            validarFoto();
            if (!categorias.some((categoria) => categoria.checked)) {
                erroCategorias.textContent = 'Selecione pelo menos uma categoria.';
                botaoCategorias?.setAttribute('aria-invalid', 'true');
                definirPainelCategoriasAberto(true);
                categorias[0]?.focus();
                throw new Error('Selecione pelo menos uma categoria para o evento.');
            }
            const cep = inputCep.value.replace(/\D/g, '');
            if (cep.length !== 8 || cep !== cepConsultado || !inputEndereco.value) {
                throw new Error('Informe um CEP válido e aguarde o preenchimento do endereço.');
            }
            if (
                !camposValor.hidden &&
                valorMaximo.value !== '' &&
                Number(valorMaximo.value) < Number(valorMinimo.value)
            ) {
                throw new Error('O valor máximo não pode ser menor que o valor mínimo.');
            }
            botaoEnviar.disabled = true;
            botaoEnviar.textContent = 'Enviando...';

            const resposta = await fetch('api/eventos/', {
                method: 'POST',
                body: new FormData(formulario),
            });
            const dados = await lerRespostaJson(resposta);

            if (resposta.status === 401) {
                window.location.href = 'login.php';
                return;
            }

            if (!resposta.ok) {
                throw new Error(dados.erro || 'Não foi possível enviar o evento.');
            }

            formulario.reset();
            cepConsultado = '';
            limparSessoesExtras();
            atualizarFotoSelecionada();
            atualizarCamposValor();
            atualizarResumoCategorias();
            atualizarCampoGeneroArtista();
            definirPainelCategoriasAberto(false);
            botaoCategorias?.setAttribute('aria-invalid', 'false');
            erroCategorias.textContent = '';
            limparEndereco('Digite o CEP para preencher o endereço automaticamente.');
            window.ShowMeUI.toast('Evento enviado para moderação! Aguarde a análise da equipe.', {
                variante: 'sucesso',
                duracao: 5000,
            });

            window.setTimeout(function () {
                window.location.href = 'perfilUsuario.php';
            }, 5200);
        } catch (erro) {
            window.ShowMeUI.toast(erro.message || 'Não foi possível enviar o evento.', {
                variante: 'erro',
            });
            botaoEnviar.disabled = false;
            botaoEnviar.textContent = 'Enviar para análise';
        }
    });
})();
