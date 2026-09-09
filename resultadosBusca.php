<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$termoBusca = isset($_GET['busca']) && is_string($_GET['busca'])
    ? trim($_GET['busca'])
    : '';
$termoBuscaEscapado = htmlspecialchars($termoBusca, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultados da busca - ShowMe</title>
  <meta name="description" content="Encontre eventos por nome, cidade, categoria ou artista.">

  <link href="assets/img/showme.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/cardsEvento.css" rel="stylesheet">
  <link href="assets/css/resultadosBusca.css" rel="stylesheet">
</head>

<body class="com-cabecalho-padrao">
  <?php require __DIR__ . '/cabecalho.php'; ?>

  <main class="resultados-busca-main">
    <div class="resultados-busca-container">
      <header class="resultados-busca-cabecalho">
        <?php if ($termoBusca !== ''): ?>
          <h1>Resultados para <span class="resultados-busca-termo">'<?= $termoBuscaEscapado ?>'</span></h1>
          <p id="resumoResultadosBusca">Buscando eventos...</p>
        <?php else: ?>
          <h1>Busca de eventos</h1>
          <p id="resumoResultadosBusca">Digite um termo no campo de busca acima.</p>
        <?php endif; ?>
      </header>

      <section
        id="resultadosBuscaGrid"
        class="resultados-busca-grid"
        aria-live="polite"
        aria-busy="true"
      >
        <p class="estado-resultados-busca">Carregando resultados...</p>
      </section>
    </div>
  </main>

  <?php require __DIR__ . '/rodape.php'; ?>

  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/resultadosBusca.js"></script>
</body>

</html>
