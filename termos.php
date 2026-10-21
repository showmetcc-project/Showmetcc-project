<?php
require_once __DIR__ . '/config/sessao.php';
iniciarSessaoSegura();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <title>Termos de Uso - ShowMe</title>
    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        .pagina-termos {
            min-height: 100vh;
            min-height: 100svh;
            padding: 104px 20px 40px;
            background: var(--showme-bg);
            color: var(--showme-text);
        }

        .termos-container {
            width: min(100%, 900px);
            min-height: calc(100svh - 144px);
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            padding: clamp(24px, 5vw, 56px);
            border: 1px solid var(--showme-border);
            border-radius: 12px;
            background: var(--showme-surface);
        }

        .termos-container h1 {
            margin: 0;
            color: var(--showme-text);
            font-family: var(--heading-font);
            font-size: 2rem;
        }

        .conteudo-termos {
            flex: 1 1 auto;
            min-height: 180px;
            margin-top: 28px;
        }

        .termos-voltar {
            align-self: flex-start;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 44px;
            margin-top: 24px;
            padding: 10px 18px;
            border: 1px solid var(--showme-primary);
            border-radius: 8px;
            background: transparent;
            color: var(--showme-primary);
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            transition: background-color .18s ease, color .18s ease;
        }

        .termos-voltar:hover,
        .termos-voltar:focus-visible {
            background: var(--showme-primary);
            color: #000;
        }

        @media (max-width: 575.98px) {
            .pagina-termos {
                padding: 84px 14px 20px;
            }

            .termos-container {
                min-height: calc(100svh - 104px);
                padding: 24px 20px;
            }

            .termos-container h1 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>
<body>
<?php
$tipoCabecalho = 'D';
$configuracaoCabecalho = [
    'fallback' => 'index.php',
];
require __DIR__ . '/cabecalho.php';
?>
<main class="pagina-termos">
    <section class="termos-container" aria-labelledby="tituloTermos">
        <h1 id="tituloTermos">Termos de Uso</h1>
        <div class="conteudo-termos"></div>
        <a class="termos-voltar" href="index.php" data-voltar-fallback="index.php">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Voltar
        </a>
    </section>
</main>
</body>
</html>
