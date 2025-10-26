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
<div class="d-flex justify-content-center">
    <div class="w-50 align-items-center text-center border" style="border-width: 2px !important; padding: 20px; margin-top: 20px;">
        <div class="titulo-container">
            <h1>Escolas</h1>
        </div>
        <table id="escolasTable" class="table table-hover table-wca table-striped"> 
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Cidade/UF</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while ($mod_data = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$mod_data['id']."</td>";
                        echo "<td>".$mod_data['nome']."</td>";
                        echo "<td>".$mod_data['cidade']."</td>";
                        echo "</tr>";
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Mantenha os scripts aqui, mas remova os links de Jquery e DataTables da HEAD
    $(document).ready(function() {
        $('#escolasTable').DataTable({
            "language": {
                "sSearch": "Pesquisar:",
                "lengthMenu": "Exibir _MENU_ registros por página",
                "zeroRecords": "Nenhum registro encontrado",
                "info": "Página _PAGE_ de _PAGES_",
                "infoEmpty": "Nenhum registro disponível",
                "infoFiltered": "(Filtrados de _MAX_ registros totais)",
                "oPaginate": {
                    "sFirst": "Primeira",
                    "sLast": "Última",
                    "sNext": "Próxima",
                    "sPrevious": "Anterior"
                }
            }
        });
    });
</script>

<?php include '../includes/layout_bottom.php'; ?>