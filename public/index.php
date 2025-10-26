<?php
// ARQUIVO: index.php (ou a página inicial)

session_start();
include '../includes/funcs.php';

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

?>
<h1>Bem-vindo <?php echo $nomeUsuario; ?></h1>

<?php include '../includes/layout_bottom.php'; ?>