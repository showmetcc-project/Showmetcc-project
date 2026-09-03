<?php require_once __DIR__ . '/config/verifica_login.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - ShowMe</title>

    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,300;1,400;1,500;1,600;1,700;1,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Jost:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Roboto+Condensed:ital,wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- CSS -->
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/usuario.css" rel="stylesheet">
</head>

<body>

 
    <nav class="topo">
        <a href="inicio.php" class="voltar">
            <i class="bi bi-arrow-left"></i>
            <span class="verde">Meu</span> <span class="rosa">Perfil</span>
        </a>
        <a href="logout.php" class="sair">
            <i class="bi bi-box-arrow-right"></i>
            <?= htmlspecialchars($_SESSION['nome_user'], ENT_QUOTES, 'UTF-8') ?> · Sair
        </a>
    </nav>

    
    <div class="banner-wrap">
        <div class="banner"></div>
        <div class="avatar">
            <i class="bi bi-person"></i>
        </div>
    </div>

    <main class="perfil-container">

 
        <div class="perfil-header">
            <div class="email-principal">User@gmail.com</div>
            <button class="btn-editar">
                <i class="bi bi-pencil"></i>
                Editar Perfil
            </button>
        </div>

     
        <form class="perfil-form">

            <div class="grupo">
                <label>Nome</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input type="text" value="Nome" readonly>
                </div>
            </div>

            <div class="grupo">
                <label>Sobrenome</label>
                <div class="input-icon">
                    <i class="bi bi-person"></i>
                    <input type="text" value="User" readonly>
                </div>
            </div>

            <div class="grupo full">
                <label>Apelido</label>
                <div class="input-icon">
                    <i class="bi bi-at"></i>
                    <input type="text" value="userzinho" readonly>
                </div>
            </div>

            <div class="grupo full">
                <label>E-mail</label>
                <div class="input-icon">
                    <i class="bi bi-envelope"></i>
                    <input type="email" value="User@gmail.com" readonly>
                </div>
            </div>

        </form>

        <!-- ESTATÍSTICAS -->
        <section class="estatisticas">

            <h2>Estatísticas</h2>

            <div class="stats-grid">

                <div class="stat-card">
                    <i class="bi bi-heart-fill stat-icon"></i>
                    <h3>12</h3>
                    <p>Eventos favoritados</p>
                </div>

                <div class="stat-card">
                    <i class="bi bi-map-fill stat-icon"></i>
                    <h3>5</h3>
                    <p>Viagens planejadas</p>
                </div>

                <div class="stat-card">
                    <i class="bi bi-calendar-check-fill stat-icon"></i>
                    <h3>8</h3>
                    <p>Eventos cadastrados</p>
                </div>

            </div>

        </section>

    </main>

    <!-- FOOTER (reutilizado do index) -->
    <?php require __DIR__ . '/rodape.php'; ?>

    <!-- Scroll Top -->
    <a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-up-short"></i>
    </a>

    <!-- Vendor JS (mesmo padrão do index) -->
    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/vendor/aos/aos.js"></script>
    <script src="assets/js/perfil.js"></script>

    <script>AOS.init();</script>

</body>

</html>
