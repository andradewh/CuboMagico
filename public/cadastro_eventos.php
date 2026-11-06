
<?php
session_start();

require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// --- Verificação de autenticação ---
$stmt = $pdo->query("SELECT COUNT(*) AS total FROM usuarios");
$totalUsuarios = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

if ($totalUsuarios > 0 && !isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// --- Usuário logado e permissões ---
$usuarioLogado = $_SESSION['usuario'] ?? null;

$isSuperuser = 0;
$idUsuarioLogado = -1;

if (is_array($usuarioLogado)) {
    $isSuperuser = (int)($usuarioLogado['superuser'] ?? 0);
    $idUsuarioLogado = (int)($usuarioLogado['id'] ?? -1);
}

// --- Busca os eventos cadastrados ---
$sql = "
    SELECT id, nome, data, local, organizador, status
    FROM eventos
    ORDER BY data DESC, id DESC
";

$result = $pdo->query($sql);
$eventos = $result->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../includes/layout_top.php'; ?>
<?php include '../includes/header.php'; ?>


<!-- ========================================= -->
<!-- CADASTRO DE EVENTOS -->
<!-- ========================================= -->

<?php if ($isSuperuser === 1): ?>

<div class="container mt-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card">

                <div class="card-body">

                    <h5 class="card-title text-center mb-4">
                        Cadastro de Evento
                    </h5>

                    <form action="processa_cadastro_eventos.php" method="POST">

                        <div class="form-group mb-3">
                            <label for="nome">Nome do Evento</label>

                            <input
                                type="text"
                                name="nome"
                                id="nome"
                                class="form-control"
                                placeholder="Nome do evento"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="form-group mb-3">
                            <label for="data">Data do Evento</label>

                            <input
                                type="date"
                                name="data"
                                id="data"
                                class="form-control"
                                required
                            >
                        </div>

                        <div class="form-group mb-3">
                            <label for="local">Local</label>

                            <input
                                type="text"
                                name="local"
                                id="local"
                                class="form-control"
                                placeholder="Local de realização"
                                maxlength="255"
                                required
                            >
                        </div>

                        <div class="form-group mb-3">
                            <label for="organizador">Organizador</label>

                            <input
                                type="text"
                                name="organizador"
                                id="organizador"
                                class="form-control"
                                placeholder="Nome do organizador"
                                maxlength="150"
                                required
                            >
                        </div>

                        <div class="form-group mb-3">
                            <label for="status">Status</label>

                            <select
                                name="status"
                                id="status"
                                class="form-control"
                                required
                            >
                                <option value="0" selected>Inativo</option>
                                <option value="1">Ativo</option>
                            </select>
                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary btn-block"
                        >
                            Cadastrar Evento
                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- ========================================= -->
<!-- LISTAGEM DE EVENTOS -->
<!-- ========================================= -->

<div class="container-fluid mt-5 mb-5">

    <h3 class="mb-3">Lista de Eventos</h3>

    <div class="table-responsive">

        <table class="table table-hover table-bordered table-striped">

            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Data</th>
                    <th>Local</th>
                    <th>Organizador</th>
                    <th>Status</th>
                    <th class="text-center">Ações</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($eventos) > 0): ?>

                    <?php foreach ($eventos as $evento): ?>

                        <tr>

                            <td>
                                <?= (int)$evento['id'] ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($evento['data'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($evento['local'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($evento['organizador'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>

                                <?php if ((int)$evento['status'] === 1): ?>

                                    <span class="badge badge-success">
                                        Ativo
                                    </span>

                                <?php else: ?>

                                    <span class="badge badge-secondary">
                                        Inativo
                                    </span>

                                <?php endif; ?>

                            </td>

                            <!-- AÇÕES -->
                            <td class="text-center">

                                <?php if ($isSuperuser === 1): ?>

                                    <div class="d-inline-flex align-items-center">

                                        <!-- EDITAR -->
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-success mr-2"
                                            data-toggle="modal"
                                            data-target="#editarEventoModal"
                                            data-id="<?= (int)$evento['id'] ?>"
                                            data-nome="<?= htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-data="<?= htmlspecialchars($evento['data'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-local="<?= htmlspecialchars($evento['local'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-organizador="<?= htmlspecialchars($evento['organizador'], ENT_QUOTES, 'UTF-8') ?>"
                                            data-status="<?= (int)$evento['status'] ?>"
                                        >
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            Editar
                                        </button>

                                        <!-- DELETAR -->
                                        <form
                                            method="POST"
                                            action="processa_apaga_evento.php"
                                            class="mb-0"
                                            onsubmit="return confirm('Tem certeza que deseja apagar o evento <?= htmlspecialchars(addslashes($evento['nome']), ENT_QUOTES, 'UTF-8') ?>? Esta ação pode ser irreversível.');"
                                        >

                                            <input
                                                type="hidden"
                                                name="id"
                                                value="<?= (int)$evento['id'] ?>"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-danger"
                                            >
                                                <i class="fa-solid fa-trash"></i>
                                                Deletar
                                            </button>

                                        </form>

                                    </div>

                                <?php else: ?>

                                    <span class="text-muted">
                                        Sem ações
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            Nenhum evento cadastrado.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>


<!-- ========================================= -->
<!-- MODAL DE EDIÇÃO DE EVENTO -->
<!-- ========================================= -->

<?php if ($isSuperuser === 1): ?>

<div
    class="modal fade"
    id="editarEventoModal"
    tabindex="-1"
    role="dialog"
    aria-labelledby="editarEventoModalLabel"
    aria-hidden="true"
>

    <div class="modal-dialog modal-dialog-centered" role="document">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="editarEventoModalLabel">
                    Editar Evento
                </h5>

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal"
                    aria-label="Fechar"
                >
                    <span aria-hidden="true">&times;</span>
                </button>

            </div>

            <form
                action="processa_edicao_eventos.php"
                method="POST"
            >

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="id"
                        id="editar_id"
                    >

                    <div class="form-group mb-3">

                        <label for="editar_nome">
                            Nome do Evento
                        </label>

                        <input
                            type="text"
                            name="nome"
                            id="editar_nome"
                            class="form-control"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="form-group mb-3">

                        <label for="editar_data">
                            Data do Evento
                        </label>

                        <input
                            type="date"
                            name="data"
                            id="editar_data"
                            class="form-control"
                            required
                        >

                    </div>

                    <div class="form-group mb-3">

                        <label for="editar_local">
                            Local
                        </label>

                        <input
                            type="text"
                            name="local"
                            id="editar_local"
                            class="form-control"
                            maxlength="255"
                            required
                        >

                    </div>

                    <div class="form-group mb-3">

                        <label for="editar_organizador">
                            Organizador
                        </label>

                        <input
                            type="text"
                            name="organizador"
                            id="editar_organizador"
                            class="form-control"
                            maxlength="150"
                            required
                        >

                    </div>

                    <div class="form-group mb-3">

                        <label for="editar_status">
                            Status
                        </label>

                        <select
                            name="status"
                            id="editar_status"
                            class="form-control"
                            required
                        >
                            <option value="0">Inativo</option>
                            <option value="1">Ativo</option>
                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-dismiss="modal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Salvar Alterações
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<?php endif; ?>


<!-- ========================================= -->
<!-- JAVASCRIPT DO MODAL -->
<!-- ========================================= -->

<script>
$(document).ready(function () {

    $('#editarEventoModal').on('show.bs.modal', function (event) {

        var botao = $(event.relatedTarget);
        var modal = $(this);

        modal.find('#editar_id').val(botao.attr('data-id'));
        modal.find('#editar_nome').val(botao.attr('data-nome'));
        modal.find('#editar_data').val(botao.attr('data-data'));
        modal.find('#editar_local').val(botao.attr('data-local'));
        modal.find('#editar_organizador').val(botao.attr('data-organizador'));
        modal.find('#editar_status').val(botao.attr('data-status'));

    });

});
</script>

<?php include '../includes/layout_bottom.php'; ?>
