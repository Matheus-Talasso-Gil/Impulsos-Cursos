<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../database/connect_postgres.php';
// Token de proteção contra CSRF, mantido durante a sessão.
$_SESSION['vinculo_token'] ??= bin2hex(random_bytes(32));
$mensagem = '';
$vinculo = false;
// Abrir a página ou cancelar descarta a conferência anterior e exige uma nova busca.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') unset($_SESSION['vinculo_pendente']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['vinculo_token'], $_POST['token'])) {
        $mensagem = 'Solicitação inválida. Recarregue a página.';
    } else {
        try {
            if (isset($_POST['confirmar'])) {
                // Usa os IDs conferidos no servidor, sem confiar em IDs enviados pelo formulário de confirmação.
                $dados = $_SESSION['vinculo_pendente'] ?? [];
                unset($_SESSION['vinculo_pendente']);
                // Revalida a disponibilidade do aluno e o e-mail da conta antes de vincular.
                $stmt = $conexao->prepare('UPDATE alunos SET usuario_id = :usuario WHERE id = :aluno AND usuario_id IS NULL AND EXISTS (SELECT 1 FROM usuarios WHERE id = :conta AND email = :email)');
                $stmt->execute([':usuario' => $dados['usuario_id'] ?? 0, ':aluno' => $dados['aluno_id'] ?? 0, ':conta' => $dados['usuario_id'] ?? 0, ':email' => $dados['email'] ?? '']);
                $mensagem = $stmt->rowCount() ? 'Conta vinculada com sucesso. O vínculo é permanente.' : 'Aluno não encontrado ou já vinculado.';
            } else {
                unset($_SESSION['vinculo_pendente']);
                $id = filter_var($_POST['aluno_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
                // CROSS JOIN combina aluno e conta; os filtros selecionam o ID e o e-mail informados.
                // NOT EXISTS exclui contas que já estão vinculadas a um cadastro de aluno.
                $stmt = $conexao->prepare('SELECT a.id AS aluno_id, a.nome, u.id AS usuario_id, u.email FROM alunos a CROSS JOIN usuarios u WHERE a.id = :id AND a.usuario_id IS NULL AND u.email = :email AND NOT EXISTS (SELECT 1 FROM alunos WHERE usuario_id = u.id)');
                $stmt->execute([':id' => $id ?: 0, ':email' => $email]);
                $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                // Evita confirmar um vínculo quando o resultado é ambíguo.
                if (count($resultados) === 1) {
                    $vinculo = $resultados[0];
                    $_SESSION['vinculo_pendente'] = $vinculo;
                } else $mensagem = 'Informe um aluno sem conta e o e-mail de uma conta existente e única ainda sem aluno.';
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            $mensagem = 'Não foi possível vincular. Confira se o aluno e a conta estão disponíveis e se a migration foi executada.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administração | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <h1>Administração</h1>
        <p>Gerencie os cadastros de alunos.</p>
        <p><a class="report-link" href="create.php">Cadastrar aluno</a></p>
        <p><a class="report-link" href="select.php">Relatório de alunos</a></p>
        <p><a class="report-link" href="select_w_w.php">Consultar aluno</a></p>
        <p><a class="report-link" href="delete.php">Excluir aluno</a></p>
        <h2>Vincular conta a aluno</h2>
        <p>Confira a identidade do titular antes de vincular. O vínculo será único e permanente.</p>
        <?php if ($mensagem !== ''): ?>
            <p role="status"><?= htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($vinculo): ?>
            <p>Aluno: <?= (int) $vinculo['aluno_id'] ?> — <?= htmlspecialchars($vinculo['nome'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Conta: <?= htmlspecialchars($vinculo['email'], ENT_QUOTES, 'UTF-8') ?></p>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['vinculo_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="submit" name="confirmar" value="Confirmar vínculo permanente">
                <a href="admin.php">Cancelar</a>
            </form>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['vinculo_token'], ENT_QUOTES, 'UTF-8') ?>">
                <label for="aluno_id">ID do aluno:</label>
                <input type="number" name="aluno_id" id="aluno_id" min="1" required>
                <label for="email">E-mail da conta existente:</label>
                <input type="email" name="email" id="email" required>
                <input type="submit" value="Conferir vínculo">
            </form>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
