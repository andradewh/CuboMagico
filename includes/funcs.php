<?php
// O include 'db_connection.php' foi movido para os arquivos principais,
// mas para que 'obterNomeDoBancoDeDados' funcione isoladamente
// ele deve estar aqui ou o require deve estar no arquivo principal
require 'db_connection.php'; // Mantendo a estrutura original

// ==========================================================
// FUNÇÕES DE BUSCA DE DADOS (Consolidado e Otimizado)
// ==========================================================

function obterNomeDoBancoDeDados($usuario_id) {
    global $pdo; 
    $query = "SELECT nome FROM usuarios WHERE id = :usuario_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        return $resultado['nome'];
    } else {
        return false;
    }
}

function obterModalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM modalidades where ativa = 1");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obterAlunos($id_evento = null) {
    global $pdo;

    // Se o id_evento não foi passado como parâmetro, tenta obter do usuário logado na sessão
    if ($id_evento === null && isset($_SESSION['usuario'])) {
        $usuarioLogado = $_SESSION['usuario'];
        $userId = is_array($usuarioLogado) ? ($usuarioLogado['id'] ?? null) : null;
        $userEmail = is_array($usuarioLogado) ? ($usuarioLogado['email'] ?? null) : $usuarioLogado;

        if ($userId || $userEmail) {
            $stmtUser = $pdo->prepare("SELECT id_evento, superuser FROM usuarios WHERE id = ? OR email = ?");
            $stmtUser->execute([$userId, $userEmail]);
            $userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

            // Se o usuário tem um evento vinculado e NÃO é superuser master global, usamos o id_evento dele
            if ($userData && !empty($userData['id_evento'])) {
                $id_evento = $userData['id_evento'];
            }
        }
    }

    // Realiza a consulta com ou sem o filtro de evento
    if ($id_evento !== null) {
        $stmt = $pdo->prepare("SELECT * FROM alunos WHERE id_evento = :id_evento");
        $stmt->bindParam(':id_evento', $id_evento, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback caso seja superuser sem evento vinculado ou sem sessão ativa
        $stmt = $pdo->query("SELECT * FROM alunos");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

function obterVinculosAlunosModalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT aluno, modalidade FROM alunomodalidade");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * FUNÇÃO OTIMIZADA para carregar todos os dados de solvers em uma única consulta.
 * Opcionalmente filtrada por evento se juntarmos com a tabela de alunos.
 */
function obterTodosOsValoresSolver($id_evento = null) {
    global $pdo;
    
    if ($id_evento !== null) {
        $sql = "SELECT ams.aluno, ams.modalidade, ams.solver1, ams.solver2, ams.solver3, ams.solver4, ams.solver5 
                FROM alunomodalidadesolver ams
                INNER JOIN alunos a ON ams.aluno = a.id
                WHERE a.id_evento = :id_evento";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id_evento', $id_evento, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->query("SELECT aluno, modalidade, solver1, solver2, solver3, solver4, solver5 FROM alunomodalidadesolver");
    }

    $resultados = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modalidadeId = $row['modalidade'];
        $alunoId = $row['aluno'];
        
        $resultados[$modalidadeId][$alunoId] = $row;
    }
    return $resultados;
}

// ==========================================================
// FUNÇÕES AUXILIARES E CRUD DE OUTRAS ENTIDADES (Atualizadas)
// ==========================================================

function obterUsuarioPorId($id) {
    global $pdo;

    $sql = "
        SELECT id, nome, email, id_evento
        FROM usuarios
        WHERE id = :id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarUsuario($id, $nome, $email, $novaSenha = null) {
    global $pdo;
    
    try {
        if ($novaSenha !== null && !empty($novaSenha)) {
            $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome = :nome, email = :email, senha = :senha WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':senha', $senhaHash, PDO::PARAM_STR);

            return $stmt->execute();
        } else {
            $sql = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);

            return $stmt->execute();
        }
    } catch (PDOException $e) {
        error_log("Erro ao atualizar usuário: " . $e->getMessage());
        return false;
    }
}

function deletaUsuario($id) {
    global $pdo;
    
    $sql = "DELETE FROM usuarios WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();
}

function obterAlunoPorId($id) {
    global $pdo; 

    $sql = "SELECT alunos.id, alunos.nome, alunos.idade, alunos.sexo, alunos.escola, alunos.id_evento
            FROM alunos 
            WHERE alunos.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarAluno($id, $nome, $idade, $sexo, $escola, $id_evento) {
    global $pdo;

    $sql = "UPDATE alunos SET nome = :nome, idade = :idade, sexo = :sexo, escola = :escola, id_evento = :id_evento WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':sexo', $sexo, PDO::PARAM_INT);
    $stmt->bindParam(':escola', $escola, PDO::PARAM_INT);
    $stmt->bindParam(':id_evento', $id_evento, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return true;
    } else {
        $error_info = $stmt->errorInfo();
        error_log("Falha na atualização para o aluno com ID: $id. Erro: " . $error_info[2]);
        return false;
    }
}

function deletaAluno($id) {
    global $pdo;
    
    $sql = "DELETE FROM alunos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    return $stmt->execute();
}

function inserirVinculoAlunoModalidade($alunoId, $modalidadesSelecionadas) {
    global $pdo;

    $sqlExcluir = "DELETE FROM alunomodalidade WHERE aluno = :alunoId";
    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindParam(':alunoId', $alunoId, PDO::PARAM_INT);
    $stmtExcluir->execute();

    foreach ($modalidadesSelecionadas as $modalidadeId => $value) {
        $sqlInserir = "INSERT INTO alunomodalidade (aluno, modalidade) VALUES (:alunoId, :modalidadeId)";
        $stmtInserir = $pdo->prepare($sqlInserir);
        $stmtInserir->bindParam(':alunoId', $alunoId, PDO::PARAM_INT);
        $stmtInserir->bindParam(':modalidadeId', $modalidadeId, PDO::PARAM_INT);
        $stmtInserir->execute();
    }

    return "Registros inseridos com sucesso!";
}

function obterVinculosExistentes($alunoId) {
    global $pdo;

    $sql = "SELECT modalidade FROM alunomodalidade WHERE aluno = :alunoId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':alunoId', $alunoId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function validarFormatoTempo($tempo) {
    $padrao = "/^\d{2}:\d{2}\.\d{2}$/";
    return preg_match($padrao, $tempo) === 1;
}
?>