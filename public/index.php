<?php
// ARQUIVO: index.php (Página Inicial/Dashboard)

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php'; // Incluir conexão com o banco de dados

// Função para calcular a faixa etária
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
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']); 
    $usuarioSessao = $_SESSION['usuario'];
} else {
    header('Location: login.php');
    exit;
}

// Identifica o usuário logado e busca o seu 'id_evento' vinculado no banco de dados
$userId = is_array($usuarioSessao) ? ($usuarioSessao['id'] ?? null) : null;
$userEmail = is_array($usuarioSessao) ? ($usuarioSessao['email'] ?? null) : $usuarioSessao;

$stmtUser = $pdo->prepare("SELECT id, nome, superuser, id_evento FROM usuarios WHERE id = ? OR email = ?");
$stmtUser->execute([$userId, $userEmail]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$idEventoCorrente = $userData['id_evento'] ?? null;
$isSuperuser = $userData['superuser'] ?? 0;

// Busca o nome do evento corrente para exibir no lugar de "Dados sintéticos"
$nomeEvento = "Painel Geral da Competição";
if ($idEventoCorrente) {
    $stmtEvento = $pdo->prepare("SELECT nome FROM eventos WHERE id = ?");
    $stmtEvento->execute([$idEventoCorrente]);
    $eventoData = $stmtEvento->fetch(PDO::FETCH_ASSOC);
    if ($eventoData) {
        $nomeEvento = $eventoData['nome'];
    }
}

// ==========================================================
// CONSULTAS SQL COM FILTRO DE CONTEXTO DO EVENTO
// ==========================================================

if ($idEventoCorrente) {
    // 1.1 Quantidade total de inscritos do evento específico
    $stmtTotal = $pdo->prepare("SELECT COUNT(id) FROM alunos WHERE id_evento = ?");
    $stmtTotal->execute([$idEventoCorrente]);
    $totalInscritos = $stmtTotal->fetchColumn();

    // 1.2 Lista de modalidades ativas (geral do sistema)
    $sqlModalidadesAtivas = "SELECT nome FROM modalidades WHERE ativa = 1 ORDER BY nome";
    $modalidadesAtivas = $pdo->query($sqlModalidadesAtivas)->fetchAll(PDO::FETCH_COLUMN, 0);

    // 1.3 Quantidade de inscritos por modalidade (apenas alunos do evento corrente)
    $sqlInscritosPorModalidade = "
        SELECT 
            m.nome, 
            COUNT(DISTINCT am.aluno) AS total
        FROM alunomodalidade am
        INNER JOIN modalidades m ON am.modalidade = m.id
        INNER JOIN alunos a ON am.aluno = a.id
        WHERE m.ativa = 1 AND a.id_evento = ?
        GROUP BY m.id, m.nome
        ORDER BY total DESC";
    $stmtMod = $pdo->prepare($sqlInscritosPorModalidade);
    $stmtMod->execute([$idEventoCorrente]);
    $inscritosPorModalidade = $stmtMod->fetchAll(PDO::FETCH_ASSOC);

    // 1.4 Quantidade de inscritos por gênero no evento corrente
    $sqlInscritosPorGenero = "
        SELECT 
            CASE 
                WHEN sexo = 1 THEN 'Masculino' 
                ELSE 'Feminino' 
            END AS genero,
            COUNT(id) AS total
        FROM alunos
        WHERE id_evento = ?
        GROUP BY sexo
        ORDER BY genero DESC";
    $stmtGen = $pdo->prepare($sqlInscritosPorGenero);
    $stmtGen->execute([$idEventoCorrente]);
    $inscritosPorGenero = $stmtGen->fetchAll(PDO::FETCH_ASSOC);

    // 1.5 Obter idades dos alunos do evento corrente para as faixas etárias
    $stmtIdades = $pdo->prepare("SELECT idade FROM alunos WHERE id_evento = ? AND idade IS NOT NULL AND idade > 0");
    $stmtIdades->execute([$idEventoCorrente]);
    $idadesAlunos = $stmtIdades->fetchAll(PDO::FETCH_COLUMN, 0);

} else {
    // Fallback caso o usuário logado seja um superusuário sem evento fixo vinculado
    $totalInscritos = $pdo->query("SELECT COUNT(id) FROM alunos")->fetchColumn();

    $sqlModalidadesAtivas = "SELECT nome FROM modalidades WHERE ativa = 1 ORDER BY nome";
    $modalidadesAtivas = $pdo->query($sqlModalidadesAtivas)->fetchAll(PDO::FETCH_COLUMN, 0);

    $sqlInscritosPorModalidade = "
        SELECT 
            m.nome, 
            COUNT(DISTINCT am.aluno) AS total
        FROM alunomodalidade am
        INNER JOIN modalidades m ON am.modalidade = m.id
        WHERE m.ativa = 1
        GROUP BY m.id, m.nome
        ORDER BY total DESC";
    $inscritosPorModalidade = $pdo->query($sqlInscritosPorModalidade)->fetchAll(PDO::FETCH_ASSOC);

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

    $sqlAlunosIdades = "SELECT idade FROM alunos WHERE idade IS NOT NULL AND idade > 0"; 
    $idadesAlunos = $pdo->query($sqlAlunosIdades)->fetchAll(PDO::FETCH_COLUMN, 0);
}

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

    <!-- Título substituído pelo nome dinâmico do evento -->
    <h2 class="mb-4 text-dark font-weight-bold">
        <i class="fas fa-trophy text-warning mr-2"></i> <?php echo htmlspecialchars($nomeEvento); ?>
    </h2>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-primary shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Inscritos Totais</h5>
                    <h1 class="card-text display-4 font-weight-bold"><?php echo $totalInscritos; ?></h1>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card text-white bg-success h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title">Modalidades Ativas (<?php echo count($modalidadesAtivas); ?>)</h5>
                    
                    <div class="mt-3">
                        <?php 
                        if (empty($modalidadesAtivas)): 
                            echo "<p class='card-text'>Nenhuma modalidade ativa.</p>";
                        else:
                            foreach ($modalidadesAtivas as $modalidade):
                        ?>
                                <span class="badge rounded-pill bg-light text-dark mr-2 mb-2 p-2">
                                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($modalidade); ?>
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
            <h3 class="text-secondary">Distribuição de Participantes</h3>
        </div>
    </div>

    <div class="row mb-4">
        
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-info text-white font-weight-bold">
                    <i class="fas fa-users mr-1"></i> Inscritos por Gênero
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($inscritosPorGenero)): ?>
                        <li class="list-group-item text-center text-muted">Nenhum registro encontrado.</li>
                    <?php else: ?>
                        <?php foreach ($inscritosPorGenero as $genero): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($genero['genero']); ?>
                                <span class="badge badge-primary badge-pill"><?php echo $genero['total']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-success text-white font-weight-bold">
                    <i class="fas fa-baby mr-1"></i> Inscritos por Faixa Etária
                </div>
                <ul class="list-group list-group-flush">
                    <?php 
                    $ordemFaixas = [
                        "Sub 8", "Sub 10", "Sub 12", "Sub 14", "Sub 16", "Sub 18", "Adulto"
                    ];
                    
                    foreach ($ordemFaixas as $faixa):
                        $totalFaixa = $inscritosPorFaixa[$faixa] ?? 0;
                    ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?php echo htmlspecialchars($faixa); ?>
                            <span class="badge badge-success badge-pill"><?php echo $totalFaixa; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-warning text-dark font-weight-bold">
                    <i class="fas fa-cube mr-1"></i> Inscritos por Modalidade
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (empty($inscritosPorModalidade)): ?>
                        <li class="list-group-item text-center text-muted">Nenhum aluno inscrito em modalidades ativas.</li>
                    <?php else: ?>
                        <?php foreach ($inscritosPorModalidade as $modalidade): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($modalidade['nome']); ?>
                                <span class="badge badge-warning badge-pill text-dark"><?php echo $modalidade['total']; ?></span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

</main>

<?php include '../includes/layout_bottom.php'; ?>