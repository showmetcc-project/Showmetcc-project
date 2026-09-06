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

        <div id="mensagemCadastro" class="alert d-none" role="alert"></div>

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

  <script>
    const formCadastro = document.getElementById('formCadastro');
    const mensagemCadastro = document.getElementById('mensagemCadastro');

    formCadastro.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      mensagemCadastro.className = 'alert d-none';
      const formulario = new FormData(formCadastro);

      try {
        const resposta = await fetch('api/usuarios/', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            nome: formulario.get('nome'),
            sobrenome: formulario.get('sobrenome'),
            email: formulario.get('email'),
            senha: formulario.get('senha')
          })
        });
        const dados = await resposta.json();

        if (!resposta.ok) {
          throw new Error(dados.erro || 'Não foi possível concluir o cadastro.');
        }

        window.location.href = 'login.php?sucesso=1';
      } catch (erro) {
        mensagemCadastro.textContent = erro.message;
        mensagemCadastro.className = 'alert alert-danger';
      }
    });
  </script>


</body>

</html>
