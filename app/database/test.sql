CREATE TABLE eventos (
    id_evento INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo_evento VARCHAR(255) NOT NULL,
    data_inicio_evento DATETIME NOT NULL,
    data_fim_evento DATETIME NOT NULL,
    local_evento VARCHAR(255) NOT NULL,
    descricao_evento TEXT,
    status_evento TINYINT(1) NOT NULL DEFAULT 1,
    gerente_evento INT,
    FOREIGN KEY (gerente_evento) REFERENCES system_users(id)
);

CREATE TABLE inscricoes (
    id_inscricao INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    data_inscricao DATETIME NOT NULL,
    id_usuario INT NOT NULL,
    id_evento INT NOT NULL,
    status_inscricao TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_usuario) REFERENCES system_users(id),
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
);

CREATE TABLE certificados (
    id_certificado INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    titulo_certificado VARCHAR(255) NOT NULL,
    descricao_certificado TEXT,
    data_emissao_certificado DATETIME DEFAULT CURRENT_TIMESTAMP,
    bg_frente VARCHAR(255),
    bg_verso VARCHAR(255),
    orientacao_pagina VARCHAR(20) DEFAULT 'landscape',
    mostra_verso TINYINT(1) DEFAULT 0,
    margem_esquerda INT DEFAULT 20,
    margem_direita INT DEFAULT 20,
    carga_horaria_certificado INT DEFAULT 0,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
);

INSERT INTO eventos (
    titulo_evento,
    data_inicio_evento,
    data_fim_evento,
    local_evento,
    descricao_evento,
    status_evento,
    gerente_evento
) VALUES (
    'Workshop de PHP',
    '2025-06-01 09:00:00',
    '2025-06-01 17:00:00',
    'Auditório Principal',
    'Evento focado em boas práticas com PHP moderno.',
    1,
    1
);

INSERT INTO inscricoes (data_inscricao, id_usuario, id_evento)
VALUES ('2025-05-17 14:30:00', 3, 1);

INSERT INTO certificados (
    id_evento,
    titulo_certificado,
    descricao_certificado,
    data_emissao_certificado,
    bg_frente,
    bg_verso,
    orientacao_pagina,
    mostra_verso,
    margem_esquerda,
    margem_direita,
    carga_horaria_certificado
) VALUES (
    1, -- ID do evento
    'Certificado de Participação',
    'Certificamos que o(a) participante participou do evento com êxito.',
    NOW(), -- Data de emissão
    'certificado_frente.jpg',
    'certificado_verso.jpg',
    'landscape', -- ou 'portrait'
    1, -- Mostrar verso: 1 para sim, 0 para não
    30, -- margem esquerda
    30, -- margem direita
    8   -- carga horária
);
