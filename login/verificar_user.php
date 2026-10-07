<?php
// Reutiliza a inicialização e a expiração centralizadas antes de verificar a autenticação.
require_once __DIR__ . '/../includes/session.php';
if (!isset($_SESSION['id'])) { header('Location: /mini_sistema/login/login.php'); exit(); }
