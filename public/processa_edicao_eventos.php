<?php
// ARQUIVO: processa_edicao_eventos.php

session_start();

require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

// Verifica autenticação
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Verifica se a requisição é POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Verifica os campos obrigatórios
    if (
        isset($_POST['id']) &&
        isset($_POST['nome']) &&
        isset($_POST['data']) &&
        isset($_POST['local']) &&
        isset($_POST['organizador']) &&
        isset($_POST['status'])
    ) {

        // Coleta dos dados
        $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
        $nome = trim($_POST['nome']);
        $data = trim($_POST['data']);
        $local = trim($_POST['local']);
        $organizador = trim($_POST['organizador']);
        $status = filter_var(
            $_POST['status'],
            FILTER_VALIDATE_INT
        );

        $erro = '';

        // 2. Verifica se o usuário é superuser
        $usuarioLogado = $_SESSION['usuario'];
        $isSuperuser = 0;

        if (is_array($usuarioLogado)) {
            $isSuperuser = (int)($usuarioLogado['superuser'] ?? 0);
        }

        if ($isSuperuser !== 1) {
            http_response_code(403);
            exit('Você não possui permissão para editar eventos.');
        }

        // 3. Validação dos campos
        $dataObj = DateTime::createFromFormat('!Y-m-d', $data);

        $dataValida = $dataObj &&
            $dataObj->format('Y-m-d') === $data;

        if (!$id || $id <= 0) {
            $erro = 'id_invalido';

        } elseif (
            $nome === '' ||
            mb_strlen($nome) > 150
        ) {
            $erro = 'nome_invalido';

        } elseif (!$dataValida) {
            $erro = 'data_invalida';

        } elseif (
            $local === '' ||
            mb_strlen($local) > 255
        ) {
            $erro = 'local_invalido';

        } elseif (
            $organizador === '' ||
            mb_strlen($organizador) > 150
        ) {
            $erro = 'organizador_invalido';

        } elseif (!in_array($status, [0, 1], true)) {
            $erro = 'status_invalido';
        }

        // 4. Caso exista algum erro de validação
        if (!empty($erro)) {
            header(
                'Location: cadastro_eventos.php?erro=' . $erro
            );
            exit;
        }

        // 5. Verifica se o evento existe e busca o banner atual (se houver)
        $consulta = "SELECT id, banner FROM eventos WHERE id = :id";

        $stmt = $pdo->prepare($consulta);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $eventoAtual = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$eventoAtual) {
            header('Location: cadastro_eventos.php?erro=evento_nao_encontrado');
            exit;
        }

        // ==========================================================
        // 5.1 PROCESSAMENTO DO NOVO UPLOAD DE BANNER (SE ENVIADO)
        // ==========================================================
        $bannerHashNome = $eventoAtual['banner']; // Mantém o banner antigo por padrão

        if (isset($_FILES['banner_evento']) && $_FILES['banner_evento']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['banner_evento']['tmp_name'];
            $fileName = $_FILES['banner_evento']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                $uploadFileDir = '../assets/files/';
                
                if (!is_dir($uploadFileDir)) {
                    mkdir($uploadFileDir, 0755, true);
                }
                
                $dest_path = $uploadFileDir . $newFileName;
                
                if (move_uploaded_file($fileTmpPath, $dest_path)) {
                    // Apaga o banner antigo da pasta se ele existir
                    if (!empty($eventoAtual['banner']) && file_exists($uploadFileDir . $eventoAtual['banner'])) {
                        @unlink($uploadFileDir . $eventoAtual['banner']);
                    }
                    // Atualiza com o novo hash
                    $bannerHashNome = $newFileName;
                }
            }
        }

        // 6. Atualiza o evento (incluindo o banner)
        $atualizarQuery = "
            UPDATE eventos
            SET
                nome = :nome,
                data = :data,
                local = :local,
                organizador = :organizador,
                status = :status,
                banner = :banner
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($atualizarQuery);

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->bindParam(':data', $data, PDO::PARAM_STR);
        $stmt->bindParam(':local', $local, PDO::PARAM_STR);
        $stmt->bindParam(':organizador', $organizador, PDO::PARAM_STR);
        $stmt->bindParam(':status', $status, PDO::PARAM_INT);
        $stmt->bindParam(':banner', $bannerHashNome, PDO::PARAM_STR);

        // 7. Executa a atualização
        if ($stmt->execute()) {

            header('Location: cadastro_eventos.php?sucesso=edicao');
            exit;

        } else {

            header('Location: cadastro_eventos.php?erro=edicao_falhou');
            exit;
        }

    } else {

        // Campos obrigatórios ausentes
        header('Location: cadastro_eventos.php?erro=campos_ausentes');
        exit;
    }

} else {

    // Método de requisição incorreto
    header('Location: cadastro_eventos.php?erro=metodo_invalido');
    exit;
}
?>