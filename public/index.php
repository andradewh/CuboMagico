<?php
session_start();
include '../includes/funcs.php';

if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

?>

<head>
    <title>Bem-vindo</title>
</head>
<body>
    <h1>Bem-vindo <?php echo $nomeUsuario; ?></h1>

</body>

<?php include '../includes/layout_bottom.php'; ?>