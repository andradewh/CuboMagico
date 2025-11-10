<?php
// ARQUIVO: processa_edicao_usuario.php (Processa a edição de Nome, Email e opcionalmente Senha)

session_start();
// Inclua o arquivo de conexão com o banco de dados
require_once '../includes/db_connection.php';
require_once '../includes/funcs.php';

if (!isset($_SESSION['usuario'])) {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificação básica dos campos obrigatórios
    if (isset($_POST['id']) && isset($_POST['nome']) && isset($_POST['email'])) {
        
        $id = $_POST['id'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        
        // Novos campos de senha (opcionais). Se não existirem, serão null.
        // Se existir, usaremos o valor.
        $novaSenha = $_POST['nova_senha'] ?? null;
        $confirmaSenha = $_POST['confirma_senha'] ?? null;

        $senhaParaAtualizar = null;
        $erro = '';

        // --- Lógica de Validação da Nova Senha ---
        if (!empty($novaSenha)) {
            if ($novaSenha !== $confirmaSenha) {
                $erro = 'senha_nao_confere';
            } elseif (strlen($novaSenha) < 6) { // Exemplo de requisito
                $erro = 'senha_curta';
            } else {
                // Se a validação passou, a nova senha será passada para a função de atualização.
                $senhaParaAtualizar = $novaSenha;
            }
        } elseif (!empty($confirmaSenha)) {
            // Caso onde a confirmação foi preenchida, mas a nova senha não
            $erro = 'confirma_sem_nova';
        }
        
        if (!empty($erro)) {
            header('Location: edicao_usuario.php?id=' . $id . '&erro=' . $erro);
            exit;
        }

        // A função atualizarUsuario agora lida com a lógica de hashing
        if (atualizarUsuario($id, $nome, $email, $senhaParaAtualizar)) {
            // Se for bem-sucedido, redireciona para a tela de listagem/gerenciamento
            header('Location: cadastro_usuarios.php?sucesso=edicao');
            exit;
        } else {
            // Erro na atualização do BD (Nome/Email/Senha)
            header('Location: cadastro_usuarios.php?erro=1&detalhe=db_update_failed');
            exit;
        }
    } else {
        // Campos POST obrigatórios ausentes
        header('Location: cadastro_usuarios.php?erro=2&detalhe=missing_fields');
        exit;
    }
} else {
    // Método de requisição incorreto (não POST)
    header('Location: cadastro_usuarios.php?erro=3&detalhe=invalid_method');
    exit;
}
?>