<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
// descarta filtros inesperados antes de montar a consulta
$cursoId = is_string($_GET['curso'] ?? null) ? $_GET['curso'] : '';
$cursos = [];
$situacao = $_GET['situacao'] ?? 'todas';
if (!is_string($situacao) || !in_array($situacao, ['todas', 'ativo', 'inativo'], true)) {
    $situacao = 'todas';
}
$filtrosAtivos = $cursoId !== '' || $situacao !== 'todas';
$alunos = [];
$erro = '';
try {
    $cursos = $conexao->query('SELECT id, nome FROM cursos ORDER BY nome, id')->fetchAll(PDO::FETCH_ASSOC);
    $idsCursos = array_map('strval', array_column($cursos, 'id'));
    if (!in_array($cursoId, $idsCursos, true)) {
        $cursoId = '';
    }
    $filtrosAtivos = $cursoId !== '' || $situacao !== 'todas';
    $alunos = listarAlunos($conexao, $cursoId, $situacao);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erro = 'Não foi possível carregar o relatório. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <h1>Alunos matriculados</h1>
        <?php if ($erro !== ''): ?><p class="message-error" role="alert"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
        <div class="report-toolbar">
            <details class="filter-panel" <?= $filtrosAtivos ? 'open' : '' ?>>
                <summary class="filters-button">Filtros</summary>
                <form action="" method="get" class="filter-form">
                    <div class="filter-fields">
                        <div class="filter-field">
                            <label for="filtro-curso">Curso</label>
                            <select name="curso" id="filtro-curso">
                                <option value="" <?= $cursoId === '' ? 'selected' : '' ?>>Todos os cursos</option>
                                <?php foreach ($cursos as $curso): ?>
                                <option value="<?= htmlspecialchars((string) $curso['id'], ENT_QUOTES, 'UTF-8') ?>" <?= $cursoId === (string) $curso['id'] ? 'selected' : '' ?>><?= htmlspecialchars($curso['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-field">
                            <label for="filtro-situacao">Situação</label>
                            <select name="situacao" id="filtro-situacao">
                                <option value="todas" <?= $situacao === 'todas' ? 'selected' : '' ?>>Todas</option>
                                <option value="ativo" <?= $situacao === 'ativo' ? 'selected' : '' ?>>Ativos</option>
                                <option value="inativo" <?= $situacao === 'inativo' ? 'selected' : '' ?>>Inativos</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-actions"><input type="submit" value="Aplicar filtros"><a class="filter-reset" href="select.php">Limpar</a></div>
                </form>
            </details>
        </div>
        <div class="table-wrapper" tabindex="0" role="region" aria-label="Relatório de alunos">
            <table>
                <caption>Relatório de alunos da Impulso Cursos</caption>
                <thead>
                    <tr>
                        <th scope="col">ID</th><th scope="col">Nome</th>

                        <th scope="col">CPF</th><th scope="col">Nascimento</th><th scope="col">Cursos</th>
                        <th scope="col">E-mail</th><th scope="col">Situação</th><th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunos as $aluno): ?>
                    <tr>

                        <td><?= htmlspecialchars((string) $aluno['id'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $aluno['nome'], ENT_QUOTES, 'UTF-8') ?></td>

                        <td><?= htmlspecialchars((string) (preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', (string) ($aluno['cpf'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $aluno['nasc'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) $aluno['cursos'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string) ($aluno['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><span class="student-status <?= $aluno['ativo'] ? 'is-active' : 'is-inactive' ?>"><?= $aluno['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
                        <td>
                            <form action="update.php" method="post" class="edit-action">
                                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $aluno['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="submit" value="Editar">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (!$alunos && $erro === ''): ?>
                    <tr><td colspan="8"><?= $filtrosAtivos ? 'Nenhum aluno corresponde aos filtros.' : 'Nenhum aluno cadastrado.' ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
