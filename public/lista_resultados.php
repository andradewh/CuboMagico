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
GROUP BY m.nome , a.nome , a.sexo
ORDER BY m.nome , a.sexo";

$result = $pdo->query($sql);

// Inicialize arrays vazios para as listas divididas por modalidade e sexo
$listasPorModalidade = array();
$listasPorSexo = array();

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $modalidade = $row["modalidade"];
    $sexo = ($row["sexo"] == 1) ? "Masculino" : "Feminino";
    $aluno = $row["aluno"];
    $media = $row["media"];
    
    // Adicione o aluno à lista por modalidade
    if (!isset($listasPorModalidade[$modalidade])) {
        $listasPorModalidade[$modalidade] = array();
    }
    $listasPorModalidade[$modalidade][] = array("aluno" => $aluno, "media" => $media);

    // Adicione o aluno à lista por sexo
    if (!isset($listasPorSexo[$sexo])) {
        $listasPorSexo[$sexo] = array();
    }
    $listasBySex[$sexo][] = array("aluno" => $aluno, "media" => $media);
}

// Agora você tem as listas divididas por modalidade e sexo em $listasPorModalidade e $listasPorSexo
?>

<!DOCTYPE html>
<html>
<head>
    <title>Listas de Resultados</title>
    <link rel="stylesheet" href="caminho_para_bootstrap/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Listas de Resultados por Modalidade</h1>
        <?php
        foreach ($listasPorModalidade as $modalidade => $alunos) {
            echo "<h2>Modalidade: $modalidade</h2>";
            echo '<table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Média das Solvers</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($alunos as $aluno) {
                echo "<tr>";
                echo "<td>" . $aluno["aluno"] . "</td>";
                echo "<td>" . $aluno["media"] . "</td>";
                echo "</tr>";
            }
            echo '</tbody>
                </table>';
        }
        ?>
        <h1>Listas de Resultados por Sexo</h1>
        <?php
        foreach ($listasPorSexo as $sexo => $alunos) {
            echo "<h2>Sexo: $sexo</h2>";
            echo '<table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Média das Solvers</th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach ($alunos as $aluno) {
                echo "<tr>";
                echo "<td>" . $aluno["aluno"] . "</td>";
                echo "<td>" . $aluno["media"] . "</td>";
                echo "</tr>";
            }
            echo '</tbody>
                </table>';
        }
        ?>
    </div>
</body>
</html>
