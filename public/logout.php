<?php
// Inicialize a sessão
session_start();

// Destrua todas as variáveis de sessão
$_SESSION = array();

// Encerre a sessão
session_destroy();

// Redirecione para a página de login (ou qualquer outra página desejada após o logout)
header('Location: login.php');
exit;
?>
