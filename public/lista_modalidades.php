<?php
session_start();
include '../includes/header.php';
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

$sql = "select * from modalidades";

$result = $pdo->query($sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Usuário</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css">
    
</head>
<body>
    <div class="d-flex justify-content-center">
        </p>
        <div class="w-50 align-items-center text-center">
            <div class="titulo-container">
                <h1>Modalidades de Competição</h1>
            </div>
            <table class="table table-hover table-bordered border border-black rounded table-striped">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nome</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        while ($mod_data = $result->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>".$mod_data['id']."</td>";
                            echo "<td>".$mod_data['nome']."</td>";
                            echo "</tr>";
                        }                
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal fade" id="pageModal" tabindex="-1" role="dialog" aria-labelledby="pageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" id="minha-modal-div">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pageModalLabel">Edição de Usuários</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="pageContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            
            // Limpe o conteúdo da modal antes de abrir
            $('#pageContent').html('');
            
            // Enviar o ID do usuário para a página de edição na modal
            $.ajax({
                type: 'POST',
                url: 'edicao_usuario.php',
                data: {
                    id: id
                },
                success: function(data) {
                    $('#pageContent').html(data);
                },
                error: function() {
                    alert('Erro ao carregar a página de edição.');
                }
            });
        });
    </script>    
</body>
</html>
