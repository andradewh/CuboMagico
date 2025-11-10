<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// --- 1. COLETA DE INFORMAÇÕES DO USUÁRIO LOGADO ---
// Verifica se já há usuários no sistema
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
$totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Se já houver usuários, exige login
if ($totalUsuarios > 0 && !isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Obtém o status e ID do usuário logado (se houver)
$usuarioLogado = $_SESSION['usuario'] ?? null;

// Inicializa com valores padrão seguros para evitar o warning e negar permissão
$isSuperuser = 0;
$idUsuarioLogado = -1;

// CORREÇÃO: Verifica se o valor de $usuarioLogado é um array antes de tentar acessar seus offsets.
// Isso resolve o "Trying to access array offset on value of type int" que aparece no print.
if (is_array($usuarioLogado)) {
    $isSuperuser = (int)$usuarioLogado['superuser'] ?? 0;
    $idUsuarioLogado = $usuarioLogado['id'] ?? -1; 
}
// ----------------------------------------------------
// Busca lista de usuários (apenas dados necessários, por segurança)
$sql = "SELECT id, nome, email, superuser FROM usuarios";
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
    <h3 class="mb-3">Lista de Usuários</h3>

    <table class="table table-hover table-bordered border border-black rounded table-striped">
        <thead>
            <tr>
                <th>#</th>
                <th>Nome</th>
                <th>Email</th>
                <th>Superuser</th> 
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($user_data = $result->fetch(PDO::FETCH_ASSOC)): 
                // Verifica se o registro atual é o próprio usuário logado
                $ehProprioUsuario = $idUsuarioLogado == $user_data['id'];

                // Regra para EDITAR: 
                // 1. É o próprio usuário (pode editar a si mesmo) OU
                // 2. É Superuser E não é ele (pode editar outros)
                $podeEditar = $ehProprioUsuario || ($isSuperuser == 1);
                
                // Regra para DELETAR: 
                // 1. É Superuser E não é ele (Superuser não pode se auto-deletar)
                $podeDeletar = $isSuperuser == 1;
            ?>
                <tr>
                    <td><?= $user_data['id'] ?></td>
                    <td><?= htmlspecialchars($user_data['nome']) ?></td>
                    <td><?= htmlspecialchars($user_data['email']) ?></td>
                    <td><?= $user_data['superuser'] == 1 ? '<span class="badge badge-primary">Sim</span>' : 'Não' ?></td>
                    <td class="d-flex">
                        <?php if ($podeEditar): ?>
                            <a class="btn btn-sm btn-success edit-btn mr-2" data-toggle="modal"
                               data-target="#pageModal" data-id="<?= $user_data['id'] ?>">Editar</a>
                        <?php endif; ?>

                        <?php if ($podeDeletar): ?>
                            <form method="post" action="processa_apaga_usuario.php" onsubmit="return confirm('Tem certeza que deseja apagar o usuário <?= htmlspecialchars($user_data['nome']) ?>? Esta ação é irreversível.');">
                                <input type="hidden" name="id" value="<?= $user_data['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger delete-btn ml-auto mr-2">Deletar</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$podeEditar && !$podeDeletar): ?>
                            <span class="text-muted">Sem Ação</span>
                        <?php endif; ?>
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
    $('#pageContent').html('<p class="text-center p-4">Carregando formulário...</p>');
    $.ajax({
        type: 'POST',
        url: 'edicao_usuario.php',
        data: { id: id },
        success: function(data) {
            $('#pageContent').html(data);
        },
        error: function(xhr) {
            // Usa o alert personalizado do Bootstrap ou exibe a mensagem de erro
            var errorMessage = xhr.responseText || 'Erro ao carregar a página de edição.';
            $('#pageContent').html('<div class="alert alert-danger" role="alert">' + errorMessage + '</div>');
        }
    });
});
</script>

<?php include '../includes/layout_bottom.php'; ?>