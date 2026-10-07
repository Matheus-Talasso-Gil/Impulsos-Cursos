<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
function buscarAlunoPorCpf($conexao, $cpf) {
    if (!is_string($cpf)) return false;
    $cpf = preg_replace('/\D/', '', $cpf);
    if ($cpf === '') return false;
    $stmt = $conexao->prepare("SELECT * FROM alunos WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf LIMIT 1");
    $stmt->bindParam(':cpf', $cpf); $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar aluno</title><?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?><main class="lookup-page">
        <div class="lookup-heading">
            <p class="lookup-eyebrow">ALUNOS</p>
            <h1>Consultar aluno</h1>
            <p>Encontre um cadastro pelo identificador que você tem em mãos.</p>
        </div><section class="lookup-section"><form class="lookup-form" action="" method="post">
                <fieldset class="lookup-methods">
                    <legend>Como deseja pesquisar?</legend>
                    <label class="lookup-method"><input type="radio" name="tipo_consulta" value="id"><span><strong>ID do aluno</strong><small>Use o número do cadastro</small></span></label>
                    <label class="lookup-method"><input type="radio" name="tipo_consulta" value="cpf"><span><strong>CPF</strong><small>Use os 11 dígitos do documento</small></span></label>
                </fieldset>
                <div id="campo-id"><label for="id">ID do aluno:</label><input type="number" name="id" id="id" min="1" max="2147483647"></div>
                <div id="campo-cpf"><label for="cpf">CPF do aluno:</label><input type="text" name="cpf" id="cpf" maxlength="14" placeholder="000.000.000-00"></div>
                <input type="submit" value="Consultar aluno">
            </form></section>
        <p class="lookup-report-link"><a class="report-link" href="select.php">Ver relatório de alunos</a></p>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            try {
                if (($_POST['tipo_consulta'] ?? '') === 'id' && !empty($_POST['id'])) {
                    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
                    if ($id !== false) {
                        echo '<section class="student-result" aria-label="Resultado da consulta">'; read_w_w($conexao, $id); echo '</section>';
                    } else echo '<p class="message-error" role="alert">Número inválido, tente novamente.</p>';
                } elseif (($_POST['tipo_consulta'] ?? '') === 'cpf' && !empty($_POST['cpf'])) {
                    $aluno = buscarAlunoPorCpf($conexao, $_POST['cpf']);
                    if ($aluno !== false) {
                        echo '<section class="student-result" aria-label="Resultado da consulta">'; read_w_w($conexao, $aluno['id']); echo '</section>';
                    } else echo '<p class="lookup-empty" role="status">Nenhum aluno encontrado com esse CPF.</p>';
                } else echo '<p class="message-warning" role="status">Informe um ID ou CPF para consultar.</p>';
            } catch (PDOException $e) {
                error_log($e->getMessage());
                echo '<p class="message-error" role="alert">Não foi possível consultar o aluno. Tente novamente.</p>';
            }
        }
        ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
