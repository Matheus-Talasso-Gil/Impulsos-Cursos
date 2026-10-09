<?php
require_once __DIR__ . '/../includes/session.php';
// o cadastro publico cria conta e aluno juntos
if (($_SESSION['tipo'] ?? '') === 'admin') {
    $destino = '/impulsos_cursos/app/select.php';
} else {
    $destino = '/impulsos_cursos/login/cadastrar.php';
}
header('Location: ' . $destino);
exit();