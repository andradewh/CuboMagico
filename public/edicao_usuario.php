<?php
// ARQUIVO: edicao_usuario.php

session_start();

require_once '../includes/funcs.php';
require_once '../includes/db_connection.php';

// Verificação de autenticação
if (!isset($_SESSION['usuario'])) {
    exit('Acesso negado. Faça login novamente.');
}

// Inicialização das variáveis
$id = null;
$nome = '';
$email = '';
$idEventoAtual = null;
$mensagem_erro = '';

// Pega o ID enviado via POST (AJAX) ou tenta pegar via GET caso haja fallback
$id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);

if ($id > 0) {
    $usuario = obterUsuarioPorId($id);

    if ($usuario) {
        $nome = $usuario['nome'];
        $email = $usuario['email'];
        $idEventoAtual = !empty($usuario['id_evento']) ? (int) $usuario['id_evento'] : null;
    } else {
        $mensagem_erro = 'Usuário não encontrado no banco de dados.';
    }
} else {
    $mensagem_erro = 'ID do usuário não fornecido ou inválido.';
}

// Busca eventos ativos e preserva o evento atual
$eventos = [];
if (!$mensagem_erro) {
    if ($idEventoAtual !== null) {
        $sqlEventos = "
            SELECT id, nome, data, status
            FROM eventos
            WHERE status = 1 OR id = :id_evento_atual
            ORDER BY data DESC, id DESC
        ";
        $stmtEventos = $pdo->prepare($sqlEventos);
        $stmtEventos->execute([':id_evento_atual' => $idEventoAtual]);
    } else {
        $sqlEventos = "
            SELECT id, nome, data, status
            FROM eventos
            WHERE status = 1
            ORDER BY data DESC, id DESC
        ";
        $stmtEventos = $pdo->query($sqlEventos);
    }
    $eventos = $stmtEventos->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid px-0">

    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger m-3" role="alert">
            <?= htmlspecialchars($mensagem_erro, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php else: ?>

        <form action="processa_edicao_usuario.php" method="POST" onsubmit="return validarSenha()">

            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="form-group">
                <label for="nome">Nome</label>
                <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label for="id_evento">Evento associado</label>
                <select class="form-control" id="id_evento" name="id_evento" required>
                    <option value="">Selecione um evento</option>
                    <?php foreach ($eventos as $evento): ?>
                        <option value="<?= (int) $evento['id'] ?>" <?= $idEventoAtual === (int) $evento['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($evento['nome'], ENT_QUOTES, 'UTF-8') ?>
                            <?= (int) $evento['status'] === 0 ? ' (Inativo - vínculo atual)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <hr class="my-4">

            <h6 class="mb-3">Alterar senha <small class="text-muted">(opcional)</small></h6>

            <div class="form-group">
                <label for="nova_senha">Nova senha</label>
                <input type="password" class="form-control" id="nova_senha" name="nova_senha" minlength="6" autocomplete="new-password" placeholder="Deixe em branco para não alterar">
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirmar nova senha</label>
                <input type="password" class="form-control" id="confirma_senha" name="confirma_senha" autocomplete="new-password" placeholder="Confirme a nova senha">
            </div>

            <button type="submit" class="btn btn-success btn-block mt-4">
                <i class="fas fa-save mr-1"></i> Salvar alterações
            </button>

        </form>

    <?php endif; ?>

</div>

<script>
function validarSenha() {
    const novaSenha = document.getElementById('nova_senha').value;
    const confirmaSenha = document.getElementById('confirma_senha').value;

    function mostrarErro(mensagem) {
        const erroId = 'senha-erro-msg';
        let erroElement = document.getElementById(erroId);
        if (erroElement) {
            erroElement.remove();
        }
        const erro = document.createElement('div');
        erro.id = erroId;
        erro.className = 'alert alert-warning mt-2';
        erro.setAttribute('role', 'alert');
        erro.textContent = mensagem;
        
        document.querySelector('form').prepend(erro);
        return false;
    }

    if (novaSenha.length > 0) {
        if (novaSenha !== confirmaSenha) {
            return mostrarErro('ERRO: A nova senha e a confirmação não coincidem.');
        }
        if (novaSenha.length < 6) {
            return mostrarErro('ERRO: A senha deve ter pelo menos 6 caracteres.');
        }
    }

    if (novaSenha.length === 0 && confirmaSenha.length > 0) {
        return mostrarErro('ERRO: Você preencheu a confirmação, mas deixou a nova senha vazia.');
    }

    return true;
}
</script>