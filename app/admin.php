<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../database/connect_postgres.php';
// Consultas fixas: os indicadores não dependem de parâmetros enviados pelo visitante.
$indicadores = [
    'alunos' => 'Alunos cadastrados',
    'usuarios' => 'Usuários cadastrados',
    'cursos' => 'Cursos cadastrados',
    'inscricoes' => 'Inscrições realizadas',
];
$resumo = [];
$erroResumo = '';
try {
    foreach ($indicadores as $tabela => $rotulo) {
        // Os nomes das tabelas vêm exclusivamente da lista fixa acima; COUNT(*) retorna 0 para tabelas vazias.
        $resumo[$tabela] = (int) $conexao->query('SELECT COUNT(*) FROM ' . $tabela)->fetchColumn();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroResumo = 'Não foi possível carregar o resumo do sistema. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="admin-dashboard">
        <h1>Painel Administrativo</h1>
        <section class="admin-summary" aria-labelledby="resumo-titulo">
            <h2 id="resumo-titulo">Resumo do sistema</h2>
            <?php if ($erroResumo !== ''): ?>
                <p class="message-warning" role="status"><?= htmlspecialchars($erroResumo, ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <dl class="admin-summary-grid">
                    <?php foreach ($indicadores as $tabela => $rotulo): ?>
                        <div class="admin-card">
                            <dt><?= htmlspecialchars($rotulo, ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= (int) $resumo[$tabela] ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </section>
        <section class="admin-actions" aria-labelledby="gerenciamento-titulo">
            <h2 id="gerenciamento-titulo">Gerenciamento</h2>
            <div class="admin-action-links">
                <a class="report-link" href="create.php">Cadastrar aluno</a>
                <a class="report-link" href="select_w_w.php">Consultar aluno</a>
                <a class="report-link" href="select.php">Relatório de alunos</a>
                <a class="report-link" href="delete.php">Excluir aluno</a>
                <a class="report-link" href="cursos.php">Todos os cursos</a>
                <a class="report-link" href="vincular_conta.php">Vincular conta a aluno</a>
                <a class="report-link" href="usuarios.php">Usuários cadastrados</a>
                <a class="report-link" href="alunos_cursos.php">Cursos dos alunos</a>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
