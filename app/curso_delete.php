<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
$_SESSION['curso_admin_token'] ??= bin2hex(random_bytes(32));
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
$curso = false;
$erro = '';
try {
    if ($id === false) throw new InvalidArgumentException('Informe um curso válido.');
    $curso = buscarCursoAdmin($conexao, $id);
    if (!$curso) throw new InvalidArgumentException('Curso não encontrado.');
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validarTokenCursoAdmin($_POST['token'] ?? null);
        if ((int) $curso['inscritos'] > 0) throw new InvalidArgumentException('Este curso possui inscrições e não pode ser excluído.');
        // Revalida no DELETE e preserva a foreign key: nenhuma inscrição é apagada.
        $stmt = $conexao->prepare('DELETE FROM cursos WHERE id = :id AND NOT EXISTS (SELECT 1 FROM inscricoes WHERE curso_id = :curso_id)');
        $stmt->execute([':id' => $id, ':curso_id' => $id]);
        if (!$stmt->rowCount()) {
            $curso = buscarCursoAdmin($conexao, $id);
            throw new InvalidArgumentException($curso ? 'Este curso possui inscrições e não pode ser excluído.' : 'Curso não encontrado.');
        }
        header('Location: cursos_admin.php?excluido=1');
        exit();
    }
} catch (InvalidArgumentException $e) {
    $erro = $e->getMessage();
} catch (PDOException $e) {
    error_log($e->getMessage());
    // PostgreSQL 23503 indica vínculo por foreign key, inclusive uma inscrição criada durante a confirmação.
    $erro = (string) $e->getCode() === '23503' ? 'Este curso possui inscrições e não pode ser excluído.' : 'Não foi possível excluir o curso. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir curso | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="delete-page">
    <h1>Excluir curso</h1>
    <?php if ($erro !== ''): ?><p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($curso): ?>
        <h2><?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p>Carga horária: <?= (int) $curso['carga_horaria'] ?> horas.</p>
        <p>Este curso possui <?= (int) $curso['inscritos'] ?> inscrições.</p>
        <?php if ((int) $curso['inscritos'] > 0): ?>
            <p class="message-warning">Este curso possui inscrições e não pode ser excluído.</p>
        <?php else: ?>
            <form method="post" class="danger-confirmation">
                <p>Deseja confirmar a exclusão deste curso?</p>
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['curso_admin_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="submit" name="confirmar" value="Confirmar exclusão">
                <a class="cancel-link" href="cursos_admin.php">Cancelar</a>
            </form>
        <?php endif; ?>
    <?php endif; ?>
    <p><a href="cursos_admin.php">Voltar ao gerenciamento de cursos</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
