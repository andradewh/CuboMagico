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

$sql = "select * from usuarios";

$result = $pdo->query($sql);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Cadastro de Usuário</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center">Cadastro de Usuário</h5>
                        <form action="processa_cadastro_usuarios.php" method="post">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Email" required>
                            </div>
                            <div class="form-group">
                                <label for="senha">Senha</label>
                                <input type="password" name="senha" id="senha" class="form-control" placeholder="Senha" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Cadastrar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="m-5">
        <table class="table table-hover table-bordered border border-black rounded table-striped">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Email</th>
                    <th scope="col">...</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while ($user_data = $result->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$user_data['id']."</td>";
                        echo "<td>".$user_data['nome']."</td>";
                        echo "<td>".$user_data['email']."</td>";
                        echo "<td class='d-flex'>";
                        echo "<a class='btn btn-sm btn-success edit-btn mr-2' data-toggle='modal' data-target='#pageModal' data-id='{$user_data['id']}'>Editar</a>";
                        echo "
                        <form method='post' action='processa_apaga_usuario.php'>
                        <input type='hidden' name='id' value='{$user_data['id']}'>
                        <button type='submit' class='btn btn-sm btn-danger delete-btn ml-auto mr-2'>Deletar</button>
                        </form>";
                        echo "</td>";
                        echo "</tr>";
                    }                
                ?>
            </tbody>
        </table>
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
