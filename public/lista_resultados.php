<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

// ==========================================================
// 1. FUNÇÕES ESSENCIAIS
// ==========================================================

if (isset($_SESSION['usuario'])) {
    // Presume que a função obterNomeDoBancoDeDados está definida em funcs.php
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

/**
 * Converte a string de tempo (MM:SS.ms) para um total de segundos (float) para comparação numérica.
 */
function time_to_float($time_str) {
    if (empty($time_str)) {
        return 999999.0; // Alto valor para colocar tempos vazios no final do ranking
    }
    // Quebra a string "MM:SS.ms" em minutos e segundos.milissegundos
    if (strpos($time_str, ':') !== false) {
        list($minutes, $seconds) = explode(':', $time_str);
        // Retorna o total de segundos como float
        return ((int)$minutes * 60) + (float)$seconds;
    }
    // Se o formato não tiver minutos
    return (float)$time_str;
}

/**
 * Define as categorias de idade (Sub X e Adulto) com base na idade numérica.
 * Implementa a divisão ampla (Sub 8, Sub 10, Sub 12, Sub 14, Sub 16, Sub 18, Adulto).
 */
function obterFaixaEtaria($idade) {
    $idade = (int) $idade; // Garante que é um inteiro

    if ($idade <= 8) {
        return "Sub 8";
    } elseif ($idade >= 9 && $idade <= 10) {
        return "Sub 10";
    } elseif ($idade >= 11 && $idade <= 12) {
        return "Sub 12";
    } elseif ($idade >= 13 && $idade <= 14) {
        return "Sub 14";
    } elseif ($idade >= 15 && $idade <= 16) {
        return "Sub 16";
    } elseif ($idade >= 17 && $idade <= 18) {
        return "Sub 18";
    } else { // Idades 19 ou mais
        return "Adulto";
    }
}


// ==========================================================
// 2. CONSULTA SQL E AGRUPAMENTO DE DADOS
// ==========================================================

// Consulta SQL para obter os resultados e a média do "Average of 3" (AVG3),
// formatada como tempo pelo MySQL.
$sql = "SELECT 
  m.nome AS modalidade,
  a.nome AS aluno,
  a.sexo,
  a.idade,
  CONVERTERSEGUNDOSPARATEMPO(
    (
      COALESCE(NULLIF(CONVERTERTEMPOPARASEGUNDOS(solver1),0),0) +
      COALESCE(NULLIF(CONVERTERTEMPOPARASEGUNDOS(solver2),0),0) +
      COALESCE(NULLIF(CONVERTERTEMPOPARASEGUNDOS(solver3),0),0) +
      COALESCE(NULLIF(CONVERTERTEMPOPARASEGUNDOS(solver4),0),0) +
      COALESCE(NULLIF(CONVERTERTEMPOPARASEGUNDOS(solver5),0),0)
      -
      LEAST(
        IF(solver1='',9999,CONVERTERTEMPOPARASEGUNDOS(solver1)),
        IF(solver2='',9999,CONVERTERTEMPOPARASEGUNDOS(solver2)),
        IF(solver3='',9999,CONVERTERTEMPOPARASEGUNDOS(solver3)),
        IF(solver4='',9999,CONVERTERTEMPOPARASEGUNDOS(solver4)),
        IF(solver5='',9999,CONVERTERTEMPOPARASEGUNDOS(solver5))
      )
      -
      GREATEST(
        IF(solver1='',0,CONVERTERTEMPOPARASEGUNDOS(solver1)),
        IF(solver2='',0,CONVERTERTEMPOPARASEGUNDOS(solver2)),
        IF(solver3='',0,CONVERTERTEMPOPARASEGUNDOS(solver3)),
        IF(solver4='',0,CONVERTERTEMPOPARASEGUNDOS(solver4)),
        IF(solver5='',0,CONVERTERTEMPOPARASEGUNDOS(solver5))
      )
    ) / ( 
      (
        (solver1<>'') + (solver2<>'') + (solver3<>'') + (solver4<>'') + (solver5<>'')
      ) - 2
    )
  ) AS media
FROM alunomodalidadesolver ams
INNER JOIN alunos a ON ams.aluno = a.id
INNER JOIN modalidades m ON ams.modalidade = m.id
WHERE ( (solver1<>'') + (solver2<>'') + (solver3<>'') + (solver4<>'') + (solver5<>'') ) >= 3
ORDER BY m.nome, a.sexo, media, aluno";

$result = $pdo->query($sql);

$listasPorModalidade = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $modalidade = $row["modalidade"];
    $sexo = ($row["sexo"] == 1) ? "Masculino" : "Feminino";
    $aluno = $row["aluno"];
    $media = $row["media"];
    $idade = $row["idade"];
    
    // Converte a idade numérica na faixa etária desejada
    $faixaEtaria = obterFaixaEtaria($idade); 
    
    // 1. Garante a MODALIDADE
    if (!isset($listasPorModalidade[$modalidade])) {
        $listasPorModalidade[$modalidade] = array();
    }

    // 2. Garante o SEXO
    if (!isset($listasPorModalidade[$modalidade][$sexo])) {
        $listasPorModalidade[$modalidade][$sexo] = array();
    }

    // 3. Garante a FAIXA ETÁRIA (o novo nível de agrupamento)
    if (!isset($listasPorModalidade[$modalidade][$sexo][$faixaEtaria])) {
        $listasPorModalidade[$modalidade][$sexo][$faixaEtaria] = array();
    }
    
    // Adiciona o aluno ao grupo
    $listasPorModalidade[$modalidade][$sexo][$faixaEtaria][] = array(
        "aluno" => $aluno, 
        "media" => $media
    );
}

?>

<head>
    <title>Ranking de Alunos por Modalidade, Sexo e Faixa Etária</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Define a largura das colunas da tabela */
        th:nth-child(1), td:nth-child(1) { width: 50px; } /* Colocação */
        th:nth-child(2), td:nth-child(2) { width: 200px; } /* Aluno */
        th:nth-child(3), td:nth-child(3) { width: 150px; } /* Média */
    </style>
</head>
<body>
    <div class="container">
        <?php
        // ==========================================================
        // 3. VISUALIZAÇÃO DOS DADOS AGRUPADOS E ORDENADOS
        // ==========================================================

        // Array de ordenação manual para Faixas Etárias (necessário pois "Sub 10" vem antes de "Sub 9" em ordem alfabética)
        $ordemFaixas = [
            "Sub 8", "Sub 10", "Sub 12", "Sub 14", "Sub 16", "Sub 18", "Adulto"
        ];
        
        // Loop 1: Modalidade
        foreach ($listasPorModalidade as $modalidade => $dadosPorSexo) {
            echo "<h1 class='mt-4'>Ranking de Alunos por Modalidade - " . htmlspecialchars($modalidade) . "</h1>";

            // Loop 2: Sexo
            foreach ($dadosPorSexo as $sexo => $dadosPorFaixaEtaria) {
                echo "<h2 class='mt-3'>Sexo: " . htmlspecialchars($sexo) . "</h2>";

                // Ordena o array de faixas etárias usando a ordem definida acima
                uksort($dadosPorFaixaEtaria, function ($a, $b) use ($ordemFaixas) {
                    $posA = array_search($a, $ordemFaixas);
                    $posB = array_search($b, $ordemFaixas);
                    
                    if ($posA !== false && $posB !== false) {
                        return $posA <=> $posB;
                    }
                    return 0; 
                });

                // Loop 3: Faixa Etária (agora em ordem correta)
                foreach ($dadosPorFaixaEtaria as $faixaEtaria => $listaDeAlunos) {
                    
                    $colocacao = 1;

                    if (count($listaDeAlunos) > 0) {
                        echo "<h3 class='mt-3'>Faixa Etária: " . htmlspecialchars($faixaEtaria) . "</h3>";
                        
                        echo '<table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Colocação</th>
                                    <th>Aluno</th>
                                    <th>Média das Solvers</th>
                                </tr>
                            </thead>
                            <tbody>';

                        // ORDENAÇÃO DE ALUNOS: Usa time_to_float() para ordenar corretamente pelo tempo (menor é melhor)
                        usort($listaDeAlunos, function ($a, $b) {
                            $time_a = time_to_float($a["media"]); 
                            $time_b = time_to_float($b["media"]);
                            // Compara os floats (segundos)
                            return $time_a <=> $time_b; 
                        });

                        // Loop 4: Alunos (em ordem de ranking)
                        foreach ($listaDeAlunos as $aluno) {
                            echo "<tr>";
                            echo "<td>" . $colocacao . "</td>";
                            echo "<td>" . htmlspecialchars($aluno["aluno"]) . "</td>";
                            // Exibe a string de tempo (MM:SS.ms) diretamente, resolvendo o erro number_format
                            echo "<td>" . htmlspecialchars($aluno["media"]) . "</td>"; 
                            echo "</tr>";
                            $colocacao++;
                        }

                        echo '</tbody>
                        </table>';
                    } else {
                         echo "<p>Não há resultados para " . htmlspecialchars($sexo) . " na faixa etária " . htmlspecialchars($faixaEtaria) . " nesta modalidade.</p>";
                    }
                }
                
                echo "<hr>"; 
            }
        }
        ?>
    </div>
</body>

<?php include '../includes/layout_bottom.php'; ?>