<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$_SESSION['inscricao_token'] ??= bin2hex(random_bytes(32)); // mantem um token aleatorio na sessao para proteger o formulario contra csrf
// usa um token separado para impedir que um formulario de inscricao altere favoritos
$_SESSION['favoritos_token'] ??= bin2hex(random_bytes(32));
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
// aceita apenas ids positivos dentro do limite do banco
$curso = false;
$erro = '';
$mensagem = $_SESSION['favoritos_mensagem'] ?? '';
unset($_SESSION['favoritos_mensagem']);
$favoritado = false;
$erroFavoritos = '';
if ($id === false) {
    http_response_code(400);
    $erro = 'Informe um curso válido.';
} else {
    try {
        $curso = buscarCursoPorId($conexao, $id);
        if (!$curso) {
            http_response_code(404);
            $erro = 'Curso não encontrado.';
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $acao = $_POST['acao'] ?? 'inscrever';
            if ($acao === 'favoritar') {
                $_SESSION['favoritos_mensagem'] = adicionarFavorito($conexao, $id, $_POST['token'] ?? null);
                header('Location: curso.php?id=' . $id);
                exit();
            } elseif ($acao === 'desfavoritar') {
                $_SESSION['favoritos_mensagem'] = removerFavorito($conexao, $id, $_POST['token'] ?? null);
                header('Location: curso.php?id=' . $id);
                exit();
            } elseif ($acao === 'inscrever') {
                $mensagem = inscreverUsuarioNoCurso($conexao, $id, $_POST['token'] ?? null);
            } else {
                throw new InvalidArgumentException('Solicitação inválida.');
            }
            // reconsulta para que a pagina mostre o estado de inscrito apos o envio
            $curso = buscarCursoPorId($conexao, $id);
        }
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        if (in_array($_POST['acao'] ?? '', ['favoritar', 'desfavoritar'], true)) {
            $erro = 'Não foi possível alterar os favoritos. Tente novamente.';
        } else {
            $erro = 'Não foi possível carregar o curso ou realizar a inscrição. Tente novamente.';
        }
    }
}
if ($curso) {
    try {
        $favoritado = cursoEstaFavoritado($conexao, $id);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erroFavoritos = 'Não foi possível carregar o estado dos favoritos. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do curso | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="course-detail-page">
    <h1>Detalhes do curso</h1>
    <p><a href="cursos.php">Todos os cursos</a> · <a href="meus_cursos.php">Meus cursos</a></p>
    <?php if ($mensagem !== ''): ?>
        <p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($curso): ?>
        <?php // escapa os dados do banco para impedir que sejam interpretados como html ?>
        <article class="course-card">
            <h2><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p class="course-description"><?= htmlspecialchars((string) ($curso['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="course-duration"><?= (int) $curso['carga_horaria'] ?> horas</p>
            <div class="course-actions">
                <?php if ($curso['inscrito']): ?>
                    <span class="course-enrolled">Já inscrito</span>
                <?php else: ?>
                    <form method="post" class="course-enrollment">
                        <input type="hidden" name="acao" value="inscrever">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['inscricao_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <input type="submit" value="Inscrever-se">
                    </form>
                <?php endif; ?>
                <?php if ($erroFavoritos === ''): ?>
                    <form method="post" class="course-enrollment">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['favoritos_token'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($favoritado): ?>
                            <input type="hidden" name="acao" value="desfavoritar">
                            <button type="submit">Remover dos favoritos</button>
                        <?php else: ?>
                            <input type="hidden" name="acao" value="favoritar">
                            <button type="submit">Favoritar</button>
                        <?php endif; ?>
                    </form>
                <?php else: ?>
                    <p class="message-warning" role="status"><?= htmlspecialchars($erroFavoritos, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
            </div>
        </article>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
