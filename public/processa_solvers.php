<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php'; // Assume que $pdo está disponível aqui

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se o formulário não foi enviado via POST, redirecione
    header('Location: edicao_alunos_modalidades_resultados.php');
    exit;
}

// ==========================================================
// 1. INICIA A TRANSAÇÃO PDO PARA GARANTIR SEGURANÇA MULTI-USUÁRIO
// ==========================================================
try {
    $pdo->beginTransaction();
} catch (PDOException $e) {
    error_log("Erro ao iniciar a transação: " . $e->getMessage());
    header('Location: edicao_alunos_modalidades_resultados.php?status=error_db_init');
    exit;
}

try {
    $resolucoes = $_POST['resolucoes'];

    // 2. PREPARA O SQL COM ON DUPLICATE KEY UPDATE
    // Esta única instrução faz o INSERT se for novo ou o UPDATE se já existir.
    $sql = "INSERT INTO alunomodalidadesolver (aluno, modalidade, solver1, solver2, solver3, solver4, solver5) 
            VALUES (:aluno, :modalidade, :s1, :s2, :s3, :s4, :s5)
            ON DUPLICATE KEY UPDATE 
                solver1 = VALUES(solver1), 
                solver2 = VALUES(solver2), 
                solver3 = VALUES(solver3), 
                solver4 = VALUES(solver4), 
                solver5 = VALUES(solver5)";
    
    $stmt = $pdo->prepare($sql);

    // 3. LOOP E EXECUÇÃO
    foreach ($resolucoes as $modalidadeId => $resolucoesAluno) {
        foreach ($resolucoesAluno as $alunoId => $solverData) {
            
            $solverValues = [];
            
            // Coletar, validar e preencher todos os 5 solvers
            for ($i = 1; $i <= 5; $i++) {
                $valor = $solverData[$i] ?? ''; // Usa o valor do POST, ou string vazia se não existir
                
                // Validação: Se não for vazio e não seguir o formato MM:SS.CC, loga o erro e usa string vazia
                if (!empty($valor) && !preg_match("/^\d{2}:\d{2}\.\d{2}$/", $valor)) {
                    error_log("Dados inválidos (formato) para aluno $alunoId e modalidade $modalidadeId. Valor: $valor");
                    $valor = ''; // Use string vazia para o banco
                }
                
                $solverValues["solver" . $i] = $valor;
            }

            // Define os parâmetros para a instrução SQL
            $params = [
                ':aluno'      => $alunoId,
                ':modalidade' => $modalidadeId,
                ':s1'         => $solverValues["solver1"],
                ':s2'         => $solverValues["solver2"],
                ':s3'         => $solverValues["solver3"],
                ':s4'         => $solverValues["solver4"],
                ':s5'         => $solverValues["solver5"],
            ];

            // Executa o INSERT ou UPDATE
            $stmt->execute($params);
        }
    }

    // 4. CONFIRMA A TRANSAÇÃO: Salva todas as alterações se não houve erros
    $pdo->commit();

    // Redireciona com sucesso
    header('Location: edicao_alunos_modalidades_resultados.php?status=success');
    exit;
    
} catch (PDOException $e) {
    
    // 5. DESFAZ A TRANSAÇÃO: Se ocorrer um erro, nenhuma alteração será salva
    $pdo->rollBack();
    
    error_log("Erro de Banco de Dados durante o processamento: " . $e->getMessage());
    header('Location: edicao_alunos_modalidades_resultados.php?status=error_db');
    exit;
}
?>