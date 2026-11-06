<?php
// ARQUIVO: lista_escolas.php

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

$sql = "select * from escolas";
$result = $pdo->query($sql);

?>

<div class="container my-5">
    <!-- Cabeçalho da Página com Título e Ações Opcionais -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-school text-primary mr-2"></i> Instituições de Ensino</h2>
            <p class="text-muted mb-0">Lista de escolas cadastradas no sistema de competições.</p>
        </div>
    </div>

    <!-- Card Principal Moderno -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="escolasTable" class="table table-hover table-striped align-middle w-100">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" style="width: 80px;">#</th>
                            <th scope="col">Nome da Instituição</th>
                            <th scope="col" style="width: 200px;">Cidade/UF</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            while ($mod_data = $result->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td class='font-weight-bold text-secondary'>".$mod_data['id']."</td>";
                                echo "<td>".htmlspecialchars($mod_data['nome'], ENT_QUOTES, 'UTF-8')."</td>";
                                echo "<td><span class='badge badge-light border px-2 py-1'>".htmlspecialchars($mod_data['cidade'], ENT_QUOTES, 'UTF-8')."</span></td>";
                                echo "</tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#escolasTable').DataTable({
            "language": {
                "sSearch": "Pesquisar:",
                "lengthMenu": "Exibir _MENU_ registros por página",
                "zeroRecords": "Nenhum registro encontrado",
                "info": "Mostrando página _PAGE_ de _PAGES_",
                "infoEmpty": "Nenhum registro disponível",
                "infoFiltered": "(Filtrados de _MAX_ registros totais)",
                "oPaginate": {
                    "sFirst": "Primeira",
                    "sLast": "Última",
                    "sNext": "Próxima",
                    "sPrevious": "Anterior"
                }
            },
            "dom": '<"row mb-3"<"col-md-6"l><"col-md-6"f>>rt<"row mt-3"<"col-md-6"i><"col-md-6"p>>',
            "pageLength": 10
        });
    });
</script>

<?php include '../includes/layout_bottom.php'; ?>