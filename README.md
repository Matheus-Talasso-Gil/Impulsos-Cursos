# Impulso Cursos

Sistema web fictício de gestão de alunos, desenvolvido com PHP, PDO e PostgreSQL.

## Funcionalidades

- Cadastro, consulta, edição e exclusão de alunos
- Pesquisa de aluno por ID ou CPF
- Relatório com filtros por turma e situação
- Validação matemática dos dígitos verificadores do CPF
- Cadastro e autenticação de usuários com senha protegida por hash
- Menus e páginas controlados pelo papel da conta

## Perfis de acesso

| Perfil | Acesso |
| --- | --- |
| Visitante | Login e cadastro de conta |
| Usuário | Início, perfil, cursos e sair |
| Administrador | Gestão de alunos, relatório, cursos, perfil e sair |

O cadastro público sempre cria contas do tipo `usuario`. A conta admin é promovida diretamente no banco e uma constraint limita esse papel ao e-mail autorizado. Após mudar o papel no banco, saia e entre novamente para atualizar a sessão. O perfil exibe o e-mail e o tipo da conta; cursos e gerenciamento de usuários ainda são páginas-base.

## Requisitos

- PHP com `pdo_pgsql` habilitado
- PostgreSQL
- `psql` para executar os scripts SQL pelo terminal

## Tecnologias

- PHP e PDO
- PostgreSQL
- HTML e CSS
- Git e GitHub

## Protótipos

Para abrir e visualizar os protótipos `.excalidraw` no VS Code, instale a extensão Excalidraw.

### Telas

<!-- markdownlint-disable MD033 -->
<details>
<summary>Tela inicial</summary>

![Tela inicial](prototipos/imagens/tela_inicial.png)
</details>
<details>
<summary>Tela de login</summary>

![Tela de login](prototipos/imagens/tela_login.png)
</details>
<details>
<summary>Tela de cadastro</summary>

![Tela de cadastro](prototipos/imagens/tela_cadastrar.png)
</details>
<details>
<summary>Tela de cadastro de aluno</summary>

![Tela de cadastro de aluno](prototipos/imagens/tela_cadastrar_aluno.png)
</details>
<details>
<summary>Tela de exclusão</summary>

![Tela de exclusão](prototipos/imagens/tela_excluir.png)
</details>
<details>
<summary>Tela de confirmação de exclusão</summary>

![Tela de confirmação de exclusão](prototipos/imagens/tela_confirmar_exclusao.png)
</details>
<details>
<summary>Tela de resultado da consulta</summary>

![Tela de resultado da consulta](prototipos/imagens/tela_resultado_da_consulta.png)
</details>

## Evolução do projeto no Trello

O desenvolvimento do **Impulso Cursos** foi acompanhado por um quadro Kanban no Trello.

As imagens abaixo registram a evolução das tarefas conforme os commits do projeto avançaram.

<details>
<summary><strong>Primeiros 7 commits</strong></summary>

Nesta etapa foi organizada a base inicial do projeto, incluindo estrutura, CRUD de alunos, login, documentação e versionamento.

![Trello - primeiros 7 commits](trello/primeiros7commits.png)

</details>

<details>
<summary><strong>Commits 8–15</strong></summary>

Continuação da documentação e criação dos diagramas do sistema e do banco de dados.

![Trello - commits 8 a 15](trello/commits8-15.png)

</details>

<details>
<summary><strong>Commits 16–21</strong></summary>

Evolução da documentação, dicionário de dados e segurança das senhas com hash.

![Trello - commits 16 a 21](trello/commits16-21.png)

</details>

<details>
<summary><strong>Commits 22–28</strong></summary>

Melhorias no cadastro de usuários, autenticação, mensagens e tratamento de erros.

![Trello - commits 22 a 28](trello/commits22-28.png)

</details>

<details>
<summary><strong>Commits 29–35</strong></summary>

Testes de autenticação e cadastro, além da criação dos protótipos das telas no Excalidraw.

![Trello - commits 29 a 35](trello/commits29-35.png)

</details>

<details>
<summary><strong>Commits 36–42</strong></summary>

Inclusão das imagens dos protótipos no README e documentação adicional das páginas do sistema.

![Trello - commits 36 a 42](trello/commits36-42.png)

</details>

<details>
<summary><strong>Commits 43–49</strong></summary>

Criação do briefing do cliente, melhorias de segurança na exibição de dados e revisão da conexão e do schema PostgreSQL.

![Trello - commits 43 a 49](trello/commits43-49.png)

</details>

<details>
<summary><strong>Commits 50–56</strong></summary>

Implementação de CPF no cadastro, edição, relatório e busca, além de melhorias nos filtros e no CSS.

![Trello - commits 50 a 56](trello/commits50-56.png)

</details>

<details>
<summary><strong>Commits 57–63</strong></summary>

Refatoração e organização do código, revisão dos arquivos de sessão/login e melhoria da estrutura dos scripts do banco de dados.

![Trello - commits 57 a 63](trello/commits57-63.png)

</details>

<details>
<summary><strong>Commits 64–70</strong></summary>

<br>

Implementação da validação matemática de CPF, criação do verificador de CPF, ajustes no cadastro de alunos e melhoria da documentação. Também foram adicionadas imagens do Trello ao README, instruções de uso mais completas e uma organização melhor para visualizar os protótipos.

![Trello - commits 64 a 70](trello/commits64-70.png)

</details>
<!-- markdownlint-enable MD033 -->

## Estrutura principal

```text
mini_sistema/
├── app/
│   ├── admin.php                 # área administrativa
│   ├── create.php                # cadastro de alunos
│   ├── cursos.php                # página de cursos
│   ├── delete.php                # exclusão de alunos
│   ├── select.php                # relatório/listagem de alunos
│   ├── select_w_w.php            # consulta individual
│   ├── tabela.md                 # documentação relacionada às tabelas
│   └── update.php                # edição de alunos
│
├── css/
│   └── style.css                 # estilos compartilhados do sistema
│
├── database/
│   ├── auto_destruicao/
│   │   └── reset_database.pgsql  # recriação destrutiva do banco em desenvolvimento
│   ├── adicionar_tipo_usuario.sql # adiciona nível de acesso aos usuários
│   ├── ajustar_senha.sql         # ajustes relacionados às senhas
│   ├── connect_postgres.php      # conexão com PostgreSQL
│   ├── table.pgsql               # criação das tabelas
│   └── verificar_user.php        # teste de cadastro e autenticação
│
├── includes/
│   ├── footer.php                # rodapé compartilhado
│   ├── functions.php             # funções reutilizadas pelo sistema
│   ├── header.php                # header adaptado conforme o tipo de usuário
│   └── session.php               # gerenciamento da sessão
│
├── login/
│   ├── cadastrar.php             # cadastro público de usuário
│   ├── login.php                 # autenticação
│   ├── logout.php                # encerramento da sessão
│   ├── perfil.php                # perfil do usuário
│   ├── verificar_admin.php       # proteção de páginas administrativas
│   ├── verificar_cpf.php         # validação/verificação de CPF
│   └── verificar_user.php        # proteção de páginas autenticadas
│
├── prototipos/
│   ├── imagens/
│   │   ├── tela_cadastrar_aluno.png
│   │   ├── tela_cadastrar.png
│   │   ├── tela_confirmar_exclusao.png
│   │   ├── tela_excluir.png
│   │   ├── tela_inicial.png
│   │   ├── tela_login.png
│   │   └── tela_resultado_da_consulta.png
│   ├── prototipo_impulso_cursos.excalidraw
│   ├── tela_cadastrar_aluno.excalidraw
│   ├── tela_cadastrar.excalidraw
│   ├── tela_confirmar_exclusao.excalidraw
│   ├── tela_consultar.excalidraw
│   ├── tela_excluir.excalidraw
│   ├── tela_inicial.excalidraw
│   ├── tela_login.excalidraw
│   └── tela_resultado_da_consulta.excalidraw
│
├── trello/
│   ├── primeiros7commits.png
│   ├── commits8-15.png
│   ├── commits16-21.png
│   ├── commits22-28.png
│   ├── commits29-35.png
│   ├── commits36-42.png
│   ├── commits43-49.png
│   ├── commits50-56.png
│   ├── commits57-63.png
│   └── commits64-70.png
│
├── briefing.md
├── diagrama.md
├── dicionario.md
├── documentacao.md
├── index.php
└── README.md
```

## Preparar o banco de dados

1. Crie o banco PostgreSQL que será usado pelo sistema.
2. Configure host, nome do banco, usuário e senha em `database/connect_postgres.php`.
3. No terminal aberto na pasta que contém `mini_sistema`, crie as tabelas iniciais:

    ```powershell
    psql -h HOST -U USUARIO -d BANCO -f mini_sistema/database/table.pgsql
    ```

Substitua `HOST`, `USUARIO` e `BANCO` pelos valores da sua instalação. `table.pgsql` cria tabelas ausentes sem apagar dados e aplica a estrutura de vínculo entre alunos e usuários.

Para atualizar um banco existente e permitir o acesso a **Meu perfil** e o vínculo de contas no painel administrativo, execute:

```powershell
psql -h HOST -U USUARIO -d BANCO -v ON_ERROR_STOP=1 -f mini_sistema/database/vincular_alunos_usuarios.sql
```

Essa migração adiciona `alunos.usuario_id`, suas restrições e a proteção de identidade, sem apagar registros. Depois, vincule o aluno à conta pelo painel administrativo.

Para atualizar um banco existente com o campo de papel, execute a migração:

```powershell
psql -h HOST -U USUARIO -d BANCO -f mini_sistema/database/adicionar_tipo_usuario.sql
```

O papel padrão é `usuario`. Para promover a conta autorizada, conecte-se ao banco e execute:

```sql
UPDATE usuarios
SET tipo = 'admin'
WHERE lower(email) = lower('EMAIL_AUTORIZADO');
```

A constraint `usuarios_admin_email_check` limita `admin` ao e-mail autorizado nos scripts de schema. Se trocar a conta autorizada, atualize essa constraint na migração e nos scripts de criação/reset.

> **Atenção:** `database/auto_destruicao/reset_database.pgsql` apaga e recria `alunos` e `usuarios`, perdendo os registros. Use somente em desenvolvimento e após confirmar o banco e fazer backup.

## Executar o sistema

1. Confirme que PHP está com `pdo_pgsql` habilitado e que `connect_postgres.php` está configurado.
2. No terminal aberto na pasta que contém `mini_sistema`, inicie o servidor:

    ```powershell
    php -S 127.0.0.1:8000
    ```

3. Acesse `http://127.0.0.1:8000/mini_sistema/`.
4. Cadastre um usuário em `http://127.0.0.1:8000/mini_sistema/login/cadastrar.php`. O cadastro público sempre cria uma conta comum.

## Acesso e segurança

- Visitantes veem somente Login e Cadastre-se no menu.
- Usuários comuns podem abrir perfil e cursos; tentativas de abrir páginas administrativas diretamente recebem HTTP 403.
- Administradores acessam as rotas de gestão protegidas por `login/verificar_admin.php`.
- O login verifica a senha com `password_verify()` e regenera o ID da sessão após autenticar.
- O CPF é validado matematicamente no cadastro e na edição. Isso confere os dígitos verificadores, mas não consulta a Receita Federal nem confirma titularidade.
- Cursos e perfil são páginas-base para expansão futura.

## Testes

Com o terminal na pasta que contém `mini_sistema`, rode o teste de cadastro/autenticação:

```powershell
php mini_sistema/database/verificar_user.php
```

O teste usa uma tabela temporária e faz rollback ao terminar. Para testar manualmente, confira o menu deslogado, crie uma conta comum, tente abrir `/mini_sistema/app/select.php` com ela e depois autentique com a conta admin autorizada.

## Senhas

As senhas são armazenadas com `password_hash()` e verificadas com `password_verify()`. A senha original não é gravada no banco.

## Publicar alterações no GitHub

Adicione apenas os arquivos que devem entrar no commit:

```powershell
git add caminho/do/arquivo
git commit -m "mensagem do commit"
git push origin main
```

## Autor

### Matheus Gil
