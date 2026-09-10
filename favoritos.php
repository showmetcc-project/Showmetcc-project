<?php
require_once __DIR__ . '/config/verifica_login.php';
require_once __DIR__ . '/config/conexao.php';

/*
|--------------------------------------------------------------------------
| IDENTIFICA O USUÁRIO LOGADO
|--------------------------------------------------------------------------
|
| Ajuste aqui somente se o seu verifica_login.php usar outro nome
| para guardar o ID do usuário na sessão.
|
*/

$idUsuario = $_SESSION['id_user'] ?? null;

if (!$idUsuario) {
    die('Usuário não identificado.');
}


/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA ESCAPAR TEXTO
|--------------------------------------------------------------------------
*/

function e($valor)
{
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}


/*
|--------------------------------------------------------------------------
| FUNÇÃO PARA FORMATAR DATA
|--------------------------------------------------------------------------
*/

function formatarData($data)
{
    if (!$data) {
        return 'Data não informada';
    }

    $timestamp = strtotime($data);

    $meses = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez'
    ];

    $dia = date('d', $timestamp);
    $mes = $meses[(int)date('m', $timestamp)];
    $ano = date('Y', $timestamp);

    return "$dia $mes $ano";
}


/*
|--------------------------------------------------------------------------
| BUSCAR FAVORITOS
|--------------------------------------------------------------------------
*/

$sqlFavoritos = "
    SELECT
        e.id_evento,
        e.nome_evento,
        e.local_evento,
        e.cidade_evento,
        e.uf,
        e.data_evento,
        e.gratuidade,
        e.imagem_evento
    FROM favoritos f
    INNER JOIN evento e
        ON e.id_evento = f.id_evento
    WHERE f.id_user = ?
      AND e.status_evento = 'ativo'
    ORDER BY e.data_evento ASC
";

$stmtFavoritos = $pdo->prepare($sqlFavoritos);
$stmtFavoritos->execute([$idUsuario]);

$favoritos = $stmtFavoritos->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| BUSCAR PLANEJADOS
|--------------------------------------------------------------------------
|
| Neste projeto, um evento passa a aparecer como planejado quando
| existe uma rota cadastrada para ele pelo usuário.
|
*/

$sqlPlanejados = "
    SELECT DISTINCT
        e.id_evento,
        e.nome_evento,
        e.local_evento,
        e.cidade_evento,
        e.uf,
        e.data_evento,
        e.gratuidade,
        e.imagem_evento
    FROM rota r
    INNER JOIN evento e
        ON e.id_evento = r.id_evento
    WHERE r.id_user = ?
      AND e.status_evento = 'ativo'
    ORDER BY e.data_evento ASC
";

$stmtPlanejados = $pdo->prepare($sqlPlanejados);
$stmtPlanejados->execute([$idUsuario]);

$planejados = $stmtPlanejados->fetchAll(PDO::FETCH_ASSOC);


/*
|--------------------------------------------------------------------------
| CONTADOR DE FAVORITOS
|--------------------------------------------------------------------------
*/

$contadorFavoritos = count($favoritos);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Meus Eventos - ShowMe</title>

    <!-- Favicons -->
    <link href="assets/img/showme.png" rel="icon">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com" rel="preconnect">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:wght@300;400;500;600;700&family=Jost:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Vendor CSS -->
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/vendor/aos/aos.css" rel="stylesheet">

    <!-- CSS customizado -->
    <link rel="stylesheet" href="assets/css/favoritos.css">

    <!-- Vendor CSS Files -->
    <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">
    <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">

    <!-- Principal CSS File -->
    <link href="assets/css/main.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>

    <link
        href="https://fonts.googleapis.com/css2?family=Anton&family=Archivo+Black&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&family=Libertinus+Serif+Display&family=Noto+Sans+JP:wght@100..900&family=Noto+Serif:ital,wght@0,100..900;1,100..900&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Roboto+Condensed:wght@0,100..900;1,100..900&family=Story+Script&family=Vend+Sans:ital,wght@0,300..700;1,300..700&display=swap"
        rel="stylesheet">

</head>


<body class="com-cabecalho-padrao">

    <?php require __DIR__ . '/cabecalho.php'; ?>


    <main class="eventos-container">

        <!-- ABAS -->
        <div class="abas">

            <button class="aba ativa" data-tab="favoritos">
                <i class="bi bi-heart"></i>
                Favoritos (<span id="contadorFavoritos"><?= $contadorFavoritos ?></span>)
            </button>

            <button class="aba" data-tab="planejados">
                <i class="bi bi-calendar-event"></i>
                Planejados (<span id="contadorPlanejados">0</span>)
            </button>

        </div>


        <!-- ══════════════════════════
             FAVORITOS
        ═══════════════════════════ -->

        <section id="favoritos" class="conteudo ativa">

            <?php if (empty($favoritos)): ?>

                <div class="card-evento">
                    <div class="info">
                        <h3>Nenhum evento favorito</h3>
                        <p>
                            <i class="bi bi-heart"></i>
                            Você ainda não adicionou nenhum evento aos favoritos.
                        </p>
                    </div>
                </div>

            <?php else: ?>

                <?php foreach ($favoritos as $evento): ?>

                    <div class="card-evento">

                        <img
                            src="<?= e($evento['imagem_evento'] ?: 'assets/img/showme.png') ?>"
                            alt="<?= e($evento['nome_evento']) ?>"
                        >

                        <div class="info">

                            <h3>
                                <?= e($evento['nome_evento']) ?>
                            </h3>

                            <p>
                                <i class="bi bi-geo-alt-fill"></i>

                                <?= e($evento['cidade_evento']) ?>,
                                <?= e($evento['uf']) ?>
                            </p>

                            <p>
                                <i class="bi bi-calendar3"></i>

                                <?= formatarData($evento['data_evento']) ?>
                            </p>

                            <button class="btn-detalhes">
                                <a href="detalhesEvento.php?id_evento=<?= (int)$evento['id_evento'] ?>">
                                    Ver detalhes
                                </a>
                            </button>

                        </div>


                        <div class="acoes">

                            <?php if ($evento['gratuidade']): ?>

                                <span class="tag gratis">
                                    Grátis
                                </span>

                            <?php else: ?>

                                <span class="tag pago">
                                    Pago
                                </span>

                            <?php endif; ?>


                            <button
                                class="btn-excluir"
                                title="Remover"
                                data-id-evento="<?= (int)$evento['id_evento'] ?>"
                            >
                                <i class="bi bi-trash3"></i>
                            </button>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </section>


        <!-- ══════════════════════════
             PLANEJADOS
        ═══════════════════════════ -->

        <section id="planejados" class="conteudo">
            <p>Carregando seus planejados...</p>

        </section>

    </main>


    <?php require __DIR__ . '/rodape.php'; ?>


    <!-- Scroll Top -->

    <a
        href="#"
        id="scroll-top"
        class="scroll-top d-flex align-items-center justify-content-center"
    >
        <i class="bi bi-arrow-up-short"></i>
    </a>


    <!-- Vendor JS -->

    <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <script src="assets/vendor/aos/aos.js"></script>

    <script src="assets/js/main.js"></script>

    <script src="assets/js/favoritos.js"></script>

    <script>
        AOS.init();
    </script>

</body>

</html>