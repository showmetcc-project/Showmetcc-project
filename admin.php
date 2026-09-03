<?php require_once __DIR__ . '/config/verifica_login.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShowMe - Painel Administrativo</title>

    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Vendor CSS (mesmo padrão do index) -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">

    <!-- CSS customizado — sempre por último -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>

<div class="container py-4">

    <!-- TOPBAR -->
    <nav class="topbar">

        <div class="logo">
            <span class="show">Show</span><span class="me">Me</span>
        </div>

        <div class="admin-badge">
            <i class="bi bi-shield-shaded"></i>
            Área Administrativa
        </div>

        <div class="user-area">
            <span class="user-email"><?= htmlspecialchars($_SESSION['nome_user'], ENT_QUOTES, 'UTF-8') ?></span>
            <a href="logout.php" class="btn-sair">
                <i class="bi bi-box-arrow-right"></i>
                Sair
            </a>
        </div>

    </nav>

    <hr class="divider">

    <!-- CABEÇALHO -->
    <section class="header-admin">

        <div class="header-row">
            <div>
                <h1><span class="verde">Painel</span> Administrativo</h1>
                <p>Gerencie os eventos enviados pelos usuários.</p>
            </div>

            <!-- Barra de busca -->
            <div class="busca">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Buscar evento...">
            </div>
        </div>

    </section>

    <!-- ESTATÍSTICAS / FILTROS -->
    <section class="stats">

        <div class="total-destaque">
            <span>2</span>
        </div>

        <button class="status-item ativo" data-filtro="todos">
            Todos <span class="badge-count">2</span>
        </button>

        <button class="status-item" data-filtro="aprovados">
            Aprovados <span class="badge-count">0</span>
        </button>

        <button class="status-item" data-filtro="reprovados">
            Reprovados <span class="badge-count">0</span>
        </button>

        <!-- Barra decorativa -->
        <div class="stats-barra"></div>

    </section>

    <!-- ══════════════════════════
         CARD 1 — EXPANDIDO
    ═══════════════════════════ -->
    <div class="evento-card expandido" data-status="pendente">

        <div class="evento-header" onclick="toggleCard(this)">

            <div class="evento-info">
                <div class="evento-icon">
                    <i class="bi bi-image"></i>
                </div>
                <div>
                    <h3>Noite de Samba &amp; Choro</h3>
                    <small>Centro Cultural Rio, Rio de Janeiro - RJ</small>
                    <small class="d-block">19/06/2025 às 20:00 · Gratuito</small>
                </div>
            </div>

            <div class="header-direito">
                <span class="badge-pendente">Pendentes</span>
                <i class="bi bi-chevron-up chevron"></i>
            </div>

        </div>

        <div class="evento-body">

            <div class="row g-4">

                <div class="col-md-6">
                    <div class="campo-info">
                        <label>Local</label>
                        <p>Centro Cultural Rio, Rio de Janeiro - RJ</p>
                    </div>
                    <div class="campo-info">
                        <label>Tipo</label>
                        <p>Gratuito</p>
                    </div>
                    <div class="campo-info">
                        <label>Descrição do evento</label>
                        <p>Uma noite especial dedicada ao samba de raiz e ao choro instrumental, com rodas ao vivo e apresentações de músicos convidados.</p>
                    </div>
                    <div class="campo-info">
                        <label>Artista / atração</label>
                        <p>Grupo Raízes do Samba — formado em 2015, o grupo é referência nacional no resgate do samba tradicional carioca.</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="campo-info">
                        <label>Data e Hora</label>
                        <p>15/08/2026 às 20:00</p>
                    </div>
                    <div class="campo-info">
                        <label>Enviado em</label>
                        <p>01/07/2026, 07:32:00</p>
                    </div>
                </div>

            </div>

        </div>

        <div class="evento-footer">
            <button class="btn-editar">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn-aprovar">
                <i class="bi bi-check-circle"></i> Aprovar
            </button>
            <button class="btn-reprovar">
                <i class="bi bi-x-circle"></i> Reprovar
            </button>
        </div>

    </div>

    <!-- ══════════════════════════
         CARD 2 — RECOLHIDO
    ═══════════════════════════ -->
    <div class="evento-card" data-status="pendente">

        <div class="evento-header" onclick="toggleCard(this)">

            <div class="evento-info">
                <div class="evento-icon">
                    <i class="bi bi-image"></i>
                </div>
                <div>
                    <h3>Exposição: Arte Urbana Brasileira</h3>
                    <small>Museu de Arte Moderna, São Paulo – SP</small>
                    <small class="d-block">05/09/2026 às 10:00 · Pago</small>
                </div>
            </div>

            <div class="header-direito">
                <span class="badge-pendente">Pendentes</span>
                <i class="bi bi-chevron-up chevron"></i>
            </div>

        </div>

        <div class="evento-body">

            <div class="row g-4">

                <div class="col-md-6">
                    <div class="campo-info">
                        <label>Local</label>
                        <p>Museu de Arte Moderna, São Paulo – SP</p>
                    </div>
                    <div class="campo-info">
                        <label>Tipo</label>
                        <p>Pago</p>
                    </div>
                    <div class="campo-info">
                        <label>Descrição do evento</label>
                        <p>Uma exposição que celebra a riqueza e diversidade da arte urbana brasileira, com obras de artistas de todo o país.</p>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="campo-info">
                        <label>Data e Hora</label>
                        <p>05/09/2026 às 10:00</p>
                    </div>
                    <div class="campo-info">
                        <label>Enviado em</label>
                        <p>20/07/2026, 14:00:00</p>
                    </div>
                </div>

            </div>

        </div>

        <div class="evento-footer">
            <button class="btn-editar">
                <i class="bi bi-pencil"></i> Editar
            </button>
            <button class="btn-aprovar">
                <i class="bi bi-check-circle"></i> Aprovar
            </button>
            <button class="btn-reprovar">
                <i class="bi bi-x-circle"></i> Reprovar
            </button>
        </div>

    </div>

</div>

<!-- FOOTER (reutilizado do index) -->
<?php require __DIR__ . '/rodape.php'; ?>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center">
    <i class="bi bi-arrow-up-short"></i>
</a>

<!-- Vendor JS -->
<script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle expand/collapse dos cards
    function toggleCard(header) {
        const card = header.closest('.evento-card');
        const chevron = header.querySelector('.chevron');
        const isExpanded = card.classList.contains('expandido');

        card.classList.toggle('expandido', !isExpanded);
        chevron.classList.toggle('bi-chevron-up',   !isExpanded);
        chevron.classList.toggle('bi-chevron-down',  isExpanded);
    }

    // Inicializa: cards sem .expandido começam com chevron apontando para baixo
    document.querySelectorAll('.evento-card:not(.expandido) .chevron').forEach(c => {
        c.classList.replace('bi-chevron-up', 'bi-chevron-down');
    });

    // Filtros de status
    document.querySelectorAll('.status-item').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.status-item').forEach(b => b.classList.remove('ativo'));
            btn.classList.add('ativo');
            // Aqui você pode adicionar lógica de filtro real futuramente
        });
    });
</script>

</body>
</html>
