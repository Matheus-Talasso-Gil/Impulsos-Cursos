<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
if (isset($_SESSION['id'])) { header('Location: /mini_sistema/login/perfil.php'); exit(); }
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        cadastrar_user($conexao, $_POST['email'] ?? '', $_POST['senha'] ?? '');
        header('Location: login.php?cadastro=sucesso'); exit();
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível salvar o cadastro. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>cadastre-se</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php' ?>
    <main class="auth-page">
        <h1>Cadastre-se no Sistema</h1>
        <?php if ($erro !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" autocomplete="username" required>
            <label for="senha">Senha: </label>
            <input type="password" name="senha" id="senha" autocomplete="new-password" required>
            <input type="submit" value="Cadastrar">
            <input type="reset" value="Limpar">
        </form>
        <p><a href="login.php">Fazer login</a></p>
    </main>
    <?php include __DIR__ . '/../includes/footer.php' ?>
</body>
</html>
