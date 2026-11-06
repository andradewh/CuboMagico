<?php
// ARQUIVO: cadastro_alunos.php

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

// Obtém o ID e o E-mail da sessão de forma segura
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

// Busca as escolas para o select do formulário
$sqlEscolas = "SELECT id, nome FROM cubomagico.escolas ORDER BY nome";
$resultEscolas = $pdo->query($sqlEscolas);

// Busca os eventos para o select do formulário
$sqlEventos = "SELECT id, nome FROM eventos where status = 1 ORDER BY data DESC";
$resultEventos = $pdo->query($sqlEventos);

// Consulta da tabela filtrada estritamente pelo id_evento do usuário logado
if ($idEventoCorrente) {
    $sql_list = "SELECT alunos.id, alunos.nome, alunos.idade, IF(alunos.sexo = 1, 'Masculino', 'Feminino') AS sexo, escolas.nome AS escola, eventos.nome AS evento_nome
            FROM alunos 
            JOIN escolas ON alunos.escola = escolas.id 
            LEFT JOIN eventos ON alunos.id_evento = eventos.id
            WHERE alunos.id_evento = :id_evento";
    $stmt = $pdo->prepare($sql_list);
    $stmt->execute([':id_evento' => $idEventoCorrente]);
} else {
    // Se o usuário não tiver nenhum evento vinculado, exibe todos
    $sql_list = "SELECT alunos.id, alunos.nome, alunos.idade, IF(alunos.sexo = 1, 'Masculino', 'Feminino') AS sexo, escolas.nome AS escola, eventos.nome AS evento_nome
            FROM alunos 
            JOIN escolas ON alunos.escola = escolas.id 
            LEFT JOIN eventos ON alunos.id_evento = eventos.id";
    $stmt = $pdo->query($sql_list);
}
?>
<body>
<div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="card-title text-center font-weight-bold mb-4">Cadastro de Competidores</h5>
                        <form action="processa_cadastro_alunos.php" method="post">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" required>
                            </div>
                            
                            <!-- Campo de Seleção de Evento -->
                            <div class="form-group">
                                <label for="id_evento">Evento</label>
                                <select name="id_evento" id="id_evento" class="custom-select" required>
                                    <option value="">Selecione o Evento</option>
                                    <?php
                                    if($resultEventos){
                                        while($ev = $resultEventos->fetch(PDO::FETCH_ASSOC)){
                                            $selected = ($idEventoCorrente == $ev['id']) ? 'selected' : '';
                                            echo '<option value="' . $ev['id'] . '" ' . $selected . '>' . htmlspecialchars($ev['nome']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="escola">Instituição de Ensino</label>
                                <select name="escola" id="escola" class="custom-select" required>
                                    <option value="">Selecione a Instituição</option>
                                    <?php
                                    if($resultEscolas){
                                        while($row = $resultEscolas->fetch(PDO::FETCH_ASSOC)){
                                            echo '<option value="' . $row['id'] . '">' . htmlspecialchars($row['nome']) . '</option>';
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
                                <label for="sexo">Sexo</label>
                                <select name="sexo" id="sexo" class="custom-select">
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
                    <th scope="col">Evento</th>
                    <th scope="col">Idade</th>
                    <th scope="col">Sexo</th>
                    <th scope="col">Instituição de Ensino</th>
                    <th scope="col">...</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    while ($alunos_data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>".$alunos_data['id']."</td>";
                        echo "<td>".htmlspecialchars($alunos_data['nome'])."</td>";
                        echo "<td>".htmlspecialchars($alunos_data['evento_nome'] ?? 'Geral')."</td>";
                        echo "<td>".$alunos_data['idade']."</td>";
                        echo "<td>".$alunos_data['sexo']."</td>";
                        echo "<td>".htmlspecialchars($alunos_data['escola'])."</td>";
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
            </div>
        </div>
    </div>
    <script>
        $(document).on('click', '.edit-btn', function() {
            var id = $(this).data('id');
            
            $('#pageContent').html('');
            
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