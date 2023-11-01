<?php
// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['idade'];
    $senha = $_POST['sexo'];
    $senha = $_POST['turma'];

    // Verifique se o email já está em uso
    $query = "SELECT id FROM alunos WHERE nome = :nome and idade = :idade and turma = :turma";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':turma', $turma, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Email já está em uso, redirecione de volta para a página de cadastro com um erro
        header('Location: cadastro_alunos.php?erro=aluno_existente');
        exit;
    }

    // Hash a senha antes de armazená-la no banco de dados
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // Insira o novo usuário no banco de dados
    $inserirQuery = "INSERT INTO usuarios (nome, idade, sexo, turma) VALUES (:nome, :idade, :sexo, :turma)";
    $stmt = $pdo->prepare($inserirQuery);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':sexo', $sexo, PDO::PARAM_INT);
    $stmt->bindParam(':turma', $turma, PDO::PARAM_STR);
    
    if ($stmt->execute()) {
        // O usuário foi cadastrado com sucesso, redirecione para uma página de sucesso ou para a página de login
        header('Location: cadastro_alunos.php?sucesso=cadastro');
    } else {
        // Ocorreu um erro ao cadastrar o usuário, redirecione de volta para a página de cadastro com um erro
        header('Location: cadastro_alunos.php?erro=cadastro_falhou');
    }
} else {
    // Redirecione para a página de cadastro se a requisição não for via POST
    header('Location: cadastro_alunos.php');
}
?>
