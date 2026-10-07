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
        <section class="courses" aria-labelledby="courses-title">
            <h2 id="courses-title">Conheça nossos cursos</h2>
            <div class="course-grid">
                <article class="course-card">
                    <h3>Informática Básica</h3>
                    <p>Aprenda a usar o computador, navegar na internet e criar documentos e planilhas.</p>
                    <p class="course-duration">Duração: 3 meses</p>
                </article>
                <article class="course-card">
                    <h3>Inglês</h3>
                    <p>Desenvolva vocabulário e pratique conversas para situações do dia a dia.</p>
                    <p class="course-duration">Duração: 12 meses</p>
                </article>
                <article class="course-card">
                    <h3>Administração</h3>
                    <p>Conheça as bases de organização, atendimento e planejamento de uma empresa.</p>
                    <p class="course-duration">Duração: 6 meses</p>
                </article>
            </div>
        </section>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>