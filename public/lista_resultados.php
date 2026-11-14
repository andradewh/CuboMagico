<?php
// ARQUIVO: lista_resultados.php

session_start();

include '../includes/funcs.php'; 
include '../includes/db_connection.php'; 

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
    $usuarioLogado = $_SESSION['usuario'];
} else {
    header('Location: login.php');
    exit;
}

// Obtém o ID e o E-mail da sessão de forma segura para recuperar o evento corrente e o banner
$userId = is_array($usuarioLogado) ? ($usuarioLogado['id'] ?? null) : null;
$userEmail = is_array($usuarioLogado) ? ($usuarioLogado['email'] ?? null) : $usuarioLogado;

$stmtUser = $pdo->prepare("SELECT id_evento, superuser FROM usuarios WHERE id = ? OR email = ?");
$stmtUser->execute([$userId, $userEmail]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$idEventoCorrente = $userData['id_evento'] ?? null;

// Busca o banner do evento corrente
$bannerEvento = null;
if ($idEventoCorrente) {
    $stmtEvento = $pdo->prepare("SELECT banner FROM eventos WHERE id = ?");
    $stmtEvento->execute([$idEventoCorrente]);
    $eventoData = $stmtEvento->fetch(PDO::FETCH_ASSOC);
    if ($eventoData) {
        $bannerEvento = $eventoData['banner'];
    }
}

function time_to_float($time_str) {
    $time_str = trim((string)$time_str);
    
    if (empty($time_str) || strtoupper($time_str) == 'DNF' || $time_str == '0') {
        return 999999.0; 
    }
    
    if (strpos($time_str, ':') !== false) {
        list($minutes, $seconds_ms) = explode(':', $time_str);
        return ((int)$minutes * 60) + (float)$seconds_ms;
    }
    
    return (float)$time_str;
}

/**
 * Converte um float de segundos para o formato MM:SS.ms
 */
function float_to_time($avg_seconds) {
    if ($avg_seconds >= 999999.0) {
        return "N/A";
    }
    
    $avg_seconds = round($avg_seconds, 2); 
    
    $minutos = floor($avg_seconds / 60);
    $segundosInt = floor($avg_seconds - $minutos * 60);
    $centesimos = round(($avg_seconds - $minutos * 60 - $segundosInt) * 100);
    
    return sprintf('%02d:%02d.%02d', $minutos, $segundosInt, $centesimos);
}

/**
 * Calcula o AVG5 WCA estrito (média de 3, descartando conforme a regra WCA).
 */
function calculate_avg5(array $solvers) {
    $tempos_segundos = [];
    foreach ($solvers as $time_str) {
        $float_val = time_to_float($time_str);
        if ($float_val < 999999.0) {
            $tempos_segundos[] = $float_val;
        }
    }

    $count = count($tempos_segundos);

    if ($count < 4) {
        return "N/A"; 
    }
    
    $sum = array_sum($tempos_segundos);
    
    if ($count == 3) {
        $sum_to_average = $sum;
    } else {
        $least = min($tempos_segundos);
        $sum_to_average = $sum - $least;
        
        if ($count == 5) {
            $greatest = max($tempos_segundos);
            $sum_to_average -= $greatest;
        }
    }

    $avg_seconds = $sum_to_average / 3;
    
    return float_to_time($avg_seconds);
}

/**
 * Encontra e formata o melhor tempo (Best Single) de um array de solves.
 */
function calculate_best_single(array $solvers) {
    $tempos_segundos = [];
    foreach ($solvers as $time_str) {
        $float_val = time_to_float($time_str);
        if ($float_val < 999999.0) {
            $tempos_segundos[] = $float_val;
        }
    }

    if (empty($tempos_segundos)) {
        return "N/A";
    }
    
    $best_single_seconds = min($tempos_segundos);
    
    return float_to_time($best_single_seconds);
}

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

function get_modalidade_icon_suffix($modalidade_nome) {
    switch ($modalidade_nome) {
        case 'Cubo 2x2': return '222';
        case 'Cubo 3x3': return '333';
        case 'Cubo 3x3 OH': return '333oh';
        case 'Cubo 4x4': return '444';
        case 'Cubo 5x5': return '555';
        case 'Cubo Megaminx': return 'minx';
        case 'Cubo Pyraminx': return 'pyram';
        case 'Cubo Skewb': return 'skewb';
        default: return '';
    }
}

function get_icon_html($modalidade_nome) {
    $icon_class_suffix = get_modalidade_icon_suffix($modalidade_nome);
    return $icon_class_suffix ? "<i class='cubing-icon event-{$icon_class_suffix}' style='margin-right: 12px; font-size: 1.1em; color: #495057;'></i>" : '';
}

function formatar_solvers_ranking($solvers) {
    $tempos_str = [
        $solvers['solver1'], $solvers['solver2'], $solvers['solver3'], 
        $solvers['solver4'], $solvers['solver5']
    ];
    
    $tempos_float = array_map('time_to_float', $tempos_str);
    $valid_solves_float = array_filter($tempos_float, function($t) { return $t < 999999.0; });
    $num_solves_validas = count($valid_solves_float);

    if ($num_solves_validas < 3) {
        return array_map(function($t) { return empty($t) || $t == '0' ? '' : htmlspecialchars($t); }, $tempos_str);
    }
    
    $melhor_float = min($valid_solves_float);
    $pior_float = max($valid_solves_float);
    
    $melhor_marcado = false;
    $pior_marcado = false;
    $resultados_formatados = [];

    foreach ($tempos_str as $index => $time_str) {
        $float_val = $tempos_float[$index];
        $display_str = empty($time_str) || $time_str == '0' ? '' : htmlspecialchars($time_str);
        
        $formatado = $display_str;

        if ($float_val < 999999.0) {
            if ($float_val == $melhor_float && !$melhor_marcado && $num_solves_validas >= 4) {
                $formatado = "(" . $display_str . ")";
                $melhor_marcado = true;
            } 
            elseif ($float_val == $pior_float && !$pior_marcado && $num_solves_validas == 5) {
                $formatado = "(" . $display_str . ")";
                $pior_marcado = true;
            } 
        } else {
             if ($num_solves_validas >= 4) {
                 $formatado = ''; 
             }
        }
        
        $resultados_formatados[] = $formatado;
    }

    return $resultados_formatados;
}

$filtro_modalidade_id = isset($_GET['modalidade_id']) && $_GET['modalidade_id'] !== '' ? $_GET['modalidade_id'] : 'all';

$sql_modalidades = "SELECT id, nome FROM modalidades WHERE ativa = 1 ORDER BY nome";
$modalidades_list = $pdo->query($sql_modalidades)->fetchAll(PDO::FETCH_ASSOC);

$where_clause = '';
$params = [];

if ($idEventoCorrente) {
    $where_clause .= " AND a.id_evento = :id_evento";
    $params[':id_evento'] = $idEventoCorrente;
}

if ($filtro_modalidade_id !== 'all' && is_numeric($filtro_modalidade_id)) {
    $filtro_modalidade_id = (int)$filtro_modalidade_id;
    $where_clause .= " AND m.id = :modalidade_id";
    $params[':modalidade_id'] = $filtro_modalidade_id;
}

include '../includes/layout_top.php';
include '../includes/header.php';

$sql = "SELECT 
    m.id AS modalidade_id,
    m.nome AS modalidade,
    a.nome AS aluno,
    a.sexo,
    a.idade,
    ams.solver1,
    ams.solver2,
    ams.solver3,
    ams.solver4,
    ams.solver5
FROM alunomodalidadesolver ams
INNER JOIN alunos a ON ams.aluno = a.id
INNER JOIN modalidades m ON ams.modalidade = m.id
WHERE m.ativa = 1 
AND ( (solver1<>'') + (solver2<>'') + (solver3<>'') + (solver4<>'') + (solver5<>'') ) >= 4
{$where_clause}
ORDER BY m.nome, a.sexo, a.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listasPorModalidade = array();

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $solvers_array = [
        $row['solver1'], $row['solver2'], $row['solver3'], 
        $row['solver4'], $row['solver5']
    ];
    
    $row['melhor'] = calculate_best_single($solvers_array);
    $row['melhor_float'] = time_to_float($row['melhor']);
    
    $row['media'] = calculate_avg5($solvers_array);
    $row['media_float'] = time_to_float($row['media']);
    
    if ($row['media'] === 'N/A') continue;

    $modalidade = $row["modalidade"];
    $sexo = ($row["sexo"] == '1') ? "Masculino" : "Feminino";
    $faixaEtaria = obterFaixaEtaria($row["idade"]); 
    
    if (!isset($listasPorModalidade[$modalidade])) {
        $listasPorModalidade[$modalidade] = array();
    }
    if (!isset($listasPorModalidade[$modalidade][$sexo])) {
        $listasPorModalidade[$modalidade][$sexo] = array();
    }
    if (!isset($listasPorModalidade[$modalidade][$sexo][$faixaEtaria])) {
        $listasPorModalidade[$modalidade][$sexo][$faixaEtaria] = array();
    }
    
    $listasPorModalidade[$modalidade][$sexo][$faixaEtaria][] = $row;
}
?>

<main class="container my-5">
    <title>Ranking de Competidores por Modalidade, Sexo e Faixa Etária</title>
    <style>
        .ranking-table th, .ranking-table td {
            text-align: center;
            vertical-align: middle;
        }
        .ranking-table th:nth-child(1), .ranking-table td:nth-child(1) { width: 70px; }
        .ranking-table th:nth-child(2), .ranking-table td:nth-child(2) { text-align: left; width: 250px; } 
        .ranking-table th:nth-child(3), .ranking-table td:nth-child(3) { font-weight: bold; width: 100px; }
        .ranking-table th:nth-child(4), .ranking-table td:nth-child(4) { font-weight: bold; width: 100px; }
        .ranking-table th:nth-child(n+5) { width: 75px; }

        .podium-1 { background-color: rgba(255, 215, 0, 0.12) !important; font-weight: bold; }
        .podium-2 { background-color: rgba(192, 192, 192, 0.12) !important; }
        .podium-3 { background-color: rgba(205, 127, 50, 0.12) !important; }

        .filter-container {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            border: 1px solid #e9ecef;
        }
        .icon-filter-group {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .icon-filter-group a {
            padding: 10px 16px;
            font-size: 1.1em;
            text-decoration: none;
            color: #495057;
            border: 1px solid #ced4da;
            background-color: #fff;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .icon-filter-group a.active {
            color: #fff;
            background-color: #212529;
            border-color: #212529;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            transform: translateY(-1px);
        }
        .icon-filter-group a:hover:not(.active) {
            background-color: #e9ecef;
            border-color: #adb5bd;
            color: #212529;
        }
        .icon-filter-group a i {
            font-size: 1.2em;
            color: inherit;
        }

        .result-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            background: #fff;
            margin-bottom: 30px;
            overflow: hidden;
        }
    </style>

    <!-- EXIBIÇÃO DO BANNER DO EVENTO ACIMA DO TÍTULO DE RESULTADOS -->
    <?php if (!empty($bannerEvento)): ?>
        <div class="mb-4 text-center">
            <img src="../assets/files/<?php echo htmlspecialchars($bannerEvento); ?>" 
                alt="Banner do Evento" 
                class="img-fluid rounded shadow-sm mx-auto d-block" 
                style="width: 100%; height: auto; max-width: 100%;">
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-medal text-warning mr-2"></i> Resultados Oficiais</h2>
            <p class="text-muted mb-0">Acompanhe o ranking detalhado de cada modalidade e categoria do evento atual.</p>
        </div>
    </div>

    <div class="filter-container mb-5">
        <label class="d-block mb-3 text-center text-uppercase font-weight-bold text-secondary" style="font-size: 0.85rem; letter-spacing: 1px;">Filtrar por Modalidade</label> 
        <div class="icon-filter-group">
            <a href="?modalidade_id=all" 
                class="<?php echo ($filtro_modalidade_id === 'all') ? 'active' : ''; ?>" 
                title="Todas as Modalidades">
                <i class="fas fa-list mr-2" style="font-size: 0.9em;"></i> <span style="font-size: 0.9rem; font-weight: 500;">Todas</span>
            </a>

            <?php foreach ($modalidades_list as $mod): 
                $icon_suffix = get_modalidade_icon_suffix($mod['nome']);
                $is_active = ((string)$filtro_modalidade_id === (string)$mod['id']);
                
                if ($icon_suffix):
            ?>
                <a href="?modalidade_id=<?php echo htmlspecialchars($mod['id']); ?>" 
                    class="<?php echo $is_active ? 'active' : ''; ?>" 
                    title="<?php echo htmlspecialchars($mod['nome']); ?>">
                    <i class="cubing-icon event-<?php echo $icon_suffix; ?>"></i>
                </a>
            <?php 
                endif;
            endforeach; ?>
        </div>
        
        <?php if (empty($modalidades_list) && $filtro_modalidade_id === 'all'): ?>
            <div class='alert alert-warning text-center mt-3 mb-0'>Nenhuma modalidade está ativa no momento.</div>
        <?php endif; ?>
    </div>

    <?php
    if (empty($listasPorModalidade)) {
        $msg = ($filtro_modalidade_id !== 'all') 
               ? "Nenhum resultado encontrado para a modalidade selecionada neste evento." 
               : "Nenhum aluno atingiu os requisitos de tempo para as modalidades ativas neste evento.";
        echo "<div class='alert alert-info shadow-sm text-center py-4'>{$msg}</div>";
    }

    $ordemFaixas = ["Sub 8", "Sub 10", "Sub 12", "Sub 14", "Sub 16", "Sub 18", "Adulto"];
    
    foreach ($listasPorModalidade as $modalidade => $dadosPorSexo) {
        $icon_html = get_icon_html($modalidade);
        
        echo "<div class='result-card p-4 p-md-5'>";
        echo "<h3 class='mb-4 pb-2 border-bottom text-dark d-flex align-items-center' style='font-weight: 700;'>{$icon_html}" . htmlspecialchars($modalidade) . "</h3>";

        foreach ($dadosPorSexo as $sexo => $dadosPorFaixaEtaria) {
            echo "<h5 class='text-secondary font-weight-bold mt-4 mb-3'><i class='fa-solid fa-venus-mars mr-2'></i> Sexo: " . htmlspecialchars($sexo) . "</h5>";

            uksort($dadosPorFaixaEtaria, function ($a, $b) use ($ordemFaixas) {
                $posA = array_search($a, $ordemFaixas);
                $posB = array_search($b, $ordemFaixas);
                return $posA <=> $posB;
            });

            foreach ($dadosPorFaixaEtaria as $faixaEtaria => $listaDeAlunos) {
                
                usort($listaDeAlunos, function ($a, $b) {
                    $time_a = $a["media_float"]; 
                    $time_b = $b["media_float"];
                    return $time_a <=> $time_b; 
                });

                if (count($listaDeAlunos) > 0) {
                    echo "<h6 class='font-weight-bold text-muted mt-3 mb-2'><i class='fa-solid fa-layer-group mr-1'></i> Faixa Etária: " . htmlspecialchars($faixaEtaria) . "</h6>";
                    
                    echo '<div class="table-responsive mb-4">
                            <table class="table table-hover table-bordered ranking-table align-middle bg-white mb-0" style="border-radius: 8px; overflow: hidden;">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Colocação</th>
                                    <th>Competidor</th>
                                    <th>Melhor</th> 
                                    <th>Média</th>
                                    <th>S1</th>
                                    <th>S2</th>
                                    <th>S3</th>
                                    <th>S4</th>
                                    <th>S5</th>
                                </tr>
                            </thead>
                            <tbody>';

                    $colocacao = 1;

                    foreach ($listaDeAlunos as $aluno) {
                        $solvers_formatadas = formatar_solvers_ranking([
                            'solver1' => $aluno["solver1"],
                            'solver2' => $aluno["solver2"],
                            'solver3' => $aluno["solver3"],
                            'solver4' => $aluno["solver4"],
                            'solver5' => $aluno["solver5"]
                        ]);

                        $podium_class = '';
                        if ($colocacao == 1) $podium_class = 'podium-1';
                        elseif ($colocacao == 2) $podium_class = 'podium-2';
                        elseif ($colocacao == 3) $podium_class = 'podium-3';

                        echo "<tr class='{$podium_class}'>";
                        
                        if ($colocacao == 1) {
                            echo "<td><span class='badge px-2 py-1 font-weight-bold' style='background-color: #ffd700; color: #333;'><i class='fas fa-trophy mr-1'></i> 1º</span></td>";
                        } elseif ($colocacao == 2) {
                            echo "<td><span class='badge px-2 py-1 font-weight-bold' style='background-color: #c0c0c0; color: #333;'><i class='fas fa-trophy mr-1'></i> 2º</span></td>";
                        } elseif ($colocacao == 3) {
                            echo "<td><span class='badge px-2 py-1 font-weight-bold' style='background-color: #cd7f32; color: #fff;'><i class='fas fa-trophy mr-1'></i> 3º</span></td>";
                        } else {
                            echo "<td><strong>" . $colocacao . "º</strong></td>";
                        }

                        echo "<td style='text-align: left; font-weight: 500;'>" . htmlspecialchars($aluno["aluno"]) . "</td>";
                        echo "<td><span class='text-success'>" . htmlspecialchars($aluno["melhor"]) . "</span></td>"; 
                        echo "<td><span class='text-primary font-weight-bold'>" . htmlspecialchars($aluno["media"]) . "</span></td>"; 
                        
                        echo "<td class='text-muted small'>" . $solvers_formatadas[0] . "</td>";
                        echo "<td class='text-muted small'>" . $solvers_formatadas[1] . "</td>";
                        echo "<td class='text-muted small'>" . $solvers_formatadas[2] . "</td>";
                        echo "<td class='text-muted small'>" . $solvers_formatadas[3] . "</td>";
                        echo "<td class='text-muted small'>" . $solvers_formatadas[4] . "</td>";

                        echo "</tr>";
                        $colocacao++;
                    }

                    echo '</tbody>
                        </table>
                    </div>';
                }
            }
        }
        echo "</div>"; 
    }
    ?>
</main>

<?php include '../includes/layout_bottom.php'; ?>