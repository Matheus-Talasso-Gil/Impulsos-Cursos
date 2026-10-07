BEGIN;

ALTER TABLE alunos ADD COLUMN IF NOT EXISTS usuario_id INTEGER;

DO $$
DECLARE
    coluna SMALLINT;
    destino SMALLINT;
    chave RECORD;
BEGIN
    SELECT attnum INTO coluna FROM pg_attribute WHERE attrelid = 'alunos'::regclass AND attname = 'usuario_id';
    SELECT attnum INTO destino FROM pg_attribute WHERE attrelid = 'usuarios'::regclass AND attname = 'id';
    IF EXISTS (SELECT 1 FROM pg_attribute WHERE attrelid = 'alunos'::regclass AND attnum = coluna AND attnotnull) THEN
        RAISE EXCEPTION 'alunos.usuario_id deve aceitar NULL';
    END IF;
    IF EXISTS (
        SELECT 1 FROM pg_constraint WHERE conrelid = 'alunos'::regclass AND contype = 'f' AND conkey = ARRAY[coluna]
        AND (confrelid <> 'usuarios'::regclass OR confkey <> ARRAY[destino] OR confdeltype NOT IN ('a', 'r') OR confupdtype NOT IN ('a', 'r'))
    ) THEN
        RAISE EXCEPTION 'Existe uma foreign key incompatível em alunos.usuario_id';
    END IF;
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conrelid = 'alunos'::regclass AND contype = 'f'
        AND conkey = ARRAY[coluna] AND confrelid = 'usuarios'::regclass AND confkey = ARRAY[destino]
    ) THEN
        ALTER TABLE alunos ADD CONSTRAINT alunos_usuario_fk FOREIGN KEY (usuario_id) REFERENCES usuarios(id);
    END IF;
    FOR chave IN SELECT conname FROM pg_constraint WHERE conrelid = 'alunos'::regclass
        AND contype = 'f' AND conkey = ARRAY[coluna] AND NOT convalidated
    LOOP
        EXECUTE format('ALTER TABLE alunos VALIDATE CONSTRAINT %I', chave.conname);
    END LOOP;
    IF NOT EXISTS (
        SELECT 1 FROM pg_index WHERE indrelid = 'alunos'::regclass AND indisunique AND indisvalid
        AND indnkeyatts = 1 AND indkey[0] = coluna
        AND (indpred IS NULL OR pg_get_expr(indpred, indrelid) = '(usuario_id IS NOT NULL)')
    ) THEN
        CREATE UNIQUE INDEX alunos_usuario_id_unique ON alunos(usuario_id) WHERE usuario_id IS NOT NULL;
    END IF;
END
$$;

CREATE OR REPLACE FUNCTION proteger_identidade_aluno() RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    IF NEW.id IS DISTINCT FROM OLD.id OR NEW.cpf IS DISTINCT FROM OLD.cpf OR NEW.nasc IS DISTINCT FROM OLD.nasc THEN
        RAISE EXCEPTION 'ID CPF e nascimento do aluno são imutáveis' USING ERRCODE = '23514';
    END IF;
    IF OLD.usuario_id IS NOT NULL AND NEW.usuario_id IS DISTINCT FROM OLD.usuario_id THEN
        RAISE EXCEPTION 'O vínculo do aluno é permanente' USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END
$$ LANGUAGE plpgsql;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_trigger WHERE tgrelid = 'alunos'::regclass AND tgname = 'alunos_identidade_imutavel') THEN
        CREATE TRIGGER alunos_identidade_imutavel BEFORE UPDATE OR DELETE ON alunos
        FOR EACH ROW EXECUTE FUNCTION proteger_identidade_aluno();
    END IF;
END
$$;

COMMIT;
