<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/functions.php';
// mantem um token imprevisivel para proteger o cadastro e a edicao de cursos
$_SESSION['curso_admin_token'] ??= bin2hex(random_bytes(32));
$erro = '';
$mensagem = '';
$curso = ['nome' => '', 'descricao' => '', 'carga_horaria' => ''];
$podeEditar = true;
$id = false;
if ($editarCurso) {
    // usa o id apenas para localizar o curso sem alterar sua identidade
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
    // preserva os campos textuais para permitir correcao apos um erro
    foreach (['nome', 'descricao', 'carga_horaria'] as $campo) {
        $curso[$campo] = is_string($_POST[$campo] ?? null) ? $_POST[$campo] : '';
    }
    try {
        validarTokenCursoAdmin($_POST['token'] ?? null);
        $dados = validarDadosCursoAdmin($_POST);
        if ($editarCurso) {
            $stmt = $conexao->prepare('UPDATE cursos SET nome = :nome, descricao = :descricao, carga_horaria = :carga_horaria WHERE id = :id');
            $stmt->execute($dados + ['id' => $id]);
            if (!$stmt->rowCount()) {
                throw new InvalidArgumentException('Curso não encontrado.');
            }
            $mensagem = 'Curso atualizado com sucesso.';
            registrarLogAdmin($conexao, 'editou', 'curso', $id, 'Editou o curso ' . $dados['nome']);
            $curso = $dados;
        } else {
            $stmt = $conexao->prepare('INSERT INTO cursos (nome, descricao, carga_horaria) VALUES (:nome, :descricao, :carga_horaria) RETURNING id');
            $stmt->execute($dados);
            $cursoId = (int) $stmt->fetchColumn();
            registrarLogAdmin($conexao, 'criou', 'curso', $cursoId, 'Criou o curso ' . $dados['nome']);
            // redirecionamento apos criar evita duplicacao ao atualizar a pagina
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
if (!$editarCurso && ($_GET['sucesso'] ?? '') === '1') {
    $mensagem = 'Curso cadastrado com sucesso.';
}
$titulo = $editarCurso ? 'Editar curso' : 'Cadastrar curso';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?> | Impulso Cursos</title>
    <?php require __DIR__ . '/stylesheet.php'; ?>
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
