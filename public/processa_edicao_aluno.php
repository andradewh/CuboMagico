<?php
// ARQUIVO: processa_edicao_aluno.php

session_start();
// Inclua o arquivo de conexão com o banco de dados e funções
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

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    if (isset($_POST['id'], $_POST['nome'], $_POST['idade'], $_POST['sexo'], $_POST['escola'], $_POST['id_evento'])){
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $idade = $_POST['idade'];
        $sexo = $_POST['sexo'];
        $escola = $_POST['escola'];
        $id_evento = $_POST['id_evento'];

        // Chamada da função de atualização passando o id_evento
        if (atualizarAluno($id, $nome, $idade, $sexo, $escola, $id_evento)){
            header('Location: cadastro_alunos.php?sucesso=edicao');
            exit;
        } else {
            header('Location: cadastro_alunos.php?erro=1');
            exit;
        }
    } else {
        header('Location: cadastro_alunos.php?erro=2');
        exit;
    }
} else {
    header('Location: cadastro_alunos.php?erro=3');
    exit;
}
?>