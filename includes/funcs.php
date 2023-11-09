<?php
// Conecte-se ao banco de dados
require 'db_connection.php';

function obterNomeDoBancoDeDados($usuario_id) {
    global $pdo; // Torne a variável $pdo global para que seja acessível nesta função

    // Consulta para obter o nome do usuário com base no ID
    $query = "SELECT nome FROM usuarios WHERE id = :usuario_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':usuario_id', $usuario_id, PDO::PARAM_INT);
    $stmt->execute();
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($resultado) {
        return $resultado['nome'];
    } else {
        // Trate o caso em que o usuário não foi encontrado
        return false;
    }
}

function obterUsuarioPorId($id) {
    global $pdo; // Certifique-se de que sua conexão com o banco de dados esteja disponível nesta função

    $sql = "SELECT * FROM usuarios WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function atualizarUsuario($id, $nome, $email) {
    global $pdo;

    // Prepara a consulta SQL para atualizar os dados do usuário
    $sql = "UPDATE usuarios SET nome = :nome, email = :email WHERE id = :id";

    // Prepara a declaração PDO
    $stmt = $pdo->prepare($sql);

    // Associa os valores aos parâmetros na consulta
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);

    // Executa a consulta
    if ($stmt->execute()) {
        // A atualização foi bem-sucedida
        return true;
    } else {
        // A atualização falhou
        return false;
    }
}

function deletaUsuario($id) {
    global $pdo;
    
    // Prepara a consulta SQL para atualizar os dados do usuário
    $sql = "DELETE FROM usuarios WHERE id = :id";

    // Prepara a declaração PDO
    $stmt = $pdo->prepare($sql);

    // Associa os valores aos parâmetros na consulta
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Executa a consulta
    if ($stmt->execute()) {
        // A atualização foi bem-sucedida
        return true;
    } else {
        // A atualização falhou
        return false;
    }
}

function obterAlunoPorId($id) {
    global $pdo; // Certifique-se de que sua conexão com o banco de dados esteja disponível nesta função

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

    // Prepara a consulta SQL para atualizar os dados do usuário
    $sql = "UPDATE alunos SET nome = :nome, idade = :idade, sexo = :sexo, escola = :escola WHERE id = :id";

    // Prepara a declaração PDO
    $stmt = $pdo->prepare($sql);

    // Associa os valores aos parâmetros na consulta
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':sexo', $sexo, PDO::PARAM_INT);
    $stmt->bindParam(':escola', $escola, PDO::PARAM_INT);

    // Executa a consulta
    if ($stmt->execute()) {
        // A atualização foi bem-sucedida
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
    
    // Prepara a consulta SQL para atualizar os dados do usuário
    $sql = "DELETE FROM alunos WHERE id = :id";

    // Prepara a declaração PDO
    $stmt = $pdo->prepare($sql);

    // Associa os valores aos parâmetros na consulta
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);

    // Executa a consulta
    if ($stmt->execute()) {
        // A atualização foi bem-sucedida
        return true;
    } else {
        // A atualização falhou
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
    // Use preg_match para verificar o formato do tempo
    // O padrão da expressão regular corresponde a "01:30.55"
    $padrao = "/^\d{2}:\d{2}\.\d{2}$/";
    return preg_match($padrao, $tempo) === 1;
}
?>
