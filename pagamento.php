<?php

require_once __DIR__ . '/config/sessao.php';
iniciarSessaoSegura();

$planos = [
    'sem-anuncios' => [
        'nome' => 'Sem Anúncios',
        'descricao' => 'Uma experiência sem interrupções.',
        'mensal' => 7.90,
        'anual' => null,
        'beneficios' => [
            'Acesso aos eventos da plataforma',
            'Navegação sem anúncios',
            'Mais conforto para descobrir eventos',
        ],
        'tipo' => 'mensal'
    ],

    'showme-plus' => [
        'nome' => 'ShowMe Plus',
        'descricao' => 'Sua experiência cultural completa.',
        'mensal' => 20.90,
        'anual' => 209.90,
        'beneficios' => [
            'Todos os benefícios do plano gratuito',
            'Sem anúncios',
            'Mais opções de planejamento de viagens',
            'Estimativas de transporte, ingressos e hospedagem',
            'Rotas e deslocamentos',
            'Favoritos e planejados ilimitados',
            'Mais sugestões de eventos',
        ],
        'tipo' => 'periodo'
    ],

    'organizadores' => [
        'nome' => 'ShowMe Organizadores',
        'descricao' => 'Mais visibilidade para seus eventos.',
        'mensal' => 46.90,
        'anual' => 379.90,
        'beneficios' => [
            'Cadastro e divulgação de eventos',
            'Envio ilimitado de eventos para análise',
            'Atualização das informações dos eventos',
            'Mais visibilidade para suas atrações',
            'Espaço para divulgação de eventos',
        ],
        'tipo' => 'periodo'
    ]
];

$codigoPlano = $_GET['plano'] ?? '';

if (!isset($planos[$codigoPlano])) {
    http_response_code(404);
    $plano = null;
} else {
    $plano = $planos[$codigoPlano];
}

$periodoInicial = 'mensal';
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Assinatura | ShowMe</title>
      <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">

  <link href="assets/css/main.css" rel="stylesheet">
<link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
<link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.css">
<link rel="stylesheet" href="assets/css/pagamento.css">
</head>

<body>

<header class="pagamento-header">
    <a href="javascript:history.back()" class="showme-logo">
        Show<span>Me</span><i>.</i>
    </a>

    <div class="header-pagamento-direita">
        <span>
            <i class="bi bi-lock"></i>
            Ambiente de assinatura
        </span>

        <a href="javascript:history.back()" class="link-voltar">
            VOLTAR
        </a>
    </div>
</header>

<main class="pagamento-page">

    <?php if (!$plano): ?>

        <section class="pagamento-erro">
            <i class="bi bi-exclamation-circle"></i>

            <h1>Plano não encontrado</h1>

            <p>
                Não conseguimos identificar o plano selecionado.
                Volte à página de planos e escolha uma assinatura.
            </p>

            <a href="institucional.php#fidelidade" class="btn-pagamento">
                Ver planos
            </a>
        </section>

    <?php else: ?>

        <section class="pagamento-container">

            <div class="pagamento-topo">

                <a href="javascript:history.back()" class="pagamento-voltar">
                    <i class="bi bi-arrow-left"></i>
                    Voltar
                </a>

                <span class="pagamento-etapa">
                    <i class="bi bi-shield-check"></i>
                    Assinatura ShowMe
                </span>

            </div>

            <div class="pagamento-titulo">

                <span class="pagamento-tag">
                    SUA EXPERIÊNCIA SHOWME
                </span>

                <h1>Finalize sua <span>assinatura</span></h1>

                <p>
                    Confira seu plano e escolha a modalidade
                    que melhor combina com você.
                </p>

            </div>

            <div class="pagamento-grid">

                <!-- PLANOS E PERÍODO -->
                <section class="pagamento-card pagamento-escolha">

                    <div class="pagamento-card-titulo">
                        <div class="pagamento-icone">
                            <i class="bi bi-stars"></i>
                        </div>

                        <div>
                            <h2>Seu plano</h2>
                            <p>Confira os detalhes da assinatura.</p>
                        </div>
                    </div>

                    <div class="plano-selecionado">

                        <span class="plano-identificacao">
                            PLANO SELECIONADO
                        </span>

                        <h3><?= htmlspecialchars($plano['nome']) ?></h3>

                        <p>
                            <?= htmlspecialchars($plano['descricao']) ?>
                        </p>

                    </div>

                    <?php if ($plano['tipo'] === 'periodo'): ?>

                        <div class="periodo-titulo">
                            Escolha a periodicidade
                        </div>

                        <div class="periodos">

                            <label class="periodo-option selecionado" id="opcaoMensal">

                                <input
                                    type="radio"
                                    name="periodo"
                                    value="mensal"
                                    checked
                                >

                                <span class="periodo-conteudo">
                                    <strong>Mensal</strong>
                                    <small>Pagamento mês a mês</small>
                                </span>

                                <span class="periodo-preco">
                                    R$ <?= number_format($plano['mensal'], 2, ',', '.') ?>
                                </span>

                            </label>

                            <label class="periodo-option" id="opcaoAnual">

                                <input
                                    type="radio"
                                    name="periodo"
                                    value="anual"
                                >

                                <span class="periodo-conteudo">
                                    <strong>Anual</strong>
                                    <small>Pagamento anual</small>
                                </span>

                                <span class="periodo-preco">
                                    R$ <?= number_format($plano['anual'], 2, ',', '.') ?>
                                </span>

                            </label>

                        </div>

                    <?php else: ?>

                        <div class="periodo-fixo">

                            <span>Periodicidade</span>

                            <strong>Mensal</strong>

                        </div>

                    <?php endif; ?>

                    <div class="beneficios-plano">

                        <h4>
                            <i class="bi bi-check-circle"></i>
                            O que está incluso
                        </h4>

                        <ul>

                            <?php foreach ($plano['beneficios'] as $beneficio): ?>

                                <li>
                                    <i class="bi bi-check2"></i>

                                    <?= htmlspecialchars($beneficio) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                </section>

                <!-- RESUMO -->
                <aside class="pagamento-card pagamento-resumo">

                    <div class="pagamento-card-titulo">

                        <div class="pagamento-icone">
                            <i class="bi bi-receipt"></i>
                        </div>

                        <div>
                            <h2>Resumo da assinatura</h2>
                            <p>Confira os valores antes de continuar.</p>
                        </div>

                    </div>

                    <div class="resumo-linha">
                        <span>Plano</span>
                        <strong><?= htmlspecialchars($plano['nome']) ?></strong>
                    </div>

                    <div class="resumo-linha">
                        <span>Periodicidade</span>
                        <strong id="resumoPeriodo">Mensal</strong>
                    </div>

                    <div class="resumo-linha">
                        <span>Valor da assinatura</span>
                        <strong id="resumoValor">
                            R$ <?= number_format($plano['mensal'], 2, ',', '.') ?>
                        </strong>
                    </div>

                    <div class="resumo-total">

                        <span>Total</span>

                        <strong id="resumoTotal">
                            R$ <?= number_format($plano['mensal'], 2, ',', '.') ?>
                        </strong>

                        <small id="resumoDetalhe">
                            Cobrança mensal
                        </small>

                    </div>

                    <div class="pagamento-seguranca">
                        <i class="bi bi-lock"></i>

                        <span>
                            Seus dados devem ser protegidos durante
                            o processo de assinatura.
                        </span>
                    </div>

                    <button
                        type="button"
                        id="btnContinuarPagamento"
                        class="btn-pagamento"
                    >
                        Continuar
                        <i class="bi bi-arrow-right"></i>
                    </button>

                    <a href="javascript:history.back()" class="pagamento-cancelar">
                        Voltar e escolher outro plano
                    </a>

                </aside>

            </div>

        </section>

    <?php endif; ?>

</main>
 <?php require __DIR__ . '/rodape.php'; ?>
 <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/aos/aos.js"></script>

  <script src="assets/js/odsModais.js"></script>
  <script src="assets/js/main.js"></script>

<script>
(function () {
    'use strict';

    const plano = <?= json_encode($codigoPlano) ?>;

    const valores = {
        mensal: <?= json_encode($plano ? $plano['mensal'] : 0) ?>,
        anual: <?= json_encode($plano ? $plano['anual'] : 0) ?>
    };

    const radios = document.querySelectorAll('input[name="periodo"]');
    const resumoPeriodo = document.getElementById('resumoPeriodo');
    const resumoValor = document.getElementById('resumoValor');
    const resumoTotal = document.getElementById('resumoTotal');
    const resumoDetalhe = document.getElementById('resumoDetalhe');
    const btnContinuar = document.getElementById('btnContinuarPagamento');

    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
    }

    function atualizarResumo(periodo) {
        const valor = valores[periodo];

        if (resumoPeriodo) {
            resumoPeriodo.textContent =
                periodo === 'anual' ? 'Anual' : 'Mensal';
        }

        if (resumoValor) {
            resumoValor.textContent = formatarMoeda(valor);
        }

        if (resumoTotal) {
            resumoTotal.textContent = formatarMoeda(valor);
        }

        if (resumoDetalhe) {
            resumoDetalhe.textContent =
                periodo === 'anual'
                    ? 'Cobrança anual'
                    : 'Cobrança mensal';
        }

        document.querySelectorAll('.periodo-option').forEach(opcao => {
            opcao.classList.remove('selecionado');
        });

        const selecionado = document.querySelector(
            'input[name="periodo"]:checked'
        );

        if (selecionado) {
            selecionado.closest('.periodo-option')
                ?.classList.add('selecionado');
        }
    }

    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            atualizarResumo(this.value);
        });
    });

    if (btnContinuar) {
        btnContinuar.addEventListener('click', function () {
            const periodoSelecionado =
                document.querySelector('input[name="periodo"]:checked');

            const periodo = periodoSelecionado
                ? periodoSelecionado.value
                : 'mensal';

            const valor = valores[periodo];

            alert(
                'Plano: ' + plano +
                '\nPeriodicidade: ' + periodo +
                '\nValor: ' + formatarMoeda(valor) +
                '\n\nA próxima etapa será a integração com o sistema de pagamento.'
            );
        });
    }

    atualizarResumo('mensal');

})();
</script>

</body>
</html>
