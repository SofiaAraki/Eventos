CREATE TABLE eventos (
    id_evento INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo_evento VARCHAR(255) NOT NULL,
    data_inicio_evento DATETIME NOT NULL,
    data_fim_evento DATETIME NOT NULL,
    local_evento VARCHAR(255),
    descricao_evento TEXT,
    status_evento TINYINT(1) NOT NULL DEFAULT 1,
    valor_evento DECIMAL(10,2) DEFAULT 0.00,
    gerente_evento INT,
    FOREIGN KEY (gerente_evento) REFERENCES system_users(id)
);

CREATE TABLE inscricoes (
    id_inscricao INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_participacao ENUM('aluno', 'banca', 'orientador', 'palestrante', 'autor') DEFAULT 'aluno',
    data_inscricao DATETIME NOT NULL,
    id_usuario INT NOT NULL,
    id_evento INT NOT NULL,
    status_inscricao TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES system_users(id),
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE certificados (
    id_certificado INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    tipo_certificado ENUM('aluno', 'banca', 'orientador', 'palestrante', 'autor') DEFAULT 'aluno',
    id_evento INT NOT NULL,
    titulo_certificado VARCHAR(255) NOT NULL,
    descricao_certificado TEXT,
    data_emissao_certificado DATETIME DEFAULT CURRENT_TIMESTAMP,
    bg_frente VARCHAR(255),
    carga_horaria_certificado INT DEFAULT 0,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE pagamentos (
    id_pagamento INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_inscricao INT NOT NULL,
    status_pagamento TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,    
    FOREIGN KEY (id_inscricao) REFERENCES inscricoes(id_inscricao)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE registros (
    id_registro INT PRIMARY KEY AUTO_INCREMENT,
    id_inscricao INT NOT NULL,
    tipo_certificado ENUM('aluno', 'banca', 'orientador', 'palestrante', 'autor') NOT NULL,
    descricao_certificado TEXT NULL,
    data_emissao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_inscricao) REFERENCES inscricoes(id_inscricao)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

CREATE TABLE tccs (
    id_tcc INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    titulo_tcc VARCHAR(255) NOT NULL,
    data_tcc DATE NOT NULL,
    id_orientador INT NULL,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_orientador) REFERENCES system_users(id) ON DELETE SET NULL ON  UPDATE CASCADE
);

CREATE TABLE autores (
    id_autor INT AUTO_INCREMENT PRIMARY KEY,
    id_tcc INT NOT NULL,
    autor INT NOT NULL,
    FOREIGN KEY (id_tcc) REFERENCES tccs(id_tcc) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (autor) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE banca (
    id_banca INT AUTO_INCREMENT PRIMARY KEY,
    id_tcc INT NOT NULL,
    banca INT NOT NULL,
    FOREIGN KEY (id_tcc) REFERENCES tccs(id_tcc) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (banca) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE
);

