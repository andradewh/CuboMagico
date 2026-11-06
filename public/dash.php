<?php
// ARQUIVO: dash.php (Dashboard de Análise de Resultados)

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php'; // Incluir conexão com o banco de dados

// Obtém os dados do usuário logado
if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
    $usuarioLogado = $_SESSION['usuario'];
} else {
    header('Location: login.php');
    exit;
}

$userId = is_array($usuarioLogado) ? ($usuarioLogado['id'] ?? null) : null;
$userEmail = is_array($usuarioLogado) ? ($usuarioLogado['email'] ?? null) : $usuarioLogado;

$stmtUser = $pdo->prepare("SELECT id_evento, superuser FROM usuarios WHERE id = ? OR email = ?");
$stmtUser->execute([$userId, $userEmail]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$defaultEventoCorrente = $userData['id_evento'] ?? null;

// Busca todos os eventos disponíveis para popular a selectbox
$stmtEventos = $pdo->query("SELECT id, nome FROM eventos ORDER BY data DESC");
$listaEventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

// Define o evento selecionado via GET (selectbox) ou usa o padrão do usuário logado
$idEventoCorrente = isset($_GET['evento_id']) && $_GET['evento_id'] !== '' ? (int)$_GET['evento_id'] : $defaultEventoCorrente;

// Se nenhum evento estiver selecionado e houver eventos cadastrados, pega o primeiro por padrão
if (!$idEventoCorrente && !empty($listaEventos)) {
    $idEventoCorrente = $listaEventos[0]['id'];
}

// ==========================================================
// FUNÇÃO DE FORMATO
// ==========================================================

function formatarTempo($segundos) {
    if (!is_numeric($segundos) || $segundos < 0) return '0:00.00';

    $total_segundos = (float)$segundos;
    
    $minutos = floor($total_segundos / 60);
    $segundos_restantes = $total_segundos - ($minutos * 60); 
    $ss_int = floor($segundos_restantes); 
    $ms_float = $segundos_restantes - $ss_int;
    $ms_int = round($ms_float * 100); 

    $mm = (string)$minutos;
    $ss = str_pad($ss_int, 2, '0', STR_PAD_LEFT);
    $ms = str_pad($ms_int, 2, '0', STR_PAD_LEFT);

    return "{$mm}:{$ss}.{$ms}";
}

// ==========================================================
// CONSULTAS SQL COM FILTRO DE CONTEXTO DO EVENTO SELECIONADO
// ==========================================================

if ($idEventoCorrente) {
    // 1.1. Razão de Solves Não Finalizadas (DNF/DNS)
    $sqlSolvesStatus = "
        SELECT SUM(CASE WHEN s.situacao_solver = 1 THEN 1 ELSE 0 END) AS total_ok,
               SUM(CASE WHEN s.situacao_solver = 0 THEN 1 ELSE 0 END) AS total_erro,
               COUNT(1) AS total_solves
        FROM unpivot_solvers s
        JOIN alunos a ON s.aluno = a.id
        WHERE a.id_evento = ?";
    $stmt = $pdo->prepare($sqlSolvesStatus);
    $stmt->execute([$idEventoCorrente]);
    $statsSolves = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1.2. Tempo Médio de Resolução por Modalidade
    $sqlMediaPorModalidade = "
        SELECT 
          m.nome AS modalidade, 
          AVG(CONVERTERTEMPOPARASEGUNDOS(s.tempo)) AS media_tempo_segundos, 
          MIN(CONVERTERTEMPOPARASEGUNDOS(s.tempo)) AS melhor_tempo_individual 
        FROM unpivot_solvers s
        JOIN modalidades m ON s.modalidade = m.id
        JOIN alunos a ON s.aluno = a.id
        WHERE s.situacao_solver = 1 AND a.id_evento = ?
        GROUP BY m.id, m.nome
        ORDER BY media_tempo_segundos ASC";
    $stmt = $pdo->prepare($sqlMediaPorModalidade);
    $stmt->execute([$idEventoCorrente]);
    $mediasModalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 1.3. Contagem de Solves por Status e Modalidade
    $sqlErrosPorModalidade = "
        SELECT 
          m.nome AS modalidade, 
          SUM(CASE WHEN s.situacao_solver = 1 THEN 1 ELSE 0 END) AS ok_count,
          SUM(CASE WHEN s.situacao_solver = 0 THEN 1 ELSE 0 END) AS erro_count
        FROM unpivot_solvers s
        JOIN modalidades m ON s.modalidade = m.id
        JOIN alunos a ON s.aluno = a.id
        WHERE a.id_evento = ?
        GROUP BY m.id, m.nome";
    $stmt = $pdo->prepare($sqlErrosPorModalidade);
    $stmt->execute([$idEventoCorrente]);
    $errosModalidades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total de inscritos do evento
    $stmtInscritos = $pdo->prepare("SELECT count(*) FROM alunos WHERE id_evento = ?");
    $stmtInscritos->execute([$idEventoCorrente]);
    $totalInscritos = $stmtInscritos->fetchColumn();

    // Média global do evento
    $stmtMediaGlobal = $pdo->prepare("
        SELECT AVG(CONVERTERTEMPOPARASEGUNDOS(s.tempo)) AS media_segundos 
        FROM unpivot_solvers s
        JOIN alunos a ON s.aluno = a.id
        WHERE s.situacao_solver = 1 AND a.id_evento = ?");
    $stmtMediaGlobal->execute([$idEventoCorrente]);
    $mediaGlobal = $stmtMediaGlobal->fetchColumn();

} else {
    $statsSolves = ['total_ok' => 0, 'total_erro' => 0, 'total_solves' => 0];
    $mediasModalidades = [];
    $errosModalidades = [];
    $totalInscritos = 0;
    $mediaGlobal = 0;
}

$totalSolves = $statsSolves['total_solves'] ?? 0;
$totalErros = $statsSolves['total_erro'] ?? 0;
$razaoErros = ($totalSolves > 0) ? ($totalErros / $totalSolves) : 0;

// ==========================================================
// PREPARAR DADOS PARA JAVASCRIPT
// ==========================================================

$labelsGrafico1 = json_encode(array_column($mediasModalidades, 'modalidade'));
$dataGrafico1 = json_encode(array_column($mediasModalidades, 'media_tempo_segundos'));

$labelsGrafico2 = json_encode(array_column($errosModalidades, 'modalidade'));
$dataOkGrafico2 = json_encode(array_column($errosModalidades, 'ok_count'));
$dataErroGrafico2 = json_encode(array_column($errosModalidades, 'erro_count'));

// ==========================================================
// INCLUSÃO DO LAYOUT
// ==========================================================

include '../includes/layout_top.php';
include '../includes/header.php';
?>

<main class="container mt-4">

    <!-- CABEÇALHO COM SELECTBOX DE EVENTOS NO TOPO DIREITO -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
        <div>
            <h1 class="font-weight-bold mb-1">Dashboard de Análise de Resultados</h1>
            <p class="text-muted mb-0">Métricas de Desempenho e Organização</p>
        </div>
        <div class="mt-3 mt-md-0">
            <form method="get" action="dash.php" class="form-inline">
                <label for="evento_id" class="mr-2 font-weight-bold text-secondary small text-uppercase">Evento:</label>
                <select name="evento_id" id="evento_id" class="custom-select custom-select-sm shadow-sm" onchange="this.form.submit()" style="min-width: 240px;">
                    <?php foreach ($listaEventos as $ev): ?>
                        <option value="<?php echo $ev['id']; ?>" <?php echo ($idEventoCorrente == $ev['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ev['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="row mb-5">
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-friends mr-2"></i> Participantes</h5>
                    <p class="card-text display-4 font-weight-bold"><?php echo $totalInscritos ?? 0; ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-stopwatch mr-2"></i> Média Global</h5>
                    <p class="card-text display-4 font-weight-bold"><?php echo formatarTempo($mediaGlobal); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-danger h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i> Taxa de Erros</h5>
                    <p class="card-text display-4 font-weight-bold"><?php echo number_format($razaoErros * 100, 2); ?>%</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-dark bg-light h-100 shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-hashtag mr-2"></i> Total de Resoluções</h5>
                    <p class="card-text display-4 font-weight-bold"><?php echo $totalSolves; ?></p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row">

        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-primary text-white font-weight-bold">
                    <h5 class="mb-0">Tempo Médio por Modalidade (segundos)</h5>
                </div>
                <div class="card-body">
                    <canvas id="mediaTempoChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-header bg-danger text-white font-weight-bold">
                    <h5 class="mb-0">Solves Válidas vs. Erros (DNF/DNS)</h5>
                </div>
                <div class="card-body">
                    <canvas id="solvesStatusChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white font-weight-bold">
                    <h5 class="mb-0">Melhor Tempo de Resolução por Modalidade</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>Modalidade</th>
                                    <th>Melhor Tempo (segundos)</th>
                                    <th>Tempo Formatado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mediasModalidades as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['modalidade']); ?></td>
                                    <td><?php echo number_format($item['melhor_tempo_individual'], 2); ?></td>
                                    <td><span class="badge badge-success px-2 py-1"><?php echo formatarTempo($item['melhor_tempo_individual']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($mediasModalidades)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">Nenhuma solve válida registrada ainda para este evento.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    const ctx1 = document.getElementById('mediaTempoChart').getContext('2d');
    const mediaTempoChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo $labelsGrafico1; ?>,
            datasets: [{
                label: 'Média de Tempo (segundos)',
                data: <?php echo $dataGrafico1; ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Tempo Médio (segundos)'
                    }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    const ctx2 = document.getElementById('solvesStatusChart').getContext('2d');
    const solvesStatusChart = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: <?php echo $labelsGrafico2; ?>,
            datasets: [
                {
                    label: 'Solves Válidas (OK)',
                    data: <?php echo $dataOkGrafico2; ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)',
                },
                {
                    label: 'Erros (DNF/DNS)',
                    data: <?php echo $dataErroGrafico2; ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.8)',
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: { stacked: true },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de Solves'
                    }
                }
            }
        }
    });
</script>

<?php include '../includes/layout_bottom.php'; ?>