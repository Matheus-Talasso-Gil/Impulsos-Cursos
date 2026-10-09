<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../database/connect_postgres.php';
$alunosCursos = [];
$erroAlunosCursos = '';
try {
    // mantem alunos sem conta ou inscricoes na listagem
    // identifica o vinculo pelo id da conta e nao pela igualdade de emails
    $alunosCursos = $conexao->query('SELECT a.id, a.nome, a.turma, a.usuario_id, u.email AS conta_email, c.id AS curso_id, c.nome AS curso_nome FROM alunos a LEFT JOIN usuarios u ON u.id = a.usuario_id LEFT JOIN inscricoes i ON i.usuario_id = a.usuario_id LEFT JOIN cursos c ON c.id = i.curso_id ORDER BY a.id, c.id')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroAlunosCursos = 'Não foi possível carregar os cursos dos alunos. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cursos dos alunos | Impulso Cursos</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="admin-dashboard">
        <section aria-labelledby="alunos-cursos-titulo">
            <h1 id="alunos-cursos-titulo">Cursos dos alunos</h1>
            <p>As inscrições são consultadas pelo usuario_id do aluno. Alunos com vários cursos aparecem em uma linha por curso.</p>
            <?php if ($erroAlunosCursos !== ''): ?>
                <p class="message-warning" role="status"><?= htmlspecialchars($erroAlunosCursos, ENT_QUOTES, 'UTF-8') ?></p>
            <?php else: ?>
                <div class="table-wrapper" tabindex="0" role="region" aria-label="Cursos dos alunos">
                    <table>
                        <caption>Alunos cadastrados e suas inscrições em cursos</caption>
                        <thead><tr><th scope="col">ID do aluno</th><th scope="col">Aluno</th><th scope="col">Turma</th><th scope="col">Conta</th><th scope="col">Curso</th></tr></thead>
                        <tbody>
                            <?php foreach ($alunosCursos as $alunoCurso): ?>
                                <?php
                                $nomeCurso = $alunoCurso['curso_nome'] ?? null;
                                if ($nomeCurso === null) {
                                    if ($alunoCurso['usuario_id'] === null) {
                                        $nomeCurso = 'Sem conta';
                                    } else {
                                        $nomeCurso = 'Sem curso';
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= (int) $alunoCurso['id'] ?></td>
                                    <td><?= htmlspecialchars($alunoCurso['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string) ($alunoCurso['turma'] ?? 'Sem turma'), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($alunoCurso['conta_email'] ?? 'Sem conta', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($nomeCurso, ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$alunosCursos): ?>
                                <tr><td colspan="5">Nenhum aluno cadastrado.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
