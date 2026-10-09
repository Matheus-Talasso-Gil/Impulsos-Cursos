<?php
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
// mantem um token imprevisivel na sessao para proteger as alteracoes contra csrf
$_SESSION['favoritos_token'] ??= bin2hex(random_bytes(32));
$favoritos = [];
$erro = '';
$erroListagem = '';
$mensagem = $_SESSION['favoritos_mensagem'] ?? '';
unset($_SESSION['favoritos_mensagem']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (($_POST['acao'] ?? '') !== 'desfavoritar') {
            throw new InvalidArgumentException('Solicitação inválida.');
        }
        $_SESSION['favoritos_mensagem'] = removerFavorito($conexao, $_POST['curso_id'] ?? null, $_POST['token'] ?? null);
        // evita repetir a remocao ao atualizar a pagina
        header('Location: favoritos.php');
        exit();
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível remover o favorito. Tente novamente.';
    }
}
try {
    $favoritos = buscarFavoritosUsuario($conexao);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroListagem = 'Não foi possível carregar seus favoritos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Favoritos | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
    <h1>Favoritos</h1>
    <p>Guarde os cursos que deseja consultar depois.</p>
    <p><a class="report-link" href="cursos.php">Ver todos os cursos</a></p>
    <?php if ($mensagem !== ''): ?>
        <p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erroListagem !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erroListagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (!$favoritos): ?>
        <section class="profile-empty favorites-empty" aria-labelledby="favoritos-vazios-titulo">
            <h2 id="favoritos-vazios-titulo">Nenhum curso favoritado ainda</h2>
            <p>Você ainda não favoritou nenhum curso. Escolha um curso e use Favoritar para guardá-lo aqui.</p>
            <a class="report-link" href="/impulsos_cursos/app/cursos.php">Ver todos os cursos</a>
        </section>
    <?php else: ?>
        <section class="courses" aria-label="Cursos favoritos">
            <div class="course-grid">
                <?php foreach ($favoritos as $curso): ?>
                    <article class="course-card">
                        <h2><?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
                        <p class="favorite-description"><?= htmlspecialchars((string) ($curso['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="course-duration"><?= (int) $curso['carga_horaria'] ?> horas</p>
                        <div class="course-actions">
                            <a class="course-link" href="curso.php?id=<?= (int) $curso['id'] ?>">Ver detalhes</a>
                            <form method="post" class="course-enrollment">
                                <input type="hidden" name="acao" value="desfavoritar">
                                <input type="hidden" name="curso_id" value="<?= (int) $curso['id'] ?>">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['favoritos_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit">Remover dos favoritos</button>
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
