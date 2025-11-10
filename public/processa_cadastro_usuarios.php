<?php
// ARQUIVO: processa_cadastro_usuarios.php

// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // 1. Verifica se o email já está em uso
    $query = "SELECT id FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Email já está em uso, redirecione de volta para a página de cadastro com um erro
        header('Location: cadastro_usuarios.php?erro=email_em_uso');
        exit;
    }

    // 2. Determina o status superuser (Superuser para o primeiro usuário)
    $stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $totalUsuarios = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    // O primeiro usuário cadastrado (totalUsuarios == 0) será o superuser (1)
    $superuserValue = $totalUsuarios == 0 ? 1 : 0;

    // Hash a senha antes de armazená-la no banco de dados
    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);

    // 3. Insere o novo usuário no banco de dados, incluindo o status superuser
    $inserirQuery = "INSERT INTO usuarios (nome, email, senha, superuser) 
                     VALUES (:nome, :email, :senha, :superuser)";
    $stmt = $pdo->prepare($inserirQuery);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':senha', $senhaHash, PDO::PARAM_STR);
    $stmt->bindParam(':superuser', $superuserValue, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // O usuário foi cadastrado com sucesso
        header('Location: cadastro_usuarios.php?sucesso=cadastro');
    } else {
        // Ocorreu um erro ao cadastrar o usuário
        header('Location: cadastro_usuarios.php?erro=cadastro_falhou');
    }
} else {
    // Redirecione para a página de cadastro se a requisição não for via POST
    header('Location: cadastro_usuario.php');
}
?>