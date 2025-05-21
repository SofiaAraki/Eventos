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
    carga_horaria_certificado INT DEFAULT 0,
    FOREIGN KEY (id_evento) REFERENCES eventos(id_evento)
);

ALTER TABLE eventos ADD valor_inscricao DECIMAL(10,2) NOT NULL DEFAULT 0.00;

CREATE TABLE pagamentos (
    id_pagamento INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_inscricao INT NOT NULL,
    status_pagamento TINYINT(1) NOT NULL DEFAULT 0,
    data_pagamento DATETIME DEFAULT CURRENT_TIMESTAMP,    
    FOREIGN KEY (id_inscricao) REFERENCES inscricoes(id)
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
    carga_horaria_certificado
) VALUES (
    1, -- ID do evento
    'Certificado de Participação',
    'Certificamos que o(a) participante participou do evento com êxito.',
    NOW(), -- Data de emissão
    'certificado_frente.jpg',
    8   -- carga horária
);



ALTER TABLE certificados
ADD CONSTRAINT fk_certificados_eventos
FOREIGN KEY (id_evento)
REFERENCES eventos(id_evento)
ON DELETE CASCADE;


ALTER TABLE inscricoes
ADD CONSTRAINT fk_inscricoes_eventos
FOREIGN KEY (id_evento)
REFERENCES eventos(id_evento)
ON DELETE CASCADE;


ALTER TABLE pagamentos
ADD CONSTRAINT fk_pagamentos_inscricoes
FOREIGN KEY (id_inscricao)
REFERENCES inscricoes(id_inscricao)
ON DELETE CASCADE;

