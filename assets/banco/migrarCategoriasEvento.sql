USE showme;

-- ---------------------------------------------------------------------------
-- Migração das categorias de evento para o padrão oficial do ShowMe.
--
-- Antes: valores livres / antigos gravados em categoria_evento, por exemplo
--        "Música", "Show Nacional", "Show Internacional", "Cinema",
--        "Workshop", "Oficina", "Gastronômico", "Literatura".
-- Depois: apenas as 9 categorias oficiais (definidas em
--         config/categoriasEvento.php), na ordem canônica:
--
--   1. Música e Shows
--   2. Cinema e Audiovisual
--   3. Teatro e Dança
--   4. Artes Visuais e Exposições
--   5. Literatura
--   6. Gastronomia
--   7. Oficinas e Workshops
--   8. Cultura Popular e Regional
--   9. Geek e Cultura Pop
--
-- Observações:
--   * A coluna é um VARCHAR com categorias separadas por vírgula. O script
--     reconstrói o valor a partir das categorias detectadas, então categorias
--     duplicadas (ex.: "Música, Show Nacional") viram um único valor
--     ("Música e Shows").
--   * É idempotente: rodar de novo não altera o resultado.
--   * Linhas sem nenhuma categoria reconhecida são mantidas como estão.
--
-- Execute UMA vez, após importar showme.sql, em bancos com dados antigos.
-- ---------------------------------------------------------------------------

START TRANSACTION;

UPDATE evento
SET categoria_evento = COALESCE(
    NULLIF(
        CONCAT_WS(', ',
            CASE WHEN categoria_evento LIKE '%Música%' OR categoria_evento LIKE '%Musica%'
                      OR categoria_evento LIKE '%Show Nacional%'
                      OR categoria_evento LIKE '%Show Internacional%'
                 THEN 'Música e Shows' END,
            CASE WHEN categoria_evento LIKE '%Cinema%' OR categoria_evento LIKE '%Audiovisual%'
                 THEN 'Cinema e Audiovisual' END,
            CASE WHEN categoria_evento LIKE '%Teatro%' OR categoria_evento LIKE '%Dança%'
                      OR categoria_evento LIKE '%Danca%'
                 THEN 'Teatro e Dança' END,
            CASE WHEN categoria_evento LIKE '%Artes Visuais%' OR categoria_evento LIKE '%Exposi%'
                 THEN 'Artes Visuais e Exposições' END,
            CASE WHEN categoria_evento LIKE '%Literatura%' THEN 'Literatura' END,
            CASE WHEN categoria_evento LIKE '%Gastronom%' THEN 'Gastronomia' END,
            CASE WHEN categoria_evento LIKE '%Workshop%' OR categoria_evento LIKE '%Oficina%'
                 THEN 'Oficinas e Workshops' END,
            CASE WHEN categoria_evento LIKE '%Cultura Popular%' OR categoria_evento LIKE '%Regional%'
                 THEN 'Cultura Popular e Regional' END,
            CASE WHEN categoria_evento LIKE '%Geek%'
                      OR (categoria_evento LIKE '%Cultura Pop%'
                          AND categoria_evento NOT LIKE '%Cultura Popular%')
                 THEN 'Geek e Cultura Pop' END
        ),
        ''
    ),
    categoria_evento
);

UPDATE solicitacao
SET categoria_evento = COALESCE(
    NULLIF(
        CONCAT_WS(', ',
            CASE WHEN categoria_evento LIKE '%Música%' OR categoria_evento LIKE '%Musica%'
                      OR categoria_evento LIKE '%Show Nacional%'
                      OR categoria_evento LIKE '%Show Internacional%'
                 THEN 'Música e Shows' END,
            CASE WHEN categoria_evento LIKE '%Cinema%' OR categoria_evento LIKE '%Audiovisual%'
                 THEN 'Cinema e Audiovisual' END,
            CASE WHEN categoria_evento LIKE '%Teatro%' OR categoria_evento LIKE '%Dança%'
                      OR categoria_evento LIKE '%Danca%'
                 THEN 'Teatro e Dança' END,
            CASE WHEN categoria_evento LIKE '%Artes Visuais%' OR categoria_evento LIKE '%Exposi%'
                 THEN 'Artes Visuais e Exposições' END,
            CASE WHEN categoria_evento LIKE '%Literatura%' THEN 'Literatura' END,
            CASE WHEN categoria_evento LIKE '%Gastronom%' THEN 'Gastronomia' END,
            CASE WHEN categoria_evento LIKE '%Workshop%' OR categoria_evento LIKE '%Oficina%'
                 THEN 'Oficinas e Workshops' END,
            CASE WHEN categoria_evento LIKE '%Cultura Popular%' OR categoria_evento LIKE '%Regional%'
                 THEN 'Cultura Popular e Regional' END,
            CASE WHEN categoria_evento LIKE '%Geek%'
                      OR (categoria_evento LIKE '%Cultura Pop%'
                          AND categoria_evento NOT LIKE '%Cultura Popular%')
                 THEN 'Geek e Cultura Pop' END
        ),
        ''
    ),
    categoria_evento
);

COMMIT;
