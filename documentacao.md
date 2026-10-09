# Impulso Cursos — Documentação do sistema

Guia para executar, compreender e apresentar o projeto de gestão de alunos.

**Início rápido:** [README](README.md) · **Página inicial:** [index.php](index.php) · **Estilos:** [style.css](css/style.css)

## Como usar este guia

| Quero… | Onde encontrar |
| --- | --- |
| Rodar o site no computador | [Preparação e execução](#2-preparação-e-execução) |
| Encontrar a responsabilidade de um arquivo | [Estrutura](#3-estrutura-e-fluxo-de-dados) e [páginas](#4-referência-das-páginas) |
| Entender os dados e as consultas | [Banco e funções](#5-banco-de-dados-e-funções) |
| Aprender com exemplos do projeto | [Conceitos](#6-conceitos-usados-no-código) e [exemplos](#7-exemplos-explicados) |
| Conferir o funcionamento ou investigar um problema | [Testes](#8-conferência-manual) e [dúvidas frequentes](#9-dúvidas-frequentes) |
| Preparar uma explicação do trabalho | [Roteiro de estudo](#12-roteiro-de-estudo) |

## Sumário

1. [Visão geral](#1-visão-geral)
2. [Preparação e execução](#2-preparação-e-execução)
3. [Estrutura e fluxo de dados](#3-estrutura-e-fluxo-de-dados)
4. [Referência das páginas](#4-referência-das-páginas)
5. [Banco de dados e funções](#5-banco-de-dados-e-funções)
6. [Conceitos usados no código](#6-conceitos-usados-no-código)
7. [Exemplos explicados](#7-exemplos-explicados)
8. [Conferência manual](#8-conferência-manual)
9. [Dúvidas frequentes](#9-dúvidas-frequentes)
10. [Limitações da versão atual](#10-limitações-da-versão-atual)
11. [Histórico e backup](#11-histórico-e-backup)
12. [Roteiro de estudo](#12-roteiro-de-estudo)

---

## 1. Visão geral

A Impulso Cursos é uma empresa fictícia de educação. O sistema apresenta os cursos e permite cadastrar, consultar, atualizar e excluir alunos. Os usuários que acessam a gestão fazem login com e-mail e senha.

Esta documentação descreve os fluxos PHP atuais. As informações sobre o banco seguem o SQL e as consultas do projeto, não uma inspeção do servidor.

### Tecnologias utilizadas

| Tecnologia | Utilização |
| --- | --- |
| HTML | Estrutura das páginas, formulários e tabela do relatório. |
| CSS | Cores, espaçamento, cartões, campos e adaptação para celular em `css/style.css`. |
| PHP | Recebimento dos formulários, controle de sessão e operações no banco. |
| PDO | Conexão do PHP com o PostgreSQL e execução das consultas. |
| PostgreSQL | Armazenamento dos alunos e usuários. |

---

## 2. Preparação e execução

1. Tenha PHP com PDO e o driver PostgreSQL habilitados.
2. Tenha acesso ao servidor PostgreSQL e às tabelas necessárias.
3. Confira a configuração em `database/connect_postgres.php`.
4. Mantenha a pasta do projeto com o nome `impulsos_cursos`, usado nos links.
5. Abra o terminal na pasta que contém `impulsos_cursos` e execute:

   ```powershell
   php -S localhost:8000
   ```

6. Abra `http://localhost:8000/impulsos_cursos/index.php`.
7. Faça login para acessar as páginas de gestão.

Para instalações novas use `database/table.pgsql`. Em bancos existentes execute `database/vincular_alunos_usuarios.sql`; o comando está no [README](README.md#preparar-o-banco-de-dados). Essa migration adiciona o vínculo sem criar contas para alunos antigos ou alterar seus dados, IDs e sequences.

Abrir o PHP diretamente como arquivo no navegador não executa o código. O servidor PHP precisa estar rodando.

---

## 3. Estrutura e fluxo de dados

```text
impulsos_cursos/
├── app/
│   ├── admin.php                 # área administrativa
│   ├── alunos_cursos.php         # consulta dos cursos dos alunos
│   ├── create.php                # redirecionamento para cadastro/relatório
│   ├── curso.php                 # detalhes e inscrição em um curso
│   ├── curso_create.php          # cadastro de cursos
│   ├── curso_delete.php          # exclusão de cursos
│   ├── curso_update.php          # edição de cursos
│   ├── cursos.php                # catálogo de cursos
│   ├── cursos_admin.php          # gestão administrativa de cursos
│   ├── delete.php                # exclusão de alunos
│   ├── meus_cursos.php           # cursos da conta autenticada
│   ├── select.php                # relatório/listagem de alunos
│   ├── select_w_w.php            # consulta individual
│   ├── tabela.md                 # documentação relacionada às tabelas
│   ├── update.php                # edição de alunos
│   └── usuarios.php              # consulta de usuários e seus vínculos
│
├── css/
│   └── style.css                 # estilos compartilhados do sistema
│
├── database/
│   ├── auto_destruicao/
│   │   └── reset_database.pgsql  # recriação destrutiva do banco em desenvolvimento
│   ├── adicionar_tipo_usuario.sql # adiciona nível de acesso aos usuários
│   ├── adicionar_created_at_usuarios.sql # adiciona a data de criação das contas
│   ├── ajustar_senha.sql         # ajustes relacionados às senhas
│   ├── connect_postgres.php      # conexão com PostgreSQL
│   ├── table.pgsql               # criação das tabelas
│   ├── verificar_created_at.php  # teste isolado da data de criação das contas
│   ├── vincular_contas_existentes.sql # associa emails antigos sem ambiguidades
│   ├── verificar_cadastro_relatorio.php # regressao do cadastro e relatorio
│   ├── vincular_alunos_usuarios.sql # migração do vínculo opcional
│   └── verificar_user.php        # teste de cadastro e autenticação
│
├── includes/
│   ├── curso_admin_form.php      # formulário compartilhado de gestão de cursos
│   ├── data_conta.php            # formatação da data de criação da conta
│   ├── footer.php                # rodapé compartilhado
│   ├── functions.php             # funções reutilizadas pelo sistema
│   ├── header.php                # cabeçalho e menu conforme o tipo de usuário
│   ├── recuperacao_senha.php      # funções de recuperação de senha demonstrativa local
│   └── session.php               # gerenciamento da sessão
│
├── login/
│   ├── cadastrar.php             # cadastro público de usuário
│   ├── login.php                 # autenticação
│   ├── logout.php                # encerramento da sessão
│   ├── perfil.php                # perfil do usuário
│   ├── recuperar_senha.php       # solicitação de recuperação de senha
│   ├── redefinir_senha.php       # definição de uma nova senha
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
│   ├── commits64-70.png
│   ├── commits71-77.png
│   ├── commits78-84.png
│   └── commits85-91.png
│
├── briefing.md
├── diagrama.md
├── dicionario.md
├── documentacao.md
├── index.php
└── README.md
```

A árvore destaca os arquivos explicados neste guia; arquivos auxiliares de SQL e anotações não estão representados.

### Como uma página recebe e devolve informações

Veja o [fluxo de uma requisição](diagrama.md#fluxo-de-uma-requisição).

Esse diagrama resume as operações protegidas. Algumas páginas carregam a conexão antes de verificar o usuário, mas a operação do formulário ocorre depois da verificação. O navegador exibe o HTML gerado; as consultas SQL são executadas pelo PHP no servidor.

---

## 4. Referência das páginas

### 4.1. Página inicial

**`index.php`** inicia a sessão e inclui o cabeçalho e o rodapé. Apresenta o nome da empresa, o slogan e três cartões: Informática Básica, Inglês e Administração. Os textos e as durações estão escritos no HTML; não são carregados do banco. A página não exige login.

### 4.2. Componentes compartilhados

| Arquivo | Função |
| --- | --- |
| `header.php` | Visitantes veem login e cadastro de conta. Contas comuns veem início, cursos, perfil e sair. Admins também veem gestão de alunos. |
| `footer.php` | Exibe o texto do rodapé. |
| `session.php` | Verifica se existe uma sessão ativa e chama `session_start()` quando necessário. |
| `functions.php` | Carrega a conexão com o banco e reúne as funções de cadastro, consulta, atualização, exclusão e busca de usuários. |

### 4.3. Conexão com o banco

**`connect_postgres.php`** define host, nome do banco, usuário e senha e cria o objeto `$conexao` com `new PDO(...)`. O banco configurado no código é `escola`. Se ocorrer uma exceção de conexão, o bloco `catch` mostra a mensagem de erro. As credenciais devem ser consultadas no próprio arquivo; não são repetidas aqui.

### 4.4. Acesso e sessão

| Arquivo | Funcionamento |
| --- | --- |
| `login.php` | Busca a conta pelo e-mail e verifica o hash com `password_verify()`. Renova o ID da sessão e guarda ID, e-mail e tipo da conta antes de redirecionar ao início. |
| `cadastrar.php` | Cria uma conta comum com senha em hash e redireciona ao login. Cria também o aluno com usuario_id preenchido na mesma transação; não realiza login automático. |
| `verificar_user.php` | Verifica a sessão. Se `$_SESSION['id']` não existir, redireciona para o login e encerra a execução com `exit()`. |
| `verificar_admin.php` | Exige login e papel admin. Contas comuns recebem HTTP 403. |
| `perfil.php` | Busca o aluno usando exclusivamente `$_SESSION['id']`. Exibe seus dados ou informa ausência de vínculo. Senha e CPF não são consultados nessa página. |
| `logout.php` | Limpa os dados de sessão, destrói a sessão e redireciona para `/impulsos_cursos/index.php`. |

### 4.5. Gestão de alunos

Todas as páginas PHP da pasta `app/` exigem login. Gestão de alunos exige admin; cursos aceitam contas comuns e administradores.

#### create.php — Compatibilidade

Redireciona administradores para o relatório e demais acessos para o cadastro público. Conta e aluno são criados por cadastrar_aluno_usuario na mesma transação.

#### `select.php` — Relatório

Chama listarAlunos com filtros de curso e situação. Mostra ID, nome, CPF, nascimento, cursos, e-mail, situação e ações em ordem de ID. STRING_AGG reúne as inscrições em uma única linha por aluno. Alunos sem conta ou sem inscrições aparecem como Sem curso.

Cada botão Editar pertence a um formulário que envia o ID por POST para `update.php`. O ID vai em um campo `hidden`, que não aparece na tela.

#### `update.php` — Edição

1. Recebe o ID enviado pelo relatório ou pela busca da própria página.
2. Executa um SELECT para carregar o aluno.
3. Preenche os campos com os dados encontrados e marca a opção ativa correspondente.
4. Quando recebe também o campo `nome`, chama `Atualizar()` para salvar os dados.
5. Consulta novamente o registro e mantém o formulário preenchido com os dados do banco.

A confirmação exibida é “ALUNO ATUALIZADO COM SUCESSO! VOLTE AO RELATÓRIO PARA CONFERIR.”. Não há redirecionamento automático ao relatório. O botão Restaurar campos repõe os valores com que o formulário foi carregado, sem alterar o banco.

O ID original fica na sessão. ID, CPF e nascimento são exibidos sem edição; a função grava somente nome, turma, situação e e-mail. A proteção no banco também impede alterar a identidade e trocar um vínculo preenchido.

#### `delete.php` — Exclusão com confirmação

1. O usuário informa o ID e clica em Continuar para exclusão.
2. A página busca o aluno e mostra ID, nome e turma.
3. Confirmar exclusão valida o token CSRF e os dados pendentes na sessão.
4. Se a conta tem inscrições, uma segunda tela na própria página lista os cursos e pede “Excluir aluno mesmo com cursos”. A primeira confirmação não exclui.
5. Após as confirmações necessárias, chama `apagar($conexao, $id)` e remove somente o aluno. A conta e suas inscrições continuam existindo; o perfil perde o vínculo com o cadastro excluído.

Cancelar abre `delete.php` e limpa as confirmações pendentes. Se as inscrições ou o vínculo mudarem entre requisições, os dados devem ser conferidos novamente. Se o aluno não existir, mostra “Aluno não encontrado”. Após excluir, volta à busca. O campo de ID aceita inteiros positivos até 2147483647, limite da coluna INTEGER do PostgreSQL.

#### `select_w_w.php` — Consulta por ID ou CPF

É a página aberta pelo menu Consultar. Permite buscar por ID inteiro positivo até 2147483647 ou por CPF com ou sem pontuação, inclusive nos registros antigos. Reutiliza `read_w_w()` para mostrar o cadastro e oferece um link ao relatório.



#### `cursos.php` — Inscrições

Lista cursos do banco e permite inscrever a conta identificada por `$_SESSION['id']`. Ignora um `usuario_id` enviado pelo navegador. A constraint do par usuário–curso e o `ON CONFLICT` impedem inscrições duplicadas. Contas sem aluno também podem se inscrever.

### 4.6. Cursos e administração

| Arquivo em `app/` | Funcionamento |
| --- | --- |
| `curso.php` | Exibe detalhes pelo ID informado no endereço e permite inscrição com token CSRF. ID inválido recebe HTTP 400; curso inexistente recebe HTTP 404. |
| `meus_cursos.php` | Consulta somente as inscrições da conta identificada pela sessão; sem inscrições, exibe uma mensagem. |
| `admin.php` | Painel administrativo com totais de alunos, usuários, cursos e inscrições. |
| `usuarios.php` | Consulta administrativa de ID, e-mail, tipo e aluno vinculado; não consulta senhas. |
| `alunos_cursos.php` | Consulta administrativa dos cursos dos alunos. |
| `cursos_admin.php` | Lista cursos e quantidade de inscritos, com acesso ao cadastro, edição e exclusão. |
| `curso_create.php` e `curso_update.php` | Exigem admin e token CSRF; validam nome e carga horária positiva no servidor. |
| `curso_delete.php` | Exige admin, confirmação e token CSRF. Cursos com inscrições não podem ser excluídos; nenhuma inscrição é apagada. |

Catálogo, detalhes e Meus cursos aceitam contas comuns e administradores. As demais páginas desta tabela exigem admin. A inscrição pelo catálogo também valida token CSRF.

---

## 5. Banco de dados e funções

### 5.1. Dados utilizados

O código utiliza quatro tabelas:

| Tabela | Campos usados | Finalidade |
| --- | --- | --- |
| `alunos` | `id`, `nome`, `cpf`, `nasc`, `turma`, `ativo`, `email`, `usuario_id` | Cadastro com conta opcional. |
| `usuarios` | `id`, `email`, `senha`, `tipo` | Contas e níveis de acesso. |
| `cursos` | `id`, `nome`, `descricao`, `carga_horaria` | Cursos disponíveis. |
| `inscricoes` | `id`, `usuario_id`, `curso_id` | Relação única entre conta e curso. |

O campo `ativo` representa a situação do aluno e não determina acesso à conta. `alunos.turma` é texto e não gera inscrições. `usuario_id` aceita NULL e possui FK e índice único parcial; alunos antigos continuam no relatório sem conta. Consulte o [dicionário](dicionario.md) e os [diagramas](diagrama.md).

### 5.2. Funções de acesso aos dados

Arquivo: [includes/functions.php](includes/functions.php).

| Função | O que faz |
| --- | --- |
| `cadastrar($conexao, $nome, $turma, $nasc, $ativo, $email, $cpf)` | Insere um aluno. Função legada; o cadastro público utiliza cadastrar_aluno_usuario. |
| `listarAlunos($conexao, $cursoId = '', $situacao = 'todas')` | Lista alunos em ordem de ID com filtros opcionais. |
| `apagar($conexao, $id)` | Exclui somente o aluno e verifica as linhas afetadas. As confirmações ficam em `delete.php`. |
| `Consultar($conexao, $id)` | Função disponível para buscar e exibir um aluno. |
| `Atualizar($conexao, $id, $nome, $turma, $nasc, $ativo, $email, $cpf)` | Mantém a assinatura existente e grava somente nome, turma, situação e e-mail. |
| `read_w_w($conexao, $id)` | Busca e exibe os dados do aluno e um link para voltar ao início. |
| `cadastrar_user($conexao, $email, $senha)` | Valida e-mail e duplicatas e cria uma conta comum com senha em hash e retorna o ID obtido por RETURNING id. |
| `consultar_user($conexao, $email)` | Retorna ID, e-mail, hash e tipo para autenticação. |

---

## 6. Conceitos usados no código

- `include`: inclui um arquivo, como o cabeçalho ou rodapé.
- `require_once`: carrega um arquivo necessário apenas uma vez na execução.
- `__DIR__`: representa a pasta do arquivo PHP no computador. É usado para localizar arquivos incluídos.
- `header('Location: ...')`: envia um redirecionamento para uma URL. Não usa um caminho físico do disco.
- `$_SERVER['REQUEST_METHOD']`: permite verificar se a página recebeu POST.
- `$_POST`: contém os campos enviados pelo formulário, identificados pelo atributo `name`.
- `isset`: verifica se uma variável ou campo existe e não é nulo.
- `$_SESSION`: guarda informações associadas à sessão do visitante entre requisições.
- `prepare`: prepara o SQL com parâmetros como `:id`.
- `bindParam`: vincula uma variável ao parâmetro da consulta.
- `execute`: executa o comando preparado.
- `fetch(PDO::FETCH_ASSOC)`: obtém uma linha com os nomes das colunas como chaves.
- `fetchAll(PDO::FETCH_ASSOC)`: obtém todas as linhas do resultado.
- `htmlspecialchars`: transforma caracteres especiais para exibir texto no HTML. É usado, por exemplo, no relatório e no formulário de edição.
- `echo` e `<?= ... ?>`: escrevem conteúdo na resposta que o navegador recebe.

No HTML, `label` identifica o campo; `input` recebe um valor; `select` apresenta opções. O atributo `required` pede ao navegador que exija preenchimento. A classe CSS liga um elemento a regras de aparência: `class="course-card"`, por exemplo, identifica os cartões dos cursos.

---

## 7. Exemplos explicados

### 7.1. Do campo do formulário ao PHP

Trecho da edição de aluno:

```html
<label for="turma">Turma:</label>
<select name="turma" id="turma" required>
    <option value="" selected disabled>Selecione a turma</option>
    <option value="INF-01">INF-01 — Informática Básica</option>
    <option value="ING-01">ING-01 — Inglês</option>
    <option value="ADM-01">ADM-01 — Administração</option>
</select>
```

`for="turma"` conecta o texto do `label` ao campo que tem `id="turma"`. Já `name="turma"` define o nome enviado ao PHP. Ao escolher Inglês, o navegador envia o valor `ING-01`, que fica disponível em `$_POST['turma']`. O texto completo da opção é apenas o que o usuário vê.

### 7.2. Buscar um aluno no banco

Trecho usado na página de exclusão, depois que o ID é recebido:

```php
$sql = 'SELECT * FROM alunos WHERE id = :id';
$stmt = $conexao->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);
```

| Etapa | Explicação |
| --- | --- |
| `SELECT ... WHERE id = :id` | Procura o registro com o ID informado. |
| `prepare($sql)` | Prepara a consulta, deixando o valor separado do SQL. |
| `bindParam(':id', $id)` | Liga o parâmetro à variável que contém o ID. |
| `execute()` | Envia a consulta para execução. |
| `fetch(...)` | Obtém um registro; sem resultado, retorna `false`. |

Quando a busca encontra um aluno, `$aluno['nome']` acessa seu nome. A chave `nome` corresponde à coluna retornada pelo banco.

### 7.3. Por que buscar não exclui?

A primeira etapa só envia o ID. O formulário seguinte contém este botão:

```html
<input type="submit" name="confirmar" value="Confirmar exclusão">
```

A página valida o token e os dados guardados na sessão antes de chamar `apagar($conexao, $id)`. Quando existem cursos, guarda uma segunda confirmação na sessão e exige `confirmar_cursos`. Enviar esse campo diretamente não pula a tela de aviso. A atribuição `$aluno = false` volta à busca depois da exclusão; ela não apaga registros.

Veja o [diagrama de exclusão com confirmação](diagrama.md#exclusão-com-confirmação).

### 7.4. Buscar para editar é diferente de salvar

O botão Editar do relatório envia somente o ID. A página carrega os dados, mas não altera o banco nessa etapa. Quando o formulário preenchido é enviado, chega também o campo `nome`:

```php
if ($aluno && isset($_POST['nome'])) {
    Atualizar($conexao, $id, $_POST['nome'], $_POST['turma'],
        $nasc, $_POST['ativo'], $_POST['email'], $cpf);
    $stmt->execute([':id' => $id]);
    $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
}
```

A função grava somente os campos permitidos; `$nasc` e `$cpf` vêm da sessão e não são atualizados. As duas linhas seguintes recuperam os dados salvos. Por isso, continuar no formulário preenchido depois de atualizar é esperado.

### 7.5. HTML e CSS no cartão de um curso

```html
<article class="course-card">
    <h3>Inglês</h3>
    <p>Desenvolva vocabulário e pratique conversas para situações do dia a dia.</p>
    <p class="course-duration">Duração: 12 meses</p>
</article>
```

`article` agrupa o conteúdo de um curso; `h3` é o título e `p` cria um parágrafo. A classe `course-card` permite aplicar as regras de `.course-card` no CSS. O texto apareceria mesmo sem essa classe: ela organiza a aparência, não cria as palavras.

### 7.6. Caminho de arquivo e endereço do navegador

```php
require_once __DIR__ . '/../includes/functions.php';
```

Esse caminho é resolvido no computador que executa o PHP. `..` significa subir uma pasta.

```php
header('Location: /impulsos_cursos/index.php');
exit();
```

Esse endereço é enviado ao navegador. `exit()` impede que o restante da página continue sendo executado após o redirecionamento.

---

## 8. Conferência manual

| Ação | Resultado esperado |
| --- | --- |
| Abrir uma página de gestão sem login | Redirecionamento ao login. |
| Entrar com credenciais válidas | Acesso ao início com sessão autenticada. |
| Cadastrar um aluno de teste | Mensagem de sucesso e registro no relatório. |
| Abrir o relatório | IDs em ordem crescente. |
| Editar pelo relatório | Formulário preenchido; salvar mostra a confirmação. |
| Consultar um ID existente | Exibição dos dados correspondentes. |
| Consultar um ID inexistente válido | Mensagem informando que não há registro. |
| Buscar para excluir e cancelar | O aluno permanece no relatório. |
| Confirmar a exclusão de um aluno de teste | Registro removido do relatório. |
| Abrir gestão com conta comum | HTTP 403. |
| Abrir perfil sem vínculo | Mensagem simples informando ausência de cadastro de aluno. |
| Cadastro público | Conta e aluno associados automaticamente na mesma transação. |
| Reutilizar conta já vinculada | Operação rejeitada sem substituir o vínculo existente. |
| Conferir ID, CPF e nascimento após editar | Valores originais preservados. |
| Inscrever uma conta sem aluno em um curso | Inscrição funciona e não cria aluno automaticamente. |
| Repetir inscrição no mesmo curso | Nenhuma inscrição duplicada. |
| Confirmar exclusão de aluno com cursos pela primeira vez | Cursos listados e nova confirmação exigida. |
| Cancelar a segunda confirmação | Cadastro permanece. |
| Concluir exclusão de aluno de teste com cursos | Somente aluno removido; conta e inscrições preservadas. |
| Clicar em Sair | Sessão encerrada; páginas protegidas passam a exigir login. |
| Cadastrar e editar curso como admin | Nome, descrição e carga horária salvos aparecem no catálogo e nos detalhes. |
| Enviar carga horária zero ou negativa | Validação rejeita a gravação. |
| Abrir gestão de cursos ou usuários com conta comum | HTTP 403. |
| Consultar Meus cursos com duas contas diferentes | Cada conta vê somente suas próprias inscrições. |
| Confirmar exclusão de curso de teste sem inscrições | Curso removido da gestão e do catálogo. |
| Tentar excluir curso com inscrições | Exclusão bloqueada; curso e inscrições preservados. |
| Enviar inscrição ou gestão de curso sem token CSRF válido | Solicitação rejeitada sem gravar a alteração. |
| Recuperar senha de conta comum de teste em localhost | Nova senha funciona; senha antiga falha; não há login automático. |
| Reutilizar token após troca ou aguardar mais de 10 minutos | Redefinição rejeitada. |
| Solicitar recuperação para e-mail inexistente ou admin | Solicitação mantém resposta genérica; redefinição não altera nenhuma senha. |
| Confirmar senhas diferentes ou com menos de 8 caracteres | Redefinição rejeitada sem alterar a senha. |
| Abrir recuperação por conexão fora de loopback | HTTP 403. |

Este roteiro é uma orientação de teste, não um registro de testes executados durante a documentação.

---

## 9. Dúvidas frequentes

| Situação | Explicação e verificação |
| --- | --- |
| Clicar em Cadastrar, Excluir ou Relatório volta ao login | As páginas exigem `$_SESSION['id']`. Entre antes de usá-las. |
| O endereço muda, mas a página não abre corretamente | Confira se o servidor foi iniciado na pasta que contém `impulsos_cursos`. Os links começam com `/impulsos_cursos/`. |
| Depois de atualizar, continuo com os campos preenchidos | Esse é o fluxo atual. Confira a mensagem de sucesso e volte ao relatório para ver o registro salvo. |
| O formulário não envia | Confira campos obrigatórios, formato de e-mail e limites numéricos indicados pelo navegador. |
| Não consigo localizar um aluno por ID | Informe um inteiro positivo até 2147483647 e confira se o cadastro ainda existe. |
| O visual antigo continua aparecendo | Atualize com `Ctrl + F5` e confira se o CSS está sendo carregado. |
| Aparece erro de conexão com o banco | Verifique se o servidor PostgreSQL está acessível e se a conexão está configurada corretamente. |
| Perfil não consegue consultar cadastro de aluno | Confira a conexão e se `vincular_alunos_usuarios.sql` foi executada. O detalhe técnico fica no log. |
| Vínculo não foi confirmado | Confira se aluno e conta estão disponíveis e se a conta manteve o mesmo e-mail desde a conferência. |

---

## 10. Limitações da versão atual

- Novas senhas são gravadas com `password_hash()` e verificadas com `password_verify()`. Antes de usar, execute `database/ajustar_senha.sql` para ampliar o campo para `VARCHAR(255)`. Senhas antigas em texto precisam ser convertidas ou redefinidas; o login não aceita texto puro armazenado no banco.
- A edição de aluno valida nome, turma, e-mail e situação no servidor. Os formulários de busca, edição e exclusão aceitam IDs positivos até 2147483647.
- Para verificar as correções de edição, CSRF, abas diferentes, CPF com pontuação e entradas inválidas, execute `php impulsos_cursos/database/verificar_alunos.php` a partir da pasta pai do projeto. O teste usa tabelas temporárias e rollback, preservando os cadastros reais.
- O cadastro inicia o campo legado turma como Sem turma; esse campo não determina inscrições.
- Cadastro, edição e exclusão de aluno, vínculo, inscrição em cursos, gestão de cursos e recuperação de senha possuem token CSRF. A edição também confere se o aluno do formulário continua sendo o selecionado na sessão, impedindo salvar em outro aluno ao alternar abas. A cobertura não inclui todos os formulários do sistema.
- A atualização não confere as linhas afetadas antes de mostrar sucesso; exclusão e vínculo conferem.
- Atualizar a página após um POST pode solicitar o reenvio do formulário, pois não há redirecionamento após todas as operações.

Essas observações descrevem a implementação encontrada; gerar esta documentação não modifica esses comportamentos.

---

## 11. Histórico e backup

O Git registra versões dos arquivos e o GitHub pode armazenar uma cópia do repositório. É necessário fazer commit e push após novas alterações para atualizar essa cópia. Os registros do PostgreSQL não são incluídos automaticamente: precisam de backup próprio.

---

## 12. Roteiro de estudo

Uma ordem prática para entender o código é começar pela tela e acompanhar o caminho dos dados:

1. Leia `index.php`, `header.php` e `footer.php` para entender a montagem das páginas.
2. Leia `create.php` e identifique como o atributo `name` vira uma chave em `$_POST`.
3. Leia `connect_postgres.php` e acompanhe `prepare`, `bindParam` e `execute` no cadastro.
4. Leia `select.php` e `listarAlunos()` para entender o `foreach` e a ordem dos IDs.
5. Siga o botão Editar até `update.php` e separe a etapa de carregar da etapa de salvar.
6. Leia `delete.php` e explique por que o campo `confirmar` muda a ação executada.
7. Leia `session.php`, `login.php`, `verificar_user.php` e `logout.php` para acompanhar o acesso do usuário.

### Perguntas para conferir o entendimento

- Qual é a diferença entre cadastrar um aluno e cadastrar um usuário?
- Por que o banco recebe `ING-01`, e não o texto inteiro da opção?
- Qual comando ordena o relatório por ID?
- Por que o botão Editar não salva imediatamente?
- O que acontece ao clicar em Cancelar na exclusão?
- Qual informação da sessão permite entrar nas páginas protegidas?
- Por que `__DIR__` é usado nos includes e não no endereço de redirecionamento?

### Exemplo de explicação do projeto

> O sistema gerencia alunos de uma empresa fictícia de cursos. O HTML apresenta os formulários, o CSS define a aparência e o PHP recebe os dados. Para salvar ou buscar registros, o PHP usa PDO para executar consultas no PostgreSQL. A sessão identifica o usuário conectado. O relatório permite acessar a edição de um aluno, e a exclusão pede confirmação antes de apagar o cadastro.

## Recuperação de senha (demonstração local)

Na tela Entrar, o link **Esqueci minha senha** abre a recuperação para contas cujo tipo é exatamente `usuario`. Administradores e futuros funcionários não são elegíveis. A solicitação apresenta a mesma mensagem e opção de continuar para qualquer e-mail; a redefinição bloqueia contas não elegíveis com aviso genérico.

O token é gerado com `random_bytes()`, fica na sessão, dura 10 minutos e é removido após uma troca bem-sucedida. Os dois formulários têm CSRF independente do token de recuperação. O tipo da conta é consultado novamente antes da troca e também exigido no `UPDATE`, que altera somente `usuarios.senha`. A senha precisa ter pelo menos 8 caracteres (até 72 bytes) e é armazenada com `password_hash()`. Não há login automático nem novas tabelas.

**Limitação:** como não há envio de e-mail ou verificação de identidade, o fluxo permite redefinir uma conta comum conhecendo seu e-mail. Por isso, ambas as páginas aceitam somente conexões de loopback (`127.0.0.1` ou `::1`), exibem aviso de demonstração e devem ser usadas com contas de teste. Não use esse mecanismo como recuperação pública em produção. Usuários já autenticados são encaminhados ao perfil sem destruir a sessão.

Para testar no próprio computador: abra o site por `localhost`, saia da conta, clique em Esqueci minha senha, informe o e-mail de uma conta comum de teste, continue e confirme uma nova senha. Entre com a nova senha; a antiga deve falhar. Reabra o mesmo link para conferir que o token não pode ser reutilizado. E-mails inexistentes, administrativos e de funcionários não permitem troca.

## Cadastro automático e relatório de cursos

O cadastro usa RETURNING id e salva alunos.usuario_id na mesma transação. O relatório mostra **Cursos**, com STRING_AGG e uma linha por aluno; sem inscrições mostra **Sem curso**. O filtro usa cursos cadastrados e EXISTS, mantendo visíveis todos os cursos do aluno encontrado. A coluna Conta e a etapa administrativa de vinculação foram removidas.

Para registros antigos, [vincular_contas_existentes.sql](database/vincular_contas_existentes.sql) compara lower(trim(email)). Somente correspondências únicas nos dois lados e contas livres são preenchidas. Vínculos existentes, casos ambíguos e dados pessoais são preservados. Execute após a migration estrutural, se o banco for antigo:

~~~sh
psql -h HOST -U USUARIO -d BANCO -v ON_ERROR_STOP=1 -f impulsos_cursos/database/vincular_contas_existentes.sql
php impulsos_cursos/database/verificar_cadastro_relatorio.php
~~~
