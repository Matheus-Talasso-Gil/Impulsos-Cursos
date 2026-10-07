<?php
require_once __DIR__ . '/verificar_user.php';
if (($_SESSION['tipo'] ?? '') !== 'admin') { http_response_code(403); exit('Acesso negado'); } // bloqueia contas sem nivel admin com resposta de acesso negado
