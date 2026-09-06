<?php $cadastroConcluido = isset($_GET['sucesso']); ?>
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
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
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
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
        rel="stylesheet">

</head>


<body class="index-page">

    <main class="main-login">

        <div class="login-wrapper">

            <div class="logo">
                <img src="assets/img/showme.png">
            </div>

            <div class="login-card">

                <h2>Entrar</h2>

                <button type="button" class="social-btn google-btn">
                <i class="bi bi-google"></i>
                Continuar com Google
            </button>

                <a href="api_spotify/spotify_login.php" class="social-btn spotify-btn">
                    <i class="bi bi-spotify"></i> Continuar com Spotify
                </a>

                <div class="divider">
                    <span>ou</span>
                </div>

                <?php if ($cadastroConcluido): ?>
                    <div class="alert alert-success" role="alert">
                        Cadastro realizado. Agora você já pode entrar.
                    </div>
                <?php endif; ?>

                <div id="mensagemLogin" class="alert d-none" role="alert"></div>

                <form id="formLogin">

                    <div class="mb-3">
                        <label>E-mail</label>

                        <div class="input-group custom-input">

                            <span class="input-group-text">
                            <i class="bi bi-envelope-fill"></i>
                        </span>

                            <input type="email" class="form-control" name="email" placeholder="seu@gmail.com" required>
                        </div>

                    </div>

                    <div class="mb-3">
                        <label>Senha</label>

                        <div class="input-group custom-input">

                            <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>

                            <input type="password" class="form-control" name="senha" placeholder="••••••••" required>
                        </div>

                    </div>

                    <button type="submit" class="btn-login">Entrar</button>

                </form>

                <div class="admin-link">
                    Procurando pela
                    <a href="loginAdm.php">área administrativa?</a>
                </div>

                <div class="register-link">
                    <a href="cadastro.php">Não tenho Cadastro</a>
                </div>

            </div>

        </div>

    </main>
    <?php require __DIR__ . '/rodape.php'; ?>

    <script>
        const formLogin = document.getElementById('formLogin');
        const mensagemLogin = document.getElementById('mensagemLogin');

        formLogin.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            mensagemLogin.className = 'alert d-none';

            const formulario = new FormData(formLogin);

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

                window.location.href = 'inicio.php';
            } catch (erro) {
                mensagemLogin.textContent = erro.message;
                mensagemLogin.className = 'alert alert-danger';
            }
        });
    </script>

</body>
</html>
