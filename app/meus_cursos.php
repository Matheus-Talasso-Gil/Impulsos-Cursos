<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$cursos = [];
$erro = '';
try {
    $cursos = buscarCursosDoUsuario($conexao);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível carregar seus cursos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus cursos | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
    <h1>Meus cursos</h1>
    <p>Acompanhe os cursos em que você está inscrito.</p>
    <p><a class="report-link" href="cursos.php">Ver todos os cursos</a></p>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (!$cursos): ?>
        <p class="message-warning">Você ainda não está inscrito em nenhum curso.</p>
    <?php else: ?>
        <section class="courses" aria-label="Cursos inscritos">
            <div class="course-grid">
                <?php foreach ($cursos as $curso): ?>
                    <article class="course-card">
                        <h3><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string) ($curso['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="course-duration"><?= (int) $curso['carga_horaria'] ?> horas</p>
                        <div class="course-actions">
                            <a class="course-link" href="curso.php?id=<?= (int) $curso['id'] ?>">Ver detalhes</a>
                            <span class="course-enrolled">Já inscrito</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
