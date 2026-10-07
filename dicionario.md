# Dicionário de dados

## Alunos

Guarda os dados dos alunos cadastrados. Os tipos abaixo seguem [table.pgsql](database/table.pgsql) e a [migration de vínculo](database/vincular_alunos_usuarios.sql). A estrutura de bancos antigos deve ser conferida após executar as migrations.

| Campo | Tipo | Chave | Aceita nulo? | Descrição |
| --- | --- | --- | --- | --- |
| id | SERIAL | PK | Não | Identificador do aluno, gerado automaticamente. |
| nome | VARCHAR(255) | — | Não | Nome do aluno. |
| cpf | VARCHAR(14) | UNIQUE | Não | Documento imutável após o cadastro. |
| nasc | DATE | — | Não | Data de nascimento imutável. |
| turma | VARCHAR(255) | — | Não | Código da turma, como INF-01, ING-01 ou ADM-01. |
| ativo | BOOLEAN | — | Não | Situação do aluno com padrão TRUE. |
| email | VARCHAR(255) | — | Não | E-mail do aluno. |
| usuario_id | INTEGER | FK e UNIQUE parcial | Sim | Referência à conta em `usuarios(id)`. |

- Base: tabela `alunos` criada em [database/table.pgsql](database/table.pgsql).
- A coluna "Aceita nulo?" segue as regras definidas no banco.
- Os formulários podem exigir campos mesmo quando o banco permite valor nulo.
- O campo `turma` é armazenado como texto e não possui chave estrangeira.
- Alunos antigos ficam com `usuario_id = NULL`; nenhuma conta é criada automaticamente. O índice único permite vários alunos sem conta e impede uma conta ligada a dois alunos.
- A função `proteger_identidade_aluno()` e o trigger `alunos_identidade_imutavel` impedem alterar ID, CPF, nascimento e vínculo já preenchido. Excluir o aluno desfaz o vínculo e preserva a conta e as inscrições.

## Usuários

Guarda as contas usadas para entrar no sistema.

| Campo | Tipo | Chave | Aceita nulo? | Descrição |
| --- | --- | --- | --- | --- |
| id | SERIAL | PK | Não | ID da conta guardado na sessão após o login. |
| email | VARCHAR(255) | UNIQUE | Não | E-mail usado no login. |
| senha | VARCHAR(255) | — | Não | Hash gerado por `password_hash()`. |
| tipo | VARCHAR(20) | CHECK | Não | `usuario` ou `admin` com padrão `usuario`. |

- Os campos aparecem nas consultas de [functions.php](includes/functions.php).
- A tabela `usuarios` é criada pelo script [table.pgsql](database/table.pgsql).
- O cadastro público cria contas comuns. `usuarios_admin_email_check` limita admin ao e-mail autorizado no SQL.
- O arquivo [ajustar_senha.sql](database/ajustar_senha.sql) ajusta o campo de senha para aceitar os hashes.
- Senhas antigas salvas como texto precisam ser convertidas ou redefinidas.

## Cursos

| Campo | Tipo | Chave | Aceita nulo? | Descrição |
| --- | --- | --- | --- | --- |
| id | SERIAL | PK | Não | Identificador do curso. |
| nome | VARCHAR(100) | — | Não | Nome do curso. |
| descricao | TEXT | — | Sim | Descrição opcional. |
| carga_horaria | INTEGER | — | Não | Duração em horas. |

## Inscrições

| Campo | Tipo | Chave | Aceita nulo? | Descrição |
| --- | --- | --- | --- | --- |
| id | SERIAL | PK | Não | Identificador da inscrição. |
| usuario_id | INTEGER | FK | Não | Referência a `usuarios(id)`. |
| curso_id | INTEGER | FK | Não | Referência a `cursos(id)`. |

`inscricao_unica` impede repetir o par `(usuario_id, curso_id)`. Inscrições pertencem à conta e podem existir sem cadastro de aluno. `alunos.turma` não gera inscrição nem possui relação automática com `cursos`. As FKs não usam exclusão em cascata.

## Scripts do banco

- [table.pgsql](database/table.pgsql) cria tabelas ausentes e aplica a estrutura de vínculo sem apagar registros.
- [vincular_alunos_usuarios.sql](database/vincular_alunos_usuarios.sql) adiciona a relação opcional e as proteções sem preencher vínculos ou alterar IDs e sequences. Confere FK e índice equivalentes antes de criar novos. Estrutura incompatível ou dados inválidos interrompem a transação.
- [adicionar_tipo_usuario.sql](database/adicionar_tipo_usuario.sql) adiciona o papel da conta e suas restrições.
- [reset_database.pgsql](database/auto_destruicao/reset_database.pgsql) é destrutivo: ao executá-lo, reinicia os dados do banco de dados do zero, apagando e recriando vazias as tabelas `alunos` e `usuarios`. Não apaga o banco PostgreSQL inteiro, mas todos os registros dessas tabelas são perdidos. Use somente em desenvolvimento, após confirmar o banco e fazer backup.

## Legenda

- **PK:** chave primária, identifica cada registro da tabela.
- **FK:** chave estrangeira, referencia uma chave de outra tabela.
- **Nulo:** ausência de valor no campo; não é o mesmo que texto vazio.
