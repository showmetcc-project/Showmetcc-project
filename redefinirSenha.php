<?php
require_once __DIR__ . '/config/csrfPagina.php';
require_once __DIR__ . '/config/validacaoSenha.php';

iniciarSessaoSegura();

$token =
    isset($_GET['token']) && is_string($_GET['token'])
        ? trim($_GET['token'])
        : (isset($_POST['token']) && is_string($_POST['token'])
            ? trim($_POST['token'])
            : '');

$tokenValido = false;
$erro = '';
$mensagem = '';
$novaSenha = '';
$confirmarSenha = '';
$registro = null;

if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
    $mensagem = 'Este link de redefinição é inválido. Solicite um novo link.';
} else {
    require_once __DIR__ . '/config/conexao.php';

    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $conn->prepare(
            'SELECT id_recuperacao, id_usuario
             FROM recuperacao_senha
             WHERE token_hash = ?
             AND usado_em IS NULL
             AND expira_em > NOW()
             LIMIT 1',
        );
        $stmt->bind_param('s', $tokenHash);
        $stmt->execute();
        $registro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($registro) {
            $tokenValido = true;
        } else {
            $mensagem = 'Este link de redefinição é inválido ou expirou. Solicite um novo link.';
        }
    } catch (Throwable $erroBanco) {
        error_log('Falha ao validar token de redefinição: ' . $erroBanco->getMessage());
        $mensagem = 'Não foi possível validar o link agora. Tente novamente em alguns instantes.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $tokenCsrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : null;
    $novaSenha = isset($_POST['nova_senha']) && is_string($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmarSenha =
        isset($_POST['confirmar_senha']) && is_string($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    if (!validarTokenCsrfPagina($tokenCsrf)) {
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } else {
        $erroSenha = validarRequisitosSenha($novaSenha);

        if ($erroSenha !== null) {
            $erro = $erroSenha;
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'A confirmação da nova senha não confere.';
        } else {
            $transacaoIniciada = false;

            try {
                $conn->begin_transaction();
                $transacaoIniciada = true;

                $tokenHash = hash('sha256', $token);
                $stmt = $conn->prepare(
                    'SELECT id_recuperacao, id_usuario
                     FROM recuperacao_senha
                     WHERE token_hash = ?
                     AND usado_em IS NULL
                     AND expira_em > NOW()
                     LIMIT 1
                     FOR UPDATE',
                );
                $stmt->bind_param('s', $tokenHash);
                $stmt->execute();
                $registroAtual = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$registroAtual) {
                    $conn->rollback();
                    $transacaoIniciada = false;
                    $tokenValido = false;
                    $mensagem = 'Este link de redefinição é inválido ou expirou. Solicite um novo link.';
                } else {
                    $idUsuario = (int) $registroAtual['id_usuario'];
                    $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);

                    $stmt = $conn->prepare(
                        'UPDATE usuario
                         SET senha_user = ?
                         WHERE id_user = ?',
                    );
                    $stmt->bind_param('si', $senhaHash, $idUsuario);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $conn->prepare(
                        'UPDATE recuperacao_senha
                         SET usado_em = NOW()
                         WHERE id_usuario = ?
                         AND usado_em IS NULL',
                    );
                    $stmt->bind_param('i', $idUsuario);
                    $stmt->execute();
                    $stmt->close();

                    $conn->commit();
                    $transacaoIniciada = false;

                    header('Location: login.php?senha_redefinida=1', true, 303);
                    exit();
                }
            } catch (Throwable $erroRedefinicao) {
                if ($transacaoIniciada) {
                    $conn->rollback();
                }
                error_log('Falha ao redefinir senha: ' . $erroRedefinicao->getMessage());
                $erro = 'Não foi possível redefinir a senha agora. Tente novamente em alguns instantes.';
            }
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
    <title>Redefinir Senha - ShowMe</title>

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
                <h2>Redefinir senha</h2>

                <?php if ($mensagem !== ''): ?>
                    <div class="alert alert-warning" role="alert">
                        <?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <p class="login-acoes-secundarias">
                        <a href="recuperarSenha.php">Solicitar um novo link</a>
                    </p>
                <?php endif; ?>

                <?php if ($tokenValido): ?>
                    <p class="login-texto-apoio">
                        Crie uma nova senha para sua conta. O link será invalidado assim que a alteração for concluída.
                    </p>

                    <?php if ($erro !== ''): ?>
                        <div class="alert alert-danger" role="alert">
                            <?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php endif; ?>

                    <form id="formRedefinirSenha" method="post" action="redefinirSenha.php" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(
                            $tokenCsrf,
                            ENT_QUOTES,
                            'UTF-8',
                        ) ?>">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="mb-3">
                            <label for="novaSenhaRedefinicao">Nova senha</label>
                            <div class="login-input-box">
                                <span class="login-input-icone"><i class="bi bi-lock-fill" aria-hidden="true"></i></span>
                                <input
                                    id="novaSenhaRedefinicao"
                                    type="password"
                                    class="form-control login-input"
                                    name="nova_senha"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    minlength="6"
                                    aria-describedby="ajudaNovaSenhaRedefinicao"
                                    required
                                >
                                <button class="login-alternar-senha" type="button" aria-label="Mostrar senha" aria-pressed="false" aria-controls="novaSenhaRedefinicao" title="Mostrar senha">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <small class="login-ajuda-senha" id="ajudaNovaSenhaRedefinicao">
                                No mínimo 6 caracteres, contendo letras e números.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label for="confirmarSenhaRedefinicao">Confirmar nova senha</label>
                            <div class="login-input-box">
                                <span class="login-input-icone"><i class="bi bi-shield-lock-fill" aria-hidden="true"></i></span>
                                <input
                                    id="confirmarSenhaRedefinicao"
                                    type="password"
                                    class="form-control login-input"
                                    name="confirmar_senha"
                                    placeholder="••••••••"
                                    autocomplete="new-password"
                                    minlength="6"
                                    required
                                >
                                <button class="login-alternar-senha" type="button" aria-label="Mostrar senha" aria-pressed="false" aria-controls="confirmarSenhaRedefinicao" title="Mostrar senha">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">Redefinir senha</button>
                    </form>

                    <p class="login-acoes-secundarias">
                        <a href="login.php">Voltar para o login</a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="assets/js/cabecalho.js" defer></script>
    <script src="assets/js/loginPasswordToggle.js" defer></script>
    <script src="assets/js/main.js"></script>
    <?php require_once __DIR__ . '/acessibilidade.php'; ?>
</body>
</html>
