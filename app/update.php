<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
<h1>Atualizar aluno</h1>
<?php
// O primeiro POST carrega o aluno pelo ID; o envio de nome identifica a etapa de salvar a edição.
// Nessa segunda etapa, o ID e os dados originais são recuperados da sessão no servidor.
$aluno = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['id']) || isset($_POST['nome']))) {
    $id = isset($_POST['nome']) ? (int) ($_SESSION['aluno_edicao_id'] ?? 0) : (int) ($_POST['id'] ?? 0);
    $stmt = $conexao->prepare('SELECT * FROM alunos WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($aluno && !isset($_POST['nome'])) {
        $_SESSION['aluno_edicao_id'] = $aluno['id'];
        $_SESSION['aluno_edicao_cpf'] = $aluno['cpf'];
        $_SESSION['aluno_edicao_nasc'] = $aluno['nasc'];
    }
    if ($aluno && isset($_POST['nome'])) {
        $cpf = $_SESSION['aluno_edicao_cpf'] ?? '';
        $nasc = $_SESSION['aluno_edicao_nasc'] ?? '';

        if ($cpf === '' || $nasc === '') { // impede salvar quando a identidade original do aluno nao foi recuperada
            echo '<p class="message-error">Não foi possível recuperar os dados originais do aluno.</p>';
        } else {
            Atualizar($conexao, $id, $_POST['nome'], $_POST['turma'], $nasc, $_POST['ativo'], $_POST['email'], $cpf);
            // Reconsulta para preencher o formulário com os valores já atualizados no banco.
            $stmt->execute([':id' => $id]);
            $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    if (!$aluno) echo '<p class="message-error">Aluno não encontrado.</p>';
} else {
    echo '<p class="message-warning">Digite o ID do aluno para carregar os dados ou escolha Editar no relatório.</p>';
}
?>
<?php if (!$aluno): ?>
<form method="post">
    <label for="id">ID do aluno:</label>
    <input type="number" name="id" id="id" min="1" max="255" required>
    <input type="submit" value="Buscar aluno">
</form>
<?php endif; ?>
<?php if ($aluno): ?>
<form method="post">

    <p>ID: <?= htmlspecialchars((string) $aluno['id'], ENT_QUOTES, 'UTF-8') ?></p>

    <label for="nome">Nome:</label>
    <input type="text" name="nome" id="nome" value="<?= htmlspecialchars((string) $aluno['nome'], ENT_QUOTES, 'UTF-8') ?>" required>

    <label>CPF:</label>
    <p><?= htmlspecialchars((string) $aluno['cpf'], ENT_QUOTES, 'UTF-8') ?></p>

    <label for="turma">Turma:</label>
    <input type="text" name="turma" id="turma" value="<?= htmlspecialchars((string) $aluno['turma'], ENT_QUOTES, 'UTF-8') ?>" required>

    <label for="email">E-mail:</label>
    <input type="email" name="email" id="email" value="<?= htmlspecialchars((string) $aluno['email'], ENT_QUOTES, 'UTF-8') ?>" required>

    <label>Data de nascimento:</label>
    <p><?= htmlspecialchars((string) $aluno['nasc'], ENT_QUOTES, 'UTF-8') ?></p>

    <label>Ativo:</label>
    <input type="radio" name="ativo" id="ativo_sim" value="true" <?= $aluno['ativo'] ? 'checked' : '' ?> required>
    <label for="ativo_sim">SIM</label>
    <input type="radio" name="ativo" id="ativo_nao" value="false" <?= !$aluno['ativo'] ? 'checked' : '' ?>>
    <label for="ativo_nao">NÃO</label>
    <input type="submit" value="Atualizar">
    <input type="reset" value="Restaurar campos">
</form>
<?php endif; ?>
<p><a class="report-link" href="select.php">Consultar RL</a></p>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
