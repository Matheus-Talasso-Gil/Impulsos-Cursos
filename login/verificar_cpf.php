<?php
require_once __DIR__ . '/../includes/functions.php';
function verificar_cpf($cpf)
{
    if (!is_string($cpf)) return false;
    $cpf = preg_replace('/\D/', '', $cpf);
    return validar_cpf($cpf) ? $cpf : false;
}
