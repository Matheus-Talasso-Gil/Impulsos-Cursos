<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$cursos = [];
$erro = '';
$mensagem = $_SESSION['cancelamento_mensagem'] ?? '';
unset($_SESSION['cancelamento_mensagem']);
$_SESSION['cancelamento_token'] ??= bin2hex(random_bytes(32));
$cursoCancelar = false;
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['token'] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION['cancelamento_token'], $token)) {
            throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
        }
        $id = filter_var($_POST['curso_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
        if ($id === false) throw new InvalidArgumentException('Informe um curso válido.');
        if (($_POST['acao'] ?? '') === 'confirmar') {
            if (($_SESSION['cancelamento_pendente'] ?? null) !== $id) {
                throw new InvalidArgumentException('Confira o curso antes de confirmar o cancelamento.');
            }
            // O ID da conta vem somente da sessão: nunca remove inscrições de outra conta.
            $stmt = $conexao->prepare('DELETE FROM inscricoes WHERE usuario_id = :usuario_id AND curso_id = :curso_id');
            $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':curso_id' => $id]);
            $_SESSION['cancelamento_mensagem'] = $stmt->rowCount() ? 'Inscrição cancelada com sucesso.' : 'Esta inscrição já não está disponível.';
            unset($_SESSION['cancelamento_pendente']);
            $_SESSION['cancelamento_token'] = bin2hex(random_bytes(32));
            header('Location: meus_cursos.php');
            exit();
        }
        if (($_POST['acao'] ?? '') !== 'cancelar') throw new InvalidArgumentException('Solicitação inválida.');
        $cursoCancelar = buscarCursoPorId($conexao, $id);
        if (!$cursoCancelar || !$cursoCancelar['inscrito']) throw new InvalidArgumentException('Você não está inscrito neste curso.');
        $_SESSION['cancelamento_pendente'] = $id;
    } else {
        unset($_SESSION['cancelamento_pendente']);
    }
} catch (InvalidArgumentException $e) {
    $erro = $e->getMessage();
    $cursoCancelar = false;
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível cancelar a inscrição. Tente novamente.';
    $cursoCancelar = false;
}
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
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
    <h1>Meus cursos</h1>
    <p>Acompanhe os cursos em que você está inscrito.</p>
    <p><a class="report-link" href="cursos.php">Ver todos os cursos</a></p>
    <?php if ($mensagem !== ''): ?>
        <p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($cursoCancelar): ?>
        <form method="post" class="danger-confirmation">
            <h2>Cancelar inscrição</h2>
            <p>Deseja cancelar sua inscrição em <strong><?= htmlspecialchars($cursoCancelar['nome'], ENT_QUOTES, 'UTF-8') ?></strong>?</p>
            <p>O curso sairá de Meus cursos. Você poderá se inscrever novamente pelo catálogo.</p>
            <input type="hidden" name="curso_id" value="<?= (int) $cursoCancelar['id'] ?>">
            <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['cancelamento_token'], ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" name="acao" value="confirmar" class="cancel-enrollment">Confirmar cancelamento</button>
            <a class="cancel-link" href="meus_cursos.php">Manter inscrição</a>
        </form>
    <?php endif; ?>
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
                            <form method="post" class="course-enrollment">
                                <input type="hidden" name="curso_id" value="<?= (int) $curso['id'] ?>">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['cancelamento_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="acao" value="cancelar" class="cancel-enrollment">Cancelar inscrição</button>
                            </form>
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
