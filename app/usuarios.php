<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../database/connect_postgres.php';
require_once __DIR__ . '/../includes/data_conta.php';
$usuariosCadastrados = [];
$erroUsuarios = '';
try {
    // Não consulta senhas: mostra apenas os dados da conta e seu vínculo com um aluno.
    $usuariosCadastrados = $conexao->query('SELECT u.id, u.email, u.tipo, u.created_at, a.nome AS aluno_nome FROM usuarios u LEFT JOIN alunos a ON a.usuario_id = u.id ORDER BY u.id')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroUsuarios = 'Não foi possível carregar os usuários cadastrados. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários cadastrados | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="admin-dashboard">
        <section aria-labelledby="usuarios-titulo">
            <h1 id="usuarios-titulo">Usuários cadastrados</h1>
            <?php if ($erroUsuarios !== ''): ?>
                <p class="message-warning" role="status"><?= htmlspecialchars($erroUsuarios, ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <div class="table-wrapper" tabindex="0" role="region" aria-label="Usuários cadastrados">
                    <table>
                        <caption>Contas cadastradas e alunos vinculados</caption>
                        <thead><tr><th scope="col">ID</th><th scope="col">E-mail</th><th scope="col">Tipo de conta</th><th scope="col">Conta criada em</th><th scope="col">Aluno vinculado</th></tr></thead>
                        <tbody>
                            <?php foreach ($usuariosCadastrados as $usuario): ?>
                                <tr>
                                    <td><?= (int) $usuario['id'] ?></td>
                                    <td><?= htmlspecialchars($usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= $usuario['tipo'] === 'admin' ? 'Administrador' : 'Usuário' ?></td>
                                    <td><?= htmlspecialchars(formatarDataCriacaoConta($usuario['created_at'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($usuario['aluno_nome'] ?? 'Sem aluno vinculado', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$usuariosCadastrados): ?>
                                <tr><td colspan="5">Nenhum usuário cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
