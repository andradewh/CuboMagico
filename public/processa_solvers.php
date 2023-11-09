<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    // Verificar se o formulário foi enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            // Processar os dados do formulário
            $resolucoes = $_POST['resolucoes'];

            // Loop através dos dados das resoluções
            foreach ($resolucoes as $modalidadeId => $resolucoesAluno) {
                foreach ($resolucoesAluno as $alunoId => $solverData) {
                    // Inicialize um array para os valores do solver
                    $solverValues = [];

                    // Loop através dos valores do solver (solver1 a solver5)
                    for ($i = 1; $i <= 5; $i++) {
                        $valor = $solverData[$i];

                        // Verifique se o campo não está vazio
                        if (!empty($valor)) {
                            // Verifique se o valor está no formato correto (MM:SS.CC)
                            if (preg_match("/^\d{2}:\d{2}\.\d{2}$/", $valor)) {
                                $solverValues["solver" . $i] = $valor;
                            } else {
                                $teste = preg_match("/^\d{2}:\d{2}\.\d{2}$/", $valor);
                                error_log("Dados inválidos para aluno $alunoId e modalidade $modalidadeId com o valor de $valor/$teste.");
                            }
                        }
                    }

                    // Certifique-se de que existam valores válidos para inserir
                    if (!empty($solverValues)) {
                        // Aqui, você pode inserir os dados no banco de dados
                        // Certifique-se de ajustar a consulta SQL de acordo com sua estrutura de tabela
                        $sqlExcluir = "DELETE FROM alunomodalidadesolver WHERE aluno = :alunoId and modalidade = :modalidadeId";
                        $stmtExcluir = $pdo->prepare($sqlExcluir);
                        $stmtExcluir->bindParam(':alunoId', $alunoId, PDO::PARAM_INT);
                        $stmtExcluir->bindParam(':modalidadeId', $modalidadeId, PDO::PARAM_INT);
                        $stmtExcluir->execute();

                        $sql = "INSERT INTO alunomodalidadesolver (aluno, modalidade, " . implode(", ", array_keys($solverValues)) . ") VALUES (:aluno, :modalidade, " . implode(", ", array_map(function($key) {
                            return ":" . $key;
                        }, array_keys($solverValues))) . ")";

                        $stmt = $pdo->prepare($sql);
                        $params = [
                            'aluno' => $alunoId,
                            'modalidade' => $modalidadeId
                        ];

                        // Adicione os valores do solver aos parâmetros
                        foreach ($solverValues as $key => $value) {
                            $params[":" . $key] = $value;
                        }

                        $stmt->execute($params);
                    }
                }
            }

            // Redirecionar para a página de sucesso ou qualquer outra página desejada
            header('Location: edicao_alunos_modalidades_resultados.php');
            exit;
        } catch (PDOException $e) {
            error_log("Erro de Banco de Dados: " . $e->getMessage());
        }
    } else {
        // Se o formulário não foi enviado, redirecione de volta para a página do formulário
        header('Location: edicao_alunos_modalidades_resultados.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?>