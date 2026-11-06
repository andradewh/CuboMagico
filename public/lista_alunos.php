<?php
// ARQUIVO: lista_alunos.php

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
    $usuarioLogado = $_SESSION['usuario'];
} else {
    header('Location: login.php');
    exit;
}

// Obtém o ID do usuário da sessão
$userId = is_array($usuarioLogado) ? ($usuarioLogado['id'] ?? null) : null;
$userEmail = is_array($usuarioLogado) ? ($usuarioLogado['email'] ?? null) : $usuarioLogado;

// Consulta direta no banco para garantir o id_evento atualizado do usuário logado
$stmtUser = $pdo->prepare("SELECT id_evento, superuser FROM usuarios WHERE id = ? OR email = ?");
$stmtUser->execute([$userId, $userEmail]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

$idEventoCorrente = $userData['id_evento'] ?? null;
$isSuperuser = $userData['superuser'] ?? 0;

include '../includes/layout_top.php';
include '../includes/header.php';

// MODIFICAÇÃO AQUI: 
// Se o usuário possuir um id_evento vinculado, filtramos estritamente por ele. 
// Caso contrário (e se realmente quiser ver tudo quando não houver evento), traz todos.
if ($idEventoCorrente) {
    $sql = "SELECT alunos.id, alunos.nome, alunos.idade, IF(alunos.sexo = 1, 'Masculino', 'Feminino') AS sexo, escolas.nome AS escola 
            FROM alunos 
            JOIN escolas ON alunos.escola = escolas.id 
            WHERE alunos.id_evento = :id_evento";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id_evento' => $idEventoCorrente]);
} else {
    // Se o superuser não tiver nenhum evento vinculado a ele próprio, exibe todos
    $sql = "SELECT alunos.id, alunos.nome, alunos.idade, IF(alunos.sexo = 1, 'Masculino', 'Feminino') AS sexo, escolas.nome AS escola 
            FROM alunos 
            JOIN escolas ON alunos.escola = escolas.id";
    $stmt = $pdo->query($sql);
}
?>

<div class="container my-5">
    <!-- Cabeçalho da Página com Título Moderno -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fa-solid fa-users text-primary mr-2"></i> Competidores</h2>
            <p class="text-muted mb-0">Relação de competidores inscritos no seu evento atual e suas respectivas instituições.</p>
        </div>
    </div>

    <!-- Card Principal com Sombra Suave -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="escolasTable" class="table table-hover table-striped align-middle w-100">
                    <thead class="thead-light">
                        <tr>
                            <th scope="col" style="width: 90px;">Código</th>
                            <th scope="col">Nome</th>
                            <th scope="col" style="width: 90px;">Idade</th>
                            <th scope="col" style="width: 130px;">Sexo</th>
                            <th scope="col">Escola / Instituição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            while ($mod_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo "<tr>";
                                echo "<td class='font-weight-bold text-secondary'>".$mod_data['id']."</td>";
                                echo "<td>".htmlspecialchars($mod_data['nome'], ENT_QUOTES, 'UTF-8')."</td>";
                                echo "<td>".$mod_data['idade']."</td>";
                                echo "<td><span class='badge badge-light border px-2 py-1'>".$mod_data['sexo']."</span></td>";
                                echo "<td>".htmlspecialchars($mod_data['escola'], ENT_QUOTES, 'UTF-8')."</td>";
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