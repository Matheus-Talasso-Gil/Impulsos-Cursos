<?php
// Página informativa: não processa formulários nem consulta o banco.
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit('Esta página é apenas informativa.');
}
// O menu pode ler a sessão existente sem alterar dados ou renovar a atividade.
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
    <title>Termos de Uso | Impulso Cursos</title>
    <?php require __DIR__ . '/includes/stylesheet.php'; ?>
</head>
<body>
<?php include __DIR__ . '/includes/header.php'; ?>
<main class="terms-page">
    <h1>Termos de Uso</h1>
    <p class="privacy-updated">Última atualização: <time datetime="2026-10-07">7 de outubro de 2026</time>.</p>
    <section aria-labelledby="sobre-sistema">
        <h2 id="sobre-sistema">1. Sobre o sistema</h2>
        <p>O Impulso Cursos é uma plataforma fictícia desenvolvida para fins educacionais. Conforme o perfil de acesso, permite cadastro de usuário, autenticação, consulta e inscrição em cursos, consulta de perfil e gerenciamento administrativo.</p>
        <p>Os acessos disponíveis são visitante, usuário e administrador. Visitantes podem consultar as páginas públicas e acessar login e cadastro. As áreas internas dependem de autenticação e do tipo da conta.</p>
    </section>
    <section aria-labelledby="cadastro-conta">
        <h2 id="cadastro-conta">2. Cadastro e conta</h2>
        <p>O cadastro solicita nome, CPF, data de nascimento, e-mail e senha. Você é responsável por fornecer informações válidas e corretas para o contexto de uso.</p>
        <p>Em atividades acadêmicas de teste, utilize dados fictícios com formatos válidos, conforme a orientação responsável pelo projeto. Não utilize dados de terceiros nem se passe por outra pessoa.</p>
        <p>Utilize sua própria conta. Não use a conta de outra pessoa, compartilhe senhas ou tente modificar o tipo da conta para obter permissões adicionais.</p>
    </section>
    <section aria-labelledby="responsabilidades-usuario">
        <h2 id="responsabilidades-usuario">3. Responsabilidades do usuário</h2>
        <ul>
            <li>Utilizar o sistema de forma responsável e respeitar os limites de acesso da sua conta.</li>
            <li>Não inserir informações falsas para enganar outras pessoas ou prejudicar cadastros.</li>
            <li>Não tentar acessar contas de terceiros ou áreas administrativas sem permissão.</li>
            <li>Não manipular cadastros ou inscrições de outras pessoas.</li>
            <li>Não utilizar a plataforma para fins abusivos ou prejudicar seu funcionamento.</li>
        </ul>
    </section>
    <section aria-labelledby="uso-cursos">
        <h2 id="uso-cursos">4. Uso dos cursos</h2>
        <p>Usuários autenticados podem visualizar os cursos disponíveis, consultar seus detalhes, realizar inscrições e acompanhar a lista de seus cursos em Meus cursos. Essa área também permite cancelar inscrições.</p>
        <p>Os cursos e as inscrições fazem parte da demonstração acadêmica. A inscrição no sistema não representa matrícula em uma instituição real nem garante certificação.</p>
    </section>
    <section aria-labelledby="uso-administrativo">
        <h2 id="uso-administrativo">5. Uso administrativo</h2>
        <p>Contas administrativas possuem acesso adicional para consultar alunos e usuários, editar informações permitidas, excluir registros quando permitido, gerenciar cursos e realizar vínculos entre contas e alunos.</p>
        <p>A administração deve utilizar essas funções de forma responsável, conferir os dados antes de confirmar alterações e respeitar as restrições do sistema.</p>
    </section>
    <section aria-labelledby="seguranca-conta">
        <h2 id="seguranca-conta">6. Segurança da conta</h2>
        <p>Mantenha sua senha em segurança e não a compartilhe. Ao terminar de usar a plataforma em um computador compartilhado, utilize a opção Sair.</p>
        <p>O sistema armazena as senhas de forma protegida. Caso perceba uso não autorizado da sua conta, procure a administração responsável pelo projeto.</p>
    </section>
    <section aria-labelledby="uso-inadequado">
        <h2 id="uso-inadequado">7. Uso inadequado do sistema</h2>
        <p>Não tente burlar a autenticação, acessar páginas administrativas sem autorização, explorar falhas para manipular dados de terceiros ou prejudicar o sistema.</p>
        <p>A aplicação restringe o acesso a áreas sem permissão. No contexto acadêmico, o uso inadequado pode resultar em restrição de acesso às atividades do projeto pela administração responsável.</p>
    </section>
    <section aria-labelledby="limitacoes-projeto">
        <h2 id="limitacoes-projeto">8. Limitações do projeto</h2>
        <p>Este sistema foi desenvolvido para fins acadêmicos e de demonstração. Ele não representa uma empresa real nem oferece garantia de disponibilidade permanente.</p>
        <p>A plataforma pode ficar indisponível para manutenção, e suas funcionalidades podem ser alteradas durante o desenvolvimento. Estes termos descrevem o uso do projeto e não substituem os termos de um serviço comercial real.</p>
        <p>Para entender como os dados são utilizados, consulte a <a href="/impulsos_cursos/privacidade.php">Política de Privacidade</a>.</p>
    </section>
    <section aria-labelledby="alteracoes-termos">
        <h2 id="alteracoes-termos">9. Alterações nos termos</h2>
        <p>Estes termos podem ser atualizados para refletir alterações no sistema. Consulte esta página para acompanhar as regras de uso; a data da última atualização aparece no início do documento.</p>
    </section>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
