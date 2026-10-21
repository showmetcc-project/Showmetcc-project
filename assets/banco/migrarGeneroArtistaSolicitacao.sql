-- Gênero musical informado no cadastro/moderação.
-- A coluna equivalente em artista já existe no schema base e é preenchida
-- quando a solicitação é aprovada.

ALTER TABLE solicitacao
    ADD COLUMN genero_artista VARCHAR(100) NULL
    AFTER nome_artista_solicitado;
