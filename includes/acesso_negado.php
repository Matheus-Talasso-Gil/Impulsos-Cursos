<?php
require_once __DIR__ . '/session.php';
http_response_code(403);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso restrito | Impulso Cursos</title>
    <?php require __DIR__ . '/stylesheet.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>
    <main class="access-denied" aria-labelledby="access-denied-title">
        <section class="access-denied-card">
            <div class="access-denied-icon" aria-hidden="true">
                <svg viewBox="0 0 64 64" width="64" height="64" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                    <path d="M20 28v-9a12 12 0 0 1 24 0v9" />
                    <rect x="12" y="28" width="40" height="29" rx="8" />
                    <circle cx="32" cy="41" r="3" />
                    <path d="M32 44v5" />
                </svg>
            </div>
            <p class="access-denied-label">403 · ÁREA ADMINISTRATIVA</p>
            <h1 id="access-denied-title">Este espaço é reservado<br>à administração.</h1>
            <p class="access-denied-description">Sua conta não tem permissão para acessar esta página. Você pode continuar aprendendo e explorar os cursos disponíveis.</p>
            <div class="access-denied-actions">
                <a class="access-denied-primary" href="/impulsos_cursos/index.php">Voltar ao início <span aria-hidden="true">→</span></a>
                <a class="access-denied-secondary" href="/impulsos_cursos/app/meus_cursos.php">Meus cursos</a>
            </div>
            <p class="access-denied-help">Precisa de acesso administrativo? Entre em contato com a administração do Impulso Cursos.</p>
        </section>
    </main>
    <?php include __DIR__ . '/footer.php'; ?>
</body>
</html>
