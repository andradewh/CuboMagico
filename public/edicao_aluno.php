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

    // Recupere os dados do alunos com base no ID
    $aluno = obterAlunoPorId($id);

    if ($aluno) {
        $nome = $aluno['nome'];
        $idade = $aluno['idade'];
        $sexo = $aluno['sexo'];
        $escola = $aluno['escola'];
    } else {
        // Aluno não encontrado, redirecione ou mostre uma mensagem de erro
        echo 'Aluno não encontrado.';
    }
} else {
    // ID do aluno não foi fornecido, redirecione ou mostre uma mensagem de erro
    echo 'ID do Aluno não fornecido.';
}

// Consulta SQL para obter as escolas
$sqlEscolas = "SELECT id, nome FROM escolas";
$stmtEscolas = $pdo->query($sqlEscolas);
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Consulta SQL para obter os sexos
$sqlSexos = "SELECT id, nome FROM sexo";
$stmtSexos = $pdo->query($sqlSexos);
$sexos = $stmtSexos->fetchAll(PDO::FETCH_ASSOC);
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
    <h5 class="card-title text-center">Edição de Aluno</h5>
    <form action="processa_edicao_aluno.php" method="post">
        <div class="form-group">
            <label for="id">Codigo</label>
            <input type="text" name="id" id="id" class="form-control" placeholder="Codigo" value="<?= $id; ?>" required readonly>
        </div>
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" value="<?= $nome; ?>" required>
        </div>
        <div class="form-group">
        <label for="selectField">Escola</label>
        <select id="escola" name="escola" class="custom-select">
            <?php foreach ($escolas as $escola): ?>
                <option value="<?= $escola['id']; ?>" <?= ($escola['id'] == $escola ? 'selected' : ''); ?>>
                    <?= $escola['nome']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        </div>
        <div class="form-group">
            <label for="idade">Idade</label>
            <input type="number" class="form-control" id="idade" name="idade" placeholder="Idade" value="<?= $idade; ?>" required>
        </div>
        <div class="form-group">
        <label for="selectField">Sexo</label>
        <select id="sexo" name="sexo" class="custom-select">
            <?php foreach ($sexos as $opcao): ?>
                <option value="<?= $opcao['id']; ?>" <?= ($opcao['id'] == $sexo ? 'selected' : ''); ?>>
                    <?= $opcao['nome']; ?>
                </option>
            <?php endforeach; ?>
        </select>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Atualizar</button>
    </form>
</body>
</html>
