<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
$aluno = false;
$erroPerfil = '';
// Busca o cadastro pelo ID da conta autenticada, sem aceitar o ID de outro usuário pelo formulário ou pela URL.
try {
    $stmt = $conexao->prepare('SELECT id, nome, nasc, turma, email, ativo FROM alunos WHERE usuario_id = :usuario_id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $erroPerfil = 'Não foi possível consultar seu cadastro de aluno. Entre em contato com o administrador.';
}
$ehAdmin = ($_SESSION['tipo'] ?? 'usuario') === 'admin';
$tipoConta = $ehAdmin ? 'Administrador' : 'Usuário';
$cursos = [];
$erroCursos = '';
// O perfil administrativo não exibe cursos, então evita também essa consulta para admins.
if (!$ehAdmin) {
    try {
        // Mantém os três primeiros cursos como prévia; o link do perfil leva à lista completa.
        $cursos = array_slice(buscarCursosDoUsuario($conexao), 0, 3);
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $erroCursos = 'Não foi possível carregar seus cursos. Tente novamente.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu perfil | Impulso Cursos</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="auth-page">
        <h1>Meu perfil</h1>
        <p>E-mail: <?= htmlspecialchars((string) ($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        <p>Tipo de conta: <?= htmlspecialchars($tipoConta, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if ($erroPerfil !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erroPerfil, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($aluno): ?>
            <h2>Cadastro de aluno</h2>
            <?php // As chaves são colunas do banco; os valores são os rótulos exibidos ao usuário. ?>
            <?php foreach (['id' => 'ID', 'nome' => 'Nome', 'nasc' => 'Data de nascimento', 'turma' => 'Turma', 'email' => 'E-mail do aluno'] as $campo => $rotulo): ?>
                <p><?= $rotulo ?>: <?= htmlspecialchars((string) $aluno[$campo], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <p>Situação: <?= $aluno['ativo'] ? 'Ativo' : 'Inativo' ?></p>
        <?php elseif (!$ehAdmin): ?>
            <p>Sua conta ainda não está vinculada a um cadastro de aluno.</p>
        <?php endif; ?>
        <?php if (!$ehAdmin): ?>
        <section class="profile-courses" aria-labelledby="seus-cursos">
            <h2 id="seus-cursos">Seus cursos</h2>
            <?php if ($erroCursos !== ''): ?>
                <p class="message-error" role="alert"><?= htmlspecialchars($erroCursos, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (!$cursos): ?>
                <p>Você ainda não está inscrito em nenhum curso.</p>
            <?php else: ?>
                <ul class="profile-course-list">
                    <?php foreach ($cursos as $curso): ?>
                        <li><a href="/impulsos_cursos/app/curso.php?id=<?= (int) $curso['id'] ?>"><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></a><span><?= (int) $curso['carga_horaria'] ?> horas</span></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="report-link" href="/impulsos_cursos/app/meus_cursos.php">Ver todos os meus cursos</a>
        </section>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
