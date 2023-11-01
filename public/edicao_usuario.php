<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Recupere os dados do usuário com base no ID
    // Substitua a linha abaixo pela sua lógica de consulta ao banco de dados
    $usuario = obterUsuarioPorId($id);

    if ($usuario) {
        $nome = $usuario['nome'];
        $email = $usuario['email'];
    } else {
        // Usuário não encontrado, redirecione ou mostre uma mensagem de erro
        echo 'Usuário não encontrado.';
    }
} else {
    // ID do usuário não foi fornecido, redirecione ou mostre uma mensagem de erro
    echo 'ID do usuário não fornecido.';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edição de Usuário</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</head>
<body>
    <h5 class="card-title text-center">Edição de Usuário</h5>
    <form action="processa_edicao_usuario.php" method="post">
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" value="<?= $nome; ?>" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="<?= $email; ?>" required>
        </div>
        <input type="hidden" name="id" value="<?= $id; ?>">
        <button type="submit" class="btn btn-primary btn-block">Atualizar Cadastro</button>
    </form>
</body>
</html>
