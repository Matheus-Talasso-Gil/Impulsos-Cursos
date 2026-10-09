<?php
// regressoes de edicao e validacao somente tabelas temporarias removidas no rollback
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
$_SESSION = ['id' => 1, 'tipo' => 'admin', 'edicao_token' => 'token-teste'];
$_SERVER['SCRIPT_NAME'] = '/impulsos_cursos/app/update.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
function conferirAluno($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
function renderizarEdicaoAluno(array $post)
{
    global $conexao;
    $_POST = $post;
    // captura o html da edicao para conferir as protecoes sem mostrar a pagina
    ob_start();
    try {
        include __DIR__ . '/../app/update.php';
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}
$conexao->beginTransaction();
try {
    // mantem os logs da edicao isolados do historico real
    $conexao->exec('CREATE TEMP TABLE usuarios (id INTEGER PRIMARY KEY, email VARCHAR(255), senha VARCHAR(255), tipo VARCHAR(20)) ON COMMIT DROP');
    $conexao->exec("INSERT INTO usuarios VALUES (1, 'admin@example.com', 'hash-teste', 'admin')");
    $migrationLogs = str_replace('CREATE TABLE IF NOT EXISTS logs_admin', 'CREATE TEMP TABLE IF NOT EXISTS logs_admin', file_get_contents(__DIR__ . '/adicionar_logs_admin.sql'));
    $conexao->exec($migrationLogs);
    $conexao->exec('CREATE TEMP TABLE alunos (id INTEGER PRIMARY KEY, nome VARCHAR(255), turma VARCHAR(255), nasc DATE, ativo BOOLEAN, email VARCHAR(255), cpf VARCHAR(14), usuario_id INTEGER) ON COMMIT DROP');
    $conexao->exec("INSERT INTO alunos VALUES (300, 'Aluno teste', 'INF-01', '2000-01-02', TRUE, 'aluno@example.com', '123.456.789-00', NULL), (301, 'Outro aluno', 'ING-01', '2001-02-03', TRUE, 'outro@example.com', '98765432100', NULL)");
    $antes = $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $formulario = renderizarEdicaoAluno(['id' => '300']);
    conferirAluno(str_contains($formulario, 'Aluno teste'), 'Deve carregar ID maior que 255.');
    $dados = ['aluno_id' => '300', 'nome' => 'Nome atualizado', 'turma' => 'ADM-01', 'email' => 'editado@example.com', 'ativo' => 'false'];
    conferirAluno(str_contains(renderizarEdicaoAluno($dados), 'Solicitação inválida'), 'Deve rejeitar edição sem CSRF.');
    conferirAluno($antes === $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'CSRF alterou aluno.');
    renderizarEdicaoAluno(['id' => '301']);
    conferirAluno(str_contains(renderizarEdicaoAluno($dados + ['token' => 'token-teste']), 'outra aba'), 'Deve rejeitar formulário de outra aba.');
    conferirAluno($antes === $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'Outra aba alterou aluno.');
    renderizarEdicaoAluno(['id' => '300']);
    $invalido = array_replace($dados, ['nome' => ['inválido'], 'token' => 'token-teste']);
    conferirAluno(str_contains(renderizarEdicaoAluno($invalido), 'Informe nome com'), 'Deve rejeitar campos não textuais.');
    renderizarEdicaoAluno($dados + ['token' => 'token-teste']);
    $salvo = $conexao->query('SELECT * FROM alunos WHERE id = 300')->fetch(PDO::FETCH_ASSOC);
    conferirAluno($salvo['nome'] === 'Nome atualizado' && $salvo['turma'] === $antes[0]['turma'] && !$salvo['ativo'], 'Edição deve salvar dados e situação sem alterar turma mesmo com envio forjado.');
    conferirAluno($salvo['cpf'] === $antes[0]['cpf'] && $salvo['nasc'] === $antes[0]['nasc'], 'Edição alterou identidade do aluno.');
    $_POST = ['tipo_consulta' => 'cpf', 'cpf' => '12345678900'];
    ob_start();
    include __DIR__ . '/../app/select_w_w.php';
    $consulta = ob_get_clean();
    conferirAluno(str_contains($consulta, 'Nome atualizado'), 'Busca deve encontrar CPF armazenado com pontuação.');
    conferirAluno(consultar_user($conexao, ['email']) === false, 'E-mail não textual deve ser rejeitado.');
    foreach ([str_repeat('a', 73), "senha\0invalida", ['senha']] as $senha) {
        try {
            cadastrar_user($conexao, 'validacao@example.com', $senha);
            throw new RuntimeException('Senha inválida foi aceita.');
        } catch (InvalidArgumentException $e) {
            // a validacao deve ocorrer antes de qualquer insert
        }
    }
    echo 'OK: IDs acima de 255, CSRF, abas diferentes, entradas inválidas, edição, identidade preservada, CPF com pontuação e limites de senha.' . PHP_EOL;
} finally {
    $conexao->rollBack();
    $_SESSION = [];
    session_destroy();
}
