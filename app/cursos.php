<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$_SESSION['inscricao_token'] ??= bin2hex(random_bytes(32)); // mantem um token aleatorio na sessao para proteger o formulario contra csrf
$mensagem = '';
$erro = '';
$erroListagem = '';
$cursos = [];
$categoria = is_string($_GET['categoria'] ?? null) ? $_GET['categoria'] : 'todas';
if (!in_array($categoria, ['todas', 'ingles', 'tecnologia'], true)) {
    $categoria = 'todas';
}
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
    if ($categoria !== 'todas') {
        $cursosFiltrados = [];
        // identifica as areas pelos nomes pois os cursos ainda nao possuem categoria no banco
        foreach ($cursos as $curso) {
            $ingles = preg_match('/ingl[eê]s/iu', $curso['nome']) === 1;
            $tecnologia = !$ingles && preg_match('/inform[aá]tica|programa[cç][aã]o|desenvolvimento|banco de dados|excel|word|powerpoint|windows|linux|redes de computadores|manuten[cç][aã]o de computadores|seguran[cç]a digital|\bgit\b|java|python|power bi|canva/iu', $curso['nome']) === 1;
            if (($categoria === 'ingles' && $ingles) || ($categoria === 'tecnologia' && $tecnologia)) {
                $cursosFiltrados[] = $curso;
            }
        }
        $cursos = $cursosFiltrados;
    }
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
    <div class="report-toolbar">
        <details class="filter-panel" <?= $categoria !== 'todas' ? 'open' : '' ?>>
            <summary class="filters-button">Filtrar cursos</summary>
            <form method="get" class="filter-form">
                <div class="filter-field">
                    <label for="categoria">Área do curso</label>
                    <select name="categoria" id="categoria">
                        <option value="todas" <?= $categoria === 'todas' ? 'selected' : '' ?>>Todos os cursos</option>
                        <option value="ingles" <?= $categoria === 'ingles' ? 'selected' : '' ?>>Inglês</option>
                        <option value="tecnologia" <?= $categoria === 'tecnologia' ? 'selected' : '' ?>>Tecnologia</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <input type="submit" value="Aplicar filtro">
                    <a class="filter-reset" href="cursos.php">Limpar</a>
                </div>
            </form>
        </details>
    </div>
    <?php if ($mensagem !== ''): ?>
        <p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>
    <?php if ($erroListagem !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erroListagem, ENT_QUOTES, 'UTF-8') ?></p>
    <?php elseif (!$cursos): ?>
        <p class="message-warning"><?= $categoria === 'todas' ? 'Nenhum curso cadastrado' : 'Nenhum curso encontrado nesta área' ?></p>
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
