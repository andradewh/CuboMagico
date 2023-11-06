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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['aluno_modalidade']) && is_array($_POST['aluno_modalidade'])) {
        foreach ($_POST['aluno_modalidade'] as $alunoId => $modalidadesSelecionadas) {
            $mensagem = inserirVinculoAlunoModalidade($alunoId, $modalidadesSelecionadas);
            echo $mensagem;
            header('Location: edicao_alunos_modalidades.php');
        }
    }
}
else {
    echo "Registros inseridos com sucesso!";
    exit;
}
?>
