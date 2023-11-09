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

// Consulta SQL para obter os resultados
$sql = "SELECT 
m.nome AS modalidade,
a.nome AS aluno,
a.sexo,
CONVERTERSEGUNDOSPARATEMPO(((CONVERTERTEMPOPARASEGUNDOS(solver1) + 
                                CONVERTERTEMPOPARASEGUNDOS(solver2) + 
                                CONVERTERTEMPOPARASEGUNDOS(solver3) + 
                                CONVERTERTEMPOPARASEGUNDOS(solver4) + 
                                CONVERTERTEMPOPARASEGUNDOS(solver5)) - 
                                LEAST(CONVERTERTEMPOPARASEGUNDOS(solver1),
                                      CONVERTERTEMPOPARASEGUNDOS(solver2),
                                      CONVERTERTEMPOPARASEGUNDOS(solver3),
                                      CONVERTERTEMPOPARASEGUNDOS(solver4),
                                      CONVERTERTEMPOPARASEGUNDOS(solver5)) - 
                                      GREATEST(CONVERTERTEMPOPARASEGUNDOS(solver1),
                                            CONVERTERTEMPOPARASEGUNDOS(solver2),
                                            CONVERTERTEMPOPARASEGUNDOS(solver3),
                                            CONVERTERTEMPOPARASEGUNDOS(solver4),
                                            CONVERTERTEMPOPARASEGUNDOS(solver5))) / 3) AS media
FROM
alunomodalidadesolver ams
    INNER JOIN
alunos a ON ams.aluno = a.id
    INNER JOIN
modalidades m ON ams.modalidade = m.id
where solver1 <> '' and solver2 <> '' and solver3 <> '' and solver4 <> '' and solver5 <> ''
ORDER BY m.nome, a.sexo, media, aluno";

$result = $pdo->query($sql);

// Inicialize arrays vazios para as listas divididas por modalidade e sexo
$listasPorModalidade = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $modalidade = $row["modalidade"];
    $sexo = ($row["sexo"] == 1) ? "Masculino" : "Feminino";
    $aluno = $row["aluno"];
    $media = $row["media"];
    
    // Adicione o aluno à lista por modalidade
    if (!isset($listasPorModalidade[$modalidade])) {
        $listasPorModalidade[$modalidade] = array();
    }

    // Adicione o aluno à lista correspondente ao sexo
    if (!isset($listasPorModalidade[$modalidade][$sexo])) {
        $listasPorModalidade[$modalidade][$sexo] = array();
    }
    
    $listasPorModalidade[$modalidade][$sexo][] = array("aluno" => $aluno, "media" => $media);
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Ranking de Alunos por Modalidade e Sexo</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        /* Defina a largura das colunas da tabela */
        th {
            width: 200px; /* Largura fixa para a coluna "Aluno" */
        }
        td {
            width: 150px; /* Largura fixa para a coluna "Média das Solvers" */
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        foreach ($listasPorModalidade as $modalidade => $sexos) {
            echo "<h1>Ranking de Alunos por Modalidade - $modalidade</h1>";

            // Inicialize contadores de colocação para ambos os sexos
            $colocacaoMasculino = 1;
            $colocacaoFeminino = 1;

            // Divida a lista por sexo
            $sexoMasculino = isset($sexos["Masculino"]) ? $sexos["Masculino"] : array();
            $sexoFeminino = isset($sexos["Feminino"]) ? $sexos["Feminino"] : array();

            // Verifique se há resultados para o sexo masculino
            if (count($sexoMasculino) > 0) {
                echo "<h2>Sexo: Masculino</h2>";
                echo '<table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Colocação</th>
                            <th>Aluno</th>
                            <th>Média das Solvers</th>
                        </tr>
                    </thead>
                    <tbody>';
                // Ordena a lista por média da menor para a maior
                usort($sexoMasculino, function ($a, $b) {
                    return floatval($a["media"]) - floatval($b["media"]);
                });
                foreach ($sexoMasculino as $aluno) {
                    echo "<tr>";
                    echo "<td style='width: 50px;'>" . $colocacaoMasculino . "</td>";
                    echo "<td style='width: 200px;'>" . $aluno["aluno"] . "</td>";
                    echo "<td style='width: 150px;'>" . $aluno["media"] . "</td>";
                    echo "</tr>";
                    $colocacaoMasculino++; // Incrementa a colocação
                }
                echo '</tbody>
                </table>';
            } else {
                echo "Não há resultados para esta modalidade e sexo masculino.";
            }

            // Verifique se há resultados para o sexo feminino
            if (count($sexoFeminino) > 0) {
                echo "<h2>Sexo: Feminino</h2>";
                echo '<table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Colocação</th>
                            <th>Aluno</th>
                            <th>Média das Solvers</th>
                        </tr>
                    </thead>
                    <tbody>';
                // Ordena a lista por média da menor para a maior
                usort($sexoFeminino, function ($a, $b) {
                    return floatval($a["media"]) - floatval($b["media"]);
                });
                foreach ($sexoFeminino as $aluno) {
                    echo "<tr>";
                    echo "<td style='width: 50px;'>" . $colocacaoFeminino . "</td>";
                    echo "<td style='width: 200px;'>" . $aluno["aluno"] . "</td>";
                    echo "<td style='width: 150px;'>" . $aluno["media"] . "</td>";
                    echo "</tr>";
                    $colocacaoFeminino++; // Incrementa a colocação
                }
                echo '</tbody>
                </table>';
            } else {
                echo "Não há resultados para esta modalidade e sexo feminino.";
            }
        }
        ?>
    </div>
</body>
</html>
