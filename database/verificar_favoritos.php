<?php
// impede que os testes de banco sejam executados pelo navegador
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session.php';
function conferirFavorito($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
function recusarFavorito($conexao, $cursoId, $token, $remover = false)
{
    try {
        if ($remover) {
            removerFavorito($conexao, $cursoId, $token);
        } else {
            adicionarFavorito($conexao, $cursoId, $token);
        }
    } catch (InvalidArgumentException $e) {
        return;
    }
    throw new RuntimeException('Uma operação inválida foi aceita.');
}
function renderizarPaginaFavoritos($arquivo)
{
    global $conexao;
    $_SERVER['SCRIPT_NAME'] = '/impulsos_cursos/app/' . $arquivo;
    ob_start();
    try {
        include __DIR__ . '/../app/' . $arquivo;
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}
$conexao->beginTransaction();
try {
    // as tabelas temporarias escondem as reais durante o teste e somem no rollback
    $conexao->exec('CREATE TEMP TABLE usuarios (id INTEGER PRIMARY KEY, email VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ON COMMIT DROP');
    $conexao->exec('CREATE TEMP TABLE alunos (id INTEGER, usuario_id INTEGER, nome VARCHAR(255), turma VARCHAR(255), ativo BOOLEAN) ON COMMIT DROP');
    $conexao->exec('CREATE TEMP TABLE cursos (id INTEGER PRIMARY KEY, nome VARCHAR(100), descricao TEXT, carga_horaria INTEGER) ON COMMIT DROP');
    $conexao->exec('CREATE TEMP TABLE inscricoes (id SERIAL PRIMARY KEY, usuario_id INTEGER REFERENCES usuarios(id), curso_id INTEGER REFERENCES cursos(id), UNIQUE(usuario_id, curso_id)) ON COMMIT DROP');
    $conexao->exec("INSERT INTO usuarios (id, email) VALUES (1, 'a@example.com'), (2, 'b@example.com')");
    $conexao->exec("INSERT INTO cursos VALUES (10, 'Zebra', 'Curso teste', 20), (11, 'Alfa', '<script>teste</script>', 30), (12, 'Curso livre', '', 10), (13, 'Curso extra', '', 40)");
    $migration = file_get_contents(__DIR__ . '/adicionar_favoritos.sql');
    $migrationTemporaria = str_replace('CREATE TABLE IF NOT EXISTS favoritos', 'CREATE TEMP TABLE IF NOT EXISTS favoritos', $migration);
    $conexao->exec($migrationTemporaria);
    $conexao->exec($migrationTemporaria);
    $_SESSION = ['id' => 1, 'tipo' => 'usuario', 'favoritos_token' => 'token-teste'];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['id' => '10'];
    $_POST = [];
    conferirFavorito(str_contains(renderizarPaginaFavoritos('curso.php'), '>Favoritar</button>'), 'Curso novo deve mostrar Favoritar.');
    conferirFavorito(str_contains(renderizarPaginaFavoritos('favoritos.php'), 'Você ainda não favoritou'), 'Lista vazia deve apresentar mensagem.');
    recusarFavorito($conexao, 10, 'errado');
    recusarFavorito($conexao, 10, null);
    recusarFavorito($conexao, 999, 'token-teste');
    recusarFavorito($conexao, ['10'], 'token-teste');
    conferirFavorito(adicionarFavorito($conexao, 10, 'token-teste') === 'Curso adicionado aos favoritos.', 'Deve favoritar.');
    $antesReexecucao = $conexao->query('SELECT * FROM favoritos')->fetchAll(PDO::FETCH_ASSOC);
    $conexao->exec($migrationTemporaria);
    conferirFavorito($antesReexecucao === $conexao->query('SELECT * FROM favoritos')->fetchAll(PDO::FETCH_ASSOC), 'Migration repetida deve preservar favoritos.');
    adicionarFavorito($conexao, 10, 'token-teste');
    conferirFavorito((int) $conexao->query('SELECT COUNT(*) FROM favoritos')->fetchColumn() === 1, 'Envio repetido não deve duplicar.');
    conferirFavorito(cursoEstaFavoritado($conexao, 10), 'Favorito deve continuar salvo após consulta.');
    conferirFavorito(str_contains(renderizarPaginaFavoritos('curso.php'), 'Remover dos favoritos'), 'Curso salvo deve mostrar remover.');
    // savepoints permitem testar restricoes sem invalidar a transacao inteira
    $conexao->exec('SAVEPOINT duplicidade');
    try {
        $conexao->exec('INSERT INTO favoritos (usuario_id, curso_id) VALUES (1, 10)');
        throw new RuntimeException('O banco aceitou favorito duplicado.');
    } catch (PDOException $e) {
        conferirFavorito((string) $e->getCode() === '23505', 'Duplicidade deve ser recusada pelo banco.');
    } finally {
        $conexao->exec('ROLLBACK TO SAVEPOINT duplicidade');
    }
    adicionarFavorito($conexao, 11, 'token-teste');
    conferirFavorito(array_column(buscarFavoritosUsuario($conexao), 'nome') === ['Alfa', 'Zebra'], 'Favoritos devem ser ordenados por nome.');
    $_SESSION['id'] = 2;
    $_GET['usuario_id'] = 1;
    $_POST['usuario_id'] = 1;
    conferirFavorito(buscarFavoritosUsuario($conexao) === [], 'Outra conta não deve ver favoritos.');
    removerFavorito($conexao, 10, 'token-teste');
    adicionarFavorito($conexao, 11, 'token-teste');
    conferirFavorito(array_column(buscarFavoritosUsuario($conexao), 'nome') === ['Alfa'], 'ID enviado pelo navegador não deve escolher a conta.');
    $_SESSION['id'] = 1;
    conferirFavorito(cursoEstaFavoritado($conexao, 10), 'Outra conta não pode remover o favorito.');
    recusarFavorito($conexao, 10, 'errado', true);
    recusarFavorito($conexao, 999, 'token-teste', true);
    conferirFavorito(removerFavorito($conexao, 10, 'token-teste') === 'Curso removido dos favoritos.', 'Deve remover favorito próprio.');
    conferirFavorito(!cursoEstaFavoritado($conexao, 10), 'Favorito removido deve desaparecer.');
    adicionarFavorito($conexao, 10, 'token-teste');
    adicionarFavorito($conexao, 12, 'token-teste');
    adicionarFavorito($conexao, 13, 'token-teste');
    $pagina = renderizarPaginaFavoritos('favoritos.php');
    conferirFavorito(str_contains($pagina, '&lt;script&gt;teste&lt;/script&gt;') && !str_contains($pagina, '<script>'), 'Descrição deve ter escape HTML.');
    $dashboard = renderizarPaginaFavoritos('dashboard.php');
    conferirFavorito(str_contains($dashboard, '4 cursos</p>'), 'Dashboard deve mostrar o total correto.');
    preg_match('/<section class="dashboard-card" aria-labelledby="favoritos-title">(.*?)<\/section>/s', $dashboard, $card);
    conferirFavorito(substr_count($card[1] ?? '', '<li>') === 3, 'Dashboard deve limitar a prévia a três favoritos.');
    conferirFavorito(str_contains($dashboard, '/impulsos_cursos/app/favoritos.php'), 'Menu comum deve conter favoritos.');
    $_SESSION['tipo'] = 'admin';
    ob_start();
    include __DIR__ . '/../includes/header.php';
    $menuAdmin = ob_get_clean();
    conferirFavorito(!str_contains($menuAdmin, '/app/favoritos.php'), 'Menu admin deve ser preservado.');
    $_SESSION['tipo'] = 'usuario';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['acao' => 'desfavoritar', 'curso_id' => 10, 'token' => 'errado', 'usuario_id' => 2];
    conferirFavorito(str_contains(renderizarPaginaFavoritos('favoritos.php'), 'Solicitação inválida'), 'Página deve rejeitar CSRF inválido.');
    conferirFavorito(cursoEstaFavoritado($conexao, 10), 'CSRF inválido não deve remover favorito.');
    $conexao->exec('DELETE FROM cursos WHERE id = 12');
    conferirFavorito(!cursoEstaFavoritado($conexao, 12), 'Excluir curso deve limpar seus favoritos.');
    $conexao->exec('INSERT INTO inscricoes (usuario_id, curso_id) VALUES (1, 10)');
    $conexao->exec('SAVEPOINT inscricao');
    try {
        $conexao->exec('DELETE FROM cursos WHERE id = 10');
        throw new RuntimeException('Curso com inscrição foi excluído.');
    } catch (PDOException $e) {
        conferirFavorito((string) $e->getCode() === '23503', 'Inscrição deve continuar bloqueando exclusão.');
    } finally {
        $conexao->exec('ROLLBACK TO SAVEPOINT inscricao');
    }
    conferirFavorito(cursoEstaFavoritado($conexao, 10), 'Exclusão bloqueada deve preservar favorito.');
    $conexao->exec('DELETE FROM usuarios WHERE id = 2');
    conferirFavorito((int) $conexao->query('SELECT COUNT(*) FROM favoritos WHERE usuario_id = 2')->fetchColumn() === 0, 'Excluir conta deve limpar seus favoritos.');
    unset($_SESSION['id']);
    recusarFavorito($conexao, 10, 'token-teste');
    echo 'OK: migration, duplicidade, CSRF, isolamento, ordenação, favoritos, páginas, dashboard, menus e exclusões.' . PHP_EOL;
} finally {
    $conexao->rollBack();
    $_SESSION = [];
    session_destroy();
}
