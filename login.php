<?php
$cadastroConcluido = isset($_GET['sucesso']);
$senhaRedefinida = isset($_GET['senha_redefinida']);
$googleConfig = is_file(__DIR__ . '/config/google.php') ? require __DIR__ . '/config/google.php' : [];
$googleClientId = is_array($googleConfig) ? trim((string) ($googleConfig['client_id'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>Showme</title>
    <meta name="description" content="">
    <meta name="keywords" content="">

    <link href="assets/img/showme.png" rel="icon">

    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Oswald:wght@400;500;600;700&family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">

    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page login-comum-page">
    <a href="institucional.php" class="voltar-home-auth" data-voltar-fallback="institucional.php" aria-label="Voltar para a página anterior" title="Voltar">
        <i class="bi bi-arrow-left"></i>
    </a>

    <main class="main-login">
        <div class="login-wrapper">
            <div class="logo">
                <img src="assets/img/showme.png">
            </div>

            <div class="login-card">
                <h2>Entrar</h2>

                <div
                    class="google-login-container"
                    data-google-login
                    data-google-client-id="<?= htmlspecialchars($googleClientId, ENT_QUOTES, 'UTF-8') ?>"
                    data-google-endpoint="api/sessoes/"
                    data-google-error-target="mensagemLogin"
                    data-google-success-url="index.php"
                    data-google-text="continue_with"></div>

                <div class="divider">
                    <span>ou</span>
                </div>

                <?php if ($cadastroConcluido): ?>
                    <div class="alert alert-success" role="alert">
                        Cadastro realizado. Agora você já pode entrar.
                    </div>
                <?php endif; ?>

                <?php if ($senhaRedefinida): ?>
                    <div class="alert alert-success" role="status">
                        Senha redefinida com sucesso. Entre com sua nova senha.
                    </div>
                <?php endif; ?>

                <div id="mensagemLogin" class="alert d-none" role="alert"></div>

                <form id="formLogin">
                    <div class="mb-3">
                        <label for="emailLogin">E-mail</label>
                        <div class="login-input-box">
                            <span class="login-input-icone"><i class="bi bi-envelope-fill" aria-hidden="true"></i></span>
                            <input id="emailLogin" type="email" class="form-control login-input" name="email" placeholder="seu@gmail.com" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="senhaLogin">Senha</label>
                        <div class="login-input-box">
                            <span class="login-input-icone"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                            <input id="senhaLogin" type="password" class="form-control login-input" name="senha" placeholder="••••••••" autocomplete="current-password" aria-label="Senha" required>
                            <button class="login-alternar-senha" type="button" aria-label="Mostrar senha" aria-pressed="false" aria-controls="senhaLogin" title="Mostrar senha">
                                <i class="bi bi-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="login-esqueceu-senha">
                            <a href="recuperarSenha.php">Esqueceu a senha?</a>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Entrar</button>
                </form>

                <div class="admin-link">
                    Procurando pela
                    <a href="loginAdmin.php">área administrativa?</a>
                </div>

                <div class="register-link">
                    <a href="cadastro.php">Não tenho Cadastro</a>
                </div>
            </div>
        </div>
    </main>
    <script src="assets/js/csrf.js"></script>
    <script src="assets/js/login.js" defer></script>
    <script src="assets/js/loginPasswordToggle.js" defer></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script src="assets/js/googleLogin.js" defer></script>
    <script src="assets/js/cabecalho.js" defer></script>
    <script src="assets/js/main.js"></script>

    <?php require_once __DIR__ . '/acessibilidade.php'; ?>
</body>
</html>
