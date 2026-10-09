<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// visitantes nao tem controle de inatividade o horario e mantido apenas no servidor
if (isset($_SESSION['id'])) {
    $agora = time();
    // a expiracao e verificada na proxima requisicao antes de gerar qualquer html
    // encerra o acesso quando a inatividade ultrapassa trinta minutos
    if (isset($_SESSION['ultima_atividade']) && $agora - $_SESSION['ultima_atividade'] > 1800) {
        $_SESSION = [];
        session_destroy();
        header('Location: /impulsos_cursos/login/login.php?expirou=1');
        exit();
    }

    // renova a atividade e inclui sessoes que ainda nao tinham esse controle
    $_SESSION['ultima_atividade'] = $agora;
}
