-- Remove o campo de gastos extras, que não é mais usado pelo planejamento.
-- O total de investimento passa a considerar apenas ingresso, transporte e hospedagem.

ALTER TABLE rota
    DROP COLUMN IF EXISTS custo_outros;
