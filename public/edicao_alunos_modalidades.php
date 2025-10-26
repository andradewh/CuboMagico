<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

// Consulta para obter a lista de alunos
$sqlAlunos = "SELECT id, nome FROM alunos";
$stmtAlunos = $pdo->query($sqlAlunos);
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obter a lista de modalidades
$sqlModalidades = "SELECT id, nome FROM modalidades where ativa = 1";
$stmtModalidades = $pdo->query($sqlModalidades);
$modalidades = $stmtModalidades->fetchAll(PDO::FETCH_ASSOC);
?>
<body>
    <div class="container">
        <h2 class="mt-4 mb-4">Vincular Alunos a Modalidades</h2>
        <div class="content-margin">
            <form action="processa_edicao_aluno_modalidade.php" method="post">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <?php
                            // Exiba as modalidades como cabeçalhos de coluna
                            foreach ($modalidades as $modalidade) {
                                echo '<th>' . $modalidade['nome'] . '</th>';
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($alunos as $aluno): ?>
                            <tr>
                                <td><?= $aluno['nome']; ?></td>
                                <?php
                                // Obtenha os vínculos existentes para o aluno
                                $vinculosExistentes = obterVinculosExistentes($aluno['id']); // Chame a função aqui
                                // Exiba as checkboxes para cada modalidade, marcando as já vinculadas
                                foreach ($modalidades as $modalidade) {
                                    echo '<td>';
                                    echo '<label>';
                                    echo '<input type="checkbox" name="aluno_modalidade[' . $aluno['id'] . '][' . $modalidade['id'] . ']" value="1"';
                                    if (in_array($modalidade['id'], $vinculosExistentes)) {
                                        echo ' checked';
                                    }
                                    echo '>';
                                    echo '</label>';
                                    echo '</td>';
                                }
                                ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <!-- Use a classe 'float-right' para alinhar o botão à direita -->
                <button type="submit" class="btn btn-primary float-right">Atualizar Vínculos</button>
            </form>
        </div>
    </div>
</body>

<?php include '../includes/layout_bottom.php'; ?>