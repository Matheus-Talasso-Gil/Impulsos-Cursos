<?php require_once __DIR__ . '/includes/session.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impulso Cursos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    <main>
        <article>
            <h1>Impulso Cursos</h1>
            <h2>Seu próximo passo começa aqui.</h2>
            <p>A Impulso Cursos é uma empresa fictícia de educação que oferece cursos de informática, inglês e administração. Nossa proposta é ajudar os alunos a desenvolver habilidades para o dia a dia e se preparar para novas oportunidades profissionais.</p>
        </article>
        <?php // O conteúdo interno só é apresentado após a autenticação; os cursos são consultados na página própria. ?>
        <?php if (!isset($_SESSION['id'])): ?>
            <section class="courses" aria-labelledby="acesso-title">
                <h2 id="acesso-title">Comece sua jornada</h2>
                <p>Para visualizar nossos cursos e realizar inscrições, entre em sua conta ou cadastre-se.</p>
                <div class="admin-action-links">
                    <a class="report-link" href="/mini_sistema/login/login.php">Entrar</a>
                    <a class="report-link" href="/mini_sistema/login/cadastrar.php">Cadastre-se</a>
                </div>
            </section>
        <?php else: ?>
            <section class="courses" aria-labelledby="courses-title">
                <h2 id="courses-title">Explore nossos cursos</h2>
                <p>Confira os cursos disponíveis na plataforma.</p>
                <a class="report-link" href="/mini_sistema/app/cursos.php">Ver todos os cursos</a>
            </section>
        <?php endif; ?>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>