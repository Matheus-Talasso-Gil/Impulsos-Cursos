# Acompanhamento do briefing

Revisão em 07/10/2026, por leitura do código e dos scripts do banco. Esta revisão não certifica os fluxos no navegador nem testes no banco real.

## Funcionalidades encontradas no código

- Cadastro público de conta e aluno, sem escolha de turma, com validação de CPF e transação.
- Login, logout, contas comuns e administrativas e menus por perfil.
- Relatório, filtros por curso/situação, busca por ID/CPF e edição de aluno.
- Vínculo automático único entre conta e aluno e perfil pessoal.
- Exclusão de aluno com confirmação, preservação da conta/inscrições e confirmação adicional quando há cursos.
- Gestão de cursos, catálogo, detalhes, inscrição, Meus cursos e cancelamento com confirmação.
- Consulta administrativa de contas e cursos dos alunos e painel com totais gerais.
- Recuperação de senha demonstrativa local, sem entrega por e-mail.
- Estilos responsivos e documentação de instalação.

## Pendências dos requisitos principais

- Pesquisa de alunos por nome ou e-mail: a consulta atual aceita apenas ID e CPF.
- Validação no servidor da edição de alunos: nome, e-mail, turma e situação ainda são enviados ao UPDATE sem validação completa de formato, tamanho e valores permitidos.
- Proteção contra solicitações forjadas na edição de alunos: falta token CSRF nesse fluxo.
- Configuração de credenciais fora do código versionado: revisar `database/connect_postgres.php`, que está rastreado pelo Git.
- Conferir os fluxos no navegador e no banco configurado e registrar resultados. Verificar também telas pequenas e mensagens de erro.

## Melhorias desejadas ainda pendentes

- Gestão de turmas por telas e banco: atualmente o filtro consulta cursos reais e a edição mantém o campo textual de turma.
- Paginação das listas.
- Papel de funcionário e permissões próprias: atualmente os papéis são usuário e administrador.
- Datas de matrícula e de última atualização.
- Histórico de alterações importantes.

## Ideias adicionais ainda pendentes

- Exportação de relatórios em CSV ou PDF.
- Indicadores de alunos ativos por turma; o painel atual mostra totais gerais.
- Recuperação de senha para uso real por canal verificado.
- Sinalização de outros cadastros incompletos; a situação é exibida no relatório.

## Limitações observadas

- As telas de consulta por ID, busca para edição e exclusão ainda limitam o campo a 255; cadastros acima desse ID precisam ser considerados.
- Alunos antigos com CPF já cadastrado não conseguem criar uma conta pelo novo cadastro combinado. O cadastro combinado rejeita CPF existente; a migration só associa contas já existentes quando há correspondência única de e-mail.
- Alunos sem cursos aparecem na listagem geral como Sem curso.

As funcionalidades além do escopo original foram incorporadas ao briefing como requisitos do cliente nesta revisão. As pendências foram mantidas, sem tratar implementação ou teste ausente como concluído.
