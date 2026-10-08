CREATE DATABASE IF NOT EXISTS showme
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE showme;

CREATE TABLE usuario (
    id_user       INT PRIMARY KEY AUTO_INCREMENT,
    nome_user     VARCHAR(100) NOT NULL,
    sobrenome     VARCHAR(100),
    email_user    VARCHAR(100) NOT NULL,
    senha_user    VARCHAR(255) NULL,

    google_id     VARCHAR(255) NULL,

    foto_perfil   VARCHAR(255) NULL,
    foto_banner   VARCHAR(255) NULL,

    tipo_usuario  ENUM('comum', 'admin') NOT NULL DEFAULT 'comum',

    data_cadastro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_usuario_email (email_user),
    UNIQUE KEY uk_usuario_google (google_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE spotify (
    id_spotify             INT PRIMARY KEY AUTO_INCREMENT,
    id_user                INT NOT NULL,

    spotify_id              VARCHAR(100),

    artistas_mais_tocados   VARCHAR(255),
    generos_preferidos      VARCHAR(255),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE artista (
    id_artista       INT PRIMARY KEY AUTO_INCREMENT,
    nome_artista     VARCHAR(150) NOT NULL,
    genero_artista   VARCHAR(100),
    imagem_artista   VARCHAR(255),

    UNIQUE KEY uk_artista_nome (nome_artista)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE google_calendar_token (
    id_usuario   INT PRIMARY KEY,
    access_token TEXT NOT NULL,
    refresh_token TEXT NOT NULL,
    expira_em    DATETIME NOT NULL,
    data_conexao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_usuario)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE evento (
    id_evento          INT PRIMARY KEY AUTO_INCREMENT,
    id_solicitacao_origem INT NULL UNIQUE,
    num_evento         INT,
    nome_evento        VARCHAR(100) NOT NULL,
    cep_evento         CHAR(8) NOT NULL,
    endereco_evento    VARCHAR(255) NOT NULL,
    numero_endereco    VARCHAR(20),
    rua_evento         VARCHAR(100),
    cidade_evento      VARCHAR(100),
    uf                 CHAR(2),
    descricao_evento   VARCHAR(1000) NOT NULL,
    data_evento        DATE NOT NULL,
    horario_evento     TIME NOT NULL,
    gratuidade         BOOLEAN NOT NULL DEFAULT FALSE,
    valor_ingresso_minimo DECIMAL(10,2),
    valor_ingresso_maximo DECIMAL(10,2),
    categoria_evento   VARCHAR(255) NOT NULL,
    link_oficial       VARCHAR(255) NOT NULL,
    imagem_evento      VARCHAR(255) NOT NULL,
    status_evento      ENUM('ativo', 'cancelado') NOT NULL DEFAULT 'ativo',

    INDEX idx_evento_data (data_evento),
    CONSTRAINT chk_evento_valores_ingresso CHECK (
        (gratuidade = TRUE AND valor_ingresso_minimo IS NULL AND valor_ingresso_maximo IS NULL)
        OR (
            gratuidade = FALSE
            AND valor_ingresso_minimo > 0
            AND (valor_ingresso_maximo IS NULL OR valor_ingresso_maximo >= valor_ingresso_minimo)
        )
    )
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
    origem           VARCHAR(255),
    orcamento_total  DECIMAL(10,2) NOT NULL DEFAULT 0,
    custo_ingresso   DECIMAL(10,2) NOT NULL DEFAULT 0,
    custo_transporte DECIMAL(10,2) NOT NULL DEFAULT 0,
    hospedagem_necessaria BOOLEAN NOT NULL DEFAULT FALSE,
    nome_hospedagem  VARCHAR(100),
    custo_hospedagem DECIMAL(10,2) NOT NULL DEFAULT 0,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
        ON DELETE CASCADE,

    UNIQUE KEY uk_rota (id_user, id_evento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunidade_post (
    id_post       INT PRIMARY KEY AUTO_INCREMENT,
    id_evento     INT NOT NULL,
    id_usuario    INT NOT NULL,
    categoria     ENUM('Duvida', 'Dica', 'Transporte', 'Hospedagem', 'Companhia', 'Relato') NOT NULL,
    texto         TEXT NOT NULL,
    status_post   ENUM('ativo', 'removido') NOT NULL DEFAULT 'ativo',
    data_criacao  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_evento) REFERENCES evento(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_user) ON DELETE CASCADE,
    INDEX idx_comunidade_post_evento_data (id_evento, data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunidade_resposta (
    id_resposta   INT PRIMARY KEY AUTO_INCREMENT,
    id_post       INT NOT NULL,
    id_usuario    INT NOT NULL,
    texto         TEXT NOT NULL,
    data_criacao  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_post) REFERENCES comunidade_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_user) ON DELETE CASCADE,
    INDEX idx_comunidade_resposta_post_data (id_post, data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunidade_curtida (
    id_post       INT NOT NULL,
    id_usuario    INT NOT NULL,

    PRIMARY KEY (id_post, id_usuario),
    FOREIGN KEY (id_post) REFERENCES comunidade_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_user) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunidade_midia (
    id_midia          INT PRIMARY KEY AUTO_INCREMENT,
    id_evento         INT NOT NULL,
    id_usuario        INT NOT NULL,
    caminho_arquivo   VARCHAR(255) NOT NULL,
    legenda           VARCHAR(255) NULL,
    permitir_download TINYINT(1) NOT NULL DEFAULT 0,
    status_midia      ENUM('ativo','removido') NOT NULL DEFAULT 'ativo',
    data_criacao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_evento) REFERENCES evento(id_evento) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_user) ON DELETE CASCADE,
    INDEX idx_comunidade_midia_evento_data (id_evento, data_criacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE comunidade_denuncia (
    id_denuncia   INT PRIMARY KEY AUTO_INCREMENT,
    id_post       INT NULL,
    id_midia      INT NULL,
    id_usuario    INT NOT NULL,
    motivo        VARCHAR(100) NOT NULL,
    status_denuncia ENUM('pendente', 'mantido', 'removido') NOT NULL DEFAULT 'pendente',
    data_criacao  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    data_moderacao DATETIME NULL,
    id_admin_moderacao INT NULL,
    UNIQUE KEY uk_denuncia_post_usuario (id_post, id_usuario),
    UNIQUE KEY uk_denuncia_midia_usuario (id_midia, id_usuario),

    FOREIGN KEY (id_post) REFERENCES comunidade_post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_midia) REFERENCES comunidade_midia(id_midia) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES usuario(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_admin_moderacao) REFERENCES usuario(id_user) ON DELETE SET NULL,
    CONSTRAINT chk_comunidade_denuncia_alvo CHECK (
        (id_post IS NOT NULL AND id_midia IS NULL)
        OR (id_post IS NULL AND id_midia IS NOT NULL)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE solicitacao (
    id_solicitacao      INT PRIMARY KEY AUTO_INCREMENT,
    id_user              INT NOT NULL,
    nome_evento          VARCHAR(100) NOT NULL,
    status_solicitacao   ENUM('pendente', 'aprovado', 'recusado') NOT NULL DEFAULT 'pendente',
    foto                 VARCHAR(255) NOT NULL,
    horario_evento       TIME NOT NULL,
    data_evento          DATE NOT NULL,
    cep_evento           CHAR(8) NOT NULL,
    endereco_evento      VARCHAR(255) NOT NULL,
    numero_endereco      VARCHAR(20),
    rua_evento           VARCHAR(100),
    cidade_evento        VARCHAR(100) NOT NULL,
    uf                   CHAR(2) NOT NULL,
    categoria_evento     VARCHAR(255) NOT NULL,
    link_oficial         VARCHAR(255) NOT NULL,
    gratuidade            BOOLEAN NOT NULL DEFAULT FALSE,
    valor_ingresso_minimo DECIMAL(10,2),
    valor_ingresso_maximo DECIMAL(10,2),
    descricao_evento      VARCHAR(1000) NOT NULL,
    descricao_artista     VARCHAR(1000) NOT NULL,
    nome_artista_solicitado VARCHAR(150) NOT NULL,
    data_solicitacao      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
        ON DELETE CASCADE,

    CONSTRAINT chk_solicitacao_valores_ingresso CHECK (
        (gratuidade = TRUE AND valor_ingresso_minimo IS NULL AND valor_ingresso_maximo IS NULL)
        OR (
            gratuidade = FALSE
            AND valor_ingresso_minimo > 0
            AND (valor_ingresso_maximo IS NULL OR valor_ingresso_maximo >= valor_ingresso_minimo)
        )
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evento
    ADD CONSTRAINT fk_evento_solicitacao
    FOREIGN KEY (id_solicitacao_origem)
        REFERENCES solicitacao(id_solicitacao)
        ON DELETE SET NULL;
