<?php
require_once __DIR__ . '/config/csrfPagina.php';
require_once __DIR__ . '/config/validacaoSenha.php';

iniciarSessaoSegura();

$email = '';
$mensagem = '';
$tipoMensagem = '';
$enviado = isset($_GET['enviado']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tokenCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $email = isset($_POST['email']) && is_string($_POST['email']) ? trim($_POST['email']) : '';

    if (!validarTokenCsrfPagina($tokenCsrf)) {
        $mensagem = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
        $tipoMensagem = 'danger';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $mensagem = 'Informe um e-mail válido.';
        $tipoMensagem = 'danger';
    } else {
        require_once __DIR__ . '/config/conexao.php';
        require_once __DIR__ . '/config/emailHelper.php';

        try {
            $stmt = $conn->prepare(
                'SELECT id_user, nome_user, email_user
                 FROM usuario
                 WHERE email_user = ?
                 LIMIT 1',
            );
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $usuario = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($usuario) {
                $idUsuario = (int) $usuario['id_user'];

                $stmt = $conn->prepare(
                    'SELECT COUNT(*) AS total
                     FROM recuperacao_senha
                     WHERE id_usuario = ?
                     AND criado_em >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)',
                );
                $stmt->bind_param('i', $idUsuario);
                $stmt->execute();
                $solicitacoesRecentes = (int) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
                $stmt->close();

                // Limita o envio de novos links sem revelar se o e-mail existe.
                if ($solicitacoesRecentes < 3) {
                    $stmt = $conn->prepare(
                        'UPDATE recuperacao_senha
                         SET usado_em = NOW()
                         WHERE id_usuario = ?
                         AND usado_em IS NULL',
                    );
                    $stmt->bind_param('i', $idUsuario);
                    $stmt->execute();
                    $stmt->close();

                    $token = bin2hex(random_bytes(32));
                    $tokenHash = hash('sha256', $token);
                    $ipSolicitante = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

                    $stmt = $conn->prepare(
                        'INSERT INTO recuperacao_senha
                            (id_usuario, token_hash, expira_em, ip_solicitante)
                         VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR), ?)',
                    );
                    $stmt->bind_param('iss', $idUsuario, $tokenHash, $ipSolicitante);
                    $stmt->execute();
                    $idRecuperacao = $stmt->insert_id;
                    $stmt->close();

                    $link = urlBaseEmailShowMe() . '/redefinirSenha.php?token=' . urlencode($token);

                    try {
                        enviarEmailRecuperacaoSenhaShowMe(
                            (string) $usuario['email_user'],
                            (string) $usuario['nome_user'],
                            $link,
                        );
                    } catch (Throwable $erroEnvio) {
                        // Não deixa um token válido sem que o e-mail tenha sido entregue.
                        $stmt = $conn->prepare('DELETE FROM recuperacao_senha WHERE id_recuperacao = ?');
                        $stmt->bind_param('i', $idRecuperacao);
                        $stmt->execute();
                        $stmt->close();
                        error_log('Falha ao enviar recuperação de senha: ' . $erroEnvio->getMessage());
                    }
                }
            }

            header('Location: recuperarSenha.php?enviado=1', true, 303);
            exit();
        } catch (Throwable $erro) {
            error_log('Falha ao solicitar recuperação de senha: ' . $erro->getMessage());
            $mensagem = 'Não foi possível processar a solicitação agora. Tente novamente em alguns instantes.';
            $tipoMensagem = 'danger';
        }
    }
}

$tokenCsrf = obterTokenCsrfPagina();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0a0a0a">
    <meta name="referrer" content="no-referrer">
    <title>Recuperar Senha - ShowMe</title>

    <link href="assets/img/showme.png" rel="icon">
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>

<body class="index-page login-comum-page">

    <a href="login.php" class="voltar-home-auth" data-voltar-fallback="login.php" aria-label="Voltar para o login" title="Voltar">
        <i class="bi bi-arrow-left" aria-hidden="true"></i>
    </a>

    <main class="main-login">
        <div class="login-wrapper">
            <div class="logo">
                <img src="assets/img/showme.png" alt="ShowMe">
            </div>

            <div class="login-card login-card-recuperacao">
                <h2>Recuperar senha</h2>
                <p class="login-texto-apoio">
                    Informe o e-mail cadastrado na sua conta. Enviaremos um link temporário para criar uma nova senha.
                </p>

                <?php if ($enviado): ?>
                    <div class="alert alert-success" role="status">
                        Se o e-mail estiver cadastrado, enviaremos um link de redefinição.
                        Verifique sua caixa de entrada e também a pasta de spam.
                    </div>
                <?php endif; ?>

                <?php if ($mensagem !== ''): ?>
                    <div class="alert alert-<?= htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8') ?>" role="alert">
                        <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <form id="formRecuperarSenha" method="post" action="recuperarSenha.php" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                        $tokenCsrf,
                        ENT_QUOTES,
                        'UTF-8',
                    ) ?>">

                    <div class="mb-3">
                        <label for="emailRecuperacao">E-mail</label>
                        <div class="login-input-box">
                            <span class="login-input-icone"><i class="bi bi-envelope-fill" aria-hidden="true"></i></span>
                            <input
                                id="emailRecuperacao"
                                type="email"
                                class="form-control login-input"
                                name="email"
                                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="seu@gmail.com"
                                autocomplete="email"
                                inputmode="email"
                                maxlength="100"
                                required
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-login">Enviar link de redefinição</button>
                </form>

                <p class="login-acoes-secundarias">
                    Lembrou a senha? <a href="login.php">Voltar para o login</a>
                </p>

                <p class="login-texto-apoio login-texto-apoio-pequeno">
                    O link expira em 1 hora e só pode ser usado uma vez.
                </p>
            </div>
        </div>
    </main>

    <script src="assets/js/cabecalho.js" defer></script>
    <script src="assets/js/main.js"></script>
    <?php require_once __DIR__ . '/acessibilidade.php'; ?>
</body>

</html>
