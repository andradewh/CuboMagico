<?php
// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Execute uma consulta para verificar as credenciais do usuário
    $query = "SELECT id, nome, email, senha FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        // Credenciais válidas, crie uma sessão para o usuário
        $_SESSION['usuario'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        
        // Redirecione para a página de boas-vindas
        header('Location: index.php');
        echo "Redirecionado para index.php";
    } else {
        // Credenciais inválidas, redirecione de volta para a página de login com um erro
        header('Location: login.php?erro=1');
    }
} else {
    // Redirecione para a página de login se a requisição não for via POST
    header('Location: login.php');
}
?>
