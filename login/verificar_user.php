<?php
// Reutiliza a inicialização e a expiração centralizadas antes de verificar a autenticação.
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['id'])) { header('Location: /impulsos_cursos/login/login.php'); exit(); }
