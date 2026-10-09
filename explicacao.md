# 📘 Explicação dos arquivos do Impulso Cursos

Este documento resume a responsabilidade dos arquivos importantes do projeto. Serve como guia para estudar o código, apresentar o sistema e encontrar onde realizar uma manutenção.

As descrições seguem o código atual. O sistema usa **PHP, HTML, CSS e PostgreSQL** para organizar contas, alunos, cursos e inscrições.

---

## 🧭 Visão geral

| Local | Responsabilidade |
| --- | --- |
| `app/` | Páginas de cursos, dashboard e gerenciamento administrativo. |
| `login/` | Cadastro, autenticação, perfil e recuperação de senha. |
| `includes/` | Funções, sessão e componentes compartilhados entre páginas. |
| `database/` | Conexão, estrutura do banco, migrações e testes pelo terminal. |
| `css/` | Aparência das páginas e adaptação a diferentes tamanhos de tela. |
| `prototipos/` | Desenhos das telas em Excalidraw e imagens de referência. |
| `trello/` | Imagens do acompanhamento das etapas do projeto. |
| Arquivos `.md` | Orientações, requisitos, diagramas e documentação. |

**Conta e aluno são registros diferentes:** a conta permite entrar no sistema; o aluno guarda os dados pessoais e escolares. O administrador confirma o vínculo entre esses registros.

## Árvore resumida do projeto

```text
impulsos_cursos/
├── app/
│   ├── dashboard.php
│   ├── admin.php
│   ├── cursos.php
│   ├── meus_cursos.php
│   ├── usuarios.php
│   └── vincular_conta.php
├── login/
│   ├── cadastrar.php
│   ├── login.php
│   ├── perfil.php
│   └── recuperar_senha.php
├── includes/
│   ├── functions.php
│   ├── session.php
│   ├── header.php
│   └── footer.php
├── database/
│   ├── connect_postgres.php
│   ├── table.pgsql
│   └── auto_destruicao/
│       └── reset_database.pgsql
├── css/
│   └── style.css
├── prototipos/
│   └── imagens/
├── trello/
├── index.php
├── privacidade.php
├── termos.php
├── README.md
├── documentacao.md
├── briefing.md
├── acompanhamento.md
├── dicionario.md
├── diagrama.md
└── explicacao.md
```

A árvore destaca os pontos de entrada. As tabelas abaixo explicam todos os arquivos PHP e os demais arquivos de apoio importantes.

---

## Páginas da raiz

| Arquivo | O que faz |
| --- | --- |
| [index.php](index.php) | Apresenta a Impulso Cursos aos visitantes. Uma conta comum autenticada é encaminhada ao dashboard. |
| [privacidade.php](privacidade.php) | Explica os dados utilizados pelo sistema e seus cuidados de privacidade. Lê a sessão existente para o menu sem renovar a atividade. |
| [termos.php](termos.php) | Apresenta as regras de utilização da plataforma. Também consulta a sessão apenas para montar o menu. |

## 🔐 `login/` — conta e acesso

| Arquivo | O que faz |
| --- | --- |
| [cadastrar.php](login/cadastrar.php) | Recebe nome, CPF, nascimento, e-mail e senha. Cria conta comum e aluno na mesma transação, com aluno ativo e turma `Sem turma`. O vínculo fica para o administrador. |
| [login.php](login/login.php) | Confere e-mail e senha, renova o identificador da sessão e guarda os dados de autenticação. Encaminha usuário ao dashboard e administrador ao painel. |
| [logout.php](login/logout.php) | Limpa os dados da sessão, encerra a autenticação e retorna à página inicial. |
| [perfil.php](login/perfil.php) | Mostra os dados da própria conta, sua data de criação e o aluno vinculado. Contas comuns também veem uma prévia de até três cursos. |
| [recuperar_senha.php](login/recuperar_senha.php) | Inicia a demonstração local de recuperação para contas comuns e apresenta um link para continuar. Não envia e-mail. |
| [redefinir_senha.php](login/redefinir_senha.php) | Valida a recuperação e recebe a nova senha com confirmação. Após salvar, o token deixa de valer. |
| [verificar_user.php](login/verificar_user.php) | Protege páginas que exigem autenticação. Visitantes são redirecionados ao login. |
| [verificar_admin.php](login/verificar_admin.php) | Exige autenticação e tipo `admin`. Contas sem essa permissão recebem acesso negado. |
| [verificar_cpf.php](login/verificar_cpf.php) | Define uma função que remove a máscara do CPF e retorna os dígitos quando a validação é aprovada. Não é uma tela de formulário. |

> A recuperação é uma demonstração acadêmica: funciona apenas por conexão local, na mesma sessão, com token válido por dez minutos e sem recuperação de contas administrativas.

## `app/` — páginas e funcionalidades

### Área da conta e cursos

| Arquivo | O que faz |
| --- | --- |
| [dashboard.php](app/dashboard.php) | Mostra o resumo da conta comum, totais de inscrições e cursos, pequenas listas de cursos e atalhos. Funciona mesmo sem aluno vinculado. |
| [cursos.php](app/cursos.php) | Lista o catálogo para contas autenticadas e permite inscrição. Indica os cursos em que a conta já está inscrita. |
| [curso.php](app/curso.php) | Mostra nome, descrição e carga horária de um curso. Também permite inscrição pela conta autenticada. |
| [meus_cursos.php](app/meus_cursos.php) | Lista as inscrições da própria conta e permite cancelar uma inscrição após confirmação. |
| [favoritos.php](app/favoritos.php) | Lista os cursos favoritos da própria conta por nome e permite removê-los. Mostra uma mensagem e acesso ao catálogo quando a lista está vazia. |

Nos detalhes do curso, **Favoritar** e **Remover dos favoritos** usam formulários POST protegidos por CSRF. O dashboard comum mostra o total e até três favoritos; favoritar não realiza uma inscrição.

As inscrições pertencem à **conta do usuário**, por isso não dependem de um aluno já vinculado. As páginas de catálogo, detalhes e Meus cursos também aceitam contas administrativas autenticadas.

### Administração de alunos e contas

| Arquivo | O que faz |
| --- | --- |
| [admin.php](app/admin.php) | Exibe totais de alunos, usuários, cursos e inscrições, com atalhos para a gestão. |
| [select.php](app/select.php) | Apresenta o relatório de alunos com filtros por turma e situação, indicação de vínculo e botão para editar. |
| [select_w_w.php](app/select_w_w.php) | Busca um aluno por ID ou CPF e apresenta seus dados. A busca por CPF aceita registros com ou sem máscara. |
| [update.php](app/update.php) | Busca e edita nome, turma, e-mail e situação do aluno. Preserva ID, CPF, nascimento e vínculo, com validação de sessão e CSRF. |
| [delete.php](app/delete.php) | Busca um aluno e pede confirmação para excluir. Se houver inscrições, exige uma confirmação adicional. Preserva a conta e os cursos inscritos. |
| [usuarios.php](app/usuarios.php) | Lista contas, tipos, datas de criação e alunos vinculados. Não exibe senhas nem oferece edição de contas. |
| [vincular_conta.php](app/vincular_conta.php) | Confere ID do aluno e e-mail da conta antes de confirmar um vínculo único e permanente. |
| [alunos_cursos.php](app/alunos_cursos.php) | Mostra os cursos dos alunos pelas contas vinculadas. Mantém na listagem alunos sem conta ou sem inscrições. |
| [create.php](app/create.php) | Mantém o endereço antigo de cadastro como redirecionamento: administrador segue para vínculo e demais acessos para cadastro público. |

### Administração de cursos

| Arquivo | O que faz |
| --- | --- |
| [cursos_admin.php](app/cursos_admin.php) | Lista cursos e quantidade de inscritos, com opções de cadastrar, editar e excluir. |
| [curso_create.php](app/curso_create.php) | Abre o formulário compartilhado no modo de cadastro de curso. |
| [curso_update.php](app/curso_update.php) | Abre o mesmo formulário no modo de edição de curso. |
| [curso_delete.php](app/curso_delete.php) | Confere o curso e pede confirmação para excluir. Bloqueia a exclusão quando existem inscrições. |

---

## `includes/` — partes reutilizadas

Esses arquivos concentram recursos usados por várias páginas, evitando repetir a mesma responsabilidade em cada tela.

| Arquivo | O que faz |
| --- | --- |
| [functions.php](includes/functions.php) | Reúne validação de CPF, operações com alunos e contas, consultas de cursos, inscrição e validações administrativas. O cadastro combinado usa transação para evitar registros parciais. |
| [session.php](includes/session.php) | Centraliza a sessão e encerra o acesso após mais de trinta minutos de inatividade, verificados na próxima requisição. |
| [header.php](includes/header.php) | Monta o cabeçalho e o menu conforme visitante, usuário ou administrador. A proteção das páginas é feita pelos verificadores de acesso. |
| [footer.php](includes/footer.php) | Monta o rodapé com links para privacidade e termos de uso. |
| [stylesheet.php](includes/stylesheet.php) | Calcula o endereço do CSS e inclui o estilo. Usa a data de alteração do arquivo para ajudar a atualizar o cache. |
| [acesso_negado.php](includes/acesso_negado.php) | Apresenta a página de acesso restrito com resposta HTTP 403. |
| [curso_admin_form.php](includes/curso_admin_form.php) | Compartilha o formulário e o processamento de cadastro e edição de cursos. Valida CSRF, nome e carga horária. |
| [data_conta.php](includes/data_conta.php) | Formata a data de criação da conta. Dados ausentes ou inválidos aparecem como `Não informada`. |
| [recuperacao_senha.php](includes/recuperacao_senha.php) | Reúne as proteções da recuperação local, criação e validação de tokens e gravação da nova senha em hash. |

## 🗄️ `database/` — banco e verificações

### Conexão e estrutura

| Arquivo | O que faz |
| --- | --- |
| [connect_postgres.php](database/connect_postgres.php) | Configura a conexão PDO com o PostgreSQL e trata falhas de conexão. |
| [table.pgsql](database/table.pgsql) | Cria tabelas ausentes de alunos, usuários, cursos e inscrições. Também aplica o vínculo e a proteção da identidade do aluno. |
| [adicionar_tipo_usuario.sql](database/adicionar_tipo_usuario.sql) | Adiciona o tipo de conta e as restrições para usuário e administrador em bancos existentes. |
| [adicionar_created_at_usuarios.sql](database/adicionar_created_at_usuarios.sql) | Adiciona a data de criação das contas. Contas antigas recebem o horário da execução da migração. |
| [vincular_alunos_usuarios.sql](database/vincular_alunos_usuarios.sql) | Adiciona o vínculo opcional entre aluno e conta, suas restrições e a proteção de ID, CPF, nascimento e vínculo já preenchido. |
| [ajustar_senha.sql](database/ajustar_senha.sql) | Amplia a coluna de senha para armazenar hashes. Não transforma senhas antigas em hash. |
| [adicionar_favoritos.sql](database/adicionar_favoritos.sql) | Cria a tabela `favoritos` em bancos existentes com ID, conta, curso e data. Impede duplicatas e limpa favoritos quando a conta ou o curso é excluído. A mesma estrutura está em `table.pgsql`. |
| [reset_database.pgsql](database/auto_destruicao/reset_database.pgsql) | Apaga e recria as tabelas de alunos e usuários para reiniciar dados de desenvolvimento. |

> **Atenção ao reset:** esse script apaga dados e não é uma instalação completa do sistema. A estrutura de cursos, inscrições e vínculo deve ser considerada separadamente.

### Testes existentes

Os arquivos abaixo são executados pelo **terminal** e usam tabelas temporárias para as verificações.

| Arquivo | O que verifica |
| --- | --- |
| [verificar_user.php](database/verificar_user.php) | Cadastro e autenticação, hashes, e-mails duplicados e criação apenas de contas comuns pelo cadastro público. |
| [verificar_alunos.php](database/verificar_alunos.php) | Edição de alunos, IDs maiores que 255, CSRF, conflito entre abas, identidade preservada, busca de CPF e entradas inválidas. |
| [verificar_created_at.php](database/verificar_created_at.php) | Migração da data de criação, sua preservação no cadastro e recuperação e a formatação apresentada nas telas. |
| [verificar_favoritos.php](database/verificar_favoritos.php) | Verifica favoritos, isolamento entre contas, CSRF, duplicidade, ordenação, exclusões e integração com as páginas usando tabelas temporárias. |

**Nomes parecidos têm papéis diferentes:** `login/verificar_user.php` protege páginas; `database/verificar_user.php` executa testes.

## Estilo, protótipos e acompanhamento

| Arquivo ou pasta | O que guarda |
| --- | --- |
| [css/style.css](css/style.css) | Cores, fontes, menus, formulários, tabelas, cartões, mensagens e regras de layout responsivo. |
| `prototipos/*.excalidraw` | Arquivos editáveis dos desenhos de telas e do protótipo geral. |
| `prototipos/imagens/` | Imagens das telas desenhadas para consulta na documentação. |
| `trello/` | Capturas do quadro de tarefas em diferentes etapas dos commits. |

## Documentação em Markdown

| Arquivo | Para que consultar |
| --- | --- |
| [README.md](README.md) | Conhecer o projeto, requisitos, instalação, execução e orientações de testes. |
| [documentacao.md](documentacao.md) | Estudar páginas, funções, conceitos e exemplos com mais detalhes. |
| [briefing.md](briefing.md) | Entender os objetivos, requisitos e melhorias desejadas para o projeto. |
| [acompanhamento.md](acompanhamento.md) | Consultar o registro de funcionalidades e pendências da revisão indicada no documento. |
| [dicionario.md](dicionario.md) | Entender os campos, tipos e restrições das tabelas do banco. |
| [diagrama.md](diagrama.md) | Visualizar relações entre dados, fluxos e casos de uso. |
| [app/tabela.md](app/tabela.md) | Consultar o diagrama específico da tabela de alunos. |
| [explicacao.md](explicacao.md) | Localizar rapidamente a responsabilidade de cada arquivo importante. |

Os documentos de requisitos e acompanhamento também contêm planos e informações históricas. Para confirmar o comportamento atual de uma funcionalidade, consulte o código correspondente.

---

## 🎓 Roteiro rápido de estudo

1. **Estrutura:** leia este guia e o `README.md`.
2. **Entrada no sistema:** acompanhe `login/login.php`, `includes/session.php` e os verificadores de acesso.
3. **Cadastro:** siga `login/cadastrar.php` até as funções de cadastro em `includes/functions.php`.
4. **Dados e vínculo:** observe `database/table.pgsql`, `app/vincular_conta.php` e `login/perfil.php`.
5. **Cursos e administração:** compare `app/cursos.php`, `app/meus_cursos.php` e as páginas administrativas.

### Como os arquivos se conectam

```text
cadastro → validar dados → salvar conta e aluno juntos → login
login → conferir senha → guardar sessao → dashboard ou painel
vinculo administrativo → confirmar conta e aluno → mostrar dados no perfil
inscricao → validar token e curso → salvar sem duplicar → meus cursos
```

### Cuidados que ajudam na manutenção

- Preserve os verificadores de acesso antes das páginas protegidas.
- Mantenha consultas preparadas, escape HTML e senhas em hash.
- Preserve tokens CSRF e confirmações antes de alterar ou excluir dados.
- Considere que excluir um aluno preserva sua conta e inscrições; excluir um curso exige ausência de inscrições.
- Mantenha cadastro de conta e aluno na mesma transação e preserve os campos imutáveis.
