<?php
// resolve o css pela url da pagina inclusive quando o projeto e servido na raiz
$diretorioPagina = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
if (in_array(basename($diretorioPagina), ['app', 'login'], true)) {
    $diretorioPagina = str_replace('\\', '/', dirname($diretorioPagina));
}
// usa a data de alteracao do arquivo para atualizar o estilo em cache
$urlEstilo = rtrim($diretorioPagina, '/.') . '/css/style.css?v=' . filemtime(__DIR__ . '/../css/style.css');
?>
<link rel="stylesheet" href="<?= htmlspecialchars($urlEstilo, ENT_QUOTES, 'UTF-8') ?>">
