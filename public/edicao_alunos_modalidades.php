<?php
// ARQUIVO: edicao_aluno_modalidade.php (ou nome correspondente)

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

// ATUALIZADO: Utiliza a função obterAlunos() que já aplica o filtro do id_evento do usuário logado
$alunos = obterAlunos();

// Consulta para obter a lista de modalidades ativas
$sqlModalidades = "SELECT id, nome FROM modalidades WHERE ativa = 1";
$stmtModalidades = $pdo->query($sqlModalidades);
$modalidades = $stmtModalidades->fetchAll(PDO::FETCH_ASSOC);
?>
<body>
    <div class="container my-5">
        <h2 class="mb-4">Vincular Competidores a Modalidades</h2>
        <div class="content-margin">
            <form action="processa_edicao_aluno_modalidade.php" method="post">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Aluno</th>
                                <?php
                                // Exibe as modalidades como cabeçalhos de coluna
                                foreach ($modalidades as $modalidade) {
                                    echo '<th>' . htmlspecialchars($modalidade['nome']) . '</th>';
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($alunos)): ?>
                                <tr>
                                    <td colspan="<?php echo count($modalidades) + 1; ?>" class="text-center text-muted">
                                        Nenhum competidor cadastrado para este evento.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($alunos as $aluno): ?>
                                    <tr>
                                        <td class="font-weight-bold"><?php echo htmlspecialchars($aluno['nome']); ?></td>
                                        <?php
                                        // Obtenha os vínculos existentes para o aluno
                                        $vinculosExistentes = obterVinculosExistentes($aluno['id']); 
                                        
                                        // Exibe as checkboxes para cada modalidade, marcando as já vinculadas
                                        foreach ($modalidades as $modalidade) {
                                            echo '<td>';
                                            echo '<div class="custom-control custom-checkbox">';
                                            $checked = in_array($modalidade['id'], $vinculosExistentes) ? 'checked' : '';
                                            echo '<input type="checkbox" class="custom-control-input" id="mod_' . $aluno['id'] . '_' . $modalidade['id'] . '" name="aluno_modalidade[' . $aluno['id'] . '][' . $modalidade['id'] . ']" value="1" ' . $checked . '>';
                                            echo '<label class="custom-control-label" for="mod_' . $aluno['id'] . '_' . $modalidade['id'] . '"></label>';
                                            echo '</div>';
                                            echo '</td>';
                                        }
                                        ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Botão alinhado à direita com espaçamento -->
                <div class="clearfix mt-4">
                    <button type="submit" class="btn btn-primary float-right">Atualizar Vínculos</button>
                </div>
            </form>
        </div>
    </div>
</body>

<?php include '../includes/layout_bottom.php'; ?>