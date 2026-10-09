<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../database/connect_postgres.php';
$logs = [];
$erro = '';
try {
    // consulta apenas o email do admin e os registros mais recentes
    $logs = $conexao->query('SELECT l.created_at, u.email AS admin_email, l.acao, l.entidade, l.entidade_id, l.descricao FROM logs_admin l JOIN usuarios u ON u.id = l.admin_id ORDER BY l.created_at DESC, l.id DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Nao foi possivel consultar o historico administrativo');
    $erro = 'Não foi possível carregar o histórico. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <h1>Logs administrativos</h1>
        <p>Últimas 100 ações administrativas registradas.</p>
        <?php if ($erro !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif (!$logs): ?>
            <p class="profile-empty">Nenhuma ação administrativa registrada ainda.</p>
        <?php else: ?>
            <div class="table-wrapper" tabindex="0" role="region" aria-label="Histórico administrativo">
                <table>
                    <caption>Ações administrativas mais recentes</caption>
                    <thead><tr><th scope="col">Data</th><th scope="col">Admin</th><th scope="col">Ação</th><th scope="col">Entidade</th><th scope="col">ID</th><th scope="col">Descrição</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars((new DateTimeImmutable($log['created_at']))->modify('+3 hours')->format('d/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($log['admin_email'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($log['acao'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($log['entidade'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string) ($log['entidade_id'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($log['descricao'], ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
