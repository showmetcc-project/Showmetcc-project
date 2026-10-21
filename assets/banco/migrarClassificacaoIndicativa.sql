USE showme;

-- ---------------------------------------------------------------------------
-- Migração: adiciona o campo de Classificação Indicativa.
--
-- Opções oficiais (config/classificacoesIndicativas.php):
--   Livre, 6 anos, 10 anos, 12 anos, 14 anos, 16 anos, 18 anos.
--
-- A coluna já entra com DEFAULT 'Livre', então os registros existentes são
-- preenchidos automaticamente. Execute UMA vez, após importar showme.sql.
--
-- Observação: a tabela de solicitações chama-se `solicitacao`.
-- ---------------------------------------------------------------------------

ALTER TABLE evento
    ADD COLUMN classificacao_indicativa VARCHAR(20) NOT NULL DEFAULT 'Livre' AFTER categoria_evento;

ALTER TABLE solicitacao
    ADD COLUMN classificacao_indicativa VARCHAR(20) NOT NULL DEFAULT 'Livre' AFTER categoria_evento;
