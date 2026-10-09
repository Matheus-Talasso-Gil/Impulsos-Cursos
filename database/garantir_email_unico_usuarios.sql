BEGIN;

-- impede novos cadastros durante a verificacao de duplicatas
LOCK TABLE usuarios IN SHARE ROW EXCLUSIVE MODE;

DO $$
DECLARE
    coluna SMALLINT;
BEGIN
    IF EXISTS (
        SELECT 1 FROM usuarios WHERE email IS NOT NULL
        GROUP BY email HAVING COUNT(*) > 1
    ) THEN
        RAISE EXCEPTION 'Existem emails duplicados e nenhum registro foi alterado';
    END IF;

    SELECT attnum INTO coluna FROM pg_attribute
    WHERE attrelid = 'usuarios'::regclass AND attname = 'email';

    IF NOT EXISTS (
        SELECT 1 FROM pg_index WHERE indrelid = 'usuarios'::regclass
        AND indisunique AND indisvalid AND indnkeyatts = 1
        AND indkey[0] = coluna AND indpred IS NULL AND indexprs IS NULL
    ) THEN
        ALTER TABLE usuarios ADD CONSTRAINT usuarios_email_unique UNIQUE (email);
    END IF;
END
$$;

COMMIT;
