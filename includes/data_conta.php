<?php
// timestamp nao contem fuso exibe o horario do banco sem converte lo
function formatarDataCriacaoConta($valor)
{
    if (!is_string($valor) || !preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}(?:\.\d{1,6})?$/D', $valor)) {
        return 'Não informada';
    }
    $formato = strpos($valor, '.') === false ? '!Y-m-d H:i:s' : '!Y-m-d H:i:s.u';
    $data = DateTimeImmutable::createFromFormat($formato, $valor);
    $erros = DateTimeImmutable::getLastErrors();
    // rejeita datas que o php corrigiu automaticamente por serem invalidas
    if (!$data || ($erros !== false && ($erros['warning_count'] || $erros['error_count']))) {
        return 'Não informada';
    }
    return $data->format('d/m/Y \à\s H:i');
}
