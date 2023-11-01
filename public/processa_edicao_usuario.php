<?php
session_start();
// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST'){
    IF(isset($_POST['id']) && isset($_POST['nome']) && isset($_POST['email'])){
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];

        if (atualizarUsuario($id, $nome, $email)){
            header('Location: cadastro_usuarios.php');
            exit;
        }
        else{
            header('Location: cadastro_usuarios.php?erro=1');
            exit;
        }
    }
    else{
        header('Location: cadastro_usuarios.php?erro=2');
        exit;
    }
}
else {
    header('Location: cadastro_usuarios.php?erro=3');
    exit;
}
?>
