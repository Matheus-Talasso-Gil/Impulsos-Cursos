<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Visitantes não têm controle de inatividade; o horário é mantido apenas no servidor.
if (isset($_SESSION['id'])) {
    $agora = time();
    // A expiração é verificada na próxima requisição, antes de gerar qualquer HTML.
    if (isset($_SESSION['ultima_atividade']) && $agora - $_SESSION['ultima_atividade'] > 1800) {
        $_SESSION = [];
        session_destroy();
        header('Location: /impulsos_cursos/login/login.php?expirou=1');
        exit();
    }

    // Também inicializa o controle para sessões que já existiam antes desta alteração.
    $_SESSION['ultima_atividade'] = $agora;
}
