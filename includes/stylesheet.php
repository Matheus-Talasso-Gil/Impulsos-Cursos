<?php
// Resolve o CSS pela URL da página, inclusive quando o projeto é servido na raiz.
$diretorioPagina = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
if (in_array(basename($diretorioPagina), ['app', 'login'], true)) {
    $diretorioPagina = str_replace('\\', '/', dirname($diretorioPagina));
}
$urlEstilo = rtrim($diretorioPagina, '/.') . '/css/style.css?v=' . filemtime(__DIR__ . '/../css/style.css');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($urlEstilo, ENT_QUOTES, 'UTF-8') ?>">
