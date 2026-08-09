CREATE TABLE usuario (
    id_user INT PRIMARY KEY,
    nome_user VARCHAR(100),
    sobrenome VARCHAR(100),
    email_user VARCHAR(100),
    senha_user VARCHAR(255)
);


CREATE TABLE spotify (
    id_spotify INT PRIMARY KEY,
    id_user INT NOT NULL,
    spotify_id VARCHAR(100),
    artistas_mais_tocados VARCHAR(255),
    generos_preferidos VARCHAR(255),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
);


CREATE TABLE artista (
    id_artista INT PRIMARY KEY,
    nome_artista VARCHAR(150),
    genero_artista VARCHAR(100),
    imagem_artista VARCHAR(255)
);


CREATE TABLE evento (
    id_evento INT PRIMARY KEY,
    num_evento INT,
    nome_evento VARCHAR(100),
    local_evento VARCHAR(100),
    rua_evento VARCHAR(100),
    cidade_evento VARCHAR(100),
    uf CHAR(2),
    descricao_evento VARCHAR(1000),
    data_evento DATE,
    categoria_evento VARCHAR(100),
    link_oficial VARCHAR(255),
    latitude_evento DECIMAL(10,7),
    longitude_evento DECIMAL(10,7)
);


CREATE TABLE artista_evento (
    id_artista INT,
    id_evento INT,

    PRIMARY KEY (id_artista, id_evento),

    FOREIGN KEY (id_artista)
        REFERENCES artista(id_artista),

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
);


CREATE TABLE preferencias (
    id_preferencia INT PRIMARY KEY,
    id_user INT NOT NULL,
    genero_preferido VARCHAR(100),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
);


CREATE TABLE favoritos (
    id_favorito INT PRIMARY KEY,
    id_user INT NOT NULL,
    id_evento INT NOT NULL,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user),

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
);


CREATE TABLE rota (
    id_rota INT PRIMARY KEY,
    id_user INT NOT NULL,
    id_evento INT NOT NULL,
    meio_transporte VARCHAR(30),
    distancia_km DECIMAL(10,2),
    tempo_estimado INT,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user),

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
);


CREATE TABLE avaliacao (
    id_avaliacao INT PRIMARY KEY,
    id_user INT NOT NULL,
    id_evento INT NOT NULL,
    nota INT,
    comentario VARCHAR(1000),
    data_avaliacao DATE,

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user),

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento)
);


CREATE TABLE midia_avaliacao (
    id_midia INT PRIMARY KEY,
    id_avaliacao INT NOT NULL,
    tipo_midia VARCHAR(20),
    url_midia VARCHAR(255),

    FOREIGN KEY (id_avaliacao)
        REFERENCES avaliacao(id_avaliacao)
);


CREATE TABLE solicitacao (
    id_solicitacao INT PRIMARY KEY,
    id_user INT NOT NULL,
    nome_evento VARCHAR(100),
    status VARCHAR(20),
    foto VARCHAR(255),
    horario_evento TIMESTAMP,
    data_evento DATE,
    local_evento VARCHAR(255),
    gratuidade BOOLEAN,
    descricao_evento VARCHAR(1000),
    descricao_artista VARCHAR(1000),

    FOREIGN KEY (id_user)
        REFERENCES usuario(id_user)
);


CREATE TABLE recomendacao (
    id_rec INT PRIMARY KEY,
    descricao_rec VARCHAR(1000)
);


CREATE TABLE evento_recomendacao (
    id_evento INT,
    id_rec INT,

    PRIMARY KEY (id_evento, id_rec),

    FOREIGN KEY (id_evento)
        REFERENCES evento(id_evento),

    FOREIGN KEY (id_rec)
        REFERENCES recomendacao(id_rec)
);