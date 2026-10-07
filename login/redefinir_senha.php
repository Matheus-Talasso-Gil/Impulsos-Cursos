<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/recuperacao_senha.php';
if (isset($_SESSION['id'])) { header('Location: /impulsos_cursos/login/perfil.php'); exit(); }
protegerRecuperacaoLocal();
require_once __DIR__ . '/../database/connect_postgres.php';
// Evita compartilhar a URL com token e armazenar esta página no cache.
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$_SESSION['recuperacao_csrf'] ??= bin2hex(random_bytes(32));
// O token vem da URL; o ID da conta é obtido apenas da sessão.
$token = $_GET['token'] ?? null;
$erro = '';
$podeRedefinir = false;
$sucesso = false;
try {
    // Confere token, prazo de 10 minutos e tipo da conta antes de liberar o formulário.
    validarRecuperacaoSenha($conexao, $token);
    $podeRedefinir = true;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Valida CSRF e senhas, salva o hash apenas para tipo usuario e invalida o token após a troca.
        redefinirSenhaUsuario($conexao, $token, $_POST['csrf'] ?? null, $_POST['senha'] ?? null, $_POST['confirmacao'] ?? null);
        $sucesso = true;
        $podeRedefinir = false;
    }
} catch (InvalidArgumentException $e) {
    $erro = $e->getMessage();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível concluir a recuperação. Tente novamente.';
    $podeRedefinir = false;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir senha | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="auth-page">
    <h1>Redefinir senha</h1>
    <?php if ($erro !== ''): ?><p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($sucesso): ?>
        <p class="message-success" role="status">Senha alterada com sucesso.</p>
    <?php elseif ($podeRedefinir): ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['recuperacao_csrf'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="senha">Nova senha:</label>
            <input type="password" name="senha" id="senha" minlength="8" autocomplete="new-password" required>
            <label for="confirmacao">Confirmar nova senha:</label>
            <input type="password" name="confirmacao" id="confirmacao" minlength="8" autocomplete="new-password" required>
            <input type="submit" value="Alterar senha">
        </form>
    <?php else: ?>
        <p><a href="recuperar_senha.php">Solicitar nova recuperação</a></p>
    <?php endif; ?>
    <p><a class="report-link" href="login.php">Voltar para entrar</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
