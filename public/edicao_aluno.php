<?php
// ARQUIVO: edicao_aluno.php

session_start();
// Inclua o arquivo de conexão com o banco de dados e funções
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

$nome = '';
$idade = '';
$sexo = '';
$escola = '';
$id_evento = '';

if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Recupere os dados do aluno com base no ID
    $aluno = obterAlunoPorId($id);

    if ($aluno) {
        $nome = $aluno['nome'];
        $idade = $aluno['idade'];
        $sexo = $aluno['sexo'];
        $escola = $aluno['escola'];
        $id_evento = $aluno['id_evento'] ?? '';
    } else {
        echo 'Competidor não encontrado.';
        exit;
    }
} else {
    echo 'ID do competidor não fornecido.';
    exit;
}

// Consulta SQL para obter as escolas
$sqlEscolas = "SELECT id, nome FROM escolas ORDER BY nome";
$stmtEscolas = $pdo->query($sqlEscolas);
$escolas = $stmtEscolas->fetchAll(PDO::FETCH_ASSOC);

// Consulta SQL para obter os sexos
$sqlSexos = "SELECT id, nome FROM sexo";
$stmtSexos = $pdo->query($sqlSexos);
$sexos = $stmtSexos->fetchAll(PDO::FETCH_ASSOC);

// Consulta SQL para obter os eventos disponíveis
$sqlEventos = "SELECT id, nome FROM eventos where status = 1 ORDER BY data DESC";
$stmtEventos = $pdo->query($sqlEventos);
$eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<body>
    <h5 class="card-title text-center font-weight-bold mb-4">Edição de Aluno</h5>
    <form action="processa_edicao_aluno.php" method="post">
        <div class="form-group">
            <label for="id">Código</label>
            <input type="text" name="id" id="id" class="form-control" value="<?= htmlspecialchars($id); ?>" required readonly>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome</label>
            <input type="text" name="nome" id="nome" class="form-control" value="<?= htmlspecialchars($nome); ?>" required>
        </div>

        <!-- Campo de Seleção de Evento -->
        <div class="form-group">
            <label for="id_evento">Evento</label>
            <select id="id_evento" name="id_evento" class="custom-select" required>
                <option value="">Selecione o Evento</option>
                <?php foreach ($eventos as $ev): ?>
                    <option value="<?= $ev['id']; ?>" <?= ($ev['id'] == $id_evento ? 'selected' : ''); ?>>
                        <?= htmlspecialchars($ev['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="escola">Instituição de Ensino</label>
            <select id="escola" name="escola" class="custom-select" required>
                <?php foreach ($escolas as $esc): ?>
                    <option value="<?= $esc['id']; ?>" <?= ($esc['id'] == $escola ? 'selected' : ''); ?>>
                        <?= htmlspecialchars($esc['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="idade">Idade</label>
            <input type="number" class="form-control" id="idade" name="idade" value="<?= htmlspecialchars($idade); ?>" required>
        </div>

        <div class="form-group">
            <label for="sexo">Sexo</label>
            <select id="sexo" name="sexo" class="custom-select" required>
                <?php foreach ($sexos as $opcao): ?>
                    <option value="<?= $opcao['id']; ?>" <?= ($opcao['id'] == $sexo ? 'selected' : ''); ?>>
                        <?= htmlspecialchars($opcao['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Atualizar</button>
    </form>
</body>
</html>