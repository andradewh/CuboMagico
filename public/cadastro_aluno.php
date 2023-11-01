<?php
session_start();
include '../includes/header.php';
include '../includes/funcs.php';

if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Pessoas</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center">Cadastro de Alunos</h5>
                        <form action="processa_cadastro_alunos.php" method="post">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" required>
                            </div>
                            <div class="form-group">
                                <label for="idade">Idade</label>
                                <input type="number" class="form-control" id="idade" name="idade" placeholder="Idade" required>
                            </div>
                            <div class="form-group">
                            <label for="selectField">Sexo</label>
                                <select name="Sexo" class="custom-select" label="Sexo">
                                    <option value="1" selected>Masculino</option>
                                    <option value="2">Feminino</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="turma">Turma</label>
                                <input type="text" class="form-control" id="turma" name="turma">
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>