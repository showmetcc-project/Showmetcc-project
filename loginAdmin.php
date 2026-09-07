<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>Showme</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/showme.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
    rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

  <!-- Principal CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">


  <!--Google fontes, Anton-->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
    rel="stylesheet">

</head>


<body class="index-page">

  <main class="main-login">

    <div class="login-wrapper">

      <div class="logoadm">
        <img src="assets/img/showme.png" alt="ShowMe">
      </div>

      <div class="login-card">

        <h2>Entrar como Administrador</h2>

        <div id="mensagemLoginAdmin" class="alert d-none" role="alert"></div>

        <form id="formLoginAdmin">

          <div class="mb-4">
            <label for="email">E-mail</label>

            <input id="email" name="email" type="email" class="form-control login-input" placeholder="admin@gmail.com" required>
          </div>

          <div class="mb-4">
            <label for="senha">Senha</label>

            <div class="password-wrapper">

              <input id="senha" name="senha" type="password" class="form-control login-input" placeholder="••••••••" required>

               

            </div>

          </div>

          <button type="submit" class="btn-login">
            Entrar
          </button>

        </form>

        <p class="bottom-text">
          Não é administrador?
          <a href="inicio.php">Voltar para a área do usuário.</a>
        </p>

      </div>

    </div>

  </main>

  <?php require __DIR__ . '/rodape.php'; ?>

  <script>
    const formLoginAdmin = document.getElementById('formLoginAdmin');
    const mensagemLoginAdmin = document.getElementById('mensagemLoginAdmin');

    formLoginAdmin.addEventListener('submit', async (evento) => {
      evento.preventDefault();
      mensagemLoginAdmin.className = 'alert d-none';
      const formulario = new FormData(formLoginAdmin);

      try {
        const resposta = await fetch('api/sessoes/', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({
            email: formulario.get('email'),
            senha: formulario.get('senha')
          })
        });
        const dados = await resposta.json();

        if (!resposta.ok) {
          throw new Error(dados.erro || 'Não foi possível entrar.');
        }

        if (dados.usuario.tipo_usuario !== 'admin') {
          await fetch('api/sessoes/', {method: 'DELETE'});
          throw new Error('Esta conta não possui perfil de administrador.');
        }

        window.location.href = 'admin.php';
      } catch (erro) {
        mensagemLoginAdmin.textContent = erro.message;
        mensagemLoginAdmin.className = 'alert alert-danger';
      }
    });
  </script>

</body>
</html>
