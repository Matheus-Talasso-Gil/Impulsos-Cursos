# Diagramas do sistema

Os arquivos do CRUD utilizam a tabela `alunos`.

O campo `id` é a chave primária. `alunos.usuario_id` é uma FK opcional e única para `usuarios(id)`.

## Diagrama Entidade-Relacionamento

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
        timestamp created_at
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

## Documentação do CRUD de alunos

### create.php

É um redirecionamento: encaminha admin para o relatório e as demais contas para Cadastre-se. Não oferece mais um formulário administrativo de cadastro de aluno. O cadastro público cria conta do tipo usuario e aluno na mesma transação, já com usuario_id preenchido automaticamente.

O campo `id` é gerado automaticamente pelo banco de dados.

### select.php

Lista alunos por ID crescente, com filtros opcionais por curso e situação e ação Editar.

### select_w_w.php

Busca e mostra um aluno específico pelo ID ou CPF informado.

### update.php

Busca um aluno pelo `id` e permite alterar:

- nome
- turma
- situação do aluno
- e-mail

ID, CPF, nascimento e vínculo já preenchido são imutáveis. A inscrição pertence à conta e o par `(usuario_id, curso_id)` é único; `alunos.turma` não gera inscrições automaticamente.

### delete.php

Busca um aluno pelo `id`.

Antes da exclusão, mostra o nome e a turma do aluno e pede confirmação para excluir.

## Fluxo de uma requisição

Visitantes que tentam abrir rotas protegidas são encaminhados ao login. Contas comuns que abrem rotas administrativas recebem HTTP 403 com a página personalizada de acesso negado. Após mais de 30 minutos sem atividade, a próxima requisição encerra a sessão e redireciona ao login com aviso de expiração.

```mermaid
flowchart TD
    A[Requisição protegida] --> B{Existe sessão?}
    B -->|Não| C[Redireciona ao login]
    B -->|Sim| D{Rota administrativa?}
    D -->|Sim| E{Conta admin?}
    E -->|Não| F[HTTP 403 e página personalizada de acesso negado]
    E -->|Sim| G[Consulta preparada no PostgreSQL]
    D -->|Não| G
    G --> H[Resposta HTML com dados escapados]
```

## Vínculo e perfil

O cadastro público obtém o ID da conta com RETURNING id e salva esse ID no aluno dentro da mesma transação. A migration de dados antigos usa correspondências únicas de e-mail normalizado.

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

## Fluxograma geral do sistema

Os diagramas abaixo complementam os fluxos técnicos anteriores e representam as páginas atuais de `login/`, `app/`, os menus de `includes/header.php`, o controle de `includes/session.php` e as funções compartilhadas. O código foi usado para resolver diferenças nas descrições antigas da documentação.

### Visitante, cadastro e entrada

```mermaid
flowchart TD
    Visitante["Visitante"] --> Inicio["Página inicial pública"]
    Inicio --> Escolha{"Entrar ou Cadastre-se?"}
    Escolha -->|Entrar| Login["Informar e-mail e senha"]
    Login --> Credenciais{"Conta encontrada e senha válida?"}
    Credenciais -->|Não| ErroLogin["Mostrar erro no login"]
    ErroLogin --> Login
    Credenciais -->|Sim| Sessao["Regenerar sessão e guardar ID, e-mail e tipo"]
    Sessao --> Autenticado["Página inicial autenticada"]
    Autenticado --> Tipo{"Tipo da conta?"}
    Tipo -->|usuario| Usuario["Menu do usuário: cursos e perfil"]
    Tipo -->|admin| Admin["Menu admin: abrir Painel e gestão"]
    Escolha -->|Cadastre-se| Cadastro["Informar nome, CPF, nascimento, e-mail e senha"]
    Cadastro --> Validacao{"CSRF e dados válidos, CPF e e-mail disponíveis?"}
    Validacao -->|Não| ErroCadastro["Exibir erro e corrigir formulário"]
    ErroCadastro --> Cadastro
    Validacao -->|Sim| Criar["Criar conta com returning id e aluno vinculado em transação"]
    Criar --> Gravou{"Gravação concluída?"}
    Gravou -->|Não| Rollback["Desfazer transação e mostrar erro"]
    Rollback --> Cadastro
    Gravou -->|Sim| Redirecionar["Redirecionar ao login com mensagem de sucesso"]
    Redirecionar --> Login
```

Ambos os tipos de conta são redirecionados ao início após o login; o admin abre o painel pelo menu ou pelos atalhos. O cadastro não autentica automaticamente nem vincula aluno por e-mail. O aluno começa ativo e com turma `Sem turma`.

### Fluxo do usuário

```mermaid
flowchart TD
    Inicio["Usuário no início autenticado"] --> Catalogo["Todos os cursos"]
    Inicio --> Meus["Meus cursos: listar inscrições da própria conta"]
    Inicio --> Perfil["Meu perfil: e-mail, tipo e data de criação da conta"]
    Inicio --> Sair["Logout: limpar e destruir sessão"]
    Sair --> Publico["Página inicial pública"]
    Catalogo --> Detalhes["Selecionar curso e ver detalhes"]
    Meus --> Detalhes
    Detalhes --> Inscrito{"Já está inscrito?"}
    Inscrito -->|Sim| Situacao["Exibir Já inscrito"]
    Inscrito -->|Não| Inscrever["Enviar inscrição pelo catálogo ou detalhes"]
    Inscrever --> Validar{"CSRF, ID e curso válidos?"}
    Validar -->|Não| Erro["Mostrar erro"]
    Validar -->|Sim| Gravar["Criar inscrição da conta da sessão sem duplicar"]
    Gravar --> Resultado["Mostrar inscrição realizada ou já existente"]
    Resultado --> Meus
    Perfil --> Vinculo{"Existe aluno vinculado à conta?"}
    Vinculo -->|Sim| Dados["Exibir dados do aluno"]
    Vinculo -->|Não| SemVinculo["Informar ausência de vínculo"]
    Dados --> Previa["Exibir prévia de até três cursos e link para Meus cursos"]
    SemVinculo --> Previa
    Previa --> Meus
```

Cursos e inscrições pertencem à conta e funcionam sem aluno vinculado. O perfil consulta o ID da sessão e não permite escolher outra conta; não exibe senha nem CPF. Admin também pode abrir catálogo, detalhes e Meus cursos: essas rotas exigem autenticação, não um papel específico. Seu perfil não mostra a prévia de cursos.

### Cancelamento de inscrição

```mermaid
flowchart TD
    Meus["Meus cursos"] --> Pedir["Solicitar cancelamento de um curso"]
    Pedir --> Validar{"CSRF, curso e inscrição própria válidos?"}
    Validar -->|Não| Erro["Exibir erro sem remover inscrição"]
    Validar -->|Sim| Conferir["Mostrar curso e pedir confirmação"]
    Conferir --> Decisao{"Confirmar cancelamento?"}
    Decisao -->|Não| Manter["Manter inscrição e voltar à lista"]
    Decisao -->|Sim| Revalidar{"CSRF e curso pendente na sessão conferem?"}
    Revalidar -->|Não| Erro
    Revalidar -->|Sim| Remover["Remover somente a inscrição da conta autenticada"]
    Remover --> Lista["Redirecionar para Meus cursos e mostrar resultado"]
    Manter --> Meus
    Lista --> Meus
```

### Fluxo administrativo

```mermaid
flowchart TD
    Admin["Admin no início autenticado"] --> Painel["Abrir painel: totais de alunos, usuários, cursos e inscrições"]
    Painel --> Consulta["Consultar aluno por ID ou CPF"]
    Painel --> Relatorio["Relatório de alunos com filtros"]
    Relatorio --> Editar["Selecionar Editar ou buscar ID em update.php"]
    Painel --> Excluir["Buscar ID e excluir aluno com confirmação"]
    Painel --> AlunosCursos["Consultar cursos dos alunos pelas contas vinculadas"]
    Painel --> Contas["Consultar usuários, tipo, data de criação e vínculo"]
    Painel --> Cursos["Gerenciar cursos"]
    Painel --> Catalogo["Todos os cursos"]
    Admin --> Perfil["Ver próprio perfil"]
    Admin --> Sair["Logout e retorno ao início público"]
```

Os atalhos do menu administrativo também abrem essas áreas. Consultar usuários é uma listagem; essa tela não oferece edição ou exclusão de contas.

### Consulta e edição de aluno

```mermaid
flowchart TD
    Admin["Admin"] --> Consulta["Consultar aluno: informar ID ou CPF"]
    Consulta --> Encontrado{"Critério válido e aluno encontrado?"}
    Encontrado -->|Não| Mensagem["Mostrar mensagem e permitir nova busca"]
    Mensagem --> Consulta
    Encontrado -->|Sim| Dados["Mostrar dados do aluno"]
    Dados --> Voltar["Voltar ao início ou abrir relatório"]
    Voltar --> Relatorio["Relatório de alunos"]
    Relatorio --> Selecionar["Selecionar Editar"]
    Admin --> BuscaEdicao["Buscar aluno por ID em update.php"]
    BuscaEdicao --> Carregar{"Aluno localizado?"}
    Selecionar --> Carregar
    Carregar -->|Não| ErroBusca["Mostrar erro e buscar novamente"]
    ErroBusca --> BuscaEdicao
    Carregar -->|Sim| Editar["Editar nome, turma, situação e e-mail"]
    Editar --> Campos{"Campos obrigatórios e formato de e-mail válidos no formulário?"}
    Campos -->|Não| Corrigir["Corrigir os campos"]
    Corrigir --> Editar
    Campos -->|Sim| Original{"Identidade original recuperada na sessão?"}
    Original -->|Não| Erro["Mostrar erro sem atualizar"]
    Original -->|Sim| Atualizar["Atualizar somente dados permitidos no banco"]
    Atualizar --> Gravou{"Banco aceitou a atualização?"}
    Gravou -->|Não| Erro
    Gravou -->|Sim| Sucesso["Mostrar sucesso e recarregar dados do formulário"]
```

ID, CPF, nascimento e vínculo já preenchido são protegidos. A validação dos campos da edição ocorre no formulário do navegador; o servidor recupera a identidade original e grava apenas os campos permitidos. A consulta individual não tem botões diretos de editar/excluir: a edição usa o relatório ou `update.php`, e a exclusão usa a busca de `delete.php`.

O fluxo de exclusão de aluno foi preservado em **Exclusão com confirmação**: exige confirmação inicial e, se a conta vinculada tiver inscrições, uma segunda confirmação com a lista de cursos. Cancelar mantém o cadastro; excluir remove somente o aluno e seu vínculo, preservando conta e inscrições.

### Gestão administrativa de cursos

```mermaid
flowchart TD
    Admin["Admin"] --> Lista["Gerenciar cursos: listar cursos e quantidade de inscritos"]
    Lista --> Acao{"Escolher ação"}
    Acao -->|Cadastrar| Novo["Informar nome, descrição e carga horária"]
    Acao -->|Editar| Editar["Selecionar curso e alterar dados permitidos"]
    Novo --> Validar{"CSRF, nome e carga horária válidos?"}
    Editar --> Validar
    Validar -->|Não| ErroDados["Exibir erro e corrigir formulário"]
    Validar -->|Sim| Gravar["Inserir ou atualizar curso e mostrar resultado"]
    Acao -->|Excluir| Curso["Carregar curso e quantidade de inscrições"]
    Curso --> Inscricoes{"Curso possui inscrições?"}
    Inscricoes -->|Sim| Bloquear["Bloquear exclusão e preservar inscrições"]
    Inscricoes -->|Não| Confirmar{"Confirmar exclusão?"}
    Confirmar -->|Não| Lista
    Confirmar -->|Sim| Revalidar{"CSRF válido e curso continua sem inscrições?"}
    Revalidar -->|Não| ErroExclusao["Recusar exclusão e mostrar motivo"]
    Revalidar -->|Sim| Excluir["Excluir curso e retornar à lista com sucesso"]
    Excluir --> Lista
```

O banco também impede excluir cursos com inscrições, inclusive se surgir uma inscrição durante a confirmação. Cadastro e edição apresentam erro quando a gravação não pode ser concluída.

### Recuperação de senha

O diagrama técnico em **Recuperação de senha demonstrativa local** integra o fluxo geral. O acesso começa no link Esqueci minha senha do login. Apenas contas `tipo = usuario` podem redefinir a senha, em conexão local e na mesma sessão do navegador. Uma conta não elegível recebe a mesma mensagem e link genéricos, mas não uma recuperação válida. O token dura dez minutos; a redefinição valida token, prazo, tipo da conta, CSRF e confirmação da nova senha, grava `password_hash()` e invalida o token após sucesso. A tela oferece Voltar para entrar; não redireciona automaticamente nem autentica a conta.

## Diagrama de casos de uso

Representação conceitual em `flowchart LR`: os atores ficam fora dos grupos de funções; as linhas sem seta ligam atores a casos de uso e as setas pontilhadas mostram decomposição ou navegação. Não representam herança de permissões nem relações UML formais.

```mermaid
flowchart LR
    Visitante["Visitante"]
    Usuario["Usuário"]
    Admin["Admin"]

    subgraph Publico["Área pública"]
        Inicial(["Visualizar página inicial pública"])
    end
    subgraph Autenticacao["Autenticação"]
        Login(["Fazer login"])
        Cadastro(["Criar conta usuario e cadastro de aluno"])
        Recuperar(["Recuperar senha de conta usuario - demonstração local"])
        Logout(["Fazer logout"])
    end
    subgraph Cursos["Cursos da própria conta"]
        InicioConta(["Visualizar página inicial autenticada"])
        Catalogo(["Ver todos os cursos"])
        Detalhes(["Ver detalhes do curso"])
        Inscrever(["Inscrever-se em curso"])
        Meus(["Ver Meus cursos"])
        Cancelar(["Cancelar própria inscrição com confirmação"])
    end
    subgraph Perfil["Perfil próprio"]
        MeuPerfil(["Ver próprio perfil"])
        DadosProprios(["Consultar dados próprios da conta e aluno vinculado"])
    end
    subgraph Administracao["Administração - acesso exclusivo do admin"]
        Painel(["Acessar painel administrativo"])
        Consultar(["Consultar aluno por ID ou CPF"])
        Listar(["Listar alunos e filtrar relatório"])
        EditarAluno(["Editar dados permitidos do aluno"])
        ExcluirAluno(["Excluir aluno com confirmações"])
        CursosAlunos(["Visualizar cursos dos alunos"])
        Contas(["Consultar usuários cadastrados"])
        Gerenciar(["Gerenciar e listar cursos"])
        CriarCurso(["Cadastrar curso"])
        EditarCurso(["Editar curso"])
        ExcluirCurso(["Excluir curso sem inscrições"])
    end

    Visitante --- Inicial
    Visitante --- Login
    Visitante --- Cadastro
    Visitante --- Recuperar
    Usuario --- Login
    Usuario --- Logout
    Usuario --- InicioConta
    Usuario --- Catalogo
    Usuario --- Detalhes
    Usuario --- Inscrever
    Usuario --- Meus
    Usuario --- Cancelar
    Usuario --- MeuPerfil
    Usuario --- DadosProprios
    Admin --- Login
    Admin --- Logout
    Admin --- InicioConta
    Admin --- Catalogo
    Admin --- Detalhes
    Admin --- Inscrever
    Admin --- Meus
    Admin --- Cancelar
    Admin --- MeuPerfil
    Admin --- DadosProprios
    Admin --- Painel
    Admin --- Consultar
    Admin --- Listar
    Admin --- EditarAluno
    Admin --- ExcluirAluno
    Admin --- CursosAlunos
    Admin --- Contas
    Admin --- Gerenciar
    Admin --- CriarCurso
    Admin --- EditarCurso
    Admin --- ExcluirCurso
    Gerenciar -.-> CriarCurso
    Gerenciar -.-> EditarCurso
    Gerenciar -.-> ExcluirCurso
    MeuPerfil -.-> DadosProprios
```

- **Visitante:** pessoa sem autenticação, com acesso à página pública, login, cadastro e solicitação de recuperação local para uma conta comum.
- **Usuário:** conta comum autenticada, com acesso aos cursos, inscrições, cancelamento e próprio perfil. Não gerencia alunos, contas, vínculos ou cursos administrativos.
- **Admin:** conta administrativa responsável por consultas e gestão de alunos e cursos, com acesso ao próprio perfil. Também pode usar as rotas de cursos da própria conta; não participa da recuperação de senha.

Ver perfil e consultar dados próprios são aspectos da mesma página, não telas distintas. A criação de aluno ocorre no cadastro público com vínculo automático na mesma transação. A exclusão de aluno preserva a conta e suas inscrições; a exclusão de curso é bloqueada se houver inscrições.
