<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="../public/index.php">Cubo Mágico Competições</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="escolasDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Escolas
                </a>
                <div class="dropdown-menu" aria-labelledby="escolasDropdown">
                    <a class="dropdown-item" href="#">Cadastrar Escola</a>
                    <a class="dropdown-item" href="../public/lista_escolas.php">Listar Escolas</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="alunosDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Alunos
                </a>
                <div class="dropdown-menu" aria-labelledby="alunosDropdown">
                    <a class="dropdown-item" href="../public/cadastro_alunos.php">Cadastrar Aluno</a>
                    <a class="dropdown-item" href="#">Listar Alunos</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="modalidadesDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Modalidades
                </a>
                <div class="dropdown-menu" aria-labelledby="modalidadesDropdown">
                    <a class="dropdown-item" href="../public/lista_modalidades.php">Listar Modalidades</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="competicaoDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Competição
                </a>
                <div class="dropdown-menu" aria-labelledby="competicaoDropdown">
                    <a class="dropdown-item" href="../public/edicao_alunos_modalidades.php">Vincular Alunos e Modalidades</a>
                    <a class="dropdown-item" href="../public/edicao_alunos_modalidades_resultados.php">Inserir Resultados</a>
                    <a class="dropdown-item" href="../public/lista_resultados.php">Resultados</a>
                </div>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="administracaoDropdown" role="button" data-toggle="dropdown"
                   aria-haspopup="true" aria-expanded="false">
                    Administração
                </a>
                <div class="dropdown-menu" aria-labelledby="administracaoDropdown">
                    <a class="dropdown-item" href="cadastro_usuarios.php">Usuários</a>
                </div>
            </li>
        </ul>
    </div>

    <form class="form-inline" method="post" action="logout.php">
        <button class="btn btn-outline-danger my-2 my-sm-0" type="submit">Sair</button>
    </form>
</nav>
