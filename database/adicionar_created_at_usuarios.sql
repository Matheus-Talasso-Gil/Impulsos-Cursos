-- Não recria a tabela nem altera IDs, senhas ou tipos.
-- Contas antigas recebem o horário desta execução, não sua data real de criação.
ALTER TABLE usuarios
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;
