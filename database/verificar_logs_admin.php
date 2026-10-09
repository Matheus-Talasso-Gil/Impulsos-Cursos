<?php
// verifica o historico usando somente tabelas temporarias
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
$cenario = $argv[1] ?? 'funcao';
$permitidos = ['funcao', 'criar', 'criar_sem_log', 'editar', 'excluir', 'aluno_editar', 'aluno_excluir', 'csrf', 'bloqueado', 'usuario', 'visitante', 'vazio', 'lista', 'falha'];
if (!in_array($cenario, $permitidos, true)) {
    throw new InvalidArgumentException('Cenário de teste desconhecido.');
}
function conferirHistorico($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
$conexao->exec('CREATE TEMP TABLE usuarios (id INTEGER PRIMARY KEY, email VARCHAR(255), senha VARCHAR(255), tipo VARCHAR(20))');
$conexao->exec("INSERT INTO usuarios VALUES (1, 'admin@example.com', 'hash-secreto', 'admin'), (2, 'usuario@example.com', 'hash-comum', 'usuario')");
$conexao->exec('CREATE TEMP TABLE cursos (id SERIAL PRIMARY KEY, nome VARCHAR(100), descricao TEXT, carga_horaria INTEGER)');
$conexao->exec("INSERT INTO cursos (nome, descricao, carga_horaria) VALUES ('Curso <teste>', 'descricao privada', 20)");
$conexao->exec('CREATE TEMP TABLE alunos (id INTEGER PRIMARY KEY, nome VARCHAR(255), turma VARCHAR(255), nasc DATE, ativo BOOLEAN, email VARCHAR(255), cpf VARCHAR(14), usuario_id INTEGER)');
$conexao->exec("INSERT INTO alunos VALUES (27, 'Aluno teste', 'Sem turma', '2000-01-01', TRUE, 'aluno@example.com', '52998224725', NULL)");
$conexao->exec('CREATE TEMP TABLE inscricoes (id SERIAL PRIMARY KEY, usuario_id INTEGER REFERENCES usuarios(id), curso_id INTEGER REFERENCES cursos(id))');
$migration = str_replace('CREATE TABLE IF NOT EXISTS logs_admin', 'CREATE TEMP TABLE IF NOT EXISTS logs_admin', file_get_contents(__DIR__ . '/adicionar_logs_admin.sql'));
$conexao->exec($migration);
$conexao->exec($migration);
$_SESSION = ['id' => 1, 'tipo' => 'admin', 'curso_admin_token' => 'token-teste', 'edicao_token' => 'token-teste', 'exclusao_token' => 'token-teste'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['SCRIPT_NAME'] = '/impulsos_cursos/app/logs_admin.php';
$_GET = ['id' => '1'];
$_POST = ['nome' => 'Curso atualizado', 'descricao' => 'descricao privada', 'carga_horaria' => '30', 'token' => 'token-teste', 'admin_id' => '2'];
$paginaTerminou = false;
ob_start();
// confere tambem paginas que encerram a requisicao depois de um redirecionamento
function finalizarTesteHistorico()
{
    global $conexao, $cenario, $paginaTerminou, $logs, $erro;
    $html = ob_get_clean();
    try {
        if (in_array($cenario, ['criar', 'editar', 'excluir', 'aluno_editar', 'aluno_excluir'], true)) {
            $registros = $conexao->query('SELECT * FROM logs_admin')->fetchAll(PDO::FETCH_ASSOC);
            conferirHistorico(count($registros) === 1 && (int) $registros[0]['admin_id'] === 1, 'Ação não registrou exatamente um log com admin da sessão.');
            $registro = $registros[0];
            $acao = ['criar' => 'criou', 'editar' => 'editou', 'excluir' => 'excluiu', 'aluno_editar' => 'editou', 'aluno_excluir' => 'excluiu'];
            conferirHistorico($registro['acao'] === $acao[$cenario], 'Ação registrada incorreta.');
            conferirHistorico(!str_contains($registro['descricao'], '52998224725') && !str_contains($registro['descricao'], 'hash-secreto') && !str_contains($registro['descricao'], 'token-teste') && !str_contains($registro['descricao'], 'descricao privada'), 'Log contém dados desnecessários.');
            if ($cenario === 'criar') {
                conferirHistorico((int) $registro['entidade_id'] === 2 && (int) $conexao->query('SELECT COUNT(*) FROM cursos')->fetchColumn() === 2, 'Criação não preservou ID ou curso.');
            } elseif ($cenario === 'editar') {
                conferirHistorico($conexao->query('SELECT nome FROM cursos WHERE id = 1')->fetchColumn() === 'Curso atualizado', 'Curso não foi editado.');
            } elseif ($cenario === 'excluir') {
                conferirHistorico((int) $conexao->query('SELECT COUNT(*) FROM cursos')->fetchColumn() === 0 && str_contains($registro['descricao'], 'Curso <teste>'), 'Exclusão perdeu nome ou manteve curso.');
            } elseif ($cenario === 'aluno_editar') {
                conferirHistorico($conexao->query('SELECT nome FROM alunos WHERE id = 27')->fetchColumn() === 'Aluno atualizado', 'Aluno não foi editado.');
            } else {
                conferirHistorico((int) $conexao->query('SELECT COUNT(*) FROM alunos')->fetchColumn() === 0, 'Aluno não foi excluído.');
            }
        } elseif ($cenario === 'criar_sem_log') {
            conferirHistorico((int) $conexao->query('SELECT COUNT(*) FROM cursos')->fetchColumn() === 2 && (int) $conexao->query('SELECT COUNT(*) FROM logs_admin')->fetchColumn() === 0, 'Falha do log interrompeu criação de curso.');
        } elseif (in_array($cenario, ['csrf', 'bloqueado'], true)) {
            conferirHistorico((int) $conexao->query('SELECT COUNT(*) FROM logs_admin')->fetchColumn() === 0 && (int) $conexao->query('SELECT COUNT(*) FROM cursos')->fetchColumn() === 1, 'Operação rejeitada alterou curso ou registrou sucesso.');
        } elseif ($cenario === 'usuario') {
            conferirHistorico(http_response_code() === 403 && !$paginaTerminou, 'Conta comum acessou o histórico.');
        } elseif ($cenario === 'visitante') {
            conferirHistorico(!$paginaTerminou && $html === '', 'Visitante recebeu conteúdo protegido.');
        } elseif ($cenario === 'vazio') {
            conferirHistorico($logs === [] && $erro === '' && str_contains($html, 'ainda') && !str_contains($html, '<table>'), 'Histórico vazio não tem mensagem.');
        } elseif ($cenario === 'lista') {
            conferirHistorico(count($logs) === 100 && $logs[0]['descricao'] === 'Registro recente' && $logs[1]['descricao'] === 'Registro 105' && $logs[99]['descricao'] === 'Registro 7', 'Limite ou ordenação incorretos.');
            conferirHistorico(str_contains($html, '&lt;admin&gt;@example.com'), 'E-mail não foi escapado.');
        } elseif ($cenario === 'falha') {
            conferirHistorico($erro !== '' && !str_contains($html, 'SQLSTATE') && !str_contains($html, 'Stack trace'), 'Erro interno foi exposto.');
        }
        echo 'OK: historico ' . $cenario . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
register_shutdown_function('finalizarTesteHistorico');
if ($cenario === 'funcao') {
    $_SESSION['tipo'] = 'usuario';
    conferirHistorico(!registrarLogAdmin($conexao, 'editou', 'aluno', 27, 'Teste'), 'Usuário comum gerou log.');
    $_SESSION = [];
    conferirHistorico(!registrarLogAdmin($conexao, 'editou', 'aluno', 27, 'Teste'), 'Visitante gerou log.');
    $_SESSION = ['id' => 1, 'tipo' => 'admin'];
    conferirHistorico(registrarLogAdmin($conexao, 'editou', 'aluno', 27, 'Teste'), 'Admin não gerou log.');
    $conexao->exec('ALTER TABLE logs_admin RENAME COLUMN acao TO acao_indisponivel');
    $conexao->beginTransaction();
    $conexao->exec("UPDATE cursos SET nome = 'Acao preservada' WHERE id = 1");
    conferirHistorico(!registrarLogAdmin($conexao, 'editou', 'curso', 1, 'Teste'), 'Falha de log não foi tratada.');
    conferirHistorico($conexao->query('SELECT nome FROM cursos WHERE id = 1')->fetchColumn() === 'Acao preservada', 'Falha do log abortou a transação principal.');
    $conexao->commit();
} elseif (in_array($cenario, ['criar', 'criar_sem_log', 'csrf'], true)) {
    if ($cenario === 'csrf') {
        $_POST['token'] = 'invalido';
    } elseif ($cenario === 'criar_sem_log') {
        $conexao->exec('ALTER TABLE logs_admin RENAME COLUMN acao TO acao_indisponivel');
    }
    include __DIR__ . '/../app/curso_create.php';
} elseif ($cenario === 'editar') {
    include __DIR__ . '/../app/curso_update.php';
} elseif ($cenario === 'excluir' || $cenario === 'bloqueado') {
    if ($cenario === 'bloqueado') {
        $conexao->exec('INSERT INTO inscricoes (usuario_id, curso_id) VALUES (2, 1)');
    }
    include __DIR__ . '/../app/curso_delete.php';
} elseif ($cenario === 'aluno_editar') {
    $_SESSION['aluno_edicao_id'] = 27;
    $_SESSION['aluno_edicao_cpf'] = '52998224725';
    $_SESSION['aluno_edicao_nasc'] = '2000-01-01';
    $_POST = ['aluno_id' => '27', 'nome' => 'Aluno atualizado', 'turma' => 'Sem turma', 'email' => 'aluno@example.com', 'ativo' => 'true', 'token' => 'token-teste'];
    include __DIR__ . '/../app/update.php';
} elseif ($cenario === 'aluno_excluir') {
    $_SESSION['exclusao_pendente'] = ['id' => '27', 'usuario_id' => null, 'cursos' => []];
    $_POST = ['id' => '27', 'confirmar' => '1', 'token' => 'token-teste'];
    include __DIR__ . '/../app/delete.php';
} else {
    if ($cenario === 'usuario') {
        $_SESSION = ['id' => 2, 'tipo' => 'usuario'];
    } elseif ($cenario === 'visitante') {
        $_SESSION = [];
    } elseif ($cenario === 'falha') {
        $conexao->exec('ALTER TABLE logs_admin RENAME COLUMN acao TO acao_indisponivel');
    } elseif ($cenario === 'lista') {
        $conexao->exec("UPDATE usuarios SET email = '<admin>@example.com' WHERE id = 1");
        $conexao->exec("INSERT INTO logs_admin (admin_id, acao, entidade, entidade_id, descricao, created_at) SELECT 1, 'editou', 'aluno', numero, 'Registro ' || numero, TIMESTAMP '2026-10-09 12:00:00' FROM generate_series(1, 105) numero");
        $conexao->exec("INSERT INTO logs_admin (admin_id, acao, entidade, descricao, created_at) VALUES (1, 'editou', 'aluno', 'Registro antigo', '2025-01-01'), (1, 'editou', 'aluno', 'Registro recente', '2027-01-01')");
    }
    $_SERVER['REQUEST_METHOD'] = 'GET';
    include __DIR__ . '/../app/logs_admin.php';
}
$paginaTerminou = true;
