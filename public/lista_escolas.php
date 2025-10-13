<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

$sql = "select * from escolas";

$result = $pdo->query($sql);

?>

<head>
    <title>Cadastro de Usuário</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css">
    
</head>
<body>
    <div class="d-flex justify-content-center">
        <div class="w-50 align-items-center text-center border" style="border-width: 2px !important; padding: 20px; margin-top: 20px;">
            <div class="titulo-container">
                <h1>Escolas</h1>
            </div>
            <table id="escolasTable" class="table table-hover table-bordered border border-black rounded table-striped">
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
</body>

<?php include '../includes/layout_bottom.php'; ?>
