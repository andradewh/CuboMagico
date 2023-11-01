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
?>
