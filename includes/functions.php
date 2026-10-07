<?php
require_once __DIR__ . '/../database/connect_postgres.php';
function validar_cpf($cpf) // valida o tamanho e os dois digitos verificadores do cpf
{
    // \D encontra tudo que não é dígito, removendo pontos, traço e espaços antes da validação.
    $cpf = preg_replace('/\D/', '', (string) $cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false; // exige 11 digitos e rejeita que todos os numeros sejam repetidos
    $soma = 0;
    for ($i = 0; $i < 9; $i++) {
    $soma += (int) $cpf[$i] * (10 - $i);} // calcula a soma usada no primeiro digito verificador, com esse codigo ele vai fazer os primeiros 9 numeros, vezes 10, depois 9, depois 8 e assim por diante
    $resto = $soma % 11;
    // Pela regra do CPF, restos 0 e 1 geram dígito 0; nos demais casos, usa-se 11 menos o resto.
    $primeiro = $resto < 2 ? 0 : 11 - $resto; 
    if ((int) $cpf[9] !== $primeiro) return false;
    $soma = 0;
    for ($i = 0; $i < 10; $i++) $soma += (int) $cpf[$i] * (11 - $i); // calcula a soma usada no segundo digito, depois so repete o que fez na verificacao do primeiro digito
    $resto = $soma % 11;
    $segundo = $resto < 2 ? 0 : 11 - $resto;
    return (int) $cpf[10] === $segundo;
}
function cadastrar($conexao, $nome, $turma, $nasc, $ativo, $email, $cpf)
{
    $sql = "INSERT INTO alunos (nome, turma, nasc, ativo, email, cpf) VALUES (:nome, :turma, :nasc, :ativo, :email, :cpf)";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":nome", $nome); $stmt->bindParam(":turma", $turma);
        $stmt->bindParam(":nasc", $nasc); $stmt->bindParam(":ativo", $ativo);
        $stmt->bindParam(":email", $email); $stmt->bindParam(":cpf", $cpf);
        $stmt->execute();
        echo "Aluno inserido com sucesso!";
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
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
    if ($situacao === 'ativo') $filtros[] = 'ativo = TRUE';
    elseif ($situacao === 'inativo') $filtros[] = 'ativo = FALSE';
    // Cria WHERE apenas quando há filtros; AND exige que todas as condições sejam atendidas.
    // Os valores externos ficam em $parametros, separados dos trechos fixos de SQL.
    if ($filtros) $sql .= ' WHERE ' . implode(' AND ', $filtros);
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
        if ($stmt->rowCount()) echo '<p class="message-success" role="status">Registro deletado.</p>';
        else echo '<p class="message-error" role="alert">Aluno não encontrado.</p>';
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
        $stmt->bindParam(":id", $id); $stmt->execute();
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
// CPF e nascimento permanecem na assinatura por compatibilidade, mas não entram no UPDATE.
function Atualizar($conexao, $id, $nome, $turma, $nasc, $ativo, $email, $cpf) // mantem os parametros existentes e altera somente os campos permitidos do aluno
{
      $sql = "UPDATE alunos SET nome = :nome, turma = :turma,
            ativo = :ativo, email = :email WHERE id = :id";
    try {
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id", $id); $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":turma", $turma);
        $stmt->bindParam(":ativo", $ativo); $stmt->bindParam(":email", $email);
        $stmt->execute();
        echo '<p class="message-success" role="status">ALUNO ATUALIZADO COM SUCESSO! VOLTE AO RELATÓRIO PARA CONFERIR.</p>';
    } catch (PDOException $e) {
        echo "Erro: " . $e->getMessage();
    }
}
function read_w_w($conexao, $id)
{
    try {
        $sql = "SELECT * FROM alunos WHERE id = :id;";
        $stmt = $conexao->prepare($sql);
        $stmt->bindParam(":id", $id); $stmt->execute();
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
        echo "Erro: " . $e->getMessage();
    }
    echo '<a class="lookup-home-link" href="../index.php">Voltar ao início</a>';
}
function cadastrar_aluno_usuario($conexao, array $dados)
{
    $nome = trim($dados['nome'] ?? '');
    $email = trim($dados['email'] ?? '');
    $cpf = preg_replace('/\D/', '', $dados['cpf'] ?? '');
    $turma = 'Sem turma'; // O administrador define a turma depois do cadastro.
    $nasc = $dados['nasc'] ?? '';
    $data = DateTimeImmutable::createFromFormat('!Y-m-d', $nasc);
    if ($nome === '' || strlen($nome) > 255) {
        throw new InvalidArgumentException('Informe um nome com até 255 caracteres.');
    }
    if (!validar_cpf($cpf)) throw new InvalidArgumentException('Informe um CPF válido.');
    if (!$data || $data->format('Y-m-d') !== $nasc || $nasc > date('Y-m-d')) {
        throw new InvalidArgumentException('Informe uma data de nascimento válida.');
    }
    // Salva conta e aluno juntos, deixando o vínculo para confirmação do admin.
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
        $conexao->commit();
    } catch (Throwable $e) {
        if ($conexao->inTransaction()) $conexao->rollBack();
        throw $e;
    }
}
function cadastrar_user($conexao, $email, $senha) // valida o email e cria uma conta comum com senha em hash
{
    $email = trim($email); // remove espacos ao redor do email informado sem alterar a senha
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
        throw new InvalidArgumentException('Informe um e-mail válido e uma senha.');
    }
        if (consultar_user($conexao, $email)) {
        throw new InvalidArgumentException('Este e-mail já está cadastrado. Entre com a senha do cadastro mais recente.');
    }
    // Guarda um hash da senha; password_verify confere a senha no login sem precisar recuperá-la.
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (email, senha, tipo) VALUES (:email, :senha, 'usuario')"; // forca o cadastro publico a criar sempre uma conta comum
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(":email", $email); $stmt->bindParam(":senha", $senhaHash);
    $stmt->execute();
}
// Se houver e-mails duplicados, ORDER BY id DESC LIMIT 1 escolhe a conta de maior ID.
function consultar_user($conexao, $email)
{
    $email = trim($email);
    $sql = "SELECT id, email, senha, tipo FROM usuarios WHERE email = :email ORDER BY id DESC LIMIT 1";
    $stmt = $conexao->prepare($sql);
    $stmt->bindParam(":email", $email); $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
// JOIN relaciona inscrições aos cursos e o filtro restringe a consulta à conta autenticada.
function buscarCursosDoUsuario($conexao)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria FROM inscricoes i JOIN cursos c ON c.id = i.curso_id WHERE i.usuario_id = :usuario_id ORDER BY c.id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// LEFT JOIN mantém cursos sem inscrição; i.id IS NOT NULL calcula se a conta já está inscrita.
function buscarCursosDisponiveis($conexao)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (i.id IS NOT NULL) AS inscrito FROM cursos c LEFT JOIN inscricoes i ON i.curso_id = c.id AND i.usuario_id = :usuario_id ORDER BY c.id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id']]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Busca o curso e calcula seu estado de inscrição usando o ID da conta guardado na sessão.
function buscarCursoPorId($conexao, $id)
{
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (i.id IS NOT NULL) AS inscrito FROM cursos c LEFT JOIN inscricoes i ON i.curso_id = c.id AND i.usuario_id = :usuario_id WHERE c.id = :id');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
function inscreverUsuarioNoCurso($conexao, $cursoId, $token)
{
    if (!is_string($token) || !isset($_SESSION['inscricao_token']) || !hash_equals($_SESSION['inscricao_token'], $token)) { // rejeita tokens ausentes ou diferentes do token da sessao para impedir solicitacoes forjadas
        throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
    }
    $cursoId = filter_var($cursoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($cursoId === false) throw new InvalidArgumentException('Informe um curso válido.');
    // INSERT ... SELECT só insere se o curso existir; ON CONFLICT evita duplicação mesmo em envios simultâneos.
    $stmt = $conexao->prepare('INSERT INTO inscricoes (usuario_id, curso_id) SELECT :usuario_id, id FROM cursos WHERE id = :curso_id ON CONFLICT (usuario_id, curso_id) DO NOTHING');
    $stmt->execute([':usuario_id' => (int) $_SESSION['id'], ':curso_id' => $cursoId]);
    if ($stmt->rowCount()) return 'Inscrição realizada com sucesso.';
    // Se nada foi inserido, distingue um curso inexistente de uma inscrição que já estava registrada.
    if (!buscarCursoPorId($conexao, $cursoId)) throw new InvalidArgumentException('Curso não encontrado.');
    return 'Você já está inscrito neste curso.';
}

// Valida os campos conforme os limites atuais da tabela cursos (nome VARCHAR(100) e carga INTEGER).
function validarDadosCursoAdmin($dados)
{
    $nome = is_string($dados['nome'] ?? null) ? trim($dados['nome']) : '';
    $descricao = is_string($dados['descricao'] ?? null) ? trim($dados['descricao']) : '';
    $carga = filter_var($dados['carga_horaria'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($nome === '' || preg_match('/^.{1,100}$/us', $nome) !== 1) {
        throw new InvalidArgumentException('Informe um nome com até 100 caracteres.');
    }
    if ($carga === false) throw new InvalidArgumentException('Informe uma carga horária inteira maior que zero.');
    return ['nome' => $nome, 'descricao' => $descricao, 'carga_horaria' => $carga];
}
function validarTokenCursoAdmin($token)
{
    if (!is_string($token) || !isset($_SESSION['curso_admin_token']) || !hash_equals($_SESSION['curso_admin_token'], $token)) {
        throw new InvalidArgumentException('Solicitação inválida. Recarregue a página e tente novamente.');
    }
}
function buscarCursoAdmin($conexao, $id)
{
    // A subconsulta conta inscrições sem depender da conta autenticada.
    $stmt = $conexao->prepare('SELECT c.id, c.nome, c.descricao, c.carga_horaria, (SELECT COUNT(*) FROM inscricoes i WHERE i.curso_id = c.id) AS inscritos FROM cursos c WHERE c.id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
