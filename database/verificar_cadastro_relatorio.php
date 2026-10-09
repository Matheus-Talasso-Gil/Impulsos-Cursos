<?php
// testa cadastro relatorio e migration sem alterar os registros reais
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
$_SESSION = ['id' => 1, 'tipo' => 'admin'];
$_SERVER['SCRIPT_NAME'] = '/impulsos_cursos/app/select.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
function conferirCadastroRelatorio($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
try {
    // tabelas temporarias ocultam as tabelas reais somente nesta conexao
    $conexao->exec("CREATE TEMP TABLE usuarios (id SERIAL PRIMARY KEY, email VARCHAR(255) UNIQUE, senha VARCHAR(255), tipo VARCHAR(20), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $conexao->exec("CREATE TEMP TABLE alunos (id SERIAL PRIMARY KEY, nome VARCHAR(255) CHECK (nome <> 'Falha teste'), cpf VARCHAR(14), nasc DATE, turma VARCHAR(255), ativo BOOLEAN, email VARCHAR(255), usuario_id INTEGER UNIQUE REFERENCES usuarios(id))");
    $conexao->exec('CREATE TEMP TABLE cursos (id SERIAL PRIMARY KEY, nome VARCHAR(255), descricao TEXT, carga_horaria INTEGER)');
    $conexao->exec('CREATE TEMP TABLE inscricoes (id SERIAL PRIMARY KEY, usuario_id INTEGER REFERENCES usuarios(id), curso_id INTEGER REFERENCES cursos(id), UNIQUE (usuario_id, curso_id))');
    $conexao->exec("CREATE FUNCTION pg_temp.proteger_vinculo_teste() RETURNS TRIGGER AS $$ BEGIN IF OLD.usuario_id IS NOT NULL AND NEW.usuario_id IS DISTINCT FROM OLD.usuario_id THEN RAISE EXCEPTION 'Vinculo imutavel'; END IF; RETURN NEW; END $$ LANGUAGE plpgsql");
    $conexao->exec('CREATE TRIGGER proteger_vinculo_teste BEFORE UPDATE ON alunos FOR EACH ROW EXECUTE FUNCTION pg_temp.proteger_vinculo_teste()');
    $dados = ['nome' => 'Aluno teste', 'cpf' => '52998224725', 'nasc' => '2000-01-02', 'email' => 'novo@example.com', 'senha' => 'senha-teste'];
    cadastrar_aluno_usuario($conexao, $dados);
    $aluno = $conexao->query('SELECT * FROM alunos')->fetch(PDO::FETCH_ASSOC);
    $conta = consultar_user($conexao, $dados['email']);
    conferirCadastroRelatorio((int) $aluno['usuario_id'] === (int) $conta['id'], 'Cadastro não vinculou a conta criada.');
    conferirCadastroRelatorio(password_verify($dados['senha'], $conta['senha']) && $conta['tipo'] === 'usuario', 'Cadastro alterou hash ou papel.');
    conferirCadastroRelatorio(listarAlunos($conexao)[0]['cursos'] === 'Sem curso', 'Aluno sem inscrição não aparece.');
    try {
        cadastrar_aluno_usuario($conexao, array_replace($dados, ['nome' => 'Falha teste', 'email' => 'falha@example.com', 'cpf' => '11144477735']));
        throw new RuntimeException('Falha de aluno deveria interromper cadastro.');
    } catch (PDOException $e) {
        conferirCadastroRelatorio(!consultar_user($conexao, 'falha@example.com'), 'Rollback deixou conta sem aluno.');
    }
    $conexao->exec("INSERT INTO cursos (nome) VALUES ('Desenvolvimento Web'), ('Excel <teste>')");
    $usuarioId = (int) $conta['id'];
    $_SESSION['id'] = $usuarioId;
    $_SESSION['inscricao_token'] = 'token-teste';
    inscreverUsuarioNoCurso($conexao, '1', 'token-teste');
    conferirCadastroRelatorio(listarAlunos($conexao)[0]['cursos'] === 'Desenvolvimento Web', 'Primeiro curso não apareceu.');
    inscreverUsuarioNoCurso($conexao, '2', 'token-teste');
    $lista = listarAlunos($conexao);
    conferirCadastroRelatorio(count($lista) === 1 && $lista[0]['cursos'] === 'Desenvolvimento Web, Excel <teste>', 'Dois cursos duplicaram ou omitiram aluno.');
    conferirCadastroRelatorio(listarAlunos($conexao, '1')[0]['cursos'] === $lista[0]['cursos'], 'Filtro ocultou os demais cursos.');
    conferirCadastroRelatorio(listarAlunos($conexao, '', 'inativo') === [], 'Filtro de situação incorreto.');
    $_GET = ['curso' => '1', 'situacao' => 'ativo'];
    ob_start();
    include __DIR__ . '/../app/select.php';
    $html = ob_get_clean();
    conferirCadastroRelatorio(str_contains($html, 'Excel &lt;teste&gt;') && str_contains($html, 'name="curso"') && !str_contains($html, 'vincular_conta.php') && !str_contains($html, '<th scope="col">Conta</th>'), 'Relatório não escapou nomes ou mantém interface antiga.');
    $cancelar = $conexao->prepare('DELETE FROM inscricoes WHERE usuario_id = :usuario_id AND curso_id = :curso_id');
    $cancelar->execute([':usuario_id' => $usuarioId, ':curso_id' => 1]);
    conferirCadastroRelatorio(listarAlunos($conexao)[0]['cursos'] === 'Excel <teste>', 'Cancelamento de um curso não refletiu.');
    $cancelar->execute([':usuario_id' => $usuarioId, ':curso_id' => 2]);
    conferirCadastroRelatorio(listarAlunos($conexao)[0]['cursos'] === 'Sem curso', 'Cancelamento de todos não refletiu.');
    $conexao->exec("INSERT INTO usuarios (email) VALUES (' Seguro@example.com '), ('duplicado@example.com'), ('DUPLICADO@example.com'), ('alunos@example.com'), ('ocupado@example.com'), ('outro@example.com')");
    $conexao->exec("INSERT INTO alunos (nome, email, usuario_id, ativo) VALUES ('Seguro', 'seguro@example.com', NULL, TRUE), ('Conta ambigua', 'duplicado@example.com', NULL, TRUE), ('Aluno ambiguo um', 'alunos@example.com', NULL, TRUE), ('Aluno ambiguo dois', ' ALUNOS@example.com ', NULL, FALSE), ('Conta ocupada', 'diferente@example.com', (SELECT id FROM usuarios WHERE email = 'ocupado@example.com'), TRUE), ('Nao reutilizar', 'ocupado@example.com', NULL, TRUE), ('Sem correspondencia', 'inexistente@example.com', NULL, TRUE), ('Vazio', '', NULL, TRUE)");
    $antes = $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $migration = file_get_contents(__DIR__ . '/vincular_contas_existentes.sql');
    $conexao->exec($migration);
    $depois = $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    conferirCadastroRelatorio(count($antes) === count($depois), 'Migration excluiu alunos.');
    foreach ($depois as $indice => $registro) {
        if ($registro['nome'] === 'Seguro') {
            conferirCadastroRelatorio($registro['usuario_id'] !== null, 'Migration não normalizou email seguro.');
            unset($registro['usuario_id'], $antes[$indice]['usuario_id']);
        }
        conferirCadastroRelatorio($registro === $antes[$indice], 'Migration alterou caso ambíguo ou vínculo preenchido.');
    }
    $conexao->exec($migration);
    conferirCadastroRelatorio($depois === $conexao->query('SELECT * FROM alunos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC), 'Migration não é idempotente.');
    conferirCadastroRelatorio(count(listarAlunos($conexao)) === count($depois), 'Relatório omitiu registros antigos.');
    $_GET = ['curso' => ['inválido'], 'situacao' => ['inválido']];
    ob_start();
    include __DIR__ . '/../app/select.php';
    $html = ob_get_clean();
    conferirCadastroRelatorio(str_contains($html, 'Sem correspondencia'), 'Filtro inesperado omitiu alunos sem conta.');
    ob_start();
    include __DIR__ . '/../app/alunos_cursos.php';
    $html = ob_get_clean();
    conferirCadastroRelatorio(str_contains($html, 'Sem conta') && !str_contains($html, 'Vincule'), 'Cursos dos alunos mantém etapa manual.');
    conferirCadastroRelatorio(!file_exists(__DIR__ . '/../app/vincular_conta.php'), 'Página antiga ainda existe.');
    echo 'OK: cadastro vinculado, hash, rollback, cursos, cancelamentos, filtros, HTML, registros antigos, migration segura e idempotente, página removida.' . PHP_EOL;
} finally {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    // o encerramento da conexao remove as tabelas temporarias
    $_SESSION = [];
    session_destroy();
}
