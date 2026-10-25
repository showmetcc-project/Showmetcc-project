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
    <link href="assets/css/paginasLegais.css" rel="stylesheet">
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
