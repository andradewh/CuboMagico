<?php
// ARQUIVO: index.php (Página Inicial/Dashboard)

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php'; // Incluir conexão com o banco de dados

// Função obterFaixaEtaria (Copiar para funcs.php se preferir manter o index mais limpo)
// Deixada aqui para garantir que a faixa etária seja calculada corretamente.
function obterFaixaEtaria($idade) {
    $idade = (int) $idade;
    if ($idade <= 8) { return "Sub 8"; } 
    elseif ($idade >= 9 && $idade <= 10) { return "Sub 10"; } 
    elseif ($idade >= 11 && $idade <= 12) { return "Sub 12"; } 
    elseif ($idade >= 13 && $idade <= 14) { return "Sub 14"; } 
    elseif ($idade >= 15 && $idade <= 16) { return "Sub 16"; } 
    elseif ($idade >= 17 && $idade <= 18) { return "Sub 18"; } 
    else { return "Adulto"; }
}


if (isset($_SESSION['usuario'])) {
    // Certifique-se de que a função obterNomeDoBancoDeDados está em funcs.php
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']); 
} else {
    header('Location: login.php');
    exit;
}

// ==========================================================
// 1. CONSULTAS SQL PARA O DASHBOARD
// ==========================================================

// 1.1 Quantidade total de inscritos (Alunos)
$sqlTotalInscritos = "SELECT COUNT(id) FROM alunos";
$totalInscritos = $pdo->query($sqlTotalInscritos)->fetchColumn();

// 1.2 Lista de modalidades ativas
$sqlModalidadesAtivas = "SELECT nome FROM modalidades WHERE ativa = 1 ORDER BY nome";
$modalidadesAtivas = $pdo->query($sqlModalidadesAtivas)->fetchAll(PDO::FETCH_COLUMN, 0);
$modalidadesStr = implode(', ', $modalidadesAtivas);

// 1.3 Quantidade de inscritos por modalidade (apenas as ativas)
$sqlInscritosPorModalidade = "
    SELECT 
        m.nome, 
        COUNT(DISTINCT am.aluno) AS total
    FROM alunomodalidade am
    INNER JOIN modalidades m ON am.modalidade = m.id
    WHERE m.ativa = 1
    GROUP BY m.nome
    ORDER BY total DESC";
$inscritosPorModalidade = $pdo->query($sqlInscritosPorModalidade)->fetchAll(PDO::FETCH_ASSOC);

// 1.4 Quantidade de inscritos por gênero (Sexos: 1=Masculino, 0=Feminino)
$sqlInscritosPorGenero = "
    SELECT 
        CASE 
            WHEN sexo = 1 THEN 'Masculino' 
            ELSE 'Feminino' 
        END AS genero,
        COUNT(id) AS total
    FROM alunos
    GROUP BY sexo
    ORDER BY genero DESC";
$inscritosPorGenero = $pdo->query($sqlInscritosPorGenero)->fetchAll(PDO::FETCH_ASSOC);

// 1.5 Obter a lista completa de alunos para calcular as faixas etárias
// Importante: Filtra apenas idades válidas e maiores que zero
$sqlAlunosIdades = "SELECT idade FROM alunos WHERE idade IS NOT NULL AND idade > 0"; 
$idadesAlunos = $pdo->query($sqlAlunosIdades)->fetchAll(PDO::FETCH_COLUMN, 0);

// 1.6 Calcular a quantidade de inscritos por faixa etária
$inscritosPorFaixa = [
    "Sub 8" => 0, "Sub 10" => 0, "Sub 12" => 0, 
    "Sub 14" => 0, "Sub 16" => 0, "Sub 18" => 0, "Adulto" => 0
];

foreach ($idadesAlunos as $idade) {
    if (is_numeric($idade)) {
        $faixa = obterFaixaEtaria((int)$idade);
        $inscritosPorFaixa[$faixa] = ($inscritosPorFaixa[$faixa] ?? 0) + 1; 
    }
}


include '../includes/layout_top.php';
include '../includes/header.php';

?>

<main class="container mt-4">

    
    <h2 class="mb-4 text-muted">Dados sintéticos da Competição</h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Inscritos Totais</h5>
                    <h1 class="card-text display-4"><?php echo $totalInscritos; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <h5 class="card-title">Modalidades Ativas (<?php echo count($modalidadesAtivas); ?>)</h5>
                    
                    <div class="mt-3">
                        <?php 
                        if (empty($modalidadesAtivas)): 
                            echo "<p class='card-text'>Nenhuma modalidade ativa.</p>";
                        else:
                            // Itera sobre a lista e cria um badge elegante para cada modalidade
                            foreach ($modalidadesAtivas as $modalidade):
                                // O uso de 'badge-pill' e 'bg-light' torna o badge arredondado e com fundo claro
                        ?>
                                <span class="badge rounded-pill bg-light text-dark me-2 mb-2 p-2">
                                    <i class="fas fa-check-circle me-1"></i> <?php echo htmlspecialchars($modalidade); ?>
                                </span>
                        <?php 
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <h3>Distribuição de Participantes</h3>
        </div>
    </div>

    <div class="row mb-4">
        
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <i class="fas fa-users"></i> Inscritos por Gênero
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($inscritosPorGenero as $genero): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($genero['genero']); ?>
                            <span class="badge badge-primary badge-pill bg-primary"><?php echo $genero['total']; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-baby"></i> Inscritos por Faixa Etária
                </div>
                <ul class="list-group list-group-flush">
                    <?php 
                    // Garante a ordem correta das faixas etárias
                    $ordemFaixas = [
                        "Sub 8", "Sub 10", "Sub 12", "Sub 14", "Sub 16", "Sub 18", "Adulto"
                    ];
                    
                    foreach ($ordemFaixas as $faixa):
                        $totalFaixa = $inscritosPorFaixa[$faixa] ?? 0;
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($faixa); ?>
                            <span class="badge badge-success badge-pill bg-success"><?php echo $totalFaixa; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-cube"></i> Inscritos por Modalidade
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($inscritosPorModalidade)): ?>
                        <li class="list-group-item text-center">Nenhum aluno inscrito em modalidades ativas.</li>
                    <?php else: ?>
                        <?php foreach ($inscritosPorModalidade as $modalidade): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($modalidade['nome']); ?>
                                <span class="badge badge-warning badge-pill bg-warning text-dark"><?php echo $modalidade['total']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

</main>

<?php include '../includes/layout_bottom.php'; ?>