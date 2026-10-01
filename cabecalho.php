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

$tipoCabecalho = strtoupper((string) ($tipoCabecalho ?? 'LEGADO'));
$configuracaoCabecalho = isset($configuracaoCabecalho) && is_array($configuracaoCabecalho)
    ? $configuracaoCabecalho
    : [];

// Os novos tipos A e B ainda não possuem uma versão aprovada para visitantes.
if (!$usuarioLogado && in_array($tipoCabecalho, ['A', 'B'], true)) {
    $tipoCabecalho = 'LEGADO';
}

$escaparCabecalho = static fn ($valor): string => htmlspecialchars(
    (string) $valor,
    ENT_QUOTES,
    'UTF-8'
);

$mostrarBusca = $tipoCabecalho === 'A' || $tipoCabecalho === 'LEGADO';
$mostrarLogout = false;
?>

<?php if ($tipoCabecalho === 'C'): ?>
  <?php
  $mostrarVoltar = (bool) ($configuracaoCabecalho['mostrar_voltar'] ?? true);
  $fallbackVoltar = $escaparCabecalho($configuracaoCabecalho['fallback'] ?? 'inicio.php');
  $iconeCabecalho = trim((string) ($configuracaoCabecalho['icone'] ?? ''));
  $tituloPrimario = $escaparCabecalho($configuracaoCabecalho['titulo_primario'] ?? '');
  $tituloSecundario = $escaparCabecalho($configuracaoCabecalho['titulo_secundario'] ?? '');
  $acaoCabecalho = (string) ($configuracaoCabecalho['acao'] ?? '');
  $mostrarLogout = $acaoCabecalho === 'logout';
  ?>
  <header id="header" class="cabecalho-subpagina fixed-top">
    <div class="container-fluid container-xl cabecalho-subpagina-conteudo">
      <div class="cabecalho-subpagina-identidade">
        <?php if ($mostrarVoltar): ?>
          <button
            type="button"
            class="cabecalho-voltar btn-voltar"
            data-voltar-fallback="<?= $fallbackVoltar ?>"
            aria-label="Voltar para a página anterior"
            title="Voltar">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
          </button>
        <?php endif; ?>

        <?php if ($iconeCabecalho !== ''): ?>
          <span class="cabecalho-subpagina-icone icone-titulo" aria-hidden="true">
            <i class="bi <?= $escaparCabecalho($iconeCabecalho) ?>"></i>
          </span>
        <?php endif; ?>

        <h1 class="cabecalho-titulo titulo" aria-label="<?= trim("$tituloPrimario $tituloSecundario") ?>">
          <span class="cabecalho-titulo-verde parte-1"><?= $tituloPrimario ?></span>
          <?php if ($tituloSecundario !== ''): ?>
            <span class="cabecalho-titulo-rosa parte-2"><?= $tituloSecundario ?></span>
          <?php endif; ?>
        </h1>
      </div>

      <?php if ($mostrarLogout): ?>
        <button type="button" class="cabecalho-acao cabecalho-acao-sair acao-direita" id="btnLogout">
          <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
          <span>Sair</span>
        </button>
      <?php endif; ?>
    </div>
  </header>

<?php elseif ($tipoCabecalho === 'D'): ?>
  <?php $fallbackVoltar = $escaparCabecalho($configuracaoCabecalho['fallback'] ?? 'inicio.php'); ?>
  <nav class="cabecalho-minimo" aria-label="Navegação de retorno">
    <button
      type="button"
      class="voltar-home-auth"
      data-voltar-fallback="<?= $fallbackVoltar ?>"
      aria-label="Voltar para a página anterior"
      title="Voltar">
      <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </button>
  </nav>

<?php else: ?>
  <header
    id="header"
    class="header d-flex align-items-center fixed-top <?= $tipoCabecalho === 'A' ? 'cabecalho-home' : ($tipoCabecalho === 'B' ? 'cabecalho-institucional' : 'cabecalho-legado') ?>">
    <div class="container-fluid container-xl position-relative cabecalho-navbar-conteudo">
      <a href="<?= $usuarioLogado ? 'inicio.php' : 'index.php' ?>" class="logo d-flex align-items-center" aria-label="ShowMe - página inicial">
        <img src="assets/img/showme.png" alt="ShowMe">
      </a>

      <?php if (in_array($tipoCabecalho, ['A', 'B'], true)): ?>
        <?php if ($mostrarBusca): ?>
          <button
            class="cabecalho-busca-toggle d-md-none"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#buscaCabecalhoArea"
            aria-controls="buscaCabecalhoArea"
            aria-expanded="false"
            aria-label="Abrir busca">
            <i class="bi bi-search" aria-hidden="true"></i>
          </button>

          <div class="cabecalho-busca-area collapse d-md-flex" id="buscaCabecalhoArea">
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
          </div>
        <?php endif; ?>

        <nav id="navmenu" class="navmenu cabecalho-nav-desktop d-none d-md-block" aria-label="Navegação principal">
          <ul>
            <?php if ($tipoCabecalho === 'A'): ?>
              <li>
                <a href="cadastro-evento.php" class="nav-cadastrar-evento">
                  <i class="bi bi-plus-lg" aria-hidden="true"></i>
                  <span>Cadastrar Evento</span>
                </a>
              </li>
              <li><a href="favoritos.php">Meus Eventos</a></li>
              <li><a href="sobre.php">Institucional</a></li>
              <li>
                <a href="perfilUsuario.php" class="nav-usuario-direto" aria-label="Abrir perfil do usuário">
                  <i class="bi bi-person-circle" aria-hidden="true"></i>
                  <span>Usuário</span>
                </a>
              </li>
            <?php else: ?>
              <li>
                <a href="inicio.php" class="nav-inicio">
                  <i class="bi bi-house-door" aria-hidden="true"></i>
                  <span>Início</span>
                </a>
              </li>
              <li>
                <a href="cadastro-evento.php" class="nav-cadastrar-evento">
                  <i class="bi bi-plus-lg" aria-hidden="true"></i>
                  <span>Cadastrar Evento</span>
                </a>
              </li>
              <li><a href="favoritos.php">Meus Eventos</a></li>
              <li class="dropdown institucional-dropdown">
                <a href="sobre.php#sobre">
                  <span>Institucional</span>
                  <i class="bi bi-chevron-down toggle-dropdown" aria-hidden="true"></i>
                </a>
                <ul>
                  <li><a href="sobre.php#sobre">Sobre</a></li>
                  <li><a href="sobre.php#como-funciona">Como Funciona</a></li>
                  <li><a href="sobre.php#fidelidade">Clube de Fidelidade</a></li>
                  <li><a href="sobre.php#perguntas">Perguntas Frequentes</a></li>
                  <li><a href="sobre.php#contato">Contato</a></li>
                </ul>
              </li>
              <li>
                <a href="perfilUsuario.php" class="nav-usuario-direto" aria-label="Abrir perfil do usuário">
                  <i class="bi bi-person-circle" aria-hidden="true"></i>
                  <span>Usuário</span>
                </a>
              </li>
            <?php endif; ?>
          </ul>
        </nav>

        <button
          class="cabecalho-hamburguer d-md-none"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#menuCabecalhoMobile"
          aria-controls="menuCabecalhoMobile"
          aria-expanded="false"
          aria-label="Abrir menu principal">
          <i class="bi bi-list" aria-hidden="true"></i>
        </button>

        <div class="collapse cabecalho-menu-mobile d-md-none" id="menuCabecalhoMobile">
          <nav aria-label="Navegação principal móvel">
            <ul>
              <?php if ($tipoCabecalho === 'A'): ?>
                <li>
                  <a href="cadastro-evento.php" class="nav-cadastrar-evento">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Cadastrar Evento</span>
                  </a>
                </li>
                <li><a href="favoritos.php">Meus Eventos</a></li>
                <li><a href="sobre.php">Institucional</a></li>
                <li>
                  <a href="perfilUsuario.php" class="nav-usuario-direto" aria-label="Abrir perfil do usuário">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span>Usuário</span>
                  </a>
                </li>
              <?php else: ?>
                <li>
                  <a href="inicio.php" class="nav-inicio">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    <span>Início</span>
                  </a>
                </li>
                <li>
                  <a href="cadastro-evento.php" class="nav-cadastrar-evento">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                    <span>Cadastrar Evento</span>
                  </a>
                </li>
                <li><a href="favoritos.php">Meus Eventos</a></li>
                <li class="cabecalho-menu-institucional">
                  <div>
                    <a href="sobre.php#sobre">Institucional</a>
                    <button
                      type="button"
                      data-bs-toggle="collapse"
                      data-bs-target="#submenuInstitucionalMobile"
                      aria-controls="submenuInstitucionalMobile"
                      aria-expanded="false"
                      aria-label="Mostrar seções institucionais">
                      <i class="bi bi-chevron-down" aria-hidden="true"></i>
                    </button>
                  </div>
                  <ul class="collapse" id="submenuInstitucionalMobile">
                    <li><a href="sobre.php#sobre">Sobre</a></li>
                    <li><a href="sobre.php#como-funciona">Como Funciona</a></li>
                    <li><a href="sobre.php#fidelidade">Clube de Fidelidade</a></li>
                    <li><a href="sobre.php#perguntas">Perguntas Frequentes</a></li>
                    <li><a href="sobre.php#contato">Contato</a></li>
                  </ul>
                </li>
                <li>
                  <a href="perfilUsuario.php" class="nav-usuario-direto" aria-label="Abrir perfil do usuário">
                    <i class="bi bi-person-circle" aria-hidden="true"></i>
                    <span>Usuário</span>
                  </a>
                </li>
              <?php endif; ?>
            </ul>
          </nav>
        </div>

      <?php else: ?>
        <?php if ($mostrarBusca): ?>
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
            <div id="sugestoesBusca" class="sugestoes-busca" role="listbox" aria-label="Sugestões de eventos" hidden></div>
          </form>
        <?php endif; ?>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="<?= $usuarioLogado ? 'inicio.php' : 'index.php#hero' ?>">Início</a></li>
            <li><a href="#about">Sobre Nós</a></li>
            <li><a href="#work-process">Como Funciona</a></li>
            <li><a href="#faq-2">Perguntas</a></li>
            <li><a href="#footer">Contato</a></li>

            <?php if ($usuarioLogado): ?>
              <li class="dropdown login-menu">
                <a href="#">
                  <span class="preto"><?= $nomeUsuario ?></span>
                  <i class="bi bi-chevron-down toggle-dropdown"></i>
                </a>
                <ul>
                  <li><a href="cadastro-evento.php"><i class="bi bi-plus-circle"></i><span>Cadastrar evento</span></a></li>
                  <li><a href="favoritos.php"><i class="bi bi-heart"></i><span>Favoritos</span></a></li>
                  <li><a href="perfilUsuario.php"><i class="bi bi-person"></i><span>Meu perfil</span></a></li>
                  <li>
                    <button type="button" class="btn-logout-menu" id="btnLogout">
                      <i class="bi bi-box-arrow-right"></i><span>Sair</span>
                    </button>
                  </li>
                </ul>
              </li>
              <?php $mostrarLogout = true; ?>
            <?php else: ?>
              <li class="login-menu"><a href="login.php"><span class="preto">Login</span></a></li>
              <li class="login-menu"><a href="cadastro.php"><span class="preto">Cadastro</span></a></li>
            <?php endif; ?>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list" aria-label="Abrir menu"></i>
        </nav>
      <?php endif; ?>
    </div>
  </header>
<?php endif; ?>

<?php if ($mostrarBusca): ?>
  <script src="assets/js/buscaEventos.js" defer></script>
<?php endif; ?>
<?php if ($mostrarLogout): ?>
  <script src="assets/js/logout.js" defer></script>
<?php endif; ?>
<script src="assets/js/cabecalho.js" defer></script>
