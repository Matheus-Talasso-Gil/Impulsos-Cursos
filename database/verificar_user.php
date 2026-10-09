<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
} // executa somente no terminal para bloquear acesso pelo navegador
require_once __DIR__ . '/../includes/functions.php';
function conferir($condicao, $mensagem)
{
    if (!$condicao) {
        throw new RuntimeException($mensagem);
    }
}
$conexao->beginTransaction();
try {
    $conexao->exec("CREATE TEMP TABLE usuarios (id SERIAL PRIMARY KEY, email VARCHAR(255), senha VARCHAR(255), tipo VARCHAR(20) NOT NULL DEFAULT 'usuario' CHECK (tipo IN ('usuario', 'admin'))) ON COMMIT DROP"); // isola os testes sem alterar a tabela real
    $inserir = $conexao->prepare('INSERT INTO usuarios (email, senha) VALUES (?, ?)');
    $inserir->execute(['duplicado@gmail.com', 'antiga']);
    $inserir->execute(['duplicado@gmail.com', password_hash('nova', PASSWORD_DEFAULT)]);
    $usuario = consultar_user($conexao, 'duplicado@gmail.com');
    conferir(password_verify('nova', $usuario['senha']), 'Deve usar o cadastro mais recente.');
    conferir(!password_verify('errada', $usuario['senha']), 'Deve rejeitar senha incorreta.');
    cadastrar_user($conexao, ' novo@gmail.com ', ' teste com espaços ');
    $usuario = consultar_user($conexao, ' novo@gmail.com ');
    conferir(password_verify(' teste com espaços ', $usuario['senha']), 'Cadastro deve permitir login e preservar a senha.');
    conferir($usuario['tipo'] === 'usuario', 'Cadastro público deve criar uma conta comum.');
    $_POST['tipo'] = 'admin'; // simula uma tentativa de enviar nivel admin pelo formulario para testar o bloqueio
    cadastrar_user($conexao, 'forcado@gmail.com', 'senha');
    conferir(consultar_user($conexao, 'forcado@gmail.com')['tipo'] === 'usuario', 'O formulário não pode criar uma conta admin.');
    unset($_POST['tipo']);
    conferir(!consultar_user($conexao, 'ausente@gmail.com'), 'Usuário ausente deve ser rejeitado.');
    try {
        cadastrar_user($conexao, 'novo@gmail.com', 'outra');
        throw new RuntimeException('Não pode cadastrar e-mail duplicado.');
    } catch (InvalidArgumentException $e) {
        conferir(password_verify(' teste com espaços ', consultar_user($conexao, 'novo@gmail.com')['senha']), 'Duplicata não pode alterar a senha.');
    }
    echo "OK: cadastro, login, duplicatas, senha incorreta e usuário ausente.\n";
} finally {
    $conexao->rollBack(); // desfaz a transacao e remove a tabela temporaria ao final
}
