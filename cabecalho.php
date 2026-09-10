<?php
$usuarioLogado = isset($_SESSION['id_user']);
$nomeUsuario = htmlspecialchars(
    (string) ($_SESSION['nome_user'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);
$termoBuscaCabecalho = isset($_GET['busca']) && is_string($_GET['busca'])
    ? htmlspecialchars(trim($_GET['busca']), ENT_QUOTES, 'UTF-8')
    : '';
?>
<header id="header" class="header d-flex align-items-center fixed-top">
  <div class="container-fluid container-xl position-relative d-flex align-items-center">
    <a href="<?= $usuarioLogado ? 'inicio.php' : 'index.php' ?>" class="logo d-flex align-items-center">
      <img src="assets/img/showme.png" alt="ShowMe">
    </a>

    <form class="busca-cabecalho" action="resultadosBusca.php" method="get" role="search">
      <div class="busca-cabecalho-campo">
        <input
          id="buscaCabecalho"
          name="busca"
          type="search"
          value="<?= $termoBuscaCabecalho ?>"
          placeholder="Buscar eventos..."
          aria-label="Buscar eventos por nome, cidade, categoria ou artista"
          aria-controls="sugestoesBusca"
          aria-autocomplete="list"
          autocomplete="off"
          spellcheck="false"
        >
        <button type="submit" aria-label="Confirmar busca">
          <i class="bi bi-search" aria-hidden="true"></i>
        </button>
      </div>
      <div
        id="sugestoesBusca"
        class="sugestoes-busca"
        role="listbox"
        aria-label="Sugestões de eventos"
        hidden
      ></div>
    </form>

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
                <a href="cadastro-evento.php">
                  <i class="bi bi-plus-circle"></i>
                  <div>
                    <span>Cadastrar evento</span>
                    <p>Enviar um evento para análise</p>
                  </div>
                </a>
              </li>
              <li>
                <a href="favoritos.php">
                  <i class="bi bi-heart"></i>
                  <div>
                    <span>Favoritos</span>
                    <p>Ver eventos salvos e planejados</p>
                  </div>
                </a>
              </li>
              <li>
                <a href="perfilUsuario.php">
                  <i class="bi bi-person"></i>
                  <div>
                    <span>Meu perfil</span>
                    <p>Ver seus dados</p>
                  </div>
                </a>
              </li>
              <li>
                <a href="loginAdmin.php">
                  <i class="bi bi-shield"></i>
                  <div>
                    <span>Administrador</span>
                    <p>Acessar a área administrativa</p>
                  </div>
                </a>
              </li>
              <li>
                <button type="button" class="btn-logout-menu" id="btnLogout">
                  <i class="bi bi-box-arrow-right"></i>
                  <div>
                    <span>Sair</span>
                    <p>Encerrar a sessão</p>
                  </div>
                </button>
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

<script src="assets/js/buscaEventos.js" defer></script>
<?php if ($usuarioLogado): ?>
  <script src="assets/js/logout.js" defer></script>
<?php endif; ?>
