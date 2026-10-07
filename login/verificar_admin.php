<?php
require_once __DIR__ . '/verificar_user.php';
if (($_SESSION['tipo'] ?? '') !== 'admin') {
    http_response_code(403);
    require __DIR__ . '/../includes/acesso_negado.php';
    exit();
}
