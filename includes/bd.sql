CREATE TABLE `sexo` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(10),
    PRIMARY KEY (`id`),
    UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

insert into sexo (nome) values('Masculino'); 
insert into sexo (nome) values('Feminino');

CREATE TABLE `escolas` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `cidade` varchar(255) NOT NULL,
    PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

insert into escolas (nome, cidade) values ('Anjos Custodios, C-Ei Ef M','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Arco-Iris, C E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Aurea Mathias Franco, C M E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Benedito R de Souza, E E C -Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Cantinho Feliz, C E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Conjunto Joao de Barro, C E-Ef M','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Criativo, E-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Dolores C Villa Verde, C M E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Eurico J D de Barros, E M Dr-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Felipe S Bittencourt, C E Dr-Ef M','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Guiti Sato, E M-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Gumercindo Lopes, E M C Prof-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Izabel Maria Artero Parra, C M E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Jose Garbugio, E M-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Juracy R S Rocha, C E-Ef M Profis','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Leonardo H Alves de Souza, C M E I','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Lucas M de Paula, E M-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Marcia R Zucoli Colombari, C M E I Profa','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Maria dos S Severino, E M-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Milton T Paes, E M Dr-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('New Life, E-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Nilo Pecanha, E M-Ei Ef','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Pedro V Parigot Souza, C E C-M-Ef M P','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Pedro V P de Souza, E-Ei Ef Mod Ed Esp','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Romario Martins, C E-Ef M	Estadual','MARIALVA/PR');
insert into escolas (nome, cidade) values ('Sao Miguel do Cambui, E M-Ei Ef','MARIALVA/PR');

CREATE TABLE `alunos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `idade` int(11) NOT NULL,
    `sexo` int(11) NOT NULL,
    `escola` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY(`SEXO`) REFERENCES `sexo`(`id`),
    FOREIGN KEY(`escola`) REFERENCES `escolas`(`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `modalidades` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100)
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

insert into modalidades (nome) values ('Cubo 2x2');
insert into modalidades (nome) values ('Cubo 3x3');
insert into modalidades (nome) values ('Cubo 4x4');
insert into modalidades (nome) values ('Cubo 5x5');
insert into modalidades (nome) values ('Cubo Pyraminx');
insert into modalidades (nome) values ('Cubo Megaminx');
insert into modalidades (nome) values ('Cubo Skewb');

CREATE TABLE `alunomodalidade` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `aluno` int(11) NOT NULL,
    `modalidade` int(11) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`aluno`) REFERENCES `alunos` (`id`),
    FOREIGN KEY (`modalidade`) REFERENCES `modalidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `alunomodalidadesolver` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `aluno` int(11) NOT NULL,
    `modalidade` int(11) NOT NULL,
    `solver1` varchar(255) NOT NULL,
    `solver2` varchar(255) NOT NULL,
    `solver3` varchar(255) NOT NULL,
    `solver4` varchar(255) NOT NULL,
    `solver5` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`aluno`) REFERENCES `alunos` (`id`),
    FOREIGN KEY (`modalidade`) REFERENCES `modalidades` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `usuarios` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `senha` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `email` (`email`)
 ) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4;