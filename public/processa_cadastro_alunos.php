<?php
// ARQUIVO: processa_cadastro_alunos.php

// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $idade = $_POST['idade'];
    $sexo = $_POST['sexo'];
    $escola = $_POST['escola'];
    $id_evento = $_POST['id_evento'] ?? null; // Captura o evento enviado pelo formulário

    // Validação básica para garantir que o evento foi selecionado
    if (empty($id_evento)) {
        header('Location: cadastro_alunos.php?erro=evento_obrigatorio');
        exit;
    }

    // Verifique se o aluno já existe dentro do mesmo evento
    $query = "SELECT id FROM cubomagico.alunos WHERE nome = :nome AND idade = :idade AND escola = :escola AND id_evento = :id_evento";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':escola', $escola, PDO::PARAM_STR);
    $stmt->bindParam(':id_evento', $id_evento, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Aluno já cadastrado neste evento
        header('Location: cadastro_alunos.php?erro=aluno_existente');
        exit;
    }

    // Insira o novo aluno no banco de dados incluindo o id_evento
    $inserirQuery = "INSERT INTO alunos (nome, idade, sexo, escola, id_evento) VALUES (:nome, :idade, :sexo, :escola, :id_evento)";
    $stmt = $pdo->prepare($inserirQuery);
    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':idade', $idade, PDO::PARAM_INT);
    $stmt->bindParam(':sexo', $sexo, PDO::PARAM_INT);
    $stmt->bindParam(':escola', $escola, PDO::PARAM_INT);
    $stmt->bindParam(':id_evento', $id_evento, PDO::PARAM_INT);
    
    if ($stmt->execute()) {
        // Cadastrado com sucesso
        header('Location: cadastro_alunos.php?sucesso=cadastro');
    } else {
        // Falha no cadastro
        header('Location: cadastro_alunos.php?erro=cadastro_falhou');
    }
} else {
    // Redirecione se a requisição não for via POST
    header('Location: cadastro_alunos.php');
}
?>