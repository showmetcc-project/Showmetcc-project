<?php
$usuarioLogado = isset($_SESSION['id_user']);
$nomeUsuario = htmlspecialchars(
    (string) ($_SESSION['nome_user'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);
?>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">
    <a href="<?= $usuarioLogado ? 'inicio.php' : 'index.php' ?>" class="logo d-flex align-items-center me-auto">
      <img src="assets/img/showme.png" alt="ShowMe">
    </a>

    <nav id="navmenu" class="navmenu">
      <ul>
        <li><a href="<?= $usuarioLogado ? 'inicio.php' : 'index.php#hero' ?>">Início</a></li>
        <li><a href="sobre.php">Sobre Nós</a></li>
        <li><a href="index.php#work-process">Como Funciona</a></li>
        <li><a href="index.php#faq-2">Perguntas</a></li>
        <li><a href="#footer">Contato</a></li>

        <?php if ($usuarioLogado): ?>
          <li class="dropdown login-menu">
            <a href="#">
              <span class="preto"><?= $nomeUsuario ?></span>
              <i class="bi bi-chevron-down toggle-dropdown"></i>
            </a>
            <ul>
              <li>
                <a href="perfilusuario.php">
                  <i class="bi bi-person"></i>
                  <div>
                    <span>Meu perfil</span>
                    <p>Ver seus dados</p>
                  </div>
                </a>
              </li>
              <li>
                <a href="loginAdm.php">
                  <i class="bi bi-shield"></i>
                  <div>
                    <span>Administrador</span>
                    <p>Acessar a área administrativa</p>
                  </div>
                </a>
              </li>
              <li>
                <a href="logout.php">
                  <i class="bi bi-box-arrow-right"></i>
                  <div>
                    <span>Sair</span>
                    <p>Encerrar a sessão</p>
                  </div>
                </a>
              </li>
            </ul>
          </li>
        <?php else: ?>
          <li><a href="login.php">Login</a></li>
          <li class="login-menu"><a href="cadastro.php"><span class="preto">Cadastro</span></a></li>
        <?php endif; ?>
      </ul>
      <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
    </nav>
  </div>
</header>
