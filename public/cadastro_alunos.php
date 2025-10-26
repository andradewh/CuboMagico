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

$sql = "SELECT id, nome from cubomagico.escolas";
$result = $pdo->query($sql);

$sql_list = "SELECT alunos.id, alunos.nome, alunos.idade, if(alunos.sexo = 1,'Masculino','Feminino') sexo, escolas.nome escola 
          from cubomagico.escolas
          join cubomagico.alunos ON alunos.escola = escolas.id
          order by alunos.id";
$result_list = $pdo->query($sql_list);

?>
<body>
<div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center">Cadastro de Alunos</h5>
                        <form action="processa_cadastro_alunos.php" method="post">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" required>
                            </div>
                            <div class="form-group">
                            <label for="selectField">Escola</label>
                                <select name="escola" class="custom-select" label="escola">
                                    <?php
                                    if($result){
                                        while($row = $result->fetch(PDO::FETCH_ASSOC)){
                                            echo '<option value="' . $row['id'] . '">' . $row['nome'] . '</option>';

                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="idade">Idade</label>
                                <input type="number" class="form-control" id="idade" name="idade" placeholder="Idade" required>
                            </div>
                            <div class="form-group">
                            <label for="selectField">Sexo</label>
                                <select name="sexo" class="custom-select" label="sexo">
                                    <option value="1" selected>Masculino</option>
                                    <option value="2">Feminino</option>
                                </select>
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
                    <th scope="col">Codigo</th>
                    <th scope="col">Nome</th>
                    <th scope="col">Idade</th>
                    <th scope="col">Sexo</th>
                    <th scope="col">Escola</th>
                    <th scope="col">...</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while ($alunos_data = $result_list->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$alunos_data['id']."</td>";
                        echo "<td>".$alunos_data['nome']."</td>";
                        echo "<td>".$alunos_data['idade']."</td>";
                        echo "<td>".$alunos_data['sexo']."</td>";
                        echo "<td>".$alunos_data['escola']."</td>";
                        echo "<td class='d-flex'>";
                        echo "<a class='btn btn-sm btn-success edit-btn mr-2' data-toggle='modal' data-target='#pageModal' data-id='{$alunos_data['id']}'>Editar</a>";
                        echo "
                        <form method='post' action='processa_apaga_aluno.php'>
                        <input type='hidden' name='id' value='{$alunos_data['id']}'>
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
                    <h5 class="modal-title" id="pageModalLabel">Edição de Alunos</h5>
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
                url: 'edicao_aluno.php',
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
<?php include '../includes/layout_bottom.php'; ?>