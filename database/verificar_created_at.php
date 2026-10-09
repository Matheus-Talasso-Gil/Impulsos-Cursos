<?php
// impede que os testes de banco sejam executados pelo navegador
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/data_conta.php';
require_once __DIR__ . '/../includes/recuperacao_senha.php';
function conferirDataConta($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
// somente tabelas temporarias nenhuma migration e aplicada a tabela real
$conexao->beginTransaction();
try {
    $conexao->exec("CREATE TEMP TABLE usuarios (id SERIAL PRIMARY KEY, email VARCHAR(255) UNIQUE, senha VARCHAR(255), tipo VARCHAR(20) NOT NULL DEFAULT 'usuario') ON COMMIT DROP");
    $conexao->exec("INSERT INTO usuarios (email, senha) VALUES ('antigo@example.com', 'hash-antigo')");
    $antes = $conexao->query('SELECT * FROM usuarios')->fetch(PDO::FETCH_ASSOC);
    $migration = file_get_contents(__DIR__ . '/adicionar_created_at_usuarios.sql');
    $conexao->exec($migration);
    $depois = $conexao->query('SELECT * FROM usuarios')->fetch(PDO::FETCH_ASSOC);
    conferirDataConta(array_diff_assoc($antes, $depois) === [], 'Migration alterou dados antigos.');
    conferirDataConta($depois['created_at'] === $conexao->query('SELECT CURRENT_TIMESTAMP::timestamp')->fetchColumn(), 'Conta antiga deve receber o horário da migration.');
    $conexao->exec($migration);
    conferirDataConta($depois === $conexao->query('SELECT * FROM usuarios')->fetch(PDO::FETCH_ASSOC), 'Reexecução alterou a conta.');
    $conexao->commit();
} catch (Throwable $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    throw $e;
}
// a primeira transacao removeu as tabelas temporarias ao confirmar
$conexao->beginTransaction();
try {
    $conexao->exec("CREATE TEMP TABLE usuarios (id SERIAL PRIMARY KEY, email VARCHAR(255) UNIQUE, senha VARCHAR(255), tipo VARCHAR(20) NOT NULL DEFAULT 'usuario') ON COMMIT DROP");
    $conexao->exec($migration);
    $_POST['created_at'] = '2000-01-01 00:00:00';
    cadastrar_user($conexao, 'novo@example.com', 'senha-teste');
    $createdAt = $conexao->query('SELECT created_at FROM usuarios')->fetchColumn();
    conferirDataConta($createdAt === $conexao->query('SELECT CURRENT_TIMESTAMP::timestamp')->fetchColumn(), 'Cadastro deve usar o horário do banco.');
    // usa uma data diferente para detectar alteracoes indevidas na recuperacao
    $conexao->exec("UPDATE usuarios SET created_at = TIMESTAMP '2026-01-02 03:04:05'");
    $createdAt = $conexao->query('SELECT created_at FROM usuarios')->fetchColumn();
    for ($i = 0; $i < 2; $i++) {
        $usuario = consultar_user($conexao, 'novo@example.com');
        conferirDataConta(password_verify('senha-teste', $usuario['senha']), 'Autenticação falhou.');
        conferirDataConta($createdAt === $conexao->query('SELECT created_at FROM usuarios')->fetchColumn(), 'Login alterou created_at.');
    }
    $_SESSION = ['recuperacao_csrf' => 'csrf-teste'];
    $token = iniciarRecuperacaoSenha($conexao, 'novo@example.com');
    redefinirSenhaUsuario($conexao, $token, 'csrf-teste', 'senha-nova', 'senha-nova');
    conferirDataConta(password_verify('senha-nova', consultar_user($conexao, 'novo@example.com')['senha']), 'Redefinição falhou.');
    conferirDataConta($createdAt === $conexao->query('SELECT created_at FROM usuarios')->fetchColumn(), 'Recuperação alterou created_at.');
    conferirDataConta(formatarDataCriacaoConta('2026-10-07 15:30:00.123456') === '07/10/2026 às 15:30', 'Formatação incorreta.');
    foreach ([null, '', 'inválida', '2026-02-30 15:30:00', 'infinity'] as $valor) {
        conferirDataConta(formatarDataCriacaoConta($valor) === 'Não informada', 'Data inválida não foi tratada.');
    }
    echo 'OK: migration, reexecução, contas antigas, cadastro, autenticação, recuperação e formatação.' . PHP_EOL;
    echo 'PostgreSQL: ' . $conexao->getAttribute(PDO::ATTR_SERVER_VERSION) . '; timezone: ' . $conexao->query('SHOW timezone')->fetchColumn() . '; PHP: ' . date_default_timezone_get() . PHP_EOL;
} finally {
    unset($_POST['created_at']);
    $conexao->rollBack();
}
