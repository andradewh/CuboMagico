<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// Verifica autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $idUsuario = (int) $_POST['id'];

    try {
        // Define o id_evento como NULL para o usuário selecionado
        $stmt = $pdo->prepare("UPDATE usuarios SET id_evento = NULL WHERE id = :id");
        $stmt->execute([':id' => $idUsuario]);

        // Redireciona de volta com sucesso (ajuste a página de retorno se necessário)
        header('Location: cadastro_usuarios.php?sucesso=desvinculado');
        exit;
    } catch (PDOException $e) {
        // Em caso de erro no banco
        echo "Erro ao desvincular usuário: " . $e->getMessage();
    }
} else {
    header('Location: cadastro_usuarios.php');
    exit;
}