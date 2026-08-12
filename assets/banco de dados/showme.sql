-- Gera��o de Modelo f�sico
-- Sql ANSI 2003 - brModelo.


CREATE DATABASE IF NOT EXISTS showme;
use showme;

CREATE TABLE usuario (
nome_user varchar(100),
sobrenome varchar(100),
spotify_id int,
email_user varchar(50),
apelido_user varchar(50),
senha_user varchar(50),
id_user int AUTO_INCREMENT PRIMARY KEY,
datanasc_user date,
localizacao_user varchar(255)
);

CREATE TABLE evento (
id_evento int not null AUTO_INCREMENT PRIMARY KEY,
num_evento int(100),
cidade_evento varchar(100),
rua_evento varchar(100),
nome_evento varchar(100),
local_evento varchar(100),
descricao_evento Text,
latitude_evento decimal(10,8),
data_evento date,
longitude_evento decimal(10,8),
categoria_evento varchar(100),
uf char(2),
link_ofcial varchar(255)
);

CREATE TABLE artista (
id_artista int AUTO_INCREMENT PRIMARY KEY,
genero_artista varchar(50),
nome_artista varchar(100),
id_user int,
FOREIGN KEY(id_user) REFERENCES usuario (id_user)
);

CREATE TABLE rota (
id_rota int AUTO_INCREMENT PRIMARY KEY,
meio_transporte varchar(30),
tempo_estimado datetime,
distancia_km decimal(10,4),
id_evento int,
FOREIGN KEY(id_evento) REFERENCES evento (id_evento)
);

CREATE TABLE clima (
id_clima int AUTO_INCREMENT PRIMARY KEY,
condicao_clima varchar(255),
temp_clima decimal(10,2)
);

CREATE TABLE recomendacao (
id_rec int AUTO_INCREMENT PRIMARY KEY,
descricao_rec Text
);

CREATE TABLE video (
id_video int AUTO_INCREMENT PRIMARY KEY,
status varchar(10),
titulo_video varchar(255),
descricao_video text,
url_video varchar(255)
);

CREATE TABLE user_rota (
id_user  int,
id_rota  int,
FOREIGN KEY(id_user) REFERENCES usuario (id_user),
FOREIGN KEY(id_rota) REFERENCES rota (id_rota)
);

CREATE TABLE evento_rec (
id_rec  int,
id_evento int,
FOREIGN KEY(id_rec) REFERENCES recomendacao (id_rec),
FOREIGN KEY(id_evento) REFERENCES evento (id_evento)
);

CREATE TABLE clima_evento (
id_evento int,
id_clima int,
FOREIGN KEY(id_evento) REFERENCES evento (id_evento),
FOREIGN KEY(id_clima) REFERENCES clima (id_clima)
);

CREATE TABLE user_video (
id_video  int,
id_user int,
FOREIGN KEY(id_video) REFERENCES video (id_video),
FOREIGN KEY(id_user) REFERENCES usuario (id_user)
);

CREATE TABLE artista_evento (
id_evento  int,
id_artista  int,
FOREIGN KEY(id_evento) REFERENCES evento (id_evento),
FOREIGN KEY(id_artista) REFERENCES artista (id_artista)
)

