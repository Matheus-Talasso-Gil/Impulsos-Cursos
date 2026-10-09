<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// remove os dados de autenticacao antes de encerrar a sessao
$_SESSION = array();
session_destroy();
header('Location: /impulsos_cursos/index.php');
exit();