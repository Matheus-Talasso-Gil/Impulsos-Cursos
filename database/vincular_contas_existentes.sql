BEGIN;

-- impede novos cadastros durante a verificacao das correspondencias
LOCK TABLE alunos, usuarios IN SHARE ROW EXCLUSIVE MODE;

-- considera todos os registros para rejeitar emails duplicados nos dois lados
WITH contas_unicas AS (
    SELECT LOWER(TRIM(email)) AS email_normalizado, MIN(id) AS usuario_id
    FROM usuarios
    WHERE email IS NOT NULL AND TRIM(email) <> ''
    GROUP BY LOWER(TRIM(email))
    HAVING COUNT(*) = 1
), alunos_unicos AS (
    SELECT LOWER(TRIM(email)) AS email_normalizado, MIN(id) AS aluno_id
    FROM alunos
    WHERE email IS NOT NULL AND TRIM(email) <> ''
    GROUP BY LOWER(TRIM(email))
    HAVING COUNT(*) = 1
)
UPDATE alunos a
SET usuario_id = c.usuario_id
FROM alunos_unicos candidato
JOIN contas_unicas c ON c.email_normalizado = candidato.email_normalizado
WHERE a.id = candidato.aluno_id
  AND a.usuario_id IS NULL
  AND NOT EXISTS (
      SELECT 1 FROM alunos ocupado WHERE ocupado.usuario_id = c.usuario_id
  );

-- preserva os vinculos existentes e deixa casos ambiguos sem conta
COMMIT;
