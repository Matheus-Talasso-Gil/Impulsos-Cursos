<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$_SESSION['inscricao_token'] ??= bin2hex(random_bytes(32)); // mantem um token aleatorio na sessao para proteger o formulario contra csrf
$mensagem = '';
$erro = '';
$erroListagem = '';
$cursos = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $mensagem = inscreverUsuarioNoCurso($conexao, $_POST['curso_id'] ?? null, $_POST['token'] ?? null);
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível realizar a inscrição. Tente novamente.';
    }
}
try {
    $cursos = buscarCursosDisponiveis($conexao);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroListagem = 'Não foi possível carregar os cursos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
    <h1>Todos os cursos</h1>
    <p>Escolha um curso para começar seus estudos</p>
    <p><a href="meus_cursos.php">Ver meus cursos</a></p>
    <?php if ($mensagem !== ''): ?>
        <p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erroListagem !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erroListagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (!$cursos): ?>
        <p class="message-warning">Nenhum curso cadastrado</p>
    <?php else: ?>
        <section class="courses">
            <div class="course-grid">
                <?php foreach ($cursos as $curso): ?>
                    <article class="course-card">
                        <h3><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p><?= htmlspecialchars((string) ($curso['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="course-duration"><?= (int) $curso['carga_horaria'] ?> horas</p>
                        <div class="course-actions">
                            <a class="course-link" href="curso.php?id=<?= (int) $curso['id'] ?>">Ver detalhes</a>
                            <?php if ($curso['inscrito']): ?>
                                <span class="course-enrolled">Já inscrito</span>
                            <?php else: ?>
                                <form method="post" class="course-enrollment">
                                    <input type="hidden" name="curso_id" value="<?= (int) $curso['id'] ?>">
                                    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['inscricao_token'], ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="submit" value="Inscrever-se">
                                </form>
                            <?php endif; ?>
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
