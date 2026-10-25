-- Migração para bases ShowMe já existentes.
-- Execute este arquivo uma vez para remover recursos órfãos do modelo antigo.
USE showme;

-- A tabela nunca foi lida ou gravada pela aplicação e não possui dependentes.
DROP TABLE IF EXISTS preferencias;

-- `num_evento` não é FK nem participa do vínculo solicitação→evento.
-- O vínculo atual usa evento.id_solicitacao_origem com FK para solicitacao.
ALTER TABLE evento
    DROP COLUMN num_evento;

-- A imagem de participante não possui fluxo de upload nem uso na interface.
ALTER TABLE artista
    DROP COLUMN imagem_artista;
