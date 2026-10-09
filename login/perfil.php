<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/verificar_user.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/data_conta.php';
$dataCriacaoConta = null;
try {
    $stmt = $conexao->prepare('SELECT created_at FROM usuarios WHERE id = :id');
    $stmt->execute([':id' => (int) $_SESSION['id']]);
    $dataCriacaoConta = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
$aluno = false;
$erroPerfil = '';
// busca somente o aluno vinculado a conta autenticada
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
// evita consultar cursos que nao aparecem no perfil administrativo
if (!$ehAdmin) {
    try {
        // mantem os tres primeiros cursos como previa o link do perfil leva a lista completa
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
    <?php require __DIR__ . '/../includes/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <main class="profile-page">
        <div class="profile-heading">
            <p class="profile-eyebrow">MINHA CONTA</p>
            <h1>Meu perfil</h1>
            <p>Seus dados e sua jornada na Impulso Cursos, em um só lugar.</p>
        </div>
        <section class="profile-account" aria-labelledby="profile-account-title">
            <div class="profile-avatar" aria-hidden="true">
                <svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="17" r="8" stroke="currentColor" stroke-width="2.5"/><path d="M9 40c0-9 6-14 15-14s15 5 15 14" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
            </div>
            <div class="profile-identity">
                <span class="profile-account-type"><?= htmlspecialchars($tipoConta, ENT_QUOTES, 'UTF-8') ?></span>
                <h2 id="profile-account-title"><?= htmlspecialchars((string) ($aluno['nome'] ?? 'Sua conta'), ENT_QUOTES, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars((string) ($_SESSION['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                <p>Conta criada em: <?= htmlspecialchars(formatarDataCriacaoConta($dataCriacaoConta), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>
        <div class="profile-layout<?= $ehAdmin ? ' profile-layout-admin' : '' ?>">
        <section class="profile-panel" aria-labelledby="profile-details-title">
            <div class="profile-section-heading">
                <h2 id="profile-details-title">Dados pessoais</h2>
                <p>Informações do seu cadastro de aluno.</p>
            </div>
        <?php if ($erroPerfil !== ''): ?>
            <p class="message-error" role="alert"><?= htmlspecialchars($erroPerfil, ENT_QUOTES, 'UTF-8') ?></p>
        <?php elseif ($aluno): ?>
            <div class="profile-registration-status"><span class="student-status <?= $aluno['ativo'] ? 'is-active' : 'is-inactive' ?>">Cadastro <?= $aluno['ativo'] ? 'ativo' : 'inativo' ?></span></div>
            <?php ?>
            <dl class="profile-details">
            <?php foreach (['id' => 'Matrícula', 'nome' => 'Nome completo', 'nasc' => 'Data de nascimento', 'turma' => 'Turma', 'email' => 'E-mail do aluno'] as $campo => $rotulo): ?>
                <div><dt><?= $rotulo ?></dt><dd><?= htmlspecialchars((string) ($aluno[$campo] ?? ''), ENT_QUOTES, 'UTF-8') ?: 'Não informado' ?></dd></div>
            <?php endforeach; ?>
            </dl>
        <?php elseif (!$ehAdmin): ?>
            <div class="profile-empty"><h3>Cadastro de aluno não encontrado</h3><p>Não há cadastro de aluno associado à sua conta. Entre em contato com a administração para conferir os dados.</p></div>
        <?php else: ?>
            <div class="profile-empty"><p>Você está acessando com uma conta administrativa.</p></div>
        <?php endif; ?>
        </section>
        <?php if (!$ehAdmin): ?>
        <section class="profile-panel profile-courses" aria-labelledby="seus-cursos">
            <div class="profile-section-heading"><h2 id="seus-cursos">Seus cursos</h2><p>Continue aprendendo e dando o próximo passo.</p></div>
            <?php if ($erroCursos !== ''): ?>
                <p class="message-error" role="alert"><?= htmlspecialchars($erroCursos, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif (!$cursos): ?>
                <div class="profile-empty"><h3>Sua jornada começa aqui</h3><p>Você ainda não está inscrito em nenhum curso.</p></div>
            <?php else: ?>
                <ul class="profile-course-list">
                    <?php foreach ($cursos as $curso): ?>
                        <li><a href="/impulsos_cursos/app/curso.php?id=<?= (int) $curso['id'] ?>"><span class="profile-course-name"><?= htmlspecialchars((string) $curso['nome'], ENT_QUOTES, 'UTF-8') ?></span><span class="profile-course-duration"><?= (int) $curso['carga_horaria'] ?> horas de aprendizado</span><span class="profile-course-arrow" aria-hidden="true">&rarr;</span></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <a class="report-link" href="/impulsos_cursos/app/meus_cursos.php">Ver todos os meus cursos</a>
        </section>
        <?php endif; ?>
        </div>
    </main>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
