<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
if (isset($_SESSION['id'])) { header('Location: /impulsos_cursos/login/perfil.php'); exit(); }
$erro = '';
$_SESSION['cadastro_token'] ??= bin2hex(random_bytes(32));
$dados = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nome', 'cpf', 'nasc', 'email', 'senha'] as $campo) {
        $dados[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
    }
    try {
        if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['cadastro_token'], $_POST['token'])) {
            throw new InvalidArgumentException('Solicitação inválida. Recarregue a página.');
        }
        cadastrar_aluno_usuario($conexao, $dados);
        unset($_SESSION['cadastro_token']);
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
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php' ?>
    <main class="auth-page">
        <h1>Cadastre-se no Sistema</h1>
        <p>Cadastre seus dados e sua conta. O administrador confirmará o vínculo ao seu cadastro de aluno.</p>
        <?php if ($erro !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['cadastro_token'], ENT_QUOTES, 'UTF-8') ?>">
            <label for="nome">Nome:</label>
            <input type="text" name="nome" id="nome" maxlength="255" autocomplete="name" value="<?= htmlspecialchars($dados['nome'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="cpf">CPF:</label>
            <input type="text" name="cpf" id="cpf" maxlength="14" placeholder="000.000.000-00" inputmode="numeric" value="<?= htmlspecialchars($dados['cpf'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="nasc">Nascimento:</label>
            <input type="date" name="nasc" id="nasc" autocomplete="bday" max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($dados['nasc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" maxlength="255" autocomplete="username" value="<?= htmlspecialchars($dados['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            <label for="senha">Senha: </label>
            <input type="password" name="senha" id="senha" autocomplete="new-password" required>
            <input type="submit" value="Cadastrar">
            <input type="reset" value="Limpar">
        </form>
        <p><a href="login.php">Entrar</a></p>
    </main>
    <?php include __DIR__ . '/../includes/footer.php' ?>
</body>
</html>
