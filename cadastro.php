<?php

session_start();
require_once __DIR__ . '/assets/config/conexao.php';

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nome = trim($_POST['nome'] ?? '');
  $sobrenome = trim($_POST['sobrenome'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $senha = $_POST['senha'] ?? '';

  if ($nome === '' || $sobrenome === '') {
    $erro = 'Informe o nome e o sobrenome.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erro = 'Informe um e-mail válido.';
  } elseif (strlen($senha) < 6) {
    $erro = 'A senha deve ter pelo menos 6 caracteres.';
  } else {
    $stmt = $conn->prepare('SELECT id_user FROM usuario WHERE email_user = ? LIMIT 1');

    if (!$stmt) {
      $erro = 'Não foi possível verificar o e-mail agora.';
    } else {
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $stmt->store_result();

      if ($stmt->num_rows > 0) {
        $erro = 'Este e-mail já está cadastrado.';
      }

      $stmt->close();
    }

    if ($erro === '') {
      $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
      $stmt = $conn->prepare(
        'INSERT INTO usuario (nome_user, sobrenome, email_user, senha_user) VALUES (?, ?, ?, ?)'
      );

      if (!$stmt) {
        $erro = 'Não foi possível concluir o cadastro agora.';
      } else {
        $stmt->bind_param('ssss', $nome, $sobrenome, $email, $senhaHash);

        if ($stmt->execute()) {
          $stmt->close();
          $conn->close();
          header('Location: login.php?sucesso=1');
          exit;
        }

        $erro = 'Não foi possível concluir o cadastro agora.';
        $stmt->close();
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Cadastro - ShowMe</title>

  <!-- Favicon -->
  <link href="assets/img/showme.png" rel="icon">

  <!-- Bootstrap -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

  <!-- Bootstrap Icons -->
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <!-- Fonte -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

  <!-- CSS -->
  <link href="assets/css/main.css" rel="stylesheet">

</head>

<body>

  <main class="main-cadastro">

    <div class="cadastro-container">

      <div class="cadastro-logo">
        <img src="assets/img/showme.png" alt="ShowMe">
      </div>

      <section class="cadastro-card">

        <h1>Criar Conta</h1>

        <?php if ($erro !== ''): ?>
          <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <form action="cadastro.php" method="POST">

          <div class="row">

            <div class="col-md-6 mb-3">

              <label for="nome">Nome</label>

              <div class="cadastro-input-box">
                <i class="bi bi-person"></i>

                <input type="text" class="form-control cadastro-input" placeholder="Digite seu nome" name="nome" id="nome" required
                  value="<?= htmlspecialchars($_POST['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              
                </div>

            </div>

            <div class="col-md-6 mb-3">

              <label for="sobrenome">Sobrenome</label>

              <div class="cadastro-input-box">
                <i class="bi bi-person"></i>

                <input
                  type="text"
                  class="form-control cadastro-input"
                  placeholder="Digite seu sobrenome"
                  name="sobrenome"
                  id="sobrenome"
                  required
                  value="<?= htmlspecialchars($_POST['sobrenome'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>

            </div>

          </div>

          <div class="mb-3">

            <label for="email">E-mail</label>

            <div class="cadastro-input-box">
              <i class="bi bi-envelope"></i>

              <input
                type="email"
                class="form-control cadastro-input"
                placeholder="seuemail@gmail.com"
                name="email"
                id="email"
                required
                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

          </div>

          <div class="mb-4">

            <label for="senha">Senha</label>

            <div class="cadastro-input-box">
              <i class="bi bi-lock"></i>

              <input
                type="password"
                class="form-control cadastro-input"
                placeholder="Mínimo 6 caracteres"
                name="senha"
                id="senha"
                minlength="6"
                required>
            </div>

          </div>

          <button type="submit" class="cadastro-btn">
            Criar Conta
          </button>

        </form>

        <p class="cadastro-link">

          Já possui conta?

          <a href="login.php">
            Entrar
          </a>

        </p>

      </section>

    </div>

  </main>


  <?php require __DIR__ . '/rodape.php'; ?>


</body>

</html>
