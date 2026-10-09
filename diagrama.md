# Diagramas do sistema

Os arquivos do CRUD utilizam a tabela `alunos`.

O campo `id` é a chave primária. `alunos.usuario_id` é uma FK opcional e única para `usuarios(id)`. Os fluxos usam `graph LR`; somente o banco usa `erDiagram`.

## Diagrama Entidade-Relacionamento

O DER foi revisado com `database/table.pgsql` e as migrations do projeto. São seis tabelas: `alunos`, `usuarios`, `cursos`, `inscricoes`, `favoritos` e `logs_admin`.

Os pares `(usuario_id, curso_id)` são únicos em `inscricoes` e `favoritos`. Excluir uma conta ou curso remove seus favoritos por cascata; inscrições impedem excluir a conta ou o curso referenciado. `logs_admin.admin_id` referencia `usuarios(id)`; `entidade_id` identifica o alvo da ação, sem FK para aluno ou curso, preservando o histórico após exclusões. Recuperação de senha usa a sessão, sem tabela própria.

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
        serial id PK
        varchar email UK
        varchar senha
        varchar tipo
        timestamp created_at
    }
    cursos {
        serial id PK
        varchar nome
        text descricao
        integer carga_horaria
    }
    inscricoes {
        serial id PK
        integer usuario_id FK
        integer curso_id FK
    }
    usuarios ||--o{ favoritos : guarda
    cursos ||--o{ favoritos : recebe
    usuarios ||--o{ logs_admin : registra
    favoritos {
        serial id PK
        integer usuario_id FK
        integer curso_id FK
        timestamp created_at
    }
    logs_admin {
        serial id PK
        integer admin_id FK
        varchar acao
        varchar entidade
        integer entidade_id
        text descricao
        timestamp created_at
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
- situação do aluno
- e-mail

ID, CPF, nascimento e vínculo já preenchido são imutáveis. A inscrição pertence à conta e o par `(usuario_id, curso_id)` é único; `alunos.turma` não gera inscrições automaticamente.

### delete.php

Busca um aluno pelo `id`.

Antes da exclusão, mostra o nome e os cursos do aluno e pede confirmação para excluir.

## Fluxo de uma requisição

Visitantes que tentam abrir rotas protegidas são encaminhados ao login. Contas comuns que abrem rotas administrativas recebem HTTP 403 com a página personalizada de acesso negado. Após mais de 30 minutos sem atividade, a próxima requisição encerra a sessão e redireciona ao login com aviso de expiração.

```mermaid
graph LR
    Requisicao["Requisição protegida"] --> Sessao{"Sessão válida?"}
    Sessao -->|Não| Login["Login"]
    Sessao -->|Sim| Rota{"Rota administrativa?"}
    Rota -->|Sim| Permissao{"Conta admin?"}
    Permissao -->|Não| Negado["HTTP 403"]
    Permissao -->|Sim| Banco["Consulta preparada"]
    Rota -->|Não| Banco
    Banco --> Resposta["HTML com dados escapados"]
```

## Vínculo e perfil

O cadastro público obtém o ID da conta com RETURNING id e salva esse ID no aluno dentro da mesma transação. A migration de dados antigos usa correspondências únicas de e-mail normalizado.

O perfil consulta o aluno por `$_SESSION['id']`. Sem vínculo, informa que a conta ainda não está vinculada a um cadastro de aluno. Cursos funcionam também para contas sem aluno.

## Exclusão com confirmação

```mermaid
graph LR
    Busca["Admin busca aluno"] --> Confirmacao["Confirmar dados do aluno"]
    Confirmacao -->|Cancelar| Manter["Manter cadastro"]
    Confirmacao --> Validar{"Token e sessão válidos?"}
    Validar -->|Não| Erro["Recusar exclusão"]
    Validar -->|Sim| Inscricoes{"Conta tem inscrições?"}
    Inscricoes -->|Não| Excluir["Excluir aluno"]
    Inscricoes -->|Sim| Cursos["Mostrar cursos vinculados"]
    Cursos --> Segunda{"Confirmar novamente?"}
    Segunda -->|Sim| Excluir
    Segunda -->|Não| Manter
    Excluir --> Resultado["Conta e inscrições preservadas"]
    Manter --> Limpar["Limpar confirmação"]
```

A segunda tela fica em `delete.php`. Excluir o aluno remove seu vínculo e o perfil passa a informar ausência de cadastro.

## Login e logout

```mermaid
graph LR
    Usuario["Usuário"] --> Login["E-mail e senha"]
    Login --> Validar{"Conta e hash válidos?"}
    Validar -->|Não| Erro["Erro no login"]
    Erro --> Login
    Validar -->|Sim| Sessao["Regenerar e salvar sessão"]
    Sessao --> Tipo{"Tipo da conta?"}
    Tipo -->|usuario| Dashboard["Dashboard"]
    Tipo -->|admin| Painel["Painel admin"]
    Dashboard --> Logout["Sair"]
    Painel --> Logout
    Logout --> Encerrar["Destruir sessão"]
    Encerrar --> Inicio["Início público"]
```

O login usa `password_verify()` e encaminha contas comuns ao dashboard e administradores ao painel. A sessão guarda ID, e-mail e tipo da conta. Após o logout, páginas protegidas exigem nova autenticação.

## Inscrição em curso

```mermaid
graph LR
    Curso["Catálogo ou detalhes"] --> Inscricao["Solicitar inscrição"]
    Inscricao --> Validar{"CSRF, ID e curso válidos?"}
    Validar -->|Não| Erro["Recusar solicitação"]
    Validar -->|Sim| Conta["Conta da sessão"]
    Conta --> Banco["Inserir sem duplicar"]
    Banco --> Duplicidade{"Já estava inscrito?"}
    Duplicidade -->|Sim| Existe["Inscrição existente"]
    Duplicidade -->|Não| Sucesso["Inscrição realizada"]
    Existe --> Meus["Meus cursos"]
    Sucesso --> Meus
```

A inscrição não depende de cadastro de aluno. O par `(usuario_id, curso_id)` é único no banco.

## Recuperação de senha demonstrativa local

```mermaid
graph LR
    Solicitar["Recuperar senha"] --> Local{"Conexão local?"}
    Local -->|Não| Negado["HTTP 403"]
    Local -->|Sim| Sessao{"Autenticado?"}
    Sessao -->|Sim| Perfil["Perfil"]
    Sessao -->|Não| Email["E-mail e CSRF"]
    Email --> Validar{"CSRF válido?"}
    Validar -->|Não| Erro["Recusar solicitação"]
    Validar -->|Sim| Limpar["Invalidar token anterior"]
    Limpar --> Conta{"Conta usuario existe?"}
    Conta -->|Sim| Token["Token na sessão: 10 minutos"]
    Conta -->|Não| Invalido["Sem token válido"]
    Token --> Mensagem["Mensagem e link genéricos"]
    Invalido --> Mensagem
```

### Redefinição da senha

```mermaid
graph LR
    Link["Link de recuperação local"] --> Senha["Nova senha e confirmação"]
    Senha --> Validar{"CSRF, token, prazo e conta válidos?"}
    Validar -->|Não| Erro["Recusar redefinição"]
    Validar -->|Sim| Conferir{"Senhas válidas e iguais?"}
    Conferir -->|Não| Erro
    Conferir -->|Sim| Hash["Gravar hash da senha"]
    Hash --> Limpar["Remover token"]
    Limpar --> Sucesso["Mostrar sucesso"]
    Sucesso -->|Voltar para entrar| Login["Login com nova senha"]
```

Não há envio de e-mail nem verificação de identidade. Use somente contas de teste no próprio computador. O fluxo não cria tabelas nem realiza login automático.

## Fluxograma geral do sistema

Os diagramas abaixo complementam os fluxos técnicos anteriores e representam as páginas atuais de `login/`, `app/`, os menus de `includes/header.php`, o controle de `includes/session.php` e as funções compartilhadas. O código foi usado para resolver diferenças nas descrições antigas da documentação.

### Visitante, cadastro e entrada

```mermaid
graph LR
    Visitante["Visitante"] --> Inicio["Início público"]
    Inicio --> Escolha{"Entrar ou cadastrar?"}
    Escolha -->|Entrar| Login["Login"]
    Escolha -->|Cadastrar| Formulario["Formulário de cadastro"]
    Formulario --> Validar{"CSRF e dados válidos?"}
    Validar -->|Não| Erro["Corrigir formulário"]
    Erro --> Formulario
    Validar -->|Sim| Transacao["Iniciar transação"]
    Transacao --> Conta["Criar conta usuario"]
    Conta --> Id["RETURNING usuario id"]
    Id --> Aluno["Criar aluno vinculado"]
    Aluno --> Resultado{"Gravação concluída?"}
    Resultado -->|Não| Rollback["Rollback e erro"]
    Rollback --> Formulario
    Resultado -->|Sim| Commit["Commit"]
    Commit --> Login
```

O login encaminha contas comuns ao dashboard e administradores ao painel. O cadastro não autentica automaticamente nem vincula aluno por e-mail. O aluno começa ativo e com turma `Sem turma`.

### Fluxo do usuário

```mermaid
graph LR
    Dashboard["Dashboard do usuário"] --> Catalogo["Todos os cursos"]
    Dashboard --> Meus["Meus cursos"]
    Dashboard --> Favoritos["Favoritos"]
    Dashboard --> Perfil["Meu perfil"]
    Dashboard --> Sair["Sair e destruir sessão"]
    Sair --> Inicio["Início público"]
    Catalogo --> Detalhes["Detalhes do curso"]
    Meus --> Detalhes
    Favoritos --> Detalhes
    Detalhes --> Inscrito{"Já inscrito?"}
    Inscrito -->|Sim| Situacao["Mostrar inscrição existente"]
    Inscrito -->|Não| Inscrever["Solicitar inscrição"]
    Inscrever --> Validar{"CSRF, ID e curso válidos?"}
    Validar -->|Não| Erro["Mostrar erro"]
    Validar -->|Sim| Banco["Gravar sem duplicar"]
    Banco --> Resultado["Mostrar resultado"]
    Resultado --> Meus
```

Cursos e inscrições pertencem à conta e funcionam sem aluno vinculado. O perfil consulta o ID da sessão e não permite escolher outra conta; não exibe senha nem CPF. Admin também pode abrir catálogo, detalhes e Meus cursos: essas rotas exigem autenticação, não um papel específico. Seu perfil não mostra a prévia de cursos.

### Cancelamento de inscrição

```mermaid
graph LR
    Meus["Meus cursos"] --> Pedido["Pedir cancelamento"]
    Pedido --> Validar{"CSRF e inscrição própria válidos?"}
    Validar -->|Não| Erro["Mostrar erro"]
    Validar -->|Sim| Curso["Mostrar curso"]
    Curso --> Confirmar{"Confirmar cancelamento?"}
    Confirmar -->|Não| Manter["Manter inscrição"]
    Manter --> Meus
    Confirmar -->|Sim| Conferir{"CSRF e curso pendente conferem?"}
    Conferir -->|Não| Erro
    Conferir -->|Sim| Remover["Remover inscrição própria"]
    Remover --> Resultado["Mostrar resultado"]
    Resultado --> Meus
```

### Fluxo administrativo

```mermaid
graph LR
    Admin["Admin"] --> Painel["Painel admin e totais"]
    Painel --> Consulta["Consultar aluno"]
    Painel --> Relatorio["Relatório de alunos"]
    Relatorio --> Editar["Editar aluno"]
    Painel --> Excluir["Excluir aluno"]
    Painel --> Vinculos["Cursos dos alunos"]
    Painel --> Usuarios["Consultar usuários"]
    Painel --> Cursos["Gerenciar cursos"]
    Admin --> Historico["Histórico administrativo"]
    Painel --> Catalogo["Catálogo de cursos"]
    Admin --> Perfil["Meu perfil"]
    Admin --> Sair["Sair"]
```

Os atalhos do menu administrativo também abrem essas áreas. Consultar usuários é uma listagem; essa tela não oferece edição ou exclusão de contas.

### Consulta de aluno

```mermaid
graph LR
    Admin["Admin"] --> Consulta["Informar ID ou CPF"]
    Consulta --> Validar{"Critério válido?"}
    Validar -->|Não| Erro["Mostrar erro"]
    Erro --> Consulta
    Validar -->|Sim| Banco["Consultar banco"]
    Banco --> Encontrado{"Aluno encontrado?"}
    Encontrado -->|Não| Mensagem["Permitir nova busca"]
    Mensagem --> Consulta
    Encontrado -->|Sim| Dados["Mostrar dados do aluno"]
    Dados --> Voltar["Início ou relatório"]
```

### Edição de aluno

```mermaid
graph LR
    Admin["Admin"] --> Busca["Buscar ID ou selecionar no relatório"]
    Busca --> Encontrado{"Aluno encontrado?"}
    Encontrado -->|Não| ErroBusca["Mostrar erro de busca"]
    ErroBusca --> Busca
    Encontrado -->|Sim| Formulario["Editar campos permitidos"]
    Formulario --> Campos{"Formulário válido?"}
    Campos -->|Não| Corrigir["Corrigir campos"]
    Corrigir --> Formulario
    Campos -->|Sim| Sessao{"CSRF e identidade na sessão válidos?"}
    Sessao -->|Não| Erro["Mostrar erro"]
    Sessao -->|Sim| Servidor{"Nome, e-mail e situação válidos?"}
    Servidor -->|Não| Erro
    Servidor -->|Sim| Banco["Atualizar campos permitidos"]
    Banco --> Gravou{"Banco aceitou?"}
    Gravou -->|Não| Erro
    Gravou -->|Sim| Sucesso["Sucesso e recarga dos dados"]
```

ID, CPF, nascimento, turma e vínculo já preenchido são protegidos. A edição valida os campos no formulário e no servidor, confere CSRF e o aluno selecionado na sessão, recupera a identidade original e grava somente nome, situação e e-mail. A consulta individual não tem botões diretos de editar/excluir: a edição usa o relatório ou `update.php`, e a exclusão usa a busca de `delete.php`.

O fluxo de exclusão de aluno foi preservado em **Exclusão com confirmação**: exige confirmação inicial e, se a conta vinculada tiver inscrições, uma segunda confirmação com a lista de cursos. Cancelar mantém o cadastro; excluir remove somente o aluno e seu vínculo, preservando conta e inscrições.

### Cadastro e edição de cursos

```mermaid
graph LR
    Admin["Admin"] --> Lista["Listar cursos e inscritos"]
    Lista --> Acao{"Criar ou editar?"}
    Acao -->|Criar| Novo["Novo curso"]
    Acao -->|Editar| Editar["Editar curso"]
    Novo --> Dados["Nome, descrição e carga horária"]
    Editar --> Dados
    Dados --> Validar{"CSRF e dados válidos?"}
    Validar -->|Não| Erro["Corrigir formulário"]
    Validar -->|Sim| Banco["Inserir ou atualizar"]
    Banco --> Gravou{"Gravação concluída?"}
    Gravou -->|Não| Erro
    Gravou -->|Sim| Resultado["Mostrar sucesso"]
```

### Exclusão de curso

```mermaid
graph LR
    Lista["Lista de cursos"] --> Curso["Carregar curso e inscrições"]
    Curso --> Inscricoes{"Tem inscrições?"}
    Inscricoes -->|Sim| Bloquear["Bloquear exclusão"]
    Inscricoes -->|Não| Confirmar{"Confirmar exclusão?"}
    Confirmar -->|Não| Lista
    Confirmar -->|Sim| Validar{"CSRF válido e sem inscrições?"}
    Validar -->|Não| Erro["Recusar e mostrar motivo"]
    Validar -->|Sim| Banco["Excluir curso"]
    Banco --> Resultado["Resultado e retorno à lista"]
    Resultado --> Lista
```

O banco também impede excluir cursos com inscrições, inclusive se surgir uma inscrição durante a confirmação. Cadastro e edição apresentam erro quando a gravação não pode ser concluída.

### Recuperação de senha

O diagrama técnico em **Recuperação de senha demonstrativa local** integra o fluxo geral. O acesso começa no link Esqueci minha senha do login. Apenas contas `tipo = usuario` podem redefinir a senha, em conexão local e na mesma sessão do navegador. Uma conta não elegível recebe a mesma mensagem e link genéricos, mas não uma recuperação válida. O token dura dez minutos; a redefinição valida token, prazo, tipo da conta, CSRF e confirmação da nova senha, grava `password_hash()` e invalida o token após sucesso. A tela oferece Voltar para entrar; não redireciona automaticamente nem autentica a conta.


### Favoritos

```mermaid
graph LR
    Curso["Catálogo ou detalhes"] --> Favoritar["Favoritar curso"]
    Favoritar --> Validar{"Sessão, CSRF e ID válidos?"}
    Validar -->|Não| Erro["Mostrar erro"]
    Validar -->|Sim| Verificar{"Curso existe?"}
    Verificar -->|Não| Erro
    Verificar -->|Sim| Salvar["Salvar sem duplicar"]
    Salvar --> Resultado["Favoritado ou já existente"]
    Resultado --> Favoritos["Favoritos da conta"]
    Favoritos --> Remover["Remover favorito"]
    Remover --> Conferir{"Sessão, CSRF e curso válidos?"}
    Conferir -->|Não| Erro
    Conferir -->|Sim| Banco["Remover somente favorito próprio"]
    Banco --> Favoritos
```

Favoritos não criam inscrições. A conta vem da sessão; a remoção não afeta os favoritos de outras contas. Usuários e administradores podem acessar essas rotas, embora o atalho Favoritos apareça apenas no menu comum.

### Perfil próprio

```mermaid
graph LR
    Perfil["Meu perfil"] --> Sessao["ID da sessão"]
    Sessao --> Usuario["Dados da própria conta"]
    Usuario --> Vinculo{"Aluno vinculado?"}
    Vinculo -->|Sim| Aluno["Dados do aluno e cursos"]
    Vinculo -->|Não| Ausencia["Informar ausência de vínculo"]
    Aluno --> Tipo{"Conta comum?"}
    Ausencia --> Tipo
    Tipo -->|Sim| Cursos["Prévia de até três cursos"]
    Tipo -->|Não| Resultado["Exibir perfil"]
    Cursos --> Resultado
    Cursos --> Meus["Meus cursos"]
```

O perfil exibe erros quando não consegue consultar o aluno ou os cursos. A prévia de cursos aparece somente para a conta comum e funciona mesmo sem aluno vinculado.

### Histórico administrativo

```mermaid
graph LR
    Admin["Admin"] --> Acao["Criar curso ou editar/excluir aluno ou curso"]
    Acao --> Resultado{"Ação principal funcionou?"}
    Resultado -->|Não| Erro["Erro sem log de sucesso"]
    Resultado -->|Sim| Log["Registrar em logs_admin"]
    Log --> Gravou{"Log gravado?"}
    Gravou -->|Sim| Historico["Histórico administrativo"]
    Gravou -->|Não| Preservar["Preservar ação e registrar falha no servidor"]
    Admin --> Historico
    Historico --> Lista["Até 100 registros recentes"]
```

O histórico registra somente ações administrativas implementadas: criação, edição e exclusão de cursos e edição/exclusão de alunos. O cadastro público não gera log administrativo. A listagem associa cada log ao e-mail do administrador, em ordem decrescente de data e ID; falhas do log não desfazem a ação principal.

### Relatório de alunos

```mermaid
graph LR
    Admin["Admin"] --> Relatorio["Relatório e filtros"]
    Relatorio --> Alunos["Consultar alunos"]
    Alunos --> Conta["Vínculo com usuarios por usuario_id"]
    Conta --> Inscricoes["Inscrições da conta"]
    Inscricoes --> Cursos["Nomes dos cursos"]
    Cursos --> Agrupar["Agrupar nomes por aluno"]
    Agrupar --> Resultado["Uma linha por aluno"]
    Resultado --> Editar["Editar aluno"]
```

`listarAlunos()` parte de `alunos` e usa o ID da conta vinculada para consultar inscrições e cursos; não faz JOIN com `usuarios`. A subconsulta com `STRING_AGG` mantém uma linha por aluno, inclusive sem vínculo ou inscrições, exibindo `Sem curso`. Os filtros de curso e situação são opcionais; o filtro por curso mantém os demais cursos na mesma linha. A ordem é por ID crescente.

## Diagrama de casos de uso

Representação conceitual em `graph LR`: os atores ficam fora dos grupos de funções; as linhas sem seta ligam atores a casos de uso e as setas pontilhadas mostram decomposição ou navegação. Não representam herança de permissões nem relações UML formais.

```mermaid
graph LR
    Visitante["Visitante"] --- Publico["Início público"]
    Visitante --- Login["Login"]
    Visitante --- Cadastro["Cadastro vinculado"]
    Visitante --- Recuperar["Recuperação local"]
    Usuario["Usuário"] --- Login
    Usuario --- Dashboard["Dashboard"]
    Usuario --- Cursos["Catálogo e detalhes"]
    Usuario --- Inscricoes["Inscrição e Meus cursos"]
    Usuario --- Cancelar["Cancelar inscrição própria"]
    Usuario --- Favoritos["Salvar e remover favoritos"]
    Usuario --- Perfil["Perfil e dados próprios"]
    Usuario --- Logout["Logout"]
    Admin["Admin"] --- Login
    Admin --- Publico
    Admin --- Cursos
    Admin --- Inscricoes
    Admin --- Cancelar
    Admin --- Favoritos
    Admin --- Perfil
    Admin --- Logout
```

### Casos de uso administrativos

```mermaid
graph LR
    Admin["Admin"] --- Painel["Painel e totais"]
    Admin --- Consulta["Consultar aluno"]
    Admin --- Relatorio["Relatório e filtros"]
    Admin --- Edicao["Editar aluno"]
    Admin --- Exclusao["Excluir aluno"]
    Admin --- Vinculos["Cursos dos alunos"]
    Admin --- Usuarios["Consultar usuários"]
    Admin --- Historico["Histórico administrativo"]
    Admin --- Cursos["Listar e gerenciar cursos"]
    Cursos -.-> Criar["Criar curso"]
    Cursos -.-> Editar["Editar curso"]
    Cursos -.-> Excluir["Excluir curso sem inscrições"]
```

- **Visitante:** pessoa sem autenticação, com acesso à página pública, login, cadastro e solicitação de recuperação local para uma conta comum.
- **Usuário:** conta comum autenticada, com acesso ao dashboard, cursos, inscrições, cancelamento, favoritos e próprio perfil. Não gerencia alunos, contas, vínculos ou cursos administrativos.
- **Admin:** conta administrativa responsável por consultas e gestão de alunos e cursos, com acesso ao próprio perfil. Também pode consultar o histórico e usar as rotas de cursos e favoritos da própria conta; não participa da recuperação de senha.

Ver perfil e consultar dados próprios são aspectos da mesma página, não telas distintas. A criação de aluno ocorre no cadastro público com vínculo automático na mesma transação. A exclusão de aluno preserva a conta e suas inscrições; a exclusão de curso é bloqueada se houver inscrições.
