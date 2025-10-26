<?php
session_start();

include '../includes/funcs.php'; 
include '../includes/db_connection.php'; 

if (isset($_SESSION['usuario'])) {
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
 * @param array $solvers Array de strings de tempo (ex: '00:49.33', '0', '00:50.44', ...)
 * @return string O tempo médio formatado (MM:SS.ms)
 */
function calculate_avg5(array $solvers) {
    $tempos_segundos = [];
    foreach ($solvers as $time_str) {
        $float_val = time_to_float($time_str);
        // Filtra APENAS os tempos válidos
        if ($float_val < 999999.0) {
            $tempos_segundos[] = $float_val;
        }
    }

    $count = count($tempos_segundos); // Número de solves válidas (3, 4 ou 5)

    if ($count < 4) {
        return "N/A"; 
    }
    
    $sum = array_sum($tempos_segundos);
    
    if ($count == 3) {
        // N=3: Média simples (nenhum descarte)
        $sum_to_average = $sum;
    } else {
        // N=4 ou N=5: Descartar o Least (melhor). 
        $least = min($tempos_segundos);
        $sum_to_average = $sum - $least;
        
        if ($count == 5) {
            // N=5: Descartar também o Greatest (pior entre os 5)
            $greatest = max($tempos_segundos);
            $sum_to_average -= $greatest;
        }
        // Se N=4, o Worst é o tempo em branco (999999.0), que já foi excluído da soma $sum.
        // Portanto, em N=4, descartamos APENAS o Least.
    }

    // O divisor para o AVG é sempre 3
    $avg_seconds = $sum_to_average / 3;
    
    return float_to_time($avg_seconds);
}

/**
 * Encontra e formata o melhor tempo (Best Single) de um array de solves.
 * @param array $solvers Array de strings de tempo.
 * @return string O melhor tempo formatado (MM:SS.ms) ou 'N/A'.
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

/**
 * Retorna o sufixo de classe WCA para o ícone.
 */
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
        // Adicione mais casos conforme a necessidade, usando os nomes exatos das suas modalidades
        default: return '';
    }
}

/**
 * Retorna o HTML do ícone da modalidade.
 */
function get_icon_html($modalidade_nome) {
    $icon_class_suffix = get_modalidade_icon_suffix($modalidade_nome);
    // Nota: O estilo 'color: #333' aqui foi mantido para o H1/H2 do ranking,
    // mas o filtro usará o CSS global.
    return $icon_class_suffix ? "<i class='cubing-icon event-{$icon_class_suffix}' style='margin-right: 10px; font-size: 1.2em; color: #333;'></i>" : '';
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
    
    // Encontrar o melhor e o pior DENTRO dos tempos válidos
    $melhor_float = min($valid_solves_float);
    $pior_float = max($valid_solves_float);
    
    $melhor_marcado = false;
    $pior_marcado = false;
    $resultados_formatados = [];

    foreach ($tempos_str as $index => $time_str) {
        $float_val = $tempos_float[$index];
        $display_str = empty($time_str) || $time_str == '0' ? '' : htmlspecialchars($time_str);
        
        $formatado = $display_str;

        // Regra de descarte da WCA:
        if ($float_val < 999999.0) {
             // 1. Marcação do Least (Melhor) - Descartado em N=4 e N=5
            if ($float_val == $melhor_float && !$melhor_marcado && $num_solves_validas >= 4) {
                $formatado = "(" . $display_str . ")";
                $melhor_marcado = true;
            } 
            // 2. Marcação do Greatest (Pior) - Descartado APENAS em N=5
            elseif ($float_val == $pior_float && !$pior_marcado && $num_solves_validas == 5) {
                $formatado = "(" . $display_str . ")";
                $pior_marcado = true;
            } 
        } else {
             // Tempo inválido (Branco) - Descartado (Worst) em N=4 e N=5
             if ($num_solves_validas >= 4) {
                 $formatado = ''; 
             }
        }
        
        $resultados_formatados[] = $formatado;
    }

    return $resultados_formatados;
}


// ----------------------------------------------------------
// CONFIGURAÇÃO DO FILTRO (Mantido)
// ----------------------------------------------------------

$filtro_modalidade_id = isset($_GET['modalidade_id']) && $_GET['modalidade_id'] !== '' ? $_GET['modalidade_id'] : 'all';

$sql_modalidades = "SELECT id, nome FROM modalidades ORDER BY nome";
$modalidades_list = $pdo->query($sql_modalidades)->fetchAll(PDO::FETCH_ASSOC);

$where_clause = '';
if ($filtro_modalidade_id !== 'all' && is_numeric($filtro_modalidade_id)) {
    $filtro_modalidade_id = (int)$filtro_modalidade_id;
    $where_clause = "AND m.id = {$filtro_modalidade_id}";
}

include '../includes/layout_top.php';
include '../includes/header.php';

// ==========================================================
// 2. CONSULTA SQL SIMPLIFICADA (Apenas seleção dos dados)
// ==========================================================

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
-- Filtra para ter pelo menos 4 solves válidas para calcular a média
WHERE ( (solver1<>'') + (solver2<>'') + (solver3<>'') + (solver4<>'') + (solver5<>'') ) >= 4
{$where_clause}
ORDER BY m.nome, a.sexo, a.nome"; 

$result = $pdo->query($sql);

$listasPorModalidade = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    
    // ** CÁLCULO E ARMAZENAMENTO DO AVG5 E MELHOR TEMPO NO PHP **
    $solvers_array = [
        $row['solver1'], $row['solver2'], $row['solver3'], 
        $row['solver4'], $row['solver5']
    ];
    
    // Novo cálculo para o Melhor Tempo
    $row['melhor'] = calculate_best_single($solvers_array);
    $row['melhor_float'] = time_to_float($row['melhor']);
    
    $row['media'] = calculate_avg5($solvers_array);
    $row['media_float'] = time_to_float($row['media']); // Para ordenação
    
    if ($row['media'] === 'N/A') continue;

    $modalidade = $row["modalidade"];
    $sexo = ($row["sexo"] == '1') ? "Masculino" : "Feminino";
    $faixaEtaria = obterFaixaEtaria($row["idade"]); 
    
    // Agrupamento (Mantido)
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

<main class="container">
    <title>Ranking de Alunos por Modalidade, Sexo e Faixa Etária</title>
    <style>
        /* AJUSTE DE LARGURA SOLICITADO */
        .ranking-table th, .ranking-table td {
            text-align: center;
            vertical-align: middle;
        }
        .ranking-table th:nth-child(1), .ranking-table td:nth-child(1) { 
            /* Colocação (Menor) */
            width: 70px; 
        }
        .ranking-table th:nth-child(2), .ranking-table td:nth-child(2) { 
            /* Aluno (Maior) */
            text-align: left; 
            width: 250px; 
        } 
        .ranking-table th:nth-child(3), .ranking-table td:nth-child(3) { 
            /* Melhor Tempo */
            font-weight: bold; 
            width: 100px; 
        }
        .ranking-table th:nth-child(4), .ranking-table td:nth-child(4) { 
            /* Média */
            font-weight: bold; 
            width: 100px; 
        }
        .ranking-table th:nth-child(5), .ranking-table th:nth-child(6), 
        .ranking-table th:nth-child(7), .ranking-table th:nth-child(8), 
        .ranking-table th:nth-child(9) { 
            /* S1 a S5 */
            width: 80px; 
        }
        
        /* ESTILOS PARA OS ÍCONES CLICÁVEIS */
        .icon-filter-group {
            /* Centraliza os ícones horizontalmente */
            display: flex;
            justify-content: center;
            flex-wrap: wrap; 
        }
        
        .icon-filter-group a {
            /* Estilo base para parecer um botão pequeno */
            padding: 8px 12px;
            font-size: 1.1em;
            margin-right: 5px;
            margin-left: 5px; 
            margin-bottom: 5px;
            text-decoration: none;
            /* MUDANÇA: Cores Cinza Escuro */
            color: #6c757d; 
            border: 1px solid #6c757d; 
            background-color: #fff;
            border-radius: .25rem;
            display: inline-block;
            line-height: 1.5;
            text-align: center;
        }
        /* MUDANÇA: Estilo para o botão ativo (selecionado) */
        .icon-filter-group a.active {
            color: #fff;
            background-color: #343a40; /* Cinza muito escuro para ativo */
            border-color: #343a40; 
        }
        .icon-filter-group a:hover:not(.active) {
            background-color: #e9ecef;
        }
        /* MUDANÇA: Garante que o ícone interno use a cor do link pai */
        .icon-filter-group a i.fa-list,
        .icon-filter-group a i.cubing-icon {
            font-size: 1.2em;
            color: inherit; 
        }
    </style>
    
    <div class="mt-4 mb-4">
        <label class="d-block mb-2 text-center" style="font-weight: bold;">Filtrar por Modalidade:</label>
        
        <div class="icon-filter-group">
            <a href="?modalidade_id=all" 
               class="<?php echo ($filtro_modalidade_id === 'all') ? 'active' : ''; ?>" 
               title="Todas as Modalidades">
                <i class="fas fa-list"></i>
            </a>

            <?php foreach ($modalidades_list as $mod): 
                $icon_suffix = get_modalidade_icon_suffix($mod['nome']);
                $is_active = ((string)$filtro_modalidade_id === (string)$mod['id']);
                
                // Renderiza o botão apenas se houver um ícone WCA mapeado
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
    </div>
    <?php
    // ==========================================================
    // 3. VISUALIZAÇÃO DOS DADOS AGRUPADOS E ORDENADOS
    // ==========================================================

    if (empty($listasPorModalidade)) {
        echo "<div class='alert alert-info'>Nenhum resultado encontrado para a seleção atual.</div>";
    }

    $ordemFaixas = [
        "Sub 8", "Sub 10", "Sub 12", "Sub 14", "Sub 16", "Sub 18", "Adulto"
    ];
    
    // Loop 1: Modalidade
    foreach ($listasPorModalidade as $modalidade => $dadosPorSexo) {
        
        $icon_html = get_icon_html($modalidade);
        echo "<h1 class='mt-4'>{$icon_html}" . htmlspecialchars($modalidade) . "</h1>";

        // Loop 2: Sexo
        foreach ($dadosPorSexo as $sexo => $dadosPorFaixaEtaria) {
            echo "<h2 class='mt-3'>Sexo: " . htmlspecialchars($sexo) . "</h2>";

            // Ordena o array de faixas etárias
            uksort($dadosPorFaixaEtaria, function ($a, $b) use ($ordemFaixas) {
                $posA = array_search($a, $ordemFaixas);
                $posB = array_search($b, $ordemFaixas);
                return $posA <=> $posB;
            });

            // Loop 3: Faixa Etária
            foreach ($dadosPorFaixaEtaria as $faixaEtaria => $listaDeAlunos) {
                
                // ORDENAÇÃO: Ordena pelo campo media_float (calculado no PHP)
                usort($listaDeAlunos, function ($a, $b) {
                    $time_a = $a["media_float"]; 
                    $time_b = $b["media_float"];
                    return $time_a <=> $time_b; 
                });

                if (count($listaDeAlunos) > 0) {
                    echo "<h3 class='mt-3'>Faixa Etária: " . htmlspecialchars($faixaEtaria) . "</h3>";
                    
                    echo '<table class="table table-striped table-bordered ranking-table">
                        <thead>
                            <tr>
                                <th>Colocação</th>
                                <th>Aluno</th>
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

                    // Loop 4: Alunos
                    foreach ($listaDeAlunos as $aluno) {
                        
                        $solvers_formatadas = formatar_solvers_ranking([
                            'solver1' => $aluno["solver1"],
                            'solver2' => $aluno["solver2"],
                            'solver3' => $aluno["solver3"],
                            'solver4' => $aluno["solver4"],
                            'solver5' => $aluno["solver5"]
                        ]);

                        echo "<tr>";
                        echo "<td>" . $colocacao . "</td>";
                        echo "<td style='text-align: left;'>" . htmlspecialchars($aluno["aluno"]) . "</td>";
                        echo "<td><strong>" . htmlspecialchars($aluno["melhor"]) . "</strong></td>"; // Nova coluna Melhor
                        echo "<td><strong>" . htmlspecialchars($aluno["media"]) . "</strong></td>"; 
                        
                        echo "<td>" . $solvers_formatadas[0] . "</td>";
                        echo "<td>" . $solvers_formatadas[1] . "</td>";
                        echo "<td>" . $solvers_formatadas[2] . "</td>";
                        echo "<td>" . $solvers_formatadas[3] . "</td>";
                        echo "<td>" . $solvers_formatadas[4] . "</td>";

                        echo "</tr>";
                        $colocacao++;
                    }

                    echo '</tbody>
                    </table>';
                }
            }
            
            echo "<hr>"; 
        }
    }
    ?>
</main>

<?php include '../includes/layout_bottom.php'; ?>