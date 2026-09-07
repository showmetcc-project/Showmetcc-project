CREATE DATABASE IF NOT EXISTS showme
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE showme;

CREATE TABLE usuario (
    id_user       INT PRIMARY KEY AUTO_INCREMENT,
    nome_user     VARCHAR(100) NOT NULL,
    sobrenome     VARCHAR(100),
    email_user    VARCHAR(100) NOT NULL,
    senha_user    VARCHAR(255) NOT NULL,

    tipo_usuario  ENUM('comum', 'admin') NOT NULL DEFAULT 'comum',

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_usuario_email (email_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spotify (
    id_spotify             INT PRIMARY KEY AUTO_INCREMENT,
    id_user                INT NOT NULL,

    spotify_id              VARCHAR(100),

    artistas_mais_tocados   VARCHAR(255),
    generos_preferidos      VARCHAR(255),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE  -- FIX: dado do Spotify não faz sentido sem o usuário dono
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artista (
    id_artista       INT PRIMARY KEY AUTO_INCREMENT,
    nome_artista     VARCHAR(150) NOT NULL,
    genero_artista   VARCHAR(100),
    imagem_artista   VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento (
    id_evento          INT PRIMARY KEY AUTO_INCREMENT,
    num_evento         INT,
    nome_evento        VARCHAR(100) NOT NULL,
    local_evento       VARCHAR(100),
    rua_evento         VARCHAR(100),
    cidade_evento      VARCHAR(100),
    uf                 CHAR(2),
    descricao_evento   VARCHAR(1000),
    data_evento        DATE,
    gratuidade         BOOLEAN NOT NULL DEFAULT FALSE,
    categoria_evento   VARCHAR(100),
    link_oficial       VARCHAR(255),
    imagem_evento      VARCHAR(255),
    status_evento      ENUM('ativo', 'cancelado') NOT NULL DEFAULT 'ativo',

    INDEX idx_evento_data (data_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artista_evento (
    id_artista INT,
    id_evento  INT,

    PRIMARY KEY (id_artista, id_evento),

    FOREIGN KEY (id_artista)
        REFERENCES artista(id_artista)
        ON DELETE CASCADE,

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE preferencias (
    id_preferencia   INT PRIMARY KEY AUTO_INCREMENT,
    id_user          INT NOT NULL,
    genero_preferido VARCHAR(100) NOT NULL,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    UNIQUE KEY uk_preferencia (id_user, genero_preferido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favoritos (
    id_favorito INT PRIMARY KEY AUTO_INCREMENT,
    id_user     INT NOT NULL,
    id_evento   INT NOT NULL,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
        ON DELETE CASCADE,

    UNIQUE KEY uk_favorito (id_user, id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rota (
    id_rota          INT PRIMARY KEY AUTO_INCREMENT,
    id_user          INT NOT NULL,
    id_evento        INT NOT NULL,
    meio_transporte  VARCHAR(30),
    distancia_km     DECIMAL(10,2),
    tempo_estimado   INT,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaliacao (
    id_avaliacao    INT PRIMARY KEY AUTO_INCREMENT,
    id_user         INT NOT NULL,
    id_evento       INT NOT NULL,
    nota            INT NOT NULL,
    comentario      VARCHAR(1000),
    data_avaliacao  DATE NOT NULL DEFAULT (CURRENT_DATE),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
        ON DELETE CASCADE,
    CONSTRAINT chk_avaliacao_nota CHECK (nota BETWEEN 1 AND 5), 
    UNIQUE KEY uk_avaliacao (id_user, id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE avaliacao_midia (
    id_midia        INT PRIMARY KEY AUTO_INCREMENT,
    id_avaliacao    INT NOT NULL,
    tipo_midia      ENUM('foto', 'video') NOT NULL,
    caminho_arquivo VARCHAR(255) NOT NULL,
    data_upload     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_avaliacao) REFERENCES avaliacao(id_avaliacao) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacao (
    id_solicitacao      INT PRIMARY KEY AUTO_INCREMENT,
    id_user              INT NOT NULL,
    nome_evento          VARCHAR(100) NOT NULL,
    status_solicitacao   ENUM('pendente', 'aprovado', 'recusado') NOT NULL DEFAULT 'pendente',
    foto                 VARCHAR(255),
    horario_evento       TIME,  
    data_evento          DATE,
    local_evento         VARCHAR(255),
    gratuidade            BOOLEAN NOT NULL DEFAULT FALSE,
    descricao_evento      VARCHAR(1000),
    descricao_artista     VARCHAR(1000),
    data_solicitacao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;