<?php
// reutiliza a inicializacao e a expiracao centralizadas antes de verificar a autenticacao
require_once __DIR__ . '/../includes/session.php';
// redireciona visitantes antes de carregar os dados da pagina protegida
if (!isset($_SESSION['id'])) {
    header('Location: /impulsos_cursos/login/login.php');
    exit();
}
