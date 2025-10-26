<?php
// ARQUIVO: header.php

// 1. Inicia a sessão no topo, se necessário
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. CORREÇÃO: Define a variável de controle.
// Verifica se a chave 'usuario' (ou a que você usa para login) existe na sessão.
$usuario_logado = isset($_SESSION['usuario']) && !empty($_SESSION['usuario']);
?>

<nav class="navbar navbar-expand-lg navbar-wca" style="background-color: #DEDEDE !important; border-bottom: 1px solid #c0c0c0;">
    <a class="navbar-brand" href="../public/index.php">
        <i class="fa-solid fa-cube me-2"></i> Cubo Mágico Competições
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="escolasDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-school"></i> Escolas
                </a>
                <div class="dropdown-menu" aria-labelledby="escolasDropdown">
                    <a class="dropdown-item" href="#"><i class="fa-solid fa-plus"></i> Cadastrar Escola</a>
                    <a class="dropdown-item" href="../public/lista_escolas.php"><i class="fa-solid fa-list"></i> Listar Escolas</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="alunosDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-user-graduate"></i> Alunos
                </a>
                <div class="dropdown-menu" aria-labelledby="alunosDropdown">
                    <a class="dropdown-item" href="../public/cadastro_alunos.php"><i class="fa-solid fa-user-plus"></i> Cadastrar Aluno</a>
                    <a class="dropdown-item" href="#"><i class="fa-solid fa-users"></i> Listar Alunos</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="modalidadesDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-shuffle"></i> Modalidades
                </a>
                <div class="dropdown-menu" aria-labelledby="modalidadesDropdown">
                    <a class="dropdown-item" href="../public/lista_modalidades.php"><i class="fa-solid fa-bars"></i> Listar Modalidades</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="competicaoDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-trophy"></i> Competição
                </a>
                <div class="dropdown-menu" aria-labelledby="competicaoDropdown">
                    <a class="dropdown-item" href="../public/edicao_alunos_modalidades.php"><i class="fa-solid fa-link"></i> Vincular Alunos/Modalidades</a>
                    <a class="dropdown-item" href="../public/edicao_alunos_modalidades_resultados.php"><i class="fa-solid fa-input-text"></i> Inserir Resultados</a>
                    <a class="dropdown-item" href="../public/lista_resultados.php"><i class="fa-solid fa-chart-bar"></i> Resultados</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="administracaoDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-users-gear"></i> Administração
                </a>
                <div class="dropdown-menu" aria-labelledby="administracaoDropdown">
                    <a class="dropdown-item" href="cadastro_usuarios.php"><i class="fa-solid fa-user-shield"></i> Usuários</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="dashDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                   <i class="fa-solid fa-solid fa-house"></i> Análise de dados
                </a>
                <div class="dropdown-menu" aria-labelledby="dashDropdown">
                    <a class="dropdown-item" href="../public/dash.php"><i class="fa-solid fa-solid fa-chart-line"></i> Dashboard</a>
                </div>
            </li>
        </ul>
    </div>

    <div class="d-flex">
        <?php if ($usuario_logado): ?>
            <span class="navbar-text text-dark mr-3 d-none d-lg-block">
                 Olá, <?php echo obterNomeDoBancoDeDados($_SESSION['usuario']); ?>
            </span>
            <form class="form-inline" method="post" action="logout.php">
                <button class="btn btn-outline-dark my-2 my-sm-0" type="submit">
                   <i class="fa-solid fa-right-from-bracket"></i> Sair
                </button>
            </form>
        <?php else: ?>
            <a class="btn btn-outline-dark my-2 my-sm-0" href="login.php">
                <i class="fa-solid fa-sign-in-alt"></i> Login
            </a>
        <?php endif; ?>
    </div>
</nav>
</nav>