-- Migração para bases ShowMe já existentes.
-- Execute este arquivo uma vez antes de usar a recuperação de senha.
USE showme;

CREATE TABLE IF NOT EXISTS recuperacao_senha (
    id_recuperacao INT PRIMARY KEY AUTO_INCREMENT,
    id_usuario     INT NOT NULL,
    token_hash     CHAR(64) NOT NULL,
    expira_em      DATETIME NOT NULL,
    usado_em       DATETIME NULL,
    criado_em      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ip_solicitante VARCHAR(45) NULL,

    UNIQUE KEY uk_recuperacao_token (token_hash),
    INDEX idx_recuperacao_usuario (id_usuario, criado_em),
    INDEX idx_recuperacao_expira (expira_em),

    FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
