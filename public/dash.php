<?php
// ARQUIVO: analise.php (Dashboard de Análise de Resultados)

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php'; // Incluir conexão com o banco de dados

// ==========================================================
// FUNÇÃO DE FORMATO
// ==========================================================

// Função auxiliar para converter tempo em segundos (float) para o formato MM:SS.MS
function formatarTempo($segundos) {
    if (!is_numeric($segundos) || $segundos < 0) return '0:00.00';

    $total_segundos = (float)$segundos;
    
    // 1. Calcular Minutos (M)
    $minutos = floor($total_segundos / 60);
    
    // 2. Calcular Segundos e Centésimos restantes
    $segundos_restantes = $total_segundos - ($minutos * 60); 
    
    // 3. Obter a parte inteira dos segundos (SS)
    $ss_int = floor($segundos_restantes); 
    
    // 4. Obter a parte fracionária (centésimos de segundo - MS)
    // Arredondamos para 2 casas decimais e multiplicamos por 100 para obter o inteiro MS
    $ms_float = $segundos_restantes - $ss_int;
    $ms_int = round($ms_float * 100); 
    
    // Formatação: M não precisa de padding, SS e MS precisam de padding de 2 dígitos
    $mm = (string)$minutos;
    $ss = str_pad($ss_int, 2, '0', STR_PAD_LEFT);
    $ms = str_pad($ms_int, 2, '0', STR_PAD_LEFT);

    return "{$mm}:{$ss}.{$ms}";
}

// ==========================================================
// 1. CONSULTAS SQL PARA MÉTRICAS
// ==========================================================

// 1.1. Razão de Solves Não Finalizadas (DNF/DNS) - Global
$sqlSolvesStatus = "
    SELECT SUM(CASE WHEN situacao_solver = 1 THEN 1 ELSE 0 END) AS total_ok,
      SUM(CASE WHEN situacao_solver = 0 THEN 1 ELSE 0 END) AS total_erro,
      COUNT(1) AS total_solves
    FROM unpivot_solvers";
$statsSolves = $pdo->query($sqlSolvesStatus)->fetch(PDO::FETCH_ASSOC);

$totalSolves = $statsSolves['total_solves'] ?? 0;
$totalErros = $statsSolves['total_erro'] ?? 0;
$razaoErros = ($totalSolves > 0) ? ($totalErros / $totalSolves) : 0;
$razaoSucesso = ($totalSolves > 0) ? ($statsSolves['total_ok'] / $totalSolves) : 0;


// 1.2. Tempo Médio de Resolução por Modalidade (Para Gráfico)
// CORREÇÃO: Removida a função 'converterSegundosParaTempo' daqui para obter o valor numérico (float)
$sqlMediaPorModalidade = "
    SELECT 
      m.nome AS modalidade, 
      AVG(CONVERTERTEMPOPARASEGUNDOS(s.tempo)) AS media_tempo_segundos, -- NOVO: Valor numérico em segundos (float) para o gráfico
      MIN(CONVERTERTEMPOPARASEGUNDOS(s.tempo)) AS melhor_tempo_individual -- Convertido para float para exibição numérica
    FROM unpivot_solvers s
    JOIN modalidades m ON s.modalidade = m.id
    WHERE situacao_solver = 1
    GROUP BY m.nome
    ORDER BY media_tempo_segundos ASC"; // Ordena pelo valor numérico
$mediasModalidades = $pdo->query($sqlMediaPorModalidade)->fetchAll(PDO::FETCH_ASSOC);

// 1.3. Contagem de Solves por Status e Modalidade (Para Gráfico de Erros)
$sqlErrosPorModalidade = "
    SELECT 
      m.nome AS modalidade, 
      SUM(CASE WHEN s.situacao_solver = 1 THEN 1 ELSE 0 END) AS ok_count,
      SUM(CASE WHEN s.situacao_solver = 0 THEN 1 ELSE 0 END) AS erro_count
    FROM unpivot_solvers s
    JOIN modalidades m ON s.modalidade = m.id
    GROUP BY m.nome";
$errosModalidades = $pdo->query($sqlErrosPorModalidade)->fetchAll(PDO::FETCH_ASSOC);

// CORREÇÃO DE SINTAXE E LÓGICA AQUI:
// 1. Ponto e vírgula adicionado no final da string.
// 2. Usando fetchColumn() para obter a contagem como um número simples.
$sqltotalInscritos = "SELECT count(*) FROM alunos";
$totalInscritos = $pdo->query($sqltotalInscritos)->fetchColumn();
// FIM DAS CORREÇÕES

// ==========================================================
// 2. PREPARAR DADOS PARA JAVASCRIPT
// ==========================================================

// Gráfico 1: Média de Tempo
$labelsGrafico1 = json_encode(array_column($mediasModalidades, 'modalidade'));
// CORREÇÃO: Agora usa a nova coluna numérica 'media_tempo_segundos'
$dataGrafico1 = json_encode(array_column($mediasModalidades, 'media_tempo_segundos'));

// Gráfico 2: Razão de Solves por Modalidade
$labelsGrafico2 = json_encode(array_column($errosModalidades, 'modalidade'));
$dataOkGrafico2 = json_encode(array_column($errosModalidades, 'ok_count'));
$dataErroGrafico2 = json_encode(array_column($errosModalidades, 'erro_count'));

// $datatotalInscritos removido pois não é usado nos gráficos e não faz sentido lógico aqui.


// ==========================================================
// 3. INCLUSÃO DO LAYOUT
// ==========================================================

include '../includes/layout_top.php';
include '../includes/header.php';

// Nome de usuário para o título, se estiver autenticado
$nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario'] ?? ''); 
?>

<main class="container mt-4">

    <h1>Dashboard de Análise de Resultados</h1>
    <h2 class="mb-4 text-muted">Métricas de Desempenho e Organização</h2>

    <div class="row mb-5">
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-user-friends me-2"></i> Participantes</h5>
                    <!-- Exibe a variável simples $totalInscritos -->
                    <p class="card-text display-4"><?php echo $totalInscritos ?? 0; ?></p>
                </div>
            </div>
        </div>

        <?php 
            // CORREÇÃO ANTERIOR APLICADA: Obtendo a média global em segundos (float)
            $mediaGlobal = $pdo->query("SELECT AVG(CONVERTERTEMPOPARASEGUNDOS(tempo)) AS media_segundos FROM unpivot_solvers WHERE situacao_solver = 1")->fetchColumn();
        ?>
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-stopwatch me-2"></i> Média Global</h5>
                    <p class="card-text display-4"><?php echo formatarTempo($mediaGlobal); ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card text-white bg-danger h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-exclamation-triangle me-2"></i> Taxa de Erros (DNF/DNS)</h5>
                    <p class="card-text display-4"><?php echo number_format($razaoErros * 100, 2); ?>%</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-dark bg-light h-100">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-hashtag me-2"></i> Total de Resoluções</h5>
                    <p class="card-text display-4"><?php echo $totalSolves; ?></p>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row">

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Tempo Médio por Modalidade (em segundos)</h5>
                </div>
                <div class="card-body">
                    <canvas id="mediaTempoChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-danger text-white">
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
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Melhor Tempo de Resolução por Modalidade</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
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
                                <td><?php echo formatarTempo($item['melhor_tempo_individual']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($mediasModalidades)): ?>
                                <tr><td colspan="3" class="text-center">Nenhuma solve válida registrada ainda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
<script>
    // ==========================================================
    // GRÁFICOS CHART.JS
    // ==========================================================

    // Gráfico 1: Tempo Médio por Modalidade (Bar Chart)
    const ctx1 = document.getElementById('mediaTempoChart').getContext('2d');
    const mediaTempoChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo $labelsGrafico1; ?>,
            datasets: [{
                label: 'Média de Tempo (segundos)',
                // CORREÇÃO: Os dados agora são numéricos (segundos)
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
                legend: {
                    display: false
                },
                title: {
                    display: true,
                    text: 'Tempo Médio de Resolução por Modalidade'
                }
            }
        }
    });

    // Gráfico 2: Solves OK vs Erro por Modalidade (Bar Chart Empilhado)
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
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Número de Solves'
                    }
                }
            },
            plugins: {
                title: {
                    display: true,
                    text: 'Distribuição de Sucesso e Erros por Modalidade'
                }
            }
        }
    });
</script>

<?php include '../includes/layout_bottom.php'; ?>