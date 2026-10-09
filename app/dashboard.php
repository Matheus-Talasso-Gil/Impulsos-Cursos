<?php
require_once __DIR__ . '/../login/verificar_user.php';
if (($_SESSION['tipo'] ?? 'usuario') === 'admin') {
    header('Location: /impulsos_cursos/app/admin.php');
    exit();
}
// recusa tipos de conta inesperados mesmo quando existe uma sessao autenticada
if (($_SESSION['tipo'] ?? 'usuario') !== 'usuario') {
    http_response_code(403);
    exit('Acesso indisponível para esta conta.');
}
require_once __DIR__ . '/../database/connect_postgres.php';
require_once __DIR__ . '/../includes/data_conta.php';
$conta = false;
$meusCursos = [];
$cursosDisponiveis = [];
$erro = '';
try {
    // busca somente a conta da sessao mesmo quando nao ha aluno vinculado
    $stmt = $conexao->prepare('SELECT u.email, u.created_at, a.nome, a.turma, a.ativo,
        a.usuario_id AS aluno_vinculado,
        (SELECT COUNT(*) FROM inscricoes i WHERE i.usuario_id = u.id) AS total_inscricoes,
        (SELECT COUNT(*) FROM cursos) AS total_cursos
        FROM usuarios u LEFT JOIN alunos a ON a.usuario_id = u.id WHERE u.id = :id');
    $stmt->execute([':id' => (int) $_SESSION['id']]);
    $conta = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$conta) {
        $erro = 'Não foi possível encontrar sua conta. Saia e entre novamente.';
    } else {
        $stmt = $conexao->prepare('SELECT c.id, c.nome FROM inscricoes i
            JOIN cursos c ON c.id = i.curso_id WHERE i.usuario_id = :id ORDER BY c.id LIMIT 3');
        $stmt->execute([':id' => (int) $_SESSION['id']]);
        $meusCursos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // calcula se cada curso ja possui inscricao da conta autenticada
        $stmt = $conexao->prepare('SELECT c.id, c.nome,
            EXISTS (SELECT 1 FROM inscricoes i WHERE i.curso_id = c.id AND i.usuario_id = :id) AS inscrito
            FROM cursos c ORDER BY c.id LIMIT 3');
        $stmt->execute([':id' => (int) $_SESSION['id']]);
        $cursosDisponiveis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível carregar seu dashboard. Tente novamente mais tarde.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="user-dashboard">
    <h1>Dashboard</h1>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
        <?php
        $saudacao = 'Olá!';
        if (trim((string) ($conta['nome'] ?? '')) !== '') {
            $saudacao = 'Olá, ' . htmlspecialchars((string) $conta['nome'], ENT_QUOTES, 'UTF-8') . '!';
        }
        ?>
        <p class="dashboard-greeting"><?= $saudacao ?></p>
        <p>Acompanhe sua conta e acesse seus cursos.</p>
        <div class="dashboard-grid">
            <section class="dashboard-card" aria-labelledby="conta-title">
                <h2 id="conta-title">Resumo da conta</h2>
                <dl class="dashboard-details">
                    <?php if ($conta['aluno_vinculado'] !== null): ?>
                        <div><dt>Nome</dt><dd><?= htmlspecialchars((string) $conta['nome'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php endif; ?>
                    <div><dt>E-mail</dt><dd><?= htmlspecialchars((string) $conta['email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <div><dt>Conta criada em</dt><dd><?= htmlspecialchars(formatarDataCriacaoConta($conta['created_at']), ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php if ($conta['aluno_vinculado'] !== null): ?>
                        <div><dt>Situação</dt><dd><?= in_array($conta['ativo'], [true, 1, '1', 't'], true) ? 'Ativo' : 'Inativo' ?></dd></div>
                        <div><dt>Turma</dt><dd><?= htmlspecialchars((string) ($conta['turma'] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'Não informada' ?></dd></div>
                    <?php endif; ?>
                </dl>
                <?php if ($conta['aluno_vinculado'] === null): ?>
                    <p>Seu cadastro ainda não está vinculado a um aluno.</p>
                <?php endif; ?>
                <a class="report-link" href="../login/perfil.php">Meu perfil</a>
            </section>
            <section class="dashboard-card" aria-labelledby="meus-title">
                <h2 id="meus-title">Meus cursos</h2>
                <p class="dashboard-total"><?= (int) $conta['total_inscricoes'] ?> <?= (int) $conta['total_inscricoes'] === 1 ? 'curso inscrito' : 'cursos inscritos' ?></p>
                <?php if (!$meusCursos): ?>
                    <p>Você ainda não está inscrito em nenhum curso.</p>
                    <a class="report-link" href="cursos.php">Ver cursos disponíveis</a>
                <?php else: ?>
                    <ul class="dashboard-course-list">
                        <?php foreach ($meusCursos as $curso): ?>
                            <li><a href="curso.php?id=<?= (int) $curso['id'] ?>"><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="report-link" href="meus_cursos.php">Ver Meus cursos</a>
            </section>
            <section class="dashboard-card" aria-labelledby="disponiveis-title">
                <h2 id="disponiveis-title">Cursos disponíveis</h2>
                <p class="dashboard-total"><?= (int) $conta['total_cursos'] ?> <?= (int) $conta['total_cursos'] === 1 ? 'curso' : 'cursos' ?> no catálogo</p>
                <?php if (!$cursosDisponiveis): ?>
                    <p>Nenhum curso disponível no momento.</p>
                <?php else: ?>
                    <ul class="dashboard-course-list">
                        <?php foreach ($cursosDisponiveis as $curso): ?>
                            <li><a href="curso.php?id=<?= (int) $curso['id'] ?>"><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></a>
                                <?php if (in_array($curso['inscrito'], [true, 1, '1', 't'], true)): ?><span class="dashboard-enrolled">Inscrito</span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a class="report-link" href="cursos.php">Ver todos os cursos</a>
            </section>
        </div>
    <?php endif; ?>
    <section class="dashboard-shortcuts" aria-labelledby="atalhos-title">
        <h2 id="atalhos-title">Atalhos</h2>
        <div class="admin-action-links">
            <a class="report-link" href="../login/perfil.php">Meu perfil</a>
            <a class="report-link" href="meus_cursos.php">Meus cursos</a>
            <a class="report-link" href="cursos.php">Todos os cursos</a>
            <a class="report-link" href="../login/logout.php">Sair</a>
        </div>
    </section>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
