<?php

session_start();
require_once __DIR__ . '/assets/config/conexao.php';

$erro = '';
$cadastroConcluido = isset($_GET['sucesso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senhaInformada = $_POST['senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senhaInformada === '') {
        $erro = 'Informe um e-mail válido e a senha.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id_user, nome_user, senha_user FROM usuario WHERE email_user = ? LIMIT 1'
        );

        if (!$stmt) {
            $erro = 'Não foi possível processar o login agora.';
        } else {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->bind_result($idUser, $nomeUser, $senhaHash);

            if ($stmt->fetch() && password_verify($senhaInformada, (string) $senhaHash)) {
                $stmt->close();
                $conn->close();

                session_regenerate_id(true);
                $_SESSION['id_user'] = $idUser;
                $_SESSION['nome_user'] = $nomeUser;

                header('Location: inicio.php');
                exit;
            }

            $stmt->close();
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
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

                <?php if ($erro !== ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="POST">

                    <div class="mb-3">
                        <label>E-mail</label>

                        <div class="input-group custom-input">

                            <span class="input-group-text">
                            <i class="bi bi-envelope-fill"></i>
                        </span>

                            <input type="email" class="form-control" name="email" placeholder="seu@gmail.com" required
                                value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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

</body>
</html>
