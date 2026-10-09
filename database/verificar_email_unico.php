<?php
// verifica a migration de emails somente em uma tabela temporaria
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit();
}
require_once __DIR__ . '/connect_postgres.php';
$cenario = $argv[1] ?? 'aplicar';
if (!in_array($cenario, ['aplicar', 'duplicados'], true)) {
    throw new InvalidArgumentException('Cenário desconhecido.');
}
$conexao->exec('CREATE TEMP TABLE usuarios (id SERIAL PRIMARY KEY, email VARCHAR(255))');
$conexao->exec("INSERT INTO usuarios (email) VALUES ('teste@example.com')");
if ($cenario === 'duplicados') {
    $conexao->exec("INSERT INTO usuarios (email) VALUES ('teste@example.com')");
}
$antes = $conexao->query('SELECT * FROM usuarios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$migration = file_get_contents(__DIR__ . '/garantir_email_unico_usuarios.sql');
try {
    $conexao->exec($migration);
    if ($cenario === 'duplicados') {
        throw new RuntimeException('Migration aceitou emails duplicados.');
    }
    $conexao->exec($migration);
    try {
        $conexao->exec("INSERT INTO usuarios (email) VALUES ('teste@example.com')");
        throw new RuntimeException('Restrição aceitou email duplicado.');
    } catch (PDOException $e) {
        if ((string) $e->getCode() !== '23505') {
            throw $e;
        }
    }
} catch (PDOException $e) {
    if ($conexao->inTransaction()) {
        $conexao->rollBack();
    }
    if ($cenario !== 'duplicados' || (string) $e->getCode() !== 'P0001') {
        throw $e;
    }
}
if ($antes !== $conexao->query('SELECT * FROM usuarios ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)) {
    throw new RuntimeException('Migration alterou os registros existentes.');
}
echo 'OK: email unico ' . $cenario . ' sem alterar registros existentes' . PHP_EOL;
