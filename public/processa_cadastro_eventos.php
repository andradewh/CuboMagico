
<?php
// ARQUIVO: processa_cadastro_eventos.php

session_start();

// Inclui a conexão com o banco de dados
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Verifica se o usuário está autenticado e é superuser
    $usuarioLogado = $_SESSION['usuario'] ?? null;

    $isSuperuser = 0;

    if (is_array($usuarioLogado)) {
        $isSuperuser = (int)($usuarioLogado['superuser'] ?? 0);
    }

    if ($isSuperuser !== 1) {
        http_response_code(403);
        exit('Você não possui permissão para cadastrar eventos.');
    }

    // 2. Coleta os dados do formulário
    $nome = trim($_POST['nome'] ?? '');
    $data = trim($_POST['data'] ?? '');
    $local = trim($_POST['local'] ?? '');
    $organizador = trim($_POST['organizador'] ?? '');
    $status = filter_var(
        $_POST['status'] ?? null,
        FILTER_VALIDATE_INT
    );

    // 3. Valida os campos
    $dataObj = DateTime::createFromFormat('!Y-m-d', $data);

    $dataValida = $dataObj &&
        $dataObj->format('Y-m-d') === $data;

    if (
        $nome === '' ||
        mb_strlen($nome) > 150 ||
        !$dataValida ||
        $local === '' ||
        mb_strlen($local) > 255 ||
        $organizador === '' ||
        mb_strlen($organizador) > 150 ||
        !in_array($status, [0, 1], true)
    ) {
        header('Location: cadastro_eventos.php?erro=dados_invalidos');
        exit;
    }

    // 4. Insere o evento no banco de dados
    $inserirQuery = "
        INSERT INTO eventos (
            nome,
            data,
            local,
            organizador,
            status
        )
        VALUES (
            :nome,
            :data,
            :local,
            :organizador,
            :status
        )
    ";

    $stmt = $pdo->prepare($inserirQuery);

    $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
    $stmt->bindParam(':data', $data, PDO::PARAM_STR);
    $stmt->bindParam(':local', $local, PDO::PARAM_STR);
    $stmt->bindParam(':organizador', $organizador, PDO::PARAM_STR);
    $stmt->bindParam(':status', $status, PDO::PARAM_INT);

    // 5. Executa o cadastro
    if ($stmt->execute()) {

        header('Location: cadastro_eventos.php?sucesso=cadastro');
        exit;

    } else {

        header('Location: cadastro_eventos.php?erro=cadastro_falhou');
        exit;
    }

} else {

    // Redireciona caso não seja uma requisição POST
    header('Location: cadastro_eventos.php');
    exit;
}
?>
