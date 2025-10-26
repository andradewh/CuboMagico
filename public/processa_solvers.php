<?php
session_start();
// O nome da página de edição para redirecionamento
$pagina_edicao = 'edicao_alunos_modalidades_resultados.php';

include '../includes/funcs.php';
include '../includes/db_connection.php'; // Assume que $pdo está disponível aqui

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// ==========================================================
// 1. CAPTURA OS VALORES DOS FILTROS ENVIADOS PELO FORMULÁRIO OCULTO
// Estes valores serão usados para manter o estado após o redirecionamento.
// ==========================================================
$modalidade_filtro = $_POST['modalidade_filtro_oculto'] ?? 'todos';
$aluno_filtro = $_POST['aluno_filtro_oculto'] ?? '';
$genero_filtro = $_POST['genero_filtro_oculto'] ?? '1'; // '1' é o default de Masculino

// ==========================================================
// 2. CONSTRUÇÃO BASE DA URL DE REDIRECIONAMENTO
// ==========================================================
$urlRedirecionamento = "{$pagina_edicao}";
$parametrosFiltro = [];

// Adiciona os filtros de volta à URL de status (exceto se for "todos" ou vazio)
if ($modalidade_filtro !== 'todos') {
    $parametrosFiltro[] = "modalidade_filtro=" . urlencode($modalidade_filtro);
}
if (!empty($aluno_filtro)) {
    $parametrosFiltro[] = "aluno_filtro=" . urlencode($aluno_filtro);
}
if ($genero_filtro !== 'todos') {
    $parametrosFiltro[] = "genero=" . urlencode($genero_filtro);
}

// Verifica se a requisição é POST e se o array de resoluções existe e não está vazio
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['resolucoes'])) {
    // Adiciona status e filtros à URL e redireciona
    $urlRedirecionamento .= "?status=no_data";
    if (!empty($parametrosFiltro)) {
        $urlRedirecionamento .= "&" . implode('&', $parametrosFiltro);
    }
    header("Location: {$urlRedirecionamento}");
    exit;
}

$resolucoes = $_POST['resolucoes'];

// ==========================================================
// 3. INICIA A TRANSAÇÃO PDO
// ==========================================================
try {
    $pdo->beginTransaction();
} catch (PDOException $e) {
    error_log("Erro ao iniciar a transação: " . $e->getMessage());
    // Adiciona status e filtros à URL e redireciona
    $urlRedirecionamento .= "?status=error_db_init";
    if (!empty($parametrosFiltro)) {
        $urlRedirecionamento .= "&" . implode('&', $parametrosFiltro);
    }
    header("Location: {$urlRedirecionamento}");
    exit;
}

try {
    // 4. PREPARA O SQL COM ON DUPLICATE KEY UPDATE (sem alteração)
    $sql = "INSERT INTO alunomodalidadesolver (aluno, modalidade, solver1, solver2, solver3, solver4, solver5) 
             VALUES (:aluno, :modalidade, :s1, :s2, :s3, :s4, :s5)
             ON DUPLICATE KEY UPDATE 
                 solver1 = VALUES(solver1), 
                 solver2 = VALUES(solver2), 
                 solver3 = VALUES(solver3), 
                 solver4 = VALUES(solver4), 
                 solver5 = VALUES(solver5)";
    
    $stmt = $pdo->prepare($sql);

    // 5. LOOP E EXECUÇÃO (sem alteração)
    foreach ($resolucoes as $modalidadeId => $resolucoesAluno) {
        foreach ($resolucoesAluno as $alunoId => $solverData) {
            
            $solverValues = [];
            
            // Coletar, validar e preencher todos os 5 solvers
            for ($i = 1; $i <= 5; $i++) {
                $valor = $solverData[$i] ?? ''; 
                
                // Validação do formato (MM:SS.ms)
                if (!empty($valor) && !preg_match("/^\d{2}:\d{2}\.\d{2}$/", $valor)) {
                    error_log("Dados inválidos (formato) para aluno $alunoId e modalidade $modalidadeId. Valor: $valor");
                    $valor = ''; 
                }
                
                $solverValues["solver" . $i] = trim($valor); 
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

            // Executa o INSERT ou UPDATE.
            $stmt->execute($params);
        }
    }

    // 6. CONFIRMA A TRANSAÇÃO
    $pdo->commit();

    // ==========================================================
    // 7. REDIRECIONA COM STATUS DE SUCESSO E OS FILTROS
    // ==========================================================
    $urlRedirecionamento .= "?status=success";
    if (!empty($parametrosFiltro)) {
        $urlRedirecionamento .= "&" . implode('&', $parametrosFiltro);
    }
    header("Location: {$urlRedirecionamento}");
    exit;
    
} catch (PDOException $e) {
    
    // 8. DESFAZ A TRANSAÇÃO
    $pdo->rollBack();
    
    error_log("Erro de Banco de Dados durante o processamento: " . $e->getMessage());

    // ==========================================================
    // 9. REDIRECIONA COM STATUS DE ERRO E OS FILTROS
    // ==========================================================
    $urlRedirecionamento .= "?status=error_db";
    if (!empty($parametrosFiltro)) {
        $urlRedirecionamento .= "&" . implode('&', $parametrosFiltro);
    }
    header("Location: {$urlRedirecionamento}");
    exit;
}
?>