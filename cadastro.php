<?php
$googleConfig = is_file(__DIR__ . '/config/google.php') ? require __DIR__ . '/config/google.php' : [];
$googleClientId = is_array($googleConfig) ? trim((string) ($googleConfig['client_id'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>Cadastro - ShowMe</title>

  <link href="assets/img/showme.png" rel="icon">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

  <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

  <link href="assets/css/main.css" rel="stylesheet">
</head>

<body>
  <a href="institucional.php" class="voltar-home-auth" data-voltar-fallback="institucional.php" aria-label="Voltar para a página anterior" title="Voltar">
    <i class="bi bi-arrow-left"></i>
  </a>

  <main class="main-cadastro">
    <div class="cadastro-container">
      <div class="cadastro-logo">
        <img src="assets/img/showme.png" alt="ShowMe">
      </div>

      <section class="cadastro-card">
        <h1>Criar Conta</h1>

        <div id="mensagemCadastro" class="alert d-none" role="alert"></div>

        <div
          class="google-login-container"
          data-google-login
          data-google-client-id="<?= htmlspecialchars($googleClientId, ENT_QUOTES, 'UTF-8') ?>"
          data-google-endpoint="api/sessoes/"
          data-google-error-target="mensagemCadastro"
          data-google-success-url="index.php"
          data-google-text="signup_with"></div>

        <div class="divider">
          <span>ou</span>
        </div>

        <form id="formCadastro">
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="nome">Nome</label>

              <div class="cadastro-input-box">
                <i class="bi bi-person"></i>

                <input type="text" class="form-control cadastro-input" placeholder="Digite seu nome" name="nome" id="nome" required>
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
                  required>
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
                required>
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
                autocomplete="new-password"
                aria-describedby="ajudaSenha"
                required>
              <button class="login-alternar-senha cadastro-alternar-senha" type="button" aria-label="Mostrar senha" aria-pressed="false" aria-controls="senha" title="Mostrar senha">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>

            <small id="ajudaSenha" class="cadastro-ajuda-senha">
              A senha deve ter no mínimo 6 caracteres, contendo letras e números.
            </small>
          </div>

          <div class="cadastro-termos-consentimento">
            <input id="aceiteTermos" name="aceiteTermos" type="checkbox" value="1" required>
            <label for="aceiteTermos">Li e concordo com os <a href="termosUso.php" target="_blank" rel="noopener">Termos de Uso</a> e a <a href="politicaPrivacidade.php" target="_blank" rel="noopener">Política de Privacidade</a></label>
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

  <script src="assets/js/csrf.js"></script>
  <script src="assets/js/cadastro.js" defer></script>
  <script src="assets/js/loginPasswordToggle.js" defer></script>
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  <script src="assets/js/googleLogin.js" defer></script>
  <script src="assets/js/cabecalho.js" defer></script>
  <script src="assets/js/main.js"></script>

  <?php require_once __DIR__ . '/acessibilidade.php'; ?>
</body>
</html>
