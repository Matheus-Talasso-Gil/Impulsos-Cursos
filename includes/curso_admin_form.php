<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/functions.php';
$_SESSION['curso_admin_token'] ??= bin2hex(random_bytes(32));
$erro = '';
$mensagem = '';
$curso = ['nome' => '', 'descricao' => '', 'carga_horaria' => ''];
$podeEditar = true;
$id = false;
if ($editarCurso) {
    // O ID vem da URL apenas para localizar o registro; o UPDATE nunca altera essa coluna.
    $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    try {
        $curso = $id !== false ? buscarCursoAdmin($conexao, $id) : false;
        if (!$curso) {
            http_response_code($id === false ? 400 : 404);
            $erro = $id === false ? 'Informe um curso válido.' : 'Curso não encontrado.';
            $podeEditar = false;
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível carregar o curso. Tente novamente.';
        $podeEditar = false;
    }
}
if ($podeEditar && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Preserva os campos digitados se houver erro, aceitando apenas valores textuais para o formulário.
    foreach (['nome', 'descricao', 'carga_horaria'] as $campo) {
        $curso[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
    }
    try {
        validarTokenCursoAdmin($_POST['token'] ?? null);
        $dados = validarDadosCursoAdmin($_POST);
        if ($editarCurso) {
            $stmt = $conexao->prepare('UPDATE cursos SET nome = :nome, descricao = :descricao, carga_horaria = :carga_horaria WHERE id = :id');
            $stmt->execute($dados + ['id' => $id]);
            if (!$stmt->rowCount()) throw new InvalidArgumentException('Curso não encontrado.');
            $mensagem = 'Curso atualizado com sucesso.';
            $curso = $dados;
        } else {
            $stmt = $conexao->prepare('INSERT INTO cursos (nome, descricao, carga_horaria) VALUES (:nome, :descricao, :carga_horaria)');
            $stmt->execute($dados);
            // Redirecionamento após criar evita duplicação ao atualizar a página.
            header('Location: curso_create.php?sucesso=1');
            exit();
        }
    } catch (InvalidArgumentException $e) {
        $erro = $e->getMessage();
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erro = 'Não foi possível salvar o curso. Tente novamente.';
    }
}
if (!$editarCurso && ($_GET['sucesso'] ?? '') === '1') $mensagem = 'Curso cadastrado com sucesso.';
$titulo = $editarCurso ? 'Editar curso' : 'Cadastrar curso';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<main>
    <h1><?= $titulo ?></h1>
    <p><a href="cursos_admin.php">Voltar ao gerenciamento de cursos</a></p>
    <?php if ($mensagem !== ''): ?><p class="message-success" role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($erro !== ''): ?><p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($podeEditar): ?>
    <form method="post">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['curso_admin_token'], ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($editarCurso): ?><p>ID: <?= (int) $id ?></p><?php endif; ?>
        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="nome" maxlength="100" value="<?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?>" required>
        <label for="descricao">Descrição (opcional):</label>
        <textarea name="descricao" id="descricao" rows="5"><?= htmlspecialchars((string) ($curso['descricao'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        <label for="carga_horaria">Carga horária (horas):</label>
        <input type="number" name="carga_horaria" id="carga_horaria" min="1" max="2147483647" step="1" value="<?= htmlspecialchars((string) $curso['carga_horaria'], ENT_QUOTES, 'UTF-8') ?>" required>
        <input type="submit" value="<?= $editarCurso ? 'Salvar alterações' : 'Cadastrar curso' ?>">
    </form>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>