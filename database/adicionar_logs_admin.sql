CREATE TABLE IF NOT EXISTS logs_admin (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER NOT NULL REFERENCES usuarios(id),
    acao VARCHAR(20) NOT NULL,
    entidade VARCHAR(20) NOT NULL,
    entidade_id INTEGER,
    descricao TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
