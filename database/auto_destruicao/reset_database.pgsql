-- atencao: destrutivo apaga todos os alunos e usuarios antes de recriar as tabelas
-- use somente para reiniciar um banco de desenvolvimento apos confirmar o banco e fazer backup
BEGIN;
DROP TABLE IF EXISTS alunos;
DROP TABLE IF EXISTS usuarios;
CREATE TABLE alunos (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(14) NOT NULL UNIQUE,
    turma VARCHAR(255) NOT NULL,
    nasc DATE NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    email VARCHAR(255) NOT NULL
);
CREATE TABLE usuarios (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'usuario',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT usuarios_tipo_check CHECK (tipo IN ('usuario', 'admin')),
    CONSTRAINT usuarios_admin_email_check CHECK (tipo <> 'admin' OR lower(email) = 'matheus321@gmail.com')
);
COMMIT;
