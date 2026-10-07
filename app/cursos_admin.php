<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
$cursos = [];
$erro = '';
try {
    // LEFT JOIN inclui cursos sem inscrições; COUNT(i.id) retorna zero nesses casos.
    $cursos = $conexao->query('SELECT c.id, c.nome, c.descricao, c.carga_horaria, COUNT(i.id) AS inscritos FROM cursos c LEFT JOIN inscricoes i ON i.curso_id = c.id GROUP BY c.id ORDER BY c.id')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível carregar os cursos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar cursos | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main class="admin-courses">
    <h1>Gerenciar cursos</h1>
    <p><a class="report-link" href="curso_create.php">Cadastrar curso</a></p>
    <?php if (($_GET['excluido'] ?? '') === '1'): ?><p class="message-success" role="status">Curso excluído com sucesso.</p><?php endif; ?>
    <?php if ($erro !== ''): ?>
        <p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
    <?php else: ?>
    <div class="table-wrapper" tabindex="0" role="region" aria-label="Gerenciamento de cursos">
        <table>
            <caption>Cursos cadastrados e quantidade de inscritos</caption>
            <thead><tr><th scope="col">ID</th><th scope="col">Nome</th><th scope="col">Descrição</th><th scope="col">Carga horária</th><th scope="col">Inscritos</th><th scope="col">Ações</th></tr></thead>
            <tbody>
                <?php foreach ($cursos as $curso): ?>
                <tr>
                    <td><?= (int) $curso['id'] ?></td>
                    <td><?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="admin-course-description"><?= htmlspecialchars($curso['descricao'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int) $curso['carga_horaria'] ?>h</td>
                    <td><?= (int) $curso['inscritos'] ?></td>
                    <td><div class="admin-course-actions"><a href="curso_update.php?id=<?= (int) $curso['id'] ?>">Editar</a><a href="curso_delete.php?id=<?= (int) $curso['id'] ?>">Excluir</a></div></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$cursos): ?><tr><td colspan="6">Nenhum curso cadastrado.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>