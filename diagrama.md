# Diagramas do CRUD

Os arquivos do CRUD utilizam a tabela `alunos`.

O campo `id` é a chave primária. `alunos.usuario_id` é uma FK opcional e única para `usuarios(id)`.

## Tabela alunos

```mermaid
erDiagram
    alunos {
        serial id PK
        varchar nome
        varchar cpf UK
        date nasc
        varchar turma
        boolean ativo
        varchar email
        integer usuario_id FK,UK
    }
    usuarios o|--o| alunos : vincula
    usuarios ||--o{ inscricoes : possui
    cursos ||--o{ inscricoes : recebe
    usuarios {
        integer id PK
        varchar email UK
        varchar senha
        varchar tipo
    }
    cursos {
        integer id PK
        varchar nome
        text descricao
        integer carga_horaria
    }
    inscricoes {
        integer id PK
        integer usuario_id FK
        integer curso_id FK
    }
```

## create.php

Cadastra um novo aluno.

O campo `id` é gerado automaticamente pelo banco de dados.

## select.php

Lista os alunos cadastrados em ordem do  menor pro maior com base no  `id`.

## select_w_w.php

Busca e mostra um aluno específico pelo ID ou CPF informado.

## update.php

Busca um aluno pelo `id` e permite alterar:

- nome
- turma
- situação do aluno
- e-mail

ID, CPF, nascimento e vínculo já preenchido são imutáveis. A inscrição pertence à conta e o par `(usuario_id, curso_id)` é único; `alunos.turma` não gera inscrições automaticamente.

## delete.php

Busca um aluno pelo `id`.

Antes da exclusão, mostra o nome e a turma do aluno e pede confirmação para excluir.

## Fluxo de uma requisição

```mermaid
flowchart TD
    A[Requisição protegida] --> B{Existe sessão?}
    B -->|Não| C[Redireciona ao login]
    B -->|Sim| D{Rota administrativa?}
    D -->|Sim| E{Conta admin?}
    E -->|Não| F[HTTP 403]
    E -->|Sim| G[Consulta preparada no PostgreSQL]
    D -->|Não| G
    G --> H[Resposta HTML com dados escapados]
```

## Vínculo e perfil

O admin informa o ID do aluno e o e-mail de uma conta real, confere os dados e confirma com token CSRF. O servidor usa os dados pendentes da sessão para criar o vínculo.

O perfil consulta o aluno por `$_SESSION['id']`. Sem vínculo, informa que a conta ainda não está vinculada a um cadastro de aluno. Cursos funcionam também para contas sem aluno.

## Exclusão com confirmação

```mermaid
flowchart TD
    A[Admin busca aluno] --> B[Mostra dados para confirmação]
    B --> C[Valida token e dados pendentes na sessão]
    C --> D{Conta possui inscrições?}
    D -->|Não| E[Exclui somente o cadastro de aluno]
    D -->|Sim| F[Lista cursos e avisa sobre a exclusão]
    F --> G{Confirma exclusão mesmo com cursos?}
    G -->|Sim| E
    G -->|Cancelar| H[Mantém cadastro e limpa confirmação]
    B -->|Cancelar| H
    E --> I[Preserva conta e inscrições]
```

A segunda tela fica em `delete.php`. Excluir o aluno remove seu vínculo e o perfil passa a informar ausência de cadastro.

## Login e logout

```mermaid
flowchart TD
    A[Envia e-mail e senha] --> B[Consulta conta e verifica hash]
    B --> C{Credenciais válidas?}
    C -->|Não| D[Exibe credenciais inválidas]
    C -->|Sim| E[Regenera ID e guarda dados da conta na sessão]
    E --> F[Redireciona ao início]
    F --> G[Usuário clica em Sair]
    G --> H[Limpa dados e destrói sessão]
    H --> I[Redireciona ao início sem autenticação]
```

O login usa `password_verify()`. Após o logout, páginas protegidas exigem nova autenticação.

## Inscrição em curso

```mermaid
flowchart TD
    A[Conta autenticada abre catálogo ou detalhes] --> B[Envia inscrição]
    B --> C{CSRF, ID e curso válidos?}
    C -->|Não| D[Rejeita solicitação]
    C -->|Sim| E[Obtém ID da conta pela sessão]
    E --> F[Insere com ON CONFLICT]
    F --> G{Inscrição já existia?}
    G -->|Sim| H[Informa inscrição existente sem duplicar]
    G -->|Não| I[Confirma inscrição]
    H --> J[Meus cursos consulta inscrições da conta]
    I --> J
```

A inscrição não depende de cadastro de aluno. O par `(usuario_id, curso_id)` é único no banco.

## Recuperação de senha demonstrativa local

```mermaid
flowchart TD
    A[Abre recuperação] --> B{Conexão de loopback?}
    B -->|Não| C[HTTP 403]
    B -->|Sim| D{Já está autenticado?}
    D -->|Sim| E[Redireciona ao perfil]
    D -->|Não| F[Envia e-mail com CSRF]
    F --> G{CSRF válido?}
    G -->|Não| H[Rejeita solicitação]
    G -->|Sim| I[Invalida recuperação anterior]
    I --> J{Conta do tipo usuario existe?}
    J -->|Sim| K[Guarda token com validade de 10 minutos na sessão]
    J -->|Não| L[Não cria recuperação válida]
    K --> M[Mostra mensagem e link genéricos]
    L --> M
    M --> N[Envia nova senha e confirmação]
    N --> O{CSRF, token, prazo, tipo e senhas válidos?}
    O -->|Não| H
    O -->|Sim| P[Atualiza somente senha com hash e tipo usuario]
    P --> Q[Remove token após sucesso]
    Q --> R[Conta deve entrar com a nova senha]
```

Não há envio de e-mail nem verificação de identidade. Use somente contas de teste no próprio computador. O fluxo não cria tabelas nem realiza login automático.
