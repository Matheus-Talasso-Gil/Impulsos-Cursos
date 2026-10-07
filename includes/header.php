<?php require_once __DIR__ . '/session.php'; ?>
<header>
    <nav aria-label="Menu principal">
        <?php if (!isset($_SESSION['id'])): ?>
            <div class="nav-account">
                <a href="/mini_sistema/login/login.php">Login</a>
                <a href="/mini_sistema/login/cadastrar.php">Cadastre-se</a>
            </div>
        <?php else: ?>
            <div class="nav-start">
                <a href="/mini_sistema/index.php">Início</a>
            </div>
            <?php if (($_SESSION['tipo'] ?? 'usuario') === 'admin'): ?>
                <div class="nav-links nav-links-admin">
                    <a href="/mini_sistema/app/admin.php">Painel</a>
                    <?php // Agrupa os atalhos administrativos sem JavaScript; cada link abre sua própria página. ?>
                    <details class="nav-group">
                        <summary>Alunos</summary>
                        <div class="nav-dropdown">
                            <a href="/mini_sistema/app/create.php">Cadastrar aluno</a>
                            <a href="/mini_sistema/app/select_w_w.php">Consultar aluno</a>
                            <a href="/mini_sistema/app/select.php">Relatório de alunos</a>
                            <a href="/mini_sistema/app/alunos_cursos.php">Cursos dos alunos</a>
                            <a href="/mini_sistema/app/delete.php">Excluir aluno</a>
                        </div>
                    </details>
                    <details class="nav-group">
                        <summary>Contas</summary>
                        <div class="nav-dropdown">
                            <a href="/mini_sistema/app/usuarios.php">Usuários cadastrados</a>
                            <a href="/mini_sistema/app/vincular_conta.php">Vincular conta a aluno</a>
                        </div>
                    </details>
                    <a href="/mini_sistema/app/cursos_admin.php">Gerenciar cursos</a>
                </div>
            <?php else: ?>
                <div class="nav-links">
                    <a href="/mini_sistema/app/meus_cursos.php">Meus cursos</a>
                    <a href="/mini_sistema/app/cursos.php">Todos os cursos</a>
                </div>
            <?php endif; ?>
            <div class="nav-account">
                <a href="/mini_sistema/login/perfil.php">Meu perfil</a>
                <a href="/mini_sistema/login/logout.php">Sair</a>
            </div>
        <?php endif; ?>
    </nav>
</header>
