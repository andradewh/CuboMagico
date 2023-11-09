<?php
session_start();
include '../includes/header.php';
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

// Função para obter os vínculos de alunos com modalidades
function obterVinculosAlunosModalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT aluno, modalidade FROM alunomodalidade");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter as modalidades
function obterModalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM modalidades");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter os alunos
function obterAlunos() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM alunos");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para obter os valores do Solver existentes no banco de dados
function obterValoresSolverExistente($modalidadeId, $alunoId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT solver1, solver2, solver3, solver4, solver5 FROM alunomodalidadesolver WHERE modalidade = :modalidade AND aluno = :aluno");
    $stmt->bindParam(':modalidade', $modalidadeId, PDO::PARAM_INT);
    $stmt->bindParam(':aluno', $alunoId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Formulário de Resoluções</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script>
document.addEventListener("DOMContentLoaded", function () {
    const generoSelect = document.getElementById('genero');
    const resolucaoRows = document.querySelectorAll('.resolucao-row');

    generoSelect.addEventListener('change', function () {
        const generoSelecionado = generoSelect.value;

        resolucaoRows.forEach(function (row) {
            const generoAluno = row.getAttribute('data-genero');
            if (generoSelecionado === 'todos' || generoSelecionado === generoAluno) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });

        // Ocultar tabelas vazias
        const modalidadeTables = document.querySelectorAll('.modalidade-table');
        modalidadeTables.forEach(function (table) {
            const rows = table.querySelectorAll('.resolucao-row');
            let hasVisibleRows = false;
            rows.forEach(function (row) {
                if (row.style.display !== 'none') {
                    hasVisibleRows = true;
                }
            });

            // Ocultar tabela e seu título se não houver linhas visíveis
            const tableTitle = table.previousElementSibling;
            if (hasVisibleRows) {
                table.style.display = 'table';
                if (tableTitle && tableTitle.classList.contains('modalidade-title')) {
                    tableTitle.style.display = 'block';
                }
            } else {
                table.style.display = 'none';
                if (tableTitle && tableTitle.classList.contains('modalidade-title')) {
                    tableTitle.style.display = 'none';
                }
            }
        });
    });

    // Inicialize a filtragem com base no gênero selecionado
    generoSelect.dispatchEvent(new Event('change'));
});
</script>
</head>
<body>
    <div class="container">
        <h2 class="mt-4 mb-4">Formulário de Resoluções</h2>
        <form action="processa_solvers.php" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <label for="genero" class="input-group-text">Filtrar por gênero:</label>
                            </div>
                            <select class="custom-select" id="genero" name="genero">
                                <option value="1" selected>Masculino</option>
                                <option value="2">Feminino</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        <form action="processa_solvers.php" method="post">
            <?php
            // Recupere a lista de modalidades do banco de dados
            $modalidades = obterModalidades();

            // Recupere a lista de alunos do banco de dados
            $alunos = obterAlunos();

            // Recupere os vínculos de alunos com modalidades do banco de dados
            $vinculos = obterVinculosAlunosModalidades();

            // Crie uma matriz associativa para facilitar a verificação de vínculos
            $vinculosExistentes = [];
            foreach ($vinculos as $vinculo) {
                $alunoId = $vinculo['aluno'];
                $modalidadeId = $vinculo['modalidade'];
                $vinculosExistentes[$modalidadeId][$alunoId] = true;
            }

            // Exiba os campos do formulário
            foreach ($modalidades as $modalidade) {
                $modalidadeId = $modalidade['id'];
                // Verifique se existem resoluções para esta modalidade
                if (isset($vinculosExistentes[$modalidadeId]) && count($vinculosExistentes[$modalidadeId]) > 0) {
                    echo '<h3 class="modalidade-title">' . $modalidade['nome'] . '</h3>';
                    echo '<table class="table table-striped modalidade-table">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Aluno</th>';
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<th>Solver ' . $i . '</th>';
                    }
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    foreach ($alunos as $aluno) {
                        $alunoId = $aluno['id'];
                        // Verifique se existe um vínculo para esta combinação de aluno e modalidade
                        if (isset($vinculosExistentes[$modalidadeId][$alunoId])) {
                            echo '<tr class="resolucao-row" data-genero="' . $aluno['sexo'] . '">';
                            echo '<td>' . $aluno['nome'] . '</td>';
                            $valoresSolverExistente = obterValoresSolverExistente($modalidadeId, $alunoId);
                            for ($i = 1; $i <= 5; $i++) {
                                $inputName = "resolucoes[" . $modalidadeId . "][" . $alunoId . "][" . $i . "]";
                                $valorExistente = $valoresSolverExistente ? $valoresSolverExistente["solver$i"] : ''; // Obtém o valor existente se disponível
                                echo '<td><input type="text" name="' . $inputName . '" value="' . $valorExistente . '"></td>';
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                    echo '</table>';
                }
            }
            ?>
            <button type="submit" class="btn btn-primary float-right">Salvar</button>
        </form>
    </div>
</body>
</html>