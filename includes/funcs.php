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

function obterAlunos() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM alunos");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obterVinculosAlunosModalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT aluno, modalidade FROM alunomodalidade");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * FUNÇÃO OTIMIZADA para carregar todos os dados de solvers em uma única consulta.
 * Isso substitui a chamada repetitiva de obterValoresSolverExistente()
 * e resolve o problema N+1 Query.
 */
function obterTodosOsValoresSolver() {
    global $pdo;
    // Seleciona todos os campos de solver
    $stmt = $pdo->query("SELECT aluno, modalidade, solver1, solver2, solver3, solver4, solver5 FROM alunomodalidadesolver");
    $resultados = [];
    
    // Organiza os resultados em um array multidimensional por [modalidade][aluno]
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $modalidadeId = $row['modalidade'];
        $alunoId = $row['aluno'];
        
        $resultados[$modalidadeId][$alunoId] = $row;
    }
    return $resultados;
}

// ==========================================================
// FUNÇÕES AUXILIARES E CRUD DE OUTRAS ENTIDADES (Mantidas)
// ==========================================================

function obterUsuarioPorId($id) {
    global $pdo; 

    $sql = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarUsuario($id, $nome, $email) {
    global $pdo;

    $sql = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function deletaUsuario($id) {
    global $pdo;
    
    $sql = "DELETE FROM usuarios WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function obterAlunoPorId($id) {
    global $pdo; 

    $sql = "SELECT alunos.id, alunos.nome, alunos.idade, alunos.sexo, alunos.escola
             from cubomagico.alunos 
             WHERE alunos.id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarAluno($id, $nome, $idade, $sexo, $escola) {
    global $pdo;

    $sql = "UPDATE alunos SET nome = :nome, idade = :idade, sexo = :sexo, escola = :escola WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':sexo', $sexo, PDO::PARAM_INT);
    $stmt->bindParam(':escola', $escola, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $log_message = "Atualização bem-sucedida para o aluno com ID: $id";
        error_log($log_message);
        return true;
    } else {
        $error_info = $stmt->errorInfo();
        $log_message = "Falha na atualização para o aluno com ID: $id. Erro: " . $error_info[2];
        error_log($log_message);
        return false;
    }
}

function deletaAluno($id) {
    global $pdo;
    
    $sql = "DELETE FROM alunos WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return true;
    } else {
        return false;
    }
}

function inserirVinculoAlunoModalidade($alunoId, $modalidadesSelecionadas) {
    global $pdo;

    // Exclua os registros existentes para o aluno
    $sqlExcluir = "DELETE FROM alunomodalidade WHERE aluno = :alunoId";
    $stmtExcluir = $pdo->prepare($sqlExcluir);
    $stmtExcluir->bindParam(':alunoId', $alunoId, PDO::PARAM_INT);
    $stmtExcluir->execute();

    // Insira os novos registros na tabela alunomodalidade
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

    $vinculos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $vinculos;
}

function validarFormatoTempo($tempo) {
    // O padrão da expressão regular corresponde a "01:30.55"
    $padrao = "/^\d{2}:\d{2}\.\d{2}$/";
    return preg_match($padrao, $tempo) === 1;
}

// A função obterValoresSolverExistente() foi removida daqui e substituída pela função obterTodosOsValoresSolver()
// que é mais eficiente.
?>