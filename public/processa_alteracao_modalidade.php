<?php
session_start();

// Use os mesmos includes de conexão e funções
include '../includes/funcs.php'; 
include '../includes/db_connection.php'; 

// 1. Verificação de Acesso (Segurança)
if (!isset($_SESSION['usuario'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado.']);
    exit;
}

// 2. Verificação de Dados Recebidos
if (!isset($_POST['modalidade_id']) || !isset($_POST['ativa'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

$modalidade_id = (int)$_POST['modalidade_id'];
// Converte 'true'/'false' (string) do JavaScript para 1/0 (int) para o DB
$ativa = $_POST['ativa'] === 'true' ? 1 : 0;

header('Content-Type: application/json');

try {
    // 3. Preparação da Consulta SQL (Usando Prepared Statements por segurança)
    $sql = "UPDATE modalidades SET ativa = :ativa WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    
    // 4. Execução da Consulta
    $stmt->bindParam(':ativa', $ativa, PDO::PARAM_INT);
    $stmt->bindParam(':id', $modalidade_id, PDO::PARAM_INT);
    
    $stmt->execute();

    // 5. Resposta de Sucesso
    echo json_encode(['success' => true, 'message' => 'Status da modalidade atualizado com sucesso.', 'id' => $modalidade_id, 'new_status' => $ativa]);

} catch (PDOException $e) {
    // 6. Resposta de Erro
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar o banco de dados: ' . $e->getMessage()]);
}

?>