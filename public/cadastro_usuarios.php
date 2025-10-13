<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// Verifica se já há usuários no sistema
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Se já houver usuários, exige login
if ($totalUsuarios > 0 && !isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Busca lista de usuários
$sql = "SELECT * FROM usuarios";
$result = $pdo->query($sql);
?>

<?php include '../includes/layout_top.php'; ?>
<?php include '../includes/header.php'; ?>

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
                <th>#</th>
                <th>Nome</th>
                <th>Email</th>
                <th>...</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user_data = $result->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= $user_data['id'] ?></td>
                    <td><?= $user_data['nome'] ?></td>
                    <td><?= $user_data['email'] ?></td>
                    <td class="d-flex">
                        <a class="btn btn-sm btn-success edit-btn mr-2" data-toggle="modal"
                           data-target="#pageModal" data-id="<?= $user_data['id'] ?>">Editar</a>

                        <form method="post" action="processa_apaga_usuario.php">
                            <input type="hidden" name="id" value="<?= $user_data['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger delete-btn ml-auto mr-2">Deletar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
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
            <div class="modal-body" id="pageContent"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).on('click', '.edit-btn', function() {
    var id = $(this).data('id');
    $('#pageContent').html('');
    $.ajax({
        type: 'POST',
        url: 'edicao_usuario.php',
        data: { id: id },
        success: function(data) {
            $('#pageContent').html(data);
        },
        error: function() {
            alert('Erro ao carregar a página de edição.');
        }
    });
});
</script>

<?php include '../includes/layout_bottom.php'; ?>
