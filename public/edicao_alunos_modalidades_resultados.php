<?php
// ARQUIVO: resolucoes.php

session_start();
include '../includes/funcs.php';
include '../includes/db_connection.php';

// ==========================================================
// CONFIGURAÇÃO DE CACHE E VARIÁVEIS DE ESTADO
// ==========================================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$statusMessage = '';
$filtrosSelecionados = [];

// ==========================================================
// LÓGICA DE RECARREGAMENTO E MENSAGENS
// ==========================================================
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    
    $modalidadeFiltro = $_GET['modalidade_filtro'] ?? 'todos';
    $alunoFiltro = $_GET['aluno_filtro'] ?? '';
    $generoFiltro = $_GET['genero'] ?? '1';
    
    $urlAtual = strtok($_SERVER["REQUEST_URI"], '?');
    
    if ($status === 'success') {
        $_SESSION['status_message'] = ['type' => 'success', 'text' => 'Dados salvos com sucesso e atualizados!'];
    } elseif ($status === 'error_db' || $status === 'error_db_init') {
        $_SESSION['status_message'] = ['type' => 'danger', 'text' => 'Erro ao salvar os dados no banco. Tente novamente.'];
    } elseif ($status === 'no_data') {
        $_SESSION['status_message'] = ['type' => 'warning', 'text' => 'Nenhum dado para salvar foi encontrado.'];
    } elseif ($status === 'error_format') {
        $_SESSION['status_message'] = ['type' => 'danger', 'text' => 'Erro: Formato de tempo inválido detectado. Use o padrão MM:SS.ms (ex: 12.34 ou 1:12.34). Os dados não foram salvos.'];
    } else {
        $_SESSION['status_message'] = ['type' => 'danger', 'text' => 'Ocorreu um erro desconhecido.'];
    }
    
    $_SESSION['filtros_selecionados'] = [
        'modalidade' => $modalidadeFiltro,
        'aluno' => $alunoFiltro,
        'genero' => $generoFiltro
    ];
    
    header("Location: " . $urlAtual); 
    exit;
}

if (isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    unset($_SESSION['status_message']);
    
    if (isset($_SESSION['filtros_selecionados'])) {
        $filtrosSelecionados = $_SESSION['filtros_selecionados'];
        unset($_SESSION['filtros_selecionados']);
    }
}

$modalidadeSelecionada = $filtrosSelecionados['modalidade'] ?? 'todos';
$generoSelecionado = $filtrosSelecionados['genero'] ?? '1';
$alunoSelecionado = $filtrosSelecionados['aluno'] ?? '';

// ==========================================================
// INÍCIO DA PÁGINA E DEPENDÊNCIAS
// ==========================================================

if (isset($_SESSION['usuario'])) {
    $nomeUsuario = obterNomeDoBancoDeDados($_SESSION['usuario']);
} else {
    header('Location: login.php');
    exit;
}

include '../includes/layout_top.php';
include '../includes/header.php';

$modalidadesParaFiltro = obterModalidades(); 
$todosOsAlunos = obterAlunos();            
$vinculos = obterVinculosAlunosModalidades(); 
$solversGlobais = obterTodosOsValoresSolver(); 

$vinculosJson = [];
foreach ($vinculos as $v) {
    $modalidadeKey = (string)$v['modalidade'];
    $alunoKey = (string)$v['aluno'];
    if (!isset($vinculosJson[$modalidadeKey])) {
        $vinculosJson[$modalidadeKey] = [];
    }
    $vinculosJson[$modalidadeKey][$alunoKey] = true; 
}
?>

<head>
    <title>Formulário de Resoluções</title>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const generoSelect = document.getElementById('genero');
        const modalidadeSelect = document.getElementById('modalidade_filtro');
        const alunoSelect = document.getElementById('aluno_filtro'); 
        const modalidadeTables = document.querySelectorAll('.modalidade-table');
        const saveButtonContainer = document.getElementById('saveButtonContainer'); 
        const saveButton = saveButtonContainer ? saveButtonContainer.querySelector('button') : null;
        
        const modalidadeFiltroOculto = document.getElementById('modalidade_filtro_oculto');
        const alunoFiltroOculto = document.getElementById('aluno_filtro_oculto');
        const generoFiltroOculto = document.getElementById('genero_filtro_oculto');

        const todosOsAlunos = <?php echo json_encode($todosOsAlunos); ?>;
        const vinculosExistentes = <?php echo json_encode($vinculosJson); ?>;

        function filtrarAlunos() {
            const modalidadeId = modalidadeSelect.value;
            const genero = generoSelect.value;
            const alunoSelecionadoAnterior = '<?php echo $alunoSelecionado; ?>';
            
            alunoSelect.innerHTML = '<option value="">Selecione um competidor</option>';
            alunoSelect.disabled = modalidadeId === 'todos';
            
            if (modalidadeId === 'todos') {
                exibirLinhaAluno(); 
                return;
            }

            let alunoAindaDisponivel = false;

            todosOsAlunos.forEach(aluno => {
                const alunoIdString = String(aluno.id); 
                const sexoString = String(aluno.sexo);
                
                const alunosDaModalidade = vinculosExistentes[modalidadeId];
                
                const isVinculado = alunosDaModalidade && alunosDaModalidade[alunoIdString]; 
                const isGeneroCorreto = genero === 'todos' || genero === sexoString; 

                if (isVinculado && isGeneroCorreto) {
                    const option = document.createElement('option');
                    option.value = alunoIdString;
                    option.textContent = aluno.nome;
                    alunoSelect.appendChild(option);
                    
                    if (alunoIdString === alunoSelecionadoAnterior) {
                        alunoAindaDisponivel = true;
                    }
                }
            });

            if (alunoAindaDisponivel) {
                 alunoSelect.value = alunoSelecionadoAnterior;
            } else {
                 alunoSelect.value = '';
            }
            
            exibirLinhaAluno();
        }

        function exibirLinhaAluno() {
            const alunoIdSelecionado = alunoSelect.value.trim(); 
            const modalidadeIdSelecionada = modalidadeSelect.value.trim();
            const generoIdSelecionado = generoSelect.value.trim(); 

            modalidadeFiltroOculto.value = modalidadeIdSelecionada;
            alunoFiltroOculto.value = alunoIdSelecionado;
            generoFiltroOculto.value = generoIdSelecionado; 
            
            if (saveButtonContainer) {
                const deveEstarVisivel = modalidadeIdSelecionada !== 'todos' && alunoIdSelecionado !== '';
                saveButtonContainer.style.display = deveEstarVisivel ? 'block' : 'none';
                if (saveButton) {
                     saveButton.disabled = !deveEstarVisivel;
                }
            }

            modalidadeTables.forEach(function (table) {
                const modalidadeId = table.getAttribute('data-modalidade-id');
                const tableTitle = document.getElementById('title-modalidade-' + modalidadeId);
                const rows = table.querySelectorAll('.resolucao-row');

                const tabelaDeveSerVisivel = modalidadeId == modalidadeIdSelecionada && alunoIdSelecionado !== '';
                
                if (tabelaDeveSerVisivel) {
                    table.style.display = 'table';
                    if (tableTitle) tableTitle.style.display = 'block';
                } else {
                    table.style.display = 'none';
                    if (tableTitle) tableTitle.style.display = 'none';
                }

                rows.forEach(function (row) {
                    const alunoIdDaLinha = row.getAttribute('data-aluno-id'); 
                    const inputs = row.querySelectorAll('input[type="text"]');
                    
                    if (tabelaDeveSerVisivel && alunoIdDaLinha === alunoIdSelecionado) { 
                        row.style.display = 'table-row';
                        inputs.forEach(input => input.disabled = false); 
                    } else {
                        row.style.display = 'none';
                        inputs.forEach(input => input.disabled = true); 
                    }
                });
            });
        }

        generoSelect.addEventListener('change', filtrarAlunos);
        modalidadeSelect.addEventListener('change', filtrarAlunos);
        alunoSelect.addEventListener('change', exibirLinhaAluno); 

        setTimeout(filtrarAlunos, 50); 
    });

    // Validação de formato e aviso de campos em branco no envio
    document.addEventListener("DOMContentLoaded", function () {
        const formResolucoes = document.querySelector('form[action="processa_solvers.php"]');
        if (formResolucoes) {
            formResolucoes.addEventListener('submit', function(e) {
                const inputsAtivos = formResolucoes.querySelectorAll('input[type="text"]:not(:disabled)');
                let erroFormato = false;
                let campoComErro = '';
                let camposEmBranco = 0;
                let totalInputs = inputsAtivos.length;

                // Regex aceita formatos de tempo válidos ou DNF/DNS (que serão convertidos em branco)
                const regexTempo = /^([0-9]+:[0-5][0-9]\.[0-9]{2}|[0-5]?[0-9]\.[0-9]{2}|[0-9]+:[0-5][0-9]|[0-5]?[0-9]|DNF|DNS)$/i;

                inputsAtivos.forEach(input => {
                    let valor = input.value.trim();
                    
                    if (valor === '') {
                        camposEmBranco++;
                        input.classList.remove('is-invalid');
                    } else if (!regexTempo.test(valor)) {
                        erroFormato = true;
                        campoComErro = valor;
                        input.classList.add('is-invalid');
                    } else {
                        input.classList.remove('is-invalid');
                    }
                });

                // 1. Se houver erro de formato, BLOQUEIA obrigatoriamente
                if (erroFormato) {
                    e.preventDefault();
                    alert('Atenção: O valor "' + campoComErro + '" está em um formato inválido.\n\nO formato correto deve seguir o padrão: \n• 01:12.34 (minutos, segundos e centésimos)');
                    return;
                }

                // 2. Se o formato estiver correto mas houver campos em branco, avisa mas permite escolher
                if (camposEmBranco > 0 && camposEmBranco < totalInputs) {
                    const prosseguir = confirm('Atenção: Existem ' + camposEmBranco + ' solver(s) em branco.\n\nVerifique o formulário antes de prosseguir ou clique em "OK" para salvar mesmo assim.');
                    if (!prosseguir) {
                        e.preventDefault();
                    }
                } else if (camposEmBranco === totalInputs) {
                    const prosseguir = confirm('Atenção: Todos os solvers deste aluno estão em branco. Deseja continuar?');
                    if (!prosseguir) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
    </script>
</head>
<body>
    <div class="container">
        <h2 class="mt-4 mb-4">Formulário de Resoluções</h2>
        
        <?php if (!empty($statusMessage)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($statusMessage['type']); ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($statusMessage['text']); ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <label for="genero" class="input-group-text">Gênero:</label>
                        </div>
                        <select class="custom-select" id="genero" name="genero">
                            <option value="todos" <?php if ($generoSelecionado === 'todos') echo 'selected'; ?>>Todos</option>
                            <option value="1" <?php if ($generoSelecionado === '1') echo 'selected'; ?>>Masculino</option>
                            <option value="2" <?php if ($generoSelecionado === '2') echo 'selected'; ?>>Feminino</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <label for="modalidade_filtro" class="input-group-text">Modalidade:</label>
                        </div>
                        <select class="custom-select" id="modalidade_filtro" name="modalidade_filtro">
                            <option value="todos" <?php if ($modalidadeSelecionada === 'todos') echo 'selected'; ?>>Todas</option>
                            <?php foreach ($modalidadesParaFiltro as $modalidade_f): ?>
                                <option value="<?php echo $modalidade_f['id']; ?>" <?php if ($modalidadeSelecionada == $modalidade_f['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($modalidade_f['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <label for="aluno_filtro" class="input-group-text">Aluno:</label>
                        </div>
                        <select class="custom-select" id="aluno_filtro" name="aluno_filtro" <?php echo ($modalidadeSelecionada === 'todos') ? 'disabled' : ''; ?>>
                            <option value="">Selecione um aluno</option>
                            <?php if (!empty($alunoSelecionado)): ?>
                                <option value="<?php echo htmlspecialchars($alunoSelecionado); ?>" selected>
                                    <?php 
                                        $alunoObj = array_filter($todosOsAlunos, fn($a) => $a['id'] == $alunoSelecionado);
                                        echo htmlspecialchars($alunoObj ? array_values($alunoObj)[0]['nome'] : 'Carregando...');
                                    ?>
                                </option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </div>

        </div>
        
        <form action="processa_solvers.php" method="post">
            
            <input type="hidden" name="modalidade_filtro_oculto" id="modalidade_filtro_oculto" value="<?php echo htmlspecialchars($modalidadeSelecionada); ?>">
            <input type="hidden" name="aluno_filtro_oculto" id="aluno_filtro_oculto" value="<?php echo htmlspecialchars($alunoSelecionado); ?>">
            <input type="hidden" name="genero_filtro_oculto" id="genero_filtro_oculto" value="<?php echo htmlspecialchars($generoSelecionado); ?>">
            
            <?php
            $vinculosExistentes = [];
            foreach ($vinculos as $vinculo) {
                $modalidadeKey = (string)$vinculo['modalidade'];
                $alunoKey = (string)$vinculo['aluno'];
                if (!isset($vinculosExistentes[$modalidadeKey])) {
                     $vinculosExistentes[$modalidadeKey] = [];
                }
                $vinculosExistentes[$modalidadeKey][$alunoKey] = true;
            }

            foreach ($modalidadesParaFiltro as $modalidade) {
                $modalidadeId = $modalidade['id'];
                
                if (isset($vinculosExistentes[$modalidadeId]) && count($vinculosExistentes[$modalidadeId]) > 0) {
                    echo '<h3 class="modalidade-title" id="title-modalidade-' . $modalidadeId . '" style="display:none;">' . htmlspecialchars($modalidade['nome']) . '</h3>';
                    echo '<table class="table table-striped modalidade-table" data-modalidade-id="' . $modalidadeId . '" style="display:none;">';
                    echo '<thead>';
                    echo '<tr>';
                    echo '<th>Aluno</th>';
                    for ($i = 1; $i <= 5; $i++) {
                        echo '<th>Solver ' . $i . '</th>';
                    }
                    echo '</tr>';
                    echo '</thead>';
                    echo '<tbody>';
                    
                    foreach ($todosOsAlunos as $aluno) {
                        $alunoId = $aluno['id'];
                        
                        if (isset($vinculosExistentes[$modalidadeId][$alunoId])) {
                            echo '<tr class="resolucao-row" data-genero="' . htmlspecialchars($aluno['sexo']) . '" data-aluno-id="' . $alunoId . '" style="display:none;">';
                            echo '<td>' . htmlspecialchars($aluno['nome']) . '</td>';
                            
                            $valoresSolverExistente = $solversGlobais[$modalidadeId][$alunoId] ?? null;
                            
                            for ($i = 1; $i <= 5; $i++) {
                                $inputName = "resolucoes[" . $modalidadeId . "][" . $alunoId . "][" . $i . "]";
                                $valorExistente = $valoresSolverExistente ? $valoresSolverExistente["solver$i"] : ''; 
                                echo '<td><input type="text" class="form-control form-control-sm" name="' . $inputName . '" value="' . htmlspecialchars($valorExistente) . '" disabled></td>'; 
                            }
                            echo '</tr>';
                        }
                    }
                    echo '</tbody>';
                    echo '</table>';
                }
            }
            ?>
            
            <div id="saveButtonContainer" style="display: none;" class="float-right mb-4">
                <button type="submit" class="btn btn-primary">Salvar</button>
            </div>
        </form>
    </div>
    <?php include '../includes/layout_bottom.php'; ?>
</body>
</html>