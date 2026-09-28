CREATE TABLE tipos_participacao (
    codigo VARCHAR(20) PRIMARY KEY,
    descricao VARCHAR(50) NOT NULL
);

INSERT INTO tipos_participacao VALUES
    ('aluno',       'Aluno'),
    ('professor',   'Professor'),
    ('palestrante', 'Palestrante'),
    ('autor',       'Autor'),
    ('banca',       'Banca'),
    ('orientador',  'Orientador'),
    ('monitor',     'Monitor'),
    ('organizador', 'Organizador');
    

CREATE TABLE evento (
    id_evento INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    titulo_evento VARCHAR(255) NOT NULL,
    arte_evento VARCHAR(255) NULL DEFAULT NULL,
    valor_evento DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    data_inicio_evento DATETIME NOT NULL,
    data_fim_evento DATETIME NOT NULL,
    local_evento VARCHAR(255),
    descricao_evento TEXT,
    status_evento TINYINT(1) NOT NULL DEFAULT 1,
    gerente_evento INT COMMENT 'Responsável/Criador do evento',
    status_aprovacao TINYINT NOT NULL DEFAULT 0 COMMENT '0=Pendente, 1=Aprovado, 2=Rejeitado',
    observacao_aprovacao TEXT NULL DEFAULT NULL COMMENT 'Parecer ou motivo de rejeição da administração',
    atualizado_por INT NULL COMMENT 'Último usuário que editou',
    data_atualizacao DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (gerente_evento) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (atualizado_por) REFERENCES system_users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE evento_coordenador (
    id_evento_coordenador INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    id_usuario INT NOT NULL,
    FOREIGN KEY (id_evento) REFERENCES evento(id_evento) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_evento_coordenador (id_evento, id_usuario)
);

CREATE TABLE inscricao (
    id_inscricao        INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario          INT NOT NULL,
    id_evento           INT NOT NULL,
    tipo_participacao   VARCHAR(20) NOT NULL DEFAULT 'aluno',
    data_inscricao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status_inscricao    TINYINT(1) NOT NULL DEFAULT 0,
    cod_validador       VARCHAR(20),
    FOREIGN KEY (id_usuario)        REFERENCES system_users(id)   ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_evento)         REFERENCES evento(id_evento)  ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tipo_participacao) REFERENCES tipos_participacao(codigo) ON UPDATE CASCADE,
    UNIQUE KEY uq_inscricao (id_usuario, id_evento, tipo_participacao)
);

CREATE TABLE pagamento (
    id_pagamento         INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_inscricao         INT NOT NULL,
    data_pagamento       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    --valor_pagamento      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status_pagamento     TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_inscricao) REFERENCES inscricao(id_inscricao) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE certificado (
    id_certificado              INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_evento                   INT NOT NULL,
    tipo_participacao            VARCHAR(20) NOT NULL DEFAULT 'aluno',
    titulo_certificado          VARCHAR(255) NOT NULL,
    data_emissao_certificado    DATETIME DEFAULT CURRENT_TIMESTAMP,
    descricao_certificado       TEXT,
    instituicao_certificado     VARCHAR(255) DEFAULT 'FAFRAM - Faculdade Dr. Francisco Maeda',
    bg_frente_certificado VARCHAR(20) DEFAULT 'fafram.png',
    assinatura_certificado VARCHAR(20) DEFAULT 'beto.png',
    carga_horaria_certificado   INT DEFAULT 0,
    presenca_minima_certificado         INT NOT NULL DEFAULT 0,
    FOREIGN KEY (id_evento)         REFERENCES evento(id_evento)           ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (tipo_participacao)  REFERENCES tipos_participacao(codigo)   ON UPDATE CASCADE,
    UNIQUE KEY uq_certificado (id_evento, tipo_participacao)
);

CREATE TABLE registro (
    id_registro                 INT AUTO_INCREMENT PRIMARY KEY,
    id_inscricao                INT NOT NULL,
    id_certificado              INT NOT NULL,          -- template de origem
    data_registro               DATETIME DEFAULT CURRENT_TIMESTAMP,
    descricao_certificado TEXT NULL,
    FOREIGN KEY (id_inscricao)   REFERENCES inscricao(id_inscricao)   ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_certificado) REFERENCES certificado(id_certificado) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_registro (id_inscricao, id_certificado)
);

CREATE TABLE tcc (
    id_tcc INT AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    titulo_tcc VARCHAR(255) NOT NULL,
    data_tcc DATE NOT NULL,
    id_orientador INT NULL,
    FOREIGN KEY (id_evento) REFERENCES evento(id_evento)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    FOREIGN KEY (id_orientador) REFERENCES system_users(id) ON DELETE SET NULL ON  UPDATE CASCADE
);

CREATE TABLE autor (
    id_autor INT AUTO_INCREMENT PRIMARY KEY,
    id_tcc INT NOT NULL,
    id_autor_usuario INT NOT NULL,
    FOREIGN KEY (id_tcc) REFERENCES tcc(id_tcc) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_autor_usuario) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_autor (id_tcc, id_autor_usuario)
);

CREATE TABLE banca (
    id_banca INT AUTO_INCREMENT PRIMARY KEY,
    id_tcc INT NOT NULL,
    id_banca_usuario INT NOT NULL,
    FOREIGN KEY (id_tcc) REFERENCES tcc(id_tcc) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_banca_usuario) REFERENCES system_users(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_banca (id_tcc, id_banca_usuario)
);

CREATE TABLE presenca (
    id_presenca     INT AUTO_INCREMENT PRIMARY KEY,
    id_inscricao    INT NOT NULL,
    data_entrada    DATETIME NULL,
    data_saida      DATETIME NULL,
    responsavel     INT NULL,
    permanencia_min INT DEFAULT NULL,
    FOREIGN KEY (id_inscricao) REFERENCES inscricao(id_inscricao) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (responsavel)  REFERENCES system_users(id)         ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE TABLE monitor (
    id_monitor   INT AUTO_INCREMENT PRIMARY KEY,
    id_evento    INT NOT NULL,
    id_usuario   INT NOT NULL,
    FOREIGN KEY (id_evento)        REFERENCES evento(id_evento)   ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (id_usuario)   REFERENCES system_users(id)     ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY uq_monitor (id_evento, id_usuario)
);

CREATE TABLE emissao_manual (
    id_emissao INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_evento INT NOT NULL,
    nome_pessoa VARCHAR(255) NOT NULL,
    bg_frente_certificado VARCHAR(50) NOT NULL DEFAULT 'fafram.png',
    descricao_certificado TEXT NOT NULL,
    data_emissao DATETIME DEFAULT CURRENT_TIMESTAMP,
    coordenador_id INT NULL,
    FOREIGN KEY (id_evento) REFERENCES evento(id_evento) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (coordenador_id) REFERENCES system_users(id) ON DELETE SET NULL ON UPDATE CASCADE
);

CREATE VIEW `view_registro` AS 
SELECT 
    `r`.`id_registro`         AS `id_registro`,
    `r`.`id_inscricao`        AS `id_inscricao`,
    `r`.`id_certificado`      AS `id_certificado`,
    `r`.`data_registro`       AS `data_registro`,
    `i`.`id_usuario`          AS `id_usuario`,
    `i`.`id_evento`           AS `id_evento`,
    `i`.`tipo_participacao`   AS `tipo_participacao`,
    `i`.`status_inscricao`    AS `status_inscricao`,
    `su`.`name`               AS `usuario_name`,
    `e`.`titulo_evento`       AS `evento_name`,
    `c`.`titulo_certificado`  AS `certificado_name`,
    `c`.`descricao_certificado` AS `modelo_certificado`,
    `tp`.`descricao`          AS `tipo_participacao_name`
FROM `registro` `r`
INNER JOIN `inscricao` `i`             ON (`i`.`id_inscricao` = `r`.`id_inscricao`)
LEFT JOIN  `system_users` `su`          ON (`su`.`id` = `i`.`id_usuario`)
LEFT JOIN  `evento` `e`                 ON (`e`.`id_evento` = `i`.`id_evento`)
LEFT JOIN  `certificado` `c`            ON (`c`.`id_certificado` = `r`.`id_certificado`)
LEFT JOIN  `tipos_participacao` `tp`    ON (`tp`.`codigo` = `i`.`tipo_participacao`);

-- Atualiza eventos existentes para 'Aprovado' (status_aprovacao = 1)
UPDATE `evento` 
SET `status_aprovacao` = 1 
WHERE `status_aprovacao` = 0;

CREATE OR REPLACE VIEW `view_relatorio_participantes` AS 
SELECT 
    `i`.`id_inscricao` AS `id_inscricao`,
    `i`.`id_evento` AS `id_evento`,
    `i`.`id_usuario` AS `id_usuario`,
    `i`.`status_inscricao` AS `status_inscricao`,
    `i`.`tipo_participacao` AS `tipo_participacao_codigo`,
    COALESCE(`tp`.`descricao`, 'Aluno') AS `tipo_participacao_descricao`,
    `u`.`name` AS `usuario_name`,
    `u`.`email` AS `usuario_email`,
    `u`.`curso` AS `usuario_curso`, -- Nova coluna adicionada
    
    CASE 
        WHEN `i`.`status_inscricao` = 1 THEN 'Confirmado'
        WHEN `i`.`status_inscricao` = 0 THEN 'Pendente'
        ELSE 'Cancelado'
    END AS `status_label`,

    COALESCE((
        SELECT CASE WHEN `p_ult`.`data_saida` IS NULL THEN 1 ELSE 0 END
        FROM `presenca` `p_ult`
        WHERE `p_ult`.`id_inscricao` = `i`.`id_inscricao`
        ORDER BY `p_ult`.`id_presenca` DESC 
        LIMIT 1
    ), 0) AS `esta_presente`,

    CASE 
        WHEN COALESCE(SUM(CASE WHEN `p`.`data_saida` IS NOT NULL THEN 1 ELSE 0 END), 0) > 0 THEN 1 
        ELSE 0 
    END AS `ja_saiu`,

    COALESCE(SUM(`p`.`permanencia_min`), 0) AS `total_minutos`,
    COALESCE(SUM(`p`.`permanencia_min`), 0) AS `permanencia_min`,

    MAX(`p`.`data_entrada`) AS `ultima_entrada`,
    MAX(`p`.`data_saida`) AS `ultima_saida`,
    CAST(MAX(`p`.`data_entrada`) AS DATE) AS `dia_evento`,

    (
        SELECT `su`.`name` 
        FROM `presenca` `p2` 
        LEFT JOIN `system_users` `su` ON (`su`.`id` = `p2`.`responsavel`)
        WHERE `p2`.`id_inscricao` = `i`.`id_inscricao`
        ORDER BY `p2`.`id_presenca` DESC 
        LIMIT 1
    ) AS `responsavel_nome`

FROM `inscricao` `i`
JOIN `system_users` `u` ON (`u`.`id` = `i`.`id_usuario`)
LEFT JOIN `tipos_participacao` `tp` ON (`tp`.`codigo` = `i`.`tipo_participacao`)
LEFT JOIN `presenca` `p` ON (`p`.`id_inscricao` = `i`.`id_inscricao`)

GROUP BY 
    `i`.`id_inscricao`, 
    `i`.`id_evento`, 
    `i`.`id_usuario`, 
    `i`.`status_inscricao`, 
    `i`.`tipo_participacao`,
    `tp`.`descricao`,
    `u`.`name`, 
    `u`.`email`,
    `u`.`curso`;