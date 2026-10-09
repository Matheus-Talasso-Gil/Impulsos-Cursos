# Briefing do projeto Impulso Cursos

## Visão do proprietário

Quero um sistema simples e confiável para organizar os alunos, as contas e os cursos da Impulso Cursos. Os alunos devem criar o próprio cadastro e acompanhar suas inscrições. A equipe deve conseguir realizar as tarefas comuns rapidamente, encontrar informações com facilidade e manter os dados protegidos. O site precisa funcionar bem em computadores e celulares.

## Objetivos

- Centralizar os dados dos alunos.
- Facilitar matrícula, consulta, atualização e exclusão de cadastros.
- Reduzir erros por meio de validação e mensagens claras.
- Permitir que a equipe acompanhe a situação dos alunos e das turmas.
- Permitir que os alunos consultem os cursos, realizem inscrições e acompanhem seus dados pessoais.

## Usuários

- **Visitante:** acessa a apresentação da empresa, o login e o cadastro público.
- **Aluno/usuário:** cria uma conta comum, consulta seu perfil e gerencia suas próprias inscrições em cursos.
- **Administrador:** gerencia alunos e cursos, consulta contas e alunos associados automaticamente; futuramente, gerencia também as permissões da equipe.
- **Funcionário autorizado:** realiza operações com alunos conforme as permissões definidas.

## Requisitos principais

### Acesso

- Login para áreas administrativas.
- Encerramento de sessão.
- Mensagens claras para falhas de autenticação.
- Acesso restrito a usuários autorizados.
- Menus específicos para visitante, aluno e administrador; impedir acesso direto de contas comuns às páginas administrativas.
- O cadastro público deve criar apenas contas comuns. A concessão inicial de acesso administrativo deve ser controlada no banco de dados.

### Alunos

- Quero que o próprio aluno se cadastre em **Cadastre-se**, informando nome, CPF, data de nascimento, e-mail e senha. O administrador não deve cadastrar pessoas pelo painel.
- Criar a conta e o cadastro de aluno juntos, sem salvar apenas um deles quando ocorrer uma falha.
- Não pedir turma no cadastro público: o aluno deve começar ativo e sem turma; os cursos são definidos pelas inscrições e o administrador altera a situação depois.
- Validar os dígitos verificadores do CPF e impedir cadastro público com CPF ou e-mail já cadastrado.
- Validar campos obrigatórios e formatos antes de salvar.
- Consultar alunos em uma lista organizada.
- Pesquisar por nome, e-mail ou ID e filtrar por curso e situação.
- Editar os dados de um aluno e confirmar o resultado da operação.
- Exibir os dados e pedir confirmação antes de excluir um aluno.
- Mostrar uma mensagem apropriada quando a busca não encontrar resultados.
- Permitir também a consulta individual por CPF.
- Permitir editar nome, e-mail e situação, preservando ID, CPF e nascimento após o cadastro.

### Contas e perfil

- Quero que o administrador consulte as contas cadastradas e veja quais possuem aluno vinculado, sem exibir senhas.
- O cadastro deve salvar automaticamente o ID da conta no aluno na mesma transação, mantendo um vínculo único e permanente.
- Manter cadastros antigos sem conta no relatório; a migration só associa correspondências únicas de e-mail.
- Mostrar no perfil somente os dados do aluno vinculado à conta autenticada e uma prévia dos seus cursos. Informar quando ainda não houver vínculo.
- Ao excluir um aluno, preservar a conta e suas inscrições. Se houver cursos inscritos, listar os cursos e pedir uma confirmação adicional antes da exclusão.

### Cursos e inscrições

- Quero que o administrador cadastre, edite e exclua cursos com nome, descrição e carga horária, validando nome obrigatório e carga horária positiva.
- Oferecer aos usuários autenticados um catálogo de cursos e uma página com os detalhes de cada curso.
- Permitir inscrição pela própria conta, impedir inscrição duplicada e indicar quando o usuário já estiver inscrito.
- Disponibilizar **Meus cursos**, com as inscrições da conta autenticada.
- Permitir cancelar a própria inscrição após confirmação e realizar uma nova inscrição posteriormente.
- Disponibilizar uma consulta administrativa dos cursos dos alunos, mantendo visíveis também os alunos sem conta ou sem inscrições.

### Relatórios e navegação

- Exibir um relatório com os dados principais dos alunos.
- Indicar claramente quando não houver cadastros ou resultados.
- Manter navegação consistente entre as telas e oferecer retorno visual após cada ação.
- Oferecer um painel administrativo com totais de alunos, usuários, cursos e inscrições e atalhos para as tarefas de gestão.
- Indicar alunos ativos e inativos e cadastros sem conta vinculada.

### Recuperação de senha

- Quero uma demonstração local de recuperação de senha para contas comuns de teste, com token temporário, confirmação da nova senha e bloqueio para contas administrativas.
- Nesta etapa, a demonstração deve funcionar apenas no próprio computador, sem envio de e-mail. A recuperação para uso real deve ser uma evolução com entrega por um canal verificado.

## Requisitos de qualidade e segurança

- Interface clara, responsiva e fácil de usar por pessoas sem conhecimento técnico.
- Formulários com rótulos e mensagens compreensíveis.
- Validar os dados no servidor, mesmo que também haja validação no navegador.
- Armazenar senhas com hash; nunca guardar senhas em texto puro.
- Usar consultas preparadas para acessar o banco de dados.
- Escapar dados exibidos em HTML para reduzir riscos de injeção de conteúdo.
- Proteger operações de alteração contra solicitações forjadas e usar a sessão autenticada para identificar o titular de inscrições e dados pessoais.
- Não publicar senhas, tokens ou credenciais no repositório.
- Documentar como configurar o banco e executar o projeto do zero.

## Melhorias desejadas

- Gerenciar turmas pelo próprio sistema, sem precisar alterar o código.
- Paginar listas grandes.
- Criar níveis de acesso para administrador e funcionário.
- Registrar datas de matrícula e de última atualização.
- Manter um histórico básico de alterações importantes.

## Ideias adicionais

- Exportar relatórios para CSV ou PDF.
- Mostrar indicadores na página inicial, como total de alunos ativos por turma.
- Evoluir a recuperação de senha para uso real com entrega por canal verificado.
- Sinalizar outros dados incompletos além da ausência de vínculo da conta.

## Prioridades

1. Manter login, cadastro público de conta e aluno, vínculo automático, consulta, edição e exclusão funcionando corretamente.
2. Melhorar busca, filtros e experiência em dispositivos móveis.
3. Implementar permissões, gestão de turmas e histórico.
4. Manter catálogo, gestão de cursos, inscrições, cancelamento, perfil e indicadores administrativos; avaliar exportações, indicadores por turma e recuperação de senha para uso real.

## Critério de conclusão

Considerar uma funcionalidade concluída quando ela puder ser usada no navegador, validar os dados, apresentar retorno claro, funcionar com o banco configurado e tiver sido testada nos cenários esperados. Registrar o resultado e eventuais pendências em `acompanhamento.md`.
