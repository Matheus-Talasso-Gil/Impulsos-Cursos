<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
// mantem um token imprevisivel para proteger a edicao contra csrf
$_SESSION['edicao_token'] ??= bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atualizar</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/header.php'; ?>
<main>
<h1>Atualizar aluno</h1>
<?php
// distingue a busca inicial do envio que salva a edicao
// usa a identidade salva na sessao para impedir alteracoes pelo formulario
$aluno = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['id']) || isset($_POST['nome']))) {
    try {
        if (isset($_POST['nome'])) {
            // compara os tokens de forma segura antes de permitir a edicao
            if (!is_string($_POST['token'] ?? null) || !hash_equals($_SESSION['edicao_token'], $_POST['token'])) {
                throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e busque o aluno novamente.');
            }
            $idFormulario = filter_var($_POST['aluno_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            // impede salvar em outro aluno quando a selecao mudou em outra aba
            if ($idFormulario === false || $idFormulario !== (int) ($_SESSION['aluno_edicao_id'] ?? 0)) {
                throw new InvalidArgumentException('O aluno em edição mudou em outra aba. Busque novamente antes de salvar.');
            }
        }
        if (isset($_POST['nome'])) {
            $id = (int) $_SESSION['aluno_edicao_id'];
        } else {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
            if ($id === false) {
                $id = 0;
            }
        }
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
                $nome = is_string($_POST['nome'] ?? null) ? trim($_POST['nome']) : '';
                $turma = is_string($_POST['turma'] ?? null) ? trim($_POST['turma']) : '';
                $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
                $ativo = $_POST['ativo'] ?? null;
                if (preg_match('/^.{1,255}$/us', $nome) !== 1 || preg_match('/^.{1,255}$/us', $turma) !== 1
                    || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)
                    || !in_array($ativo, ['true', 'false'], true)) {
                    throw new InvalidArgumentException('Informe nome e turma com até 255 caracteres, um e-mail válido e a situação do aluno.');
                }
                Atualizar($conexao, $id, $nome, $turma, $nasc, $ativo, $email, $cpf);
                // reconsulta para preencher o formulario com os valores ja atualizados no banco
                $stmt->execute([':id' => $id]);
                $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
        if (!$aluno) {
            echo '<p class="message-error">Aluno não encontrado.</p>';
        }
    } catch (InvalidArgumentException $e) {
        echo '<p class="message-error" role="alert">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo '<p class="message-error" role="alert">Não foi possível carregar os dados do aluno. Tente novamente.</p>';
    }
} else {
    echo '<p class="message-warning">Digite o ID do aluno para carregar os dados ou escolha Editar no relatório.</p>';
}
?>
<?php if (!$aluno): ?>
<form method="post">
    <label for="id">ID do aluno:</label>
    <input type="number" name="id" id="id" min="1" max="2147483647" required>
    <input type="submit" value="Buscar aluno">
</form>
<?php endif; ?>
<?php if ($aluno): ?>
<form method="post">

    <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['edicao_token'], ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="aluno_id" value="<?= (int) $aluno['id'] ?>">
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
