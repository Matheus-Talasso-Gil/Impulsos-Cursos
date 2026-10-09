<?php
require_once __DIR__ . '/../includes/session.php';
// o cadastro passou para cadastre se o administrador confirma os vinculos
if (($_SESSION['tipo'] ?? '') === 'admin') {
    $destino = '/impulsos_cursos/app/vincular_conta.php';
} else {
    $destino = '/impulsos_cursos/login/cadastrar.php';
}
header('Location: ' . $destino);
exit();