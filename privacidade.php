<?php
// Página informativa: não processa formulários nem consulta o banco.
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Esta página é apenas informativa.');
}
// Lê uma sessão existente apenas para o menu, sem atualizar dados ou renovar a atividade.
$headerSessaoSomenteLeitura = true;
$cookieSessao = $_COOKIE[session_name()] ?? null;
if (session_status() === PHP_SESSION_NONE && is_string($cookieSessao)
    && preg_match('/^[a-zA-Z0-9,-]{1,256}$/D', $cookieSessao) === 1) {
    session_start(['read_and_close' => true]);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade | Impulso Cursos</title>
    <?php require __DIR__ . '/includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="privacy-page">
    <h1>Política de Privacidade</h1>
    <p class="privacy-updated">Última atualização: <time datetime="2026-10-07">7 de outubro de 2026</time>.</p>
    <section aria-labelledby="sobre-politica">
        <h2 id="sobre-politica">1. Sobre esta política</h2>
        <p>Esta página explica quais dados o Impulso Cursos utiliza e como eles ajudam no funcionamento do sistema. A política pode ser consultada por visitantes, usuários e administradores.</p>
    </section>
    <section aria-labelledby="dados-coletados">
        <h2 id="dados-coletados">2. Dados coletados</h2>
        <p>Conforme o cadastro e o uso do sistema, podem ser armazenados:</p>
        <ul>
            <li>Nome, CPF, data de nascimento e e-mail do aluno.</li>
            <li>Turma e situação do cadastro do aluno, como ativo ou inativo.</li>
            <li>E-mail da conta e senha protegida em formato hash.</li>
            <li>Tipo de conta, como usuário ou administrador, e data de criação da conta.</li>
            <li>Inscrições em cursos e vínculo entre a conta e o cadastro de aluno, quando existente.</li>
        </ul>
        <p>O CPF é usado para identificar o aluno de forma única, evitar cadastros duplicados e validar o cadastro. A validação confere matematicamente os dígitos do CPF; não há integração com a Receita Federal nem confirmação de quem é o titular do documento.</p>
    </section>
    <section aria-labelledby="uso-dados">
        <h2 id="uso-dados">3. Como usamos os dados</h2>
        <p>Os dados são usados para criar e identificar contas, autenticar usuários e manter o cadastro de alunos. Também permitem realizar inscrições em cursos, exibir o perfil e os cursos da própria conta, apoiar o gerenciamento administrativo e manter o funcionamento do sistema.</p>
    </section>
    <section aria-labelledby="seguranca-informacoes">
        <h2 id="seguranca-informacoes">4. Segurança das informações</h2>
        <p>Os cadastros e as inscrições ficam armazenados no banco PostgreSQL utilizado pelo sistema. O acesso às áreas internas depende de login, e as páginas administrativas exigem uma conta de administrador.</p>
        <p>As senhas cadastradas pelo sistema não são armazenadas em texto puro. Elas são transformadas em um hash, uma representação protegida usada para conferir a senha no login. Para isso, o sistema utiliza <code>password_hash()</code> no cadastro e <code>password_verify()</code> na autenticação.</p>
        <p>O sistema possui uma demonstração local de redefinição de senha para contas comuns. Esse recurso é destinado a testes acadêmicos.</p>
        <p>O projeto busca seguir boas práticas de proteção e uso responsável de dados. Essas medidas não representam uma garantia de segurança absoluta.</p>
    </section>
    <section aria-labelledby="compartilhamento">
        <h2 id="compartilhamento">5. Compartilhamento</h2>
        <p>No contexto atual deste projeto, os dados não são vendidos nem compartilhados com anunciantes. Não há integração com serviços externos de marketing. A administração utiliza os dados nas atividades de gerenciamento previstas no sistema.</p>
    </section>
    <section aria-labelledby="direitos-usuario">
        <h2 id="direitos-usuario">6. Direitos do usuário</h2>
        <p>Ao entrar em sua conta, você pode consultar seus próprios dados pelo Meu perfil. Para solicitar a correção de dados permitidos ou tratar de dúvidas relacionadas à conta, procure a administração responsável pelo projeto.</p>
        <p>Este sistema não oferece exclusão automática de conta nem exportação de dados.</p>
    </section>
    <section aria-labelledby="projeto-academico">
        <h2 id="projeto-academico">7. Projeto acadêmico</h2>
        <p>O Impulso Cursos é um sistema fictício desenvolvido para fins educacionais e demonstração acadêmica. Para atividades de teste, utilize dados fictícios.</p>
        <p>Esta política descreve o funcionamento atual do projeto e não afirma conformidade jurídica completa com a Lei Geral de Proteção de Dados (LGPD).</p>
    </section>
    <section aria-labelledby="atualizacoes-politica">
        <h2 id="atualizacoes-politica">8. Atualizações desta política</h2>
        <p>Esta política poderá ser atualizada quando o funcionamento do projeto mudar. A data da última atualização aparece no início desta página.</p>
        <p>Para conhecer as regras de utilização da plataforma, consulte os <a href="/impulsos_cursos/termos.php">Termos de Uso</a>.</p>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
