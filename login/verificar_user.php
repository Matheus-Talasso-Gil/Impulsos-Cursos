<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id'])) { header('Location: /mini_sistema/login/login.php'); exit(); }