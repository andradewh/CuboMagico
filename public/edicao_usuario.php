<?php
// ARQUIVO: edicao_usuario.php (Formulário de Edição de Usuário e Senha)

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';


if (isset($_SESSION['usuario'])) {
    $nomeUsuarioLogado = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

// Inicialização de variáveis
$id = null;
$nome = '';
$email = '';
$mensagem_erro = '';

// Verifica se o ID do usuário foi passado
if (isset($_POST['id'])) {
    $id = $_POST['id'];

    // Recupera os dados do usuário. A função obterUsuarioPorId foi corrigida no funcs.php.
    $usuario = obterUsuarioPorId($id); 

    if ($usuario) {
        $nome = $usuario['nome'];
        $email = $usuario['email'];
    } else {
        $mensagem_erro = 'Usuário não encontrado.';
    }
} else {
    $mensagem_erro = 'ID do usuário não fornecido.';
}
?>

<div class="container mt-5">
    <?php if ($mensagem_erro): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $mensagem_erro; ?>
        </div>
    <?php else: ?>
            </div>
            <div class="card-body">
                <!-- Adicionado onsubmit para validação JavaScript da senha -->
                <form action="processa_edicao_usuario.php" method="post" onsubmit="return validarSenha()">
                    
                    <!-- Campos de Nome e Email -->
                    <div class="form-group mb-3">
                        <label for="nome">Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" placeholder="Nome" value="<?= htmlspecialchars($nome); ?>" required>
                    </div>
                    <div class="form-group mb-4">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="Email" value="<?= htmlspecialchars($email); ?>" required>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3">Alterar Senha (Opcional)</h6>
                    
                    <!-- Campos de Nova Senha -->
                    <div class="form-group mb-3">
                        <label for="nova_senha">Nova Senha</label>
                        <input type="password" name="nova_senha" id="nova_senha" class="form-control" placeholder="Deixe em branco para não alterar a senha">
                    </div>
                    <div class="form-group mb-4">
                        <label for="confirma_senha">Confirmar Nova Senha</label>
                        <input type="password" name="confirma_senha" id="confirma_senha" class="form-control" placeholder="Confirme a nova senha">
                    </div>

                    <input type="hidden" name="id" value="<?= htmlspecialchars($id); ?>">
                    <button type="submit" class="btn btn-success btn-block w-100">Atualizar Cadastro</button>
                </form>
            </div>
    <?php endif; ?>
</div>

<script>
    function validarSenha() {
        const novaSenha = document.getElementById('nova_senha').value;
        const confirmaSenha = document.getElementById('confirma_senha').value;
        const container = document.querySelector('.container');
        
        // Função para mostrar erro (já que alert() não pode ser usado)
        function mostrarErro(mensagem) {
            const erroId = 'senha-erro-msg';
            const erroElement = document.getElementById(erroId);
            if (erroElement) erroElement.remove();
            
            container.insertAdjacentHTML('afterbegin', 
                `<div id="${erroId}" class="alert alert-warning" role="alert">${mensagem}</div>`);
            
            // Remove o erro após 4 segundos
            setTimeout(() => { 
                const erro = document.getElementById(erroId);
                if (erro) erro.remove();
            }, 4000);
            return false;
        }

        // Se a nova senha foi preenchida, a confirmação também deve ser e deve coincidir
        if (novaSenha.length > 0) {
            if (novaSenha !== confirmaSenha) {
                return mostrarErro('ERRO: A nova senha e a confirmação não coincidem.');
            }
            if (novaSenha.length < 6) { 
                return mostrarErro('ERRO: A senha deve ter pelo menos 6 caracteres.');
            }
        } 
        
        // Garante que a confirmação não foi preenchida sozinha
        if (novaSenha.length === 0 && confirmaSenha.length > 0) {
            return mostrarErro('ERRO: Você preencheu a confirmação, mas deixou a nova senha vazia.');
        }

        return true;
    }
</script>

<?php 
// Incluir o layout inferior (assumido)
include '../includes/layout_bottom.php'; 
?>