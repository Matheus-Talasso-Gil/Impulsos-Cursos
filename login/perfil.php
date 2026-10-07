<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/verificar_user.php';
require_once __DIR__ . '/../database/connect_postgres.php';
$aluno = false;
$erroPerfil = '';
try {
    $stmt = $conexao->prepare('SELECT id, nome, nasc, turma, email, ativo FROM alunos WHERE usuario_id = :usuario_id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroPerfil = 'Não foi possível consultar seu cadastro de aluno. Entre em contato com o administrador.';
}
$tipoConta = ($_SESSION['tipo'] ?? 'usuario') === 'admin' ? 'Administrador' : 'Usuário';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="auth-page">
        <h1>Meu perfil</h1>
        <p>E-mail: <?= htmlspecialchars((string) ($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p>Tipo de conta: <?= htmlspecialchars($tipoConta, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($erroPerfil !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erroPerfil, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($aluno): ?>
            <h2>Cadastro de aluno</h2>
            <?php foreach (['id' => 'ID', 'nome' => 'Nome', 'nasc' => 'Data de nascimento', 'turma' => 'Turma', 'email' => 'E-mail do aluno'] as $campo => $rotulo): ?>
                <p><?= $rotulo ?>: <?= htmlspecialchars((string) $aluno[$campo], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <p>Situação: <?= $aluno['ativo'] ? 'Ativo' : 'Inativo' ?></p>
        <?php else: ?>
            <p>Sua conta ainda não está vinculada a um cadastro de aluno.</p>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
