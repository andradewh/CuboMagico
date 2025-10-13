<?php
$servername = "localhost"; // Endereço do servidor MySQL
$username = "root"; // Nome de usuário do MySQL
$password = ""; // Senha do MySQL
$database = "cubomagico"; // Nome do banco de dados

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
    // Defina o modo de erro do PDO como exceção
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Conexão falhou: " . $e->getMessage());
}

