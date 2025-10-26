<?php
session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

// Certifique-se de que a função 'obterNomeDoBancoDeDados' existe em 'funcs.php'
if (isset($_SESSION['usuario'])) {
    // O usuário está autenticado
    // Nota: A função abaixo é um placeholder, ajuste conforme sua implementação real.
    // $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']); 
} else {
    // O usuário não está autenticado, redirecione para a página de login
    header('Location: login.php');
    exit;
}

// FUNÇÃO AUXILIAR PARA O ÍCONE (Você precisará garantir que esta função exista no seu código ou a defina aqui)
function get_modalidade_icon_suffix($modalidade_nome) {
    switch ($modalidade_nome) {
        case 'Cubo 3x3': return 'event-333';
        case 'Cubo 2x2': return 'event-222';
        case 'Cubo 3x3 OH': return 'event-333oh';
        case 'Cubo 4x4': return 'event-444';
        case 'Cubo 5x5': return 'event-555';
        case 'Cubo Megaminx': return 'event-minx';
        case 'Cubo Pyraminx': return 'event-pyram';
        case 'Cubo Skewb': return 'event-skewb';
        default: return '';
    }
}


include '../includes/layout_top.php'; // Abre <html>, <head>, <body>
include '../includes/header.php'; // Inclui a barra de navegação

// CONSULTA MODIFICADA: Incluindo o campo 'ativa'
$sql = "SELECT id, nome, ativa FROM modalidades ORDER BY nome";
$result = $pdo->query($sql);

?>
<main class="container mt-4"> 
    
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 40px; /* Largura reduzida para ser menor */
            height: 20px; /* Altura reduzida para ser menor */
        }

        .switch input { 
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 20px; /* Ajustado para se adequar ao novo tamanho */
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 16px; /* Ajustado */
            width: 16px; /* Ajustado */
            left: 2px; /* Ajustado */
            bottom: 2px; /* Ajustado */
            background-color: white;
            -webkit-transition: .4s;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #28a745; /* Verde Bootstrap */
        }

        input:focus + .slider {
            box-shadow: 0 0 1px #28a745;
        }

        input:checked + .slider:before {
            -webkit-transform: translateX(20px); /* Ajustado */
            -ms-transform: translateX(20px); /* Ajustado */
            transform: translateX(20px); /* Ajustado */
        }
    </style>

    <div class="d-flex justify-content-center">
        <div class="align-items-center text-center mt-3" style="width: 60%;"> 
            <div class="titulo-container">
                <h1>Modalidades de Competição</h1>
                <p class="text-muted">Use o *switch* para ativar ou desativar as modalidades para a próxima competição.</p>
            </div>
            <table class="table table-hover table-wca table-bordered border border-black rounded table-striped">
                <thead>
                    <tr>
                        <th scope="col" style="width: 5%;"></th> 
                        <th scope="col">Cód.</th>
                        <th scope="col" style="text-align: left;">Nome da Modalidade</th>
                        <th scope="col" style="width: 10%;">Ativa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        while ($mod_data = $result->fetch(PDO::FETCH_ASSOC)) {
                            
                            $id_modalidade = $mod_data['id'];
                            $nome_modalidade = $mod_data['nome'];
                            // Assume que 1 é ativo e 0 é inativo
                            $is_ativa = (int)$mod_data['ativa'] === 1; 
                            $icon_class_suffix = get_modalidade_icon_suffix($nome_modalidade);
                            
                            // Monta o HTML do ícone
                            $icon_html = $icon_class_suffix ? "<i class='cubing-icon $icon_class_suffix'></i>" : '';

                            echo "<tr>";
                            
                            // 1. CÉLULA DO ÍCONE
                            echo "<td style='vertical-align: middle;'>{$icon_html}</td>"; 
                            
                            // 2. CÉLULA DO CÓDIGO
                            echo "<td style='vertical-align: middle;'>".htmlspecialchars($id_modalidade)."</td>";
                            
                            // 3. CÉLULA DO NOME
                            echo "<td style='text-align: left; vertical-align: middle;'>".htmlspecialchars($nome_modalidade)."</td>";
                            
                            // 4. CÉLULA DO CHECKLIST (SWITCH)
                            echo "<td style='vertical-align: middle;'>";
                            echo "<label class='switch' title='Clique para alterar o status'>";
                            echo "<input type='checkbox' 
                                        data-modalidade-id='".htmlspecialchars($id_modalidade)."' 
                                        class='modalidade-switch' 
                                        ".($is_ativa ? 'checked' : '').">";
                            echo "<span class='slider round'></span>";
                            echo "</label>";
                            echo "</td>";
                            
                            echo "</tr>";
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="modal fade" id="pageModal" tabindex="-1" role="dialog" aria-labelledby="pageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" id="minha-modal-div">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pageModalLabel">Edição de Usuários</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="pageContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            // Script para o clique no switch de ativação de modalidade
            $('.modalidade-switch').on('change', function() {
                var modalidadeId = $(this).data('modalidade-id');
                var isChecked = $(this).is(':checked');
                
                // Exibe uma mensagem de processamento ou bloqueia a UI, se necessário
                var statusText = isChecked ? 'Ativando...' : 'Desativando...';
                
                // AJAX para enviar a alteração para o script PHP
                $.ajax({
                    type: 'POST',
                    url: 'processa_alteracao_modalidade.php', // O NOVO ARQUIVO QUE CRIAMOS
                    data: { 
                        modalidade_id: modalidadeId, 
                        ativa: isChecked // Envia true ou false
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Sucesso: feedback visual rápido (pode usar um toast ou alert)
                            console.log(response.message);
                            var newStatus = response.new_status == 1 ? 'ATIVADA' : 'DESATIVADA';
                            // Exemplo de notificação (você pode usar um alert simples ou uma biblioteca de notificações)
                            alert('Modalidade ID ' + modalidadeId + ' ' + newStatus + ' com sucesso!');
                        } else {
                            // Erro de lógica no PHP
                            alert('Erro ao atualizar status: ' + response.message);
                            // Desfaz a mudança visual do switch em caso de erro no DB
                            $('.modalidade-switch[data-modalidade-id="' + modalidadeId + '"]').prop('checked', !isChecked);
                        }
                    },
                    error: function(xhr, status, error) {
                        // Erro de comunicação ou servidor
                        alert('Erro na comunicação com o servidor. Status: ' + xhr.status);
                        // Desfaz a mudança visual do switch em caso de erro de comunicação
                        $('.modalidade-switch[data-modalidade-id="' + modalidadeId + '"]').prop('checked', !isChecked);
                    }
                });
            });

            // Código para o botão de edição (Mantido do seu código original)
            $(document).on('click', '.edit-btn', function() {
                var id = $(this).data('id');
                // Limpe o conteúdo da modal antes de abrir
                $('#pageContent').html('');
                
                // Enviar o ID do usuário para a página de edição na modal
                $.ajax({
                    type: 'POST',
                    url: 'edicao_usuario.php',
                    data: { id: id },
                    success: function(data) {
                        $('#pageContent').html(data);
                    },
                    error: function() {
                        alert('Erro ao carregar a página de edição.');
                    }
                });
            });
        });
    </script>
</main>
<?php include '../includes/layout_bottom.php'; ?>