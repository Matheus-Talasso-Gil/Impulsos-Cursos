<?php
require_once __DIR__ . '/../includes/session.php';
// O cadastro passou para Cadastre-se; o administrador confirma os vínculos.
$destino = ($_SESSION['tipo'] ?? '') === 'admin'
    ? '/impulsos_cursos/app/vincular_conta.php'
    : '/impulsos_cursos/login/cadastrar.php';
header('Location: ' . $destino);
exit();