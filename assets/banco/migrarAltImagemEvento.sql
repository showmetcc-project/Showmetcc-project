USE showme;

-- ---------------------------------------------------------------------------
-- Migração: adiciona o texto alternativo (alt) das imagens dos eventos.
--
-- O campo é opcional. Quando vazio, a interface gera automaticamente:
--   "Imagem de divulgação para o evento [Nome do Evento] - [Cidade]"
--
-- Execute UMA vez, após importar showme.sql.
-- ---------------------------------------------------------------------------

ALTER TABLE evento
    ADD COLUMN alt_imagem VARCHAR(255) NULL AFTER imagem_evento;

ALTER TABLE solicitacao
    ADD COLUMN alt_imagem VARCHAR(255) NULL AFTER foto;
