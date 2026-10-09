<?php
require_once __DIR__ . '/../database/connect_postgres.php';
function validar_cpf($cpf) // valida o tamanho e os dois digitos verificadores do cpf
{
    // remove caracteres que nao sao digitos antes de validar o cpf
    $cpf = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    } // exige 11 digitos e rejeita que todos os numeros sejam repetidos
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
        // aplica os pesos de 10 a 2 aos primeiros nove digitos
        $soma += (int) $cpf[$i] * (10 - $i);
    }
    $resto = $soma % 11;
    // pela regra do cpf restos 0 e 1 geram digito 0 nos demais casos usa se 11 menos o resto
    $primeiro = $resto < 2 ? 0 : 11 - $resto; 
    if ((int) $cpf[9] !== $primeiro) {
        return false;
    }
    $soma = 0;
    // calcula a soma usada no segundo digito verificador
    for ($i = 0; $i < 10; $i++) {
        $soma += (int) $cpf[$i] * (11 - $i);
    }
    $resto = $soma % 11;
    $segundo = $resto < 2 ? 0 : 11 - $resto;
    return (int) $cpf[10] === $segundo;
}
function cadastrar($conexao, $nome, $turma, $nasc, $ativo, $email, $cpf)
{
    $sql = "INSERT INTO alunos (nome, turma, nasc, ativo, email, cpf) VALUES (:nome, :turma, :nasc, :ativo, :email, :cpf)";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":turma", $turma);
        $stmt->bindParam(":nasc", $nasc);
        $stmt->bindParam(":ativo", $ativo);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":cpf", $cpf);
        $stmt->execute();
        echo "Aluno inserido com sucesso!";
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo '<p class="message-error" role="alert">Não foi possível salvar o aluno. Tente novamente.</p>';
    }
}
function listarAlunos($conexao, $turma = '', $situacao = 'todas')
{
    $sql = "SELECT * FROM alunos";
    $filtros = [];
    $parametros = [];
    if ($turma !== '') {
        $filtros[] = 'turma = :turma';
        $parametros[':turma'] = $turma;
    }
    if ($situacao === 'ativo') {
        $filtros[] = 'ativo = TRUE';
    } elseif ($situacao === 'inativo') {
        $filtros[] = 'ativo = FALSE';
    }
    // combina os filtros para exigir todas as condicoes informadas
    // separa os valores externos do sql para impedir injecao
    if ($filtros) {
        $sql .= ' WHERE ' . implode(' AND ', $filtros);
    }
    $sql .= ' ORDER BY id ASC';
    $stmt = $conexao->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
function apagar($conexao, $id)
{
    if ($id > 0) {
        $sql = "DELETE FROM alunos WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->execute([':id' => $id]);
        if ($stmt->rowCount()) {
            echo '<p class="message-success" role="status">Registro deletado.</p>';
        } else {
            echo '<p class="message-error" role="alert">Aluno não encontrado.</p>';
        }
    } else {
        echo '<p class="message-error" role="alert">Insira um ID para apagar.</p>';
    }
}
function Consultar($conexao, $id)
{
    $sql = "SELECT nome, nasc, turma, ativo, email, cpf
            FROM alunos
            WHERE id = :id";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($aluno) {
        echo 'Aluno: ' . htmlspecialchars((string) $aluno['nome'], ENT_QUOTES, 'UTF-8') . '<br>';
        echo 'CPF: ' . htmlspecialchars((string) $aluno['cpf'], ENT_QUOTES, 'UTF-8') . '<br>';
        echo 'Turma: ' . htmlspecialchars((string) $aluno['turma'], ENT_QUOTES, 'UTF-8') . '<br>';
        echo 'E-mail: ' . htmlspecialchars((string) $aluno['email'], ENT_QUOTES, 'UTF-8') . '<br>';
        echo 'Nasc: ' . htmlspecialchars((string) $aluno['nasc'], ENT_QUOTES, 'UTF-8') . '<br>';
        echo "Ativo: " . ($aluno['ativo'] ? "SIM" : "NÃO") . "<br>";
    } else {
        echo "Aluno não encontrado.";
    }
}
// preserva cpf e nascimento mesmo recebendo esses campos por compatibilidade
function Atualizar($conexao, $id, $nome, $turma, $nasc, $ativo, $email, $cpf)
{
      $sql = "UPDATE alunos SET nome = :nome, turma = :turma,
            ativo = :ativo, email = :email WHERE id = :id";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":turma", $turma);
        $stmt->bindParam(":ativo", $ativo);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        echo '<p class="message-success" role="status">ALUNO ATUALIZADO COM SUCESSO! VOLTE AO RELATÓRIO PARA CONFERIR.</p>';
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo '<p class="message-error" role="alert">Não foi possível atualizar o aluno. Tente novamente.</p>';
    }
}
function read_w_w($conexao, $id)
{
    try {
        $sql = "SELECT * FROM alunos WHERE id = :id;";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $aluno = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($aluno !== false) {
            $cpfFormatado = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', (string) ($aluno['cpf'] ?? ''));
            echo '<h2>Dados do aluno</h2><dl class="student-details">';
            echo '<div><dt>ID</dt><dd>' . htmlspecialchars((string) $aluno['id'], ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>Aluno</dt><dd>' . htmlspecialchars((string) $aluno['nome'], ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>CPF</dt><dd>' . htmlspecialchars((string) $cpfFormatado, ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>Turma</dt><dd>' . htmlspecialchars((string) $aluno['turma'], ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>E-mail</dt><dd>' . htmlspecialchars((string) $aluno['email'], ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>Data de nascimento</dt><dd>' . htmlspecialchars((string) $aluno['nasc'], ENT_QUOTES, 'UTF-8') . '</dd></div>';
            echo '<div><dt>Status</dt><dd><span class="student-status ' . ($aluno['ativo'] ? 'is-active' : 'is-inactive') . '">' . ($aluno['ativo'] ? 'Ativo' : 'Inativo') . '</span></dd></div>';
            echo '</dl>';
        } else {
            echo '<p class="lookup-empty" role="status">Nenhum registro encontrado.</p>';
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo '<p class="message-error" role="alert">Não foi possível consultar o aluno. Tente novamente.</p>';
    }
    echo '<a class="lookup-home-link" href="../index.php">Voltar ao início</a>';
}
function cadastrar_aluno_usuario($conexao, array $dados)
{
    $nome = trim($dados['nome'] ?? '');
    $email = trim($dados['email'] ?? '');
    $cpf = preg_replace('/\D/', '', $dados['cpf'] ?? '');
    $turma = 'Sem turma'; // o administrador define a turma depois do cadastro
    $nasc = $dados['nasc'] ?? '';
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $nasc);
    if ($nome === '' || strlen($nome) > 255) {
        throw new InvalidArgumentException('Informe um nome com até 255 caracteres.');
    }
    if (!validar_cpf($cpf)) {
        throw new InvalidArgumentException('Informe um CPF válido.');
    }
    // rejeita datas inexistentes ou futuras antes de criar a conta
    if (!$data || $data->format('Y-m-d') !== $nasc || $nasc > date('Y-m-d')) {
        throw new InvalidArgumentException('Informe uma data de nascimento válida.');
    }
    // salva conta e aluno juntos deixando o vinculo para confirmacao do admin
    $conexao->beginTransaction();
    try {
        $stmt = $conexao->prepare("SELECT id FROM alunos WHERE regexp_replace(cpf, '[^0-9]', '', 'g') = :cpf");
        $stmt->execute([':cpf' => $cpf]);
        if ($stmt->fetchColumn() !== false) {
            throw new InvalidArgumentException('Este CPF já possui cadastro de aluno. Procure o administrador.');
        }
        cadastrar_user($conexao, $email, $dados['senha'] ?? '');
        $stmt = $conexao->prepare('INSERT INTO alunos (nome, cpf, nasc, turma, ativo, email) VALUES (:nome, :cpf, :nasc, :turma, TRUE, :email)');
        $stmt->execute([':nome' => $nome, ':cpf' => $cpf, ':nasc' => $nasc, ':turma' => $turma, ':email' => $email]);
        // confirma o cadastro somente depois de salvar conta e aluno
        $conexao->commit();
    } catch (Throwable $e) {
        if ($conexao->inTransaction()) {
            // desfaz as alteracoes se qualquer etapa do cadastro falhar
            $conexao->rollBack();
        }
        throw $e;
    }
}
function cadastrar_user($conexao, $email, $senha) // valida o email e cria uma conta comum com senha em hash
{
    if (!is_string($email) || !is_string($senha)) {
        throw new InvalidArgumentException('Informe um e-mail válido e uma senha.');
    }
    $email = trim($email);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        throw new InvalidArgumentException('Informe um e-mail válido e uma senha.');
    }
    // impede senhas que excedem o limite do hash ou contem caracteres nulos
    if (strlen($email) > 255 || strlen($senha) > 72 || str_contains($senha, "\0")) {
        throw new InvalidArgumentException('Use um e-mail com até 255 caracteres e uma senha com até 72 bytes, sem caracteres nulos.');
    }
    if (consultar_user($conexao, $email)) {
        throw new InvalidArgumentException('Este e-mail já está cadastrado. Entre com a senha do cadastro mais recente.');
    }
    // transforma a senha em hash antes de salvar no banco
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (email, senha, tipo) VALUES (:email, :senha, 'usuario')"; // forca o cadastro publico a criar sempre uma conta comum
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":senha", $senhaHash);
    $stmt->execute();
}
// usa a conta mais recente quando existem emails duplicados
function consultar_user($conexao, $email)
{
    if (!is_string($email)) {
        return false;
    }
    $email = trim($email);
    $sql = "SELECT id, email, senha, tipo FROM usuarios WHERE email = :email ORDER BY id DESC LIMIT 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
// busca apenas os cursos da conta autenticada
function buscarCursosDoUsuario($conexao)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria FROM inscricoes i JOIN cursos c ON c.id = i.curso_id WHERE i.usuario_id = :usuario_id ORDER BY c.id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// inclui cursos sem inscricao e identifica os ja inscritos pela conta
function buscarCursosDisponiveis($conexao)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (i.id IS NOT NULL) AS inscrito FROM cursos c LEFT JOIN inscricoes i ON i.curso_id = c.id AND i.usuario_id = :usuario_id ORDER BY c.id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// verifica a inscricao usando somente a conta salva na sessao
function buscarCursoPorId($conexao, $id)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (i.id IS NOT NULL) AS inscrito FROM cursos c LEFT JOIN inscricoes i ON i.curso_id = c.id AND i.usuario_id = :usuario_id WHERE c.id = :id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function inscreverUsuarioNoCurso($conexao, $cursoId, $token)
{
    if (!is_string($token) || !isset($_SESSION['inscricao_token']) || !hash_equals($_SESSION['inscricao_token'], $token)) { // compara os tokens de forma segura para impedir solicitacoes forjadas
        throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
    }
    $cursoId = filter_var($cursoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($cursoId === false) {
        throw new InvalidArgumentException('Informe um curso válido.');
    }
    // inscreve apenas em cursos existentes e evita duplicatas em envios simultaneos
    $stmt = $conexao->prepare('INSERT INTO inscricoes (usuario_id, curso_id) SELECT :usuario_id, id FROM cursos WHERE id = :curso_id ON CONFLICT (usuario_id, curso_id) DO NOTHING');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':curso_id' => $cursoId]);
    if ($stmt->rowCount()) {
        return 'Inscrição realizada com sucesso.';
    }
    // distingue curso inexistente de inscricao ja registrada
    if (!buscarCursoPorId($conexao, $cursoId)) {
        throw new InvalidArgumentException('Curso não encontrado.');
    }
    return 'Você já está inscrito neste curso.';
}

// valida os campos para respeitar os limites da tabela de cursos
function validarDadosCursoAdmin($dados)
{
    $nome = is_string($dados['nome'] ?? null) ? trim($dados['nome']) : '';
    $descricao = is_string($dados['descricao'] ?? null) ? trim($dados['descricao']) : '';
    $carga = filter_var($dados['carga_horaria'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($nome === '' || preg_match('/^.{1,100}$/us', $nome) !== 1) {
        throw new InvalidArgumentException('Informe um nome com até 100 caracteres.');
    }
    if ($carga === false) {
        throw new InvalidArgumentException('Informe uma carga horária inteira maior que zero.');
    }
    return ['nome' => $nome, 'descricao' => $descricao, 'carga_horaria' => $carga];
}
function validarTokenCursoAdmin($token)
{
    // compara os tokens de forma segura para proteger as alteracoes de cursos
    if (!is_string($token) || !isset($_SESSION['curso_admin_token']) || !hash_equals($_SESSION['curso_admin_token'], $token)) {
        throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
    }
}
function buscarCursoAdmin($conexao, $id)
{
    // a subconsulta conta inscricoes sem depender da conta autenticada
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (SELECT COUNT(*) FROM inscricoes i WHERE i.curso_id = c.id) AS inscritos FROM cursos c WHERE c.id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function validarFavorito($cursoId, $token)
{
    // usa somente a identidade da sessao para impedir operacoes em outra conta
    $usuarioId = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($usuarioId === false) {
        throw new InvalidArgumentException('Entre em sua conta para alterar os favoritos.');
    }
    // compara os tokens de forma segura antes de alterar os favoritos
    if (!is_string($token) || !isset($_SESSION['favoritos_token']) || !hash_equals($_SESSION['favoritos_token'], $token)) {
        throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
    }
    $cursoId = filter_var($cursoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($cursoId === false) {
        throw new InvalidArgumentException('Informe um curso válido.');
    }
    return $cursoId;
}

function cursoEstaFavoritado($conexao, $cursoId)
{
    $stmt = $conexao->prepare('SELECT id FROM favoritos WHERE usuario_id = :usuario_id AND curso_id = :curso_id');
    $stmt->execute([':usuario_id' => (int) ($_SESSION['id'] ?? 0), ':curso_id' => $cursoId]);
    return $stmt->fetchColumn() !== false;
}

function adicionarFavorito($conexao, $cursoId, $token)
{
    $cursoId = validarFavorito($cursoId, $token);
    // insere apenas cursos existentes e usa a restricao unique para evitar duplicatas simultaneas
    $stmt = $conexao->prepare('INSERT INTO favoritos (usuario_id, curso_id) SELECT :usuario_id, id FROM cursos WHERE id = :curso_id ON CONFLICT (usuario_id, curso_id) DO NOTHING');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':curso_id' => $cursoId]);
    if ($stmt->rowCount() > 0) {
        return 'Curso adicionado aos favoritos.';
    }
    if (!buscarCursoPorId($conexao, $cursoId)) {
        throw new InvalidArgumentException('Curso não encontrado.');
    }
    return 'Este curso já está nos seus favoritos.';
}

function removerFavorito($conexao, $cursoId, $token)
{
    $cursoId = validarFavorito($cursoId, $token);
    if (!buscarCursoPorId($conexao, $cursoId)) {
        throw new InvalidArgumentException('Curso não encontrado.');
    }
    // restringe a exclusao a conta da sessao mesmo se outra conta favoritou o mesmo curso
    $stmt = $conexao->prepare('DELETE FROM favoritos WHERE usuario_id = :usuario_id AND curso_id = :curso_id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':curso_id' => $cursoId]);
    if ($stmt->rowCount() > 0) {
        return 'Curso removido dos favoritos.';
    }
    return 'Este curso já não está nos seus favoritos.';
}

function buscarFavoritosUsuario($conexao)
{
    // relaciona os cursos somente aos favoritos da conta autenticada
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria FROM favoritos f JOIN cursos c ON c.id = f.curso_id WHERE f.usuario_id = :usuario_id ORDER BY c.nome, c.id');
    $stmt->execute([':usuario_id' => (int) ($_SESSION['id'] ?? 0)]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
