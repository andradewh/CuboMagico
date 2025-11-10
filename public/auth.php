<?php
require_once '../includes/db_connection.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $query = "SELECT id, nome, email, senha, superuser FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        
        unset($usuario['senha']);

        $usuario['superuser'] = isset($usuario['superuser']) && (int)$usuario['superuser'] == 1 ? 1 : 0;


        $_SESSION['usuario'] = $usuario;

        header('Location: index.php');
        exit;
    } else {
        header('Location: login.php?erro=1');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>