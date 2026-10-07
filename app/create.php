<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../login/verificar_admin.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../login/verificar_cpf.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Aluno</title>
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main>
        <h1>Matricular aluno</h1>
        <?php
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Retorna o CPF sem pontuação ou false quando o tamanho ou os dígitos verificadores são inválidos.
            $cpf = verificar_cpf($_POST['cpf'] ?? '');
            if ($cpf === false) {
                echo '<p class="message-error" role="alert">Informe um CPF válido.</p>';
            } else {


    $sql = "INSERT INTO alunos  (nome, cpf, nasc, turma, ativo, email)
                        VALUES   (:nome, :cpf, :nasc, :turma, :ativo, :email)";
                // Os marcadores recebem os valores separados do SQL, evitando injeção SQL nos campos do formulário.
                $stmt = $conexao->prepare($sql);
                $stmt->bindParam(":nome", $_POST['nome']);
                $stmt->bindParam(":cpf", $cpf);
                $stmt->bindParam(":nasc", $_POST['nasc']);
                $stmt->bindParam(":turma", $_POST['turma']);
                $stmt->bindParam(":ativo", $_POST['ativo']);
                $stmt->bindParam(":email", $_POST['email']);
                $stmt->execute();
                echo '<p class="message-success" role="status">Aluno cadastrado com sucesso!</p>';
            }
        }
        ?>
        <form action="" method="post">
            <label for="nome">Nome: </label>
            <input type="text" name="nome" id="nome" required>

            <label for="cpf">CPF: </label>
            <input type="text" name="cpf" id="cpf" maxlength="14" placeholder="000.000.000-00" inputmode="numeric" required>
            <label for="turma">Turma: </label>
            <select name="turma" id="turma" required>
                <option value="" selected disabled>Selecione a turma</option>
                <option value="INF-01">INF-01 — Informática Básica</option>
                <option value="ING-01">ING-01 — Inglês</option>
                <option value="ADM-01">ADM-01 — Administração</option>
            </select>
            <label for="email">E-mail: </label>
            <input type="email" name="email" id="email" required>
            <label for="nasc">Nascimento: </label>
            <input type="date" name="nasc" id="nasc" required>
            <label>Ativo: </label>
            <input type="radio" name="ativo" id="ativo_sim" value="true" required>
            <label for="ativo_sim">SIM</label>
            <input type="radio" name="ativo" id="ativo_nao" value="false">
            <label for="ativo_nao">NÃO</label>
            <input type="submit" value="Cadastrar">
            <input type="reset" value="Limpar">
        </form>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
