
<?php
// ARQUIVO: processa_edicao_usuario.php

session_start();

require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// --- Verificação de autenticação ---
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// --- Verifica método POST ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cadastro_usuarios.php?erro=3&detalhe=invalid_method');
    exit;
}

// --- Verifica campos obrigatórios ---
if (
    !isset($_POST['id']) ||
    !isset($_POST['nome']) ||
    !isset($_POST['email']) ||
    !isset($_POST['id_evento'])
) {
    header('Location: cadastro_usuarios.php?erro=2&detalhe=missing_fields');
    exit;
}

// --- Recupera os dados ---
$id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
$nome = trim($_POST['nome']);
$email = trim($_POST['email']);
$idEvento = filter_var($_POST['id_evento'], FILTER_VALIDATE_INT);

$novaSenha = $_POST['nova_senha'] ?? '';
$confirmaSenha = $_POST['confirma_senha'] ?? '';

$senhaParaAtualizar = null;
$erro = '';

// --- Validação dos dados ---
if (!$id || $id <= 0 || $nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: cadastro_usuarios.php?erro=dados_invalidos');
    exit;
}

// --- Validação do evento ---
if (!$idEvento || $idEvento <= 0) {
    header('Location: cadastro_usuarios.php?erro=evento_invalido');
    exit;
}

$stmtEvento = $pdo->prepare("
    SELECT id
    FROM eventos
    WHERE id = :id_evento
      AND status = 1
");

$stmtEvento->execute([
    ':id_evento' => $idEvento
]);

if (!$stmtEvento->fetchColumn()) {
    header('Location: cadastro_usuarios.php?erro=evento_inativo_ou_inexistente');
    exit;
}

// --- Validação da nova senha ---
if (!empty($novaSenha)) {

    if ($novaSenha !== $confirmaSenha) {
        $erro = 'senha_nao_confere';

    } elseif (strlen($novaSenha) < 6) {
        $erro = 'senha_curta';

    } else {
        $senhaParaAtualizar = $novaSenha;
    }

} elseif (!empty($confirmaSenha)) {

    $erro = 'confirma_sem_nova';
}

if (!empty($erro)) {
    header('Location: cadastro_usuarios.php?erro=' . $erro);
    exit;
}

// --- Atualização dos dados do usuário ---
try {

    $pdo->beginTransaction();

    // Atualiza nome, email e senha utilizando a função existente.
    if (!atualizarUsuario($id, $nome, $email, $senhaParaAtualizar)) {
        throw new Exception('Falha ao atualizar os dados do usuário.');
    }

    // Atualiza o evento associado ao usuário.
    $sqlEvento = "
        UPDATE usuarios
        SET id_evento = :id_evento
        WHERE id = :id
    ";

    $stmtAtualizaEvento = $pdo->prepare($sqlEvento);

    $stmtAtualizaEvento->execute([
        ':id_evento' => $idEvento,
        ':id' => $id
    ]);

    $pdo->commit();

    header('Location: cadastro_usuarios.php?sucesso=edicao');
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    header('Location: cadastro_usuarios.php?erro=1&detalhe=db_update_failed');
    exit;
}

?>
