<?php
// ARQUIVO: public/relatorio_pendencias.php

session_start();
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Cabeçalhos para evitar cache
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Busca todos os eventos disponíveis para o filtro
$stmtEventos = $pdo->query("SELECT id, nome FROM eventos ORDER BY nome ASC");
$listaEventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);

// Evento selecionado (padrão: o primeiro da lista ou via GET)
$eventoSelecionado = $_GET['evento_id'] ?? ($listaEventos[0]['id'] ?? '');

$pendencias = [];

if (!empty($eventoSelecionado)) {
    // Consulta ajustada para buscar também o nome do usuário que fez a última alteração (LEFT JOIN caso seja NULL)
    $sql = "SELECT 
                a.id AS aluno_id, 
                a.nome AS aluno_nome, 
                m.id AS modalidade_id, 
                m.nome AS modalidade_nome,
                ams.solver1, ams.solver2, ams.solver3, ams.solver4, ams.solver5,
                u.nome AS usuario_nome
            FROM alunomodalidadesolver ams
            JOIN alunos a ON ams.aluno = a.id
            JOIN modalidades m ON ams.modalidade = m.id
            LEFT JOIN usuarios u ON ams.usuario_id = u.id
            WHERE a.id_evento = ? 
              AND (ams.solver1 IS NULL OR ams.solver1 = '' 
               OR ams.solver2 IS NULL OR ams.solver2 = '' 
               OR ams.solver3 IS NULL OR ams.solver3 = '' 
               OR ams.solver4 IS NULL OR ams.solver4 = '' 
               OR ams.solver5 IS NULL OR ams.solver5 = '')
            ORDER BY m.nome ASC, a.nome ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$eventoSelecionado]);
    $pendencias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Inclui o topo e o cabeçalho padrão com o Bootstrap e o menu
include '../includes/layout_top.php';
include '../includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa-solid fa-triangle-exclamation text-warning me-2"></i> Relatório de Solvers em Branco</h2>
        <a href="edicao_alunos_modalidades_resultados.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Voltar para Inserir Resultados
        </a>
    </div>

    <p class="text-muted">
        Esta página lista todos os competidores que possuem 1 ou mais tempos (*solvers*) em branco ou pendentes de preenchimento por modalidade.
    </p>

    <!-- Filtro por Evento -->
    <form method="get" action="relatorio_pendencias.php" class="form-inline mb-4 bg-light p-3 rounded border">
        <div class="form-group mr-3">
            <label for="evento_id" class="mr-2 font-weight-bold">Selecione o Evento:</label>
            <select name="evento_id" id="evento_id" class="custom-select" onchange="this.form.submit()">
                <?php foreach ($listaEventos as $ev): ?>
                    <option value="<?php echo $ev['id']; ?>" <?php if ($eventoSelecionado == $ev['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($ev['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

    <?php if (empty($pendencias)): ?>
        <div class="alert alert-success" role="alert">
            <i class="fa-solid fa-circle-check"></i> Parabéns! Não há competidores com solvers em branco para este evento. Todos os tempos estão preenchidos.
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            Foram encontradas <b><?php echo count($pendencias); ?></b> associações de competidores com pendências de tempo.
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered bg-white shadow-sm">
                <thead class="thead-dark">
                    <tr>
                        <th>Modalidade</th>
                        <th>Competidor</th>
                        <th class="text-center">Solver 1</th>
                        <th class="text-center">Solver 2</th>
                        <th class="text-center">Solver 3</th>
                        <th class="text-center">Solver 4</th>
                        <th class="text-center">Solver 5</th>
                        <th class="text-center">Atualizado por</th>
                        <th class="text-center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendencias as $p): ?>
                        <tr>
                            <td class="align-middle font-weight-bold"><?php echo htmlspecialchars($p['modalidade_nome']); ?></td>
                            <td class="align-middle"><?php echo htmlspecialchars($p['aluno_nome']); ?></td>
                            
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php 
                                    $val = trim($p["solver$i"] ?? '');
                                    $isIncompleto = ($val === '');
                                ?>
                                <td class="text-center align-middle <?php echo $isIncompleto ? 'table-danger text-danger font-weight-bold' : ''; ?>">
                                    <?php echo $isIncompleto ? 'Pendente' : htmlspecialchars($val); ?>
                                </td>
                            <?php endfor; ?>

                            <!-- Exibe o nome do usuário responsável pela alteração -->
                            <td class="text-center align-middle">
                                <?php 
                                    if (!empty($p['usuario_nome'])) {
                                        echo '<span class="badge badge-info">' . htmlspecialchars($p['usuario_nome']) . '</span>';
                                    } else {
                                        echo '<span class="text-muted small">Não registrado</span>';
                                    }
                                ?>
                            </td>
                            
                            <td class="text-center align-middle">
                                <a href="edicao_alunos_modalidades_resultados.php?modalidade_filtro=<?php echo $p['modalidade_id']; ?>&aluno_filtro=<?php echo $p['aluno_id']; ?>" class="btn btn-sm btn-primary" title="Preencher tempos">
                                    <i class="fa-solid fa-pen-to-square"></i> Preencher
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/layout_bottom.php'; ?>