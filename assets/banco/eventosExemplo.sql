USE showme;

-- Carga opcional para desenvolvimento. Execute uma única vez, depois de importar showme.sql.
START TRANSACTION;

INSERT INTO evento (
    nome_evento, cep_evento, endereco_evento, numero_endereco, rua_evento,
    cidade_evento, uf, descricao_evento, data_evento, horario_evento,
    gratuidade, valor_ingresso_minimo, valor_ingresso_maximo, categoria_evento,
    link_oficial, imagem_evento, status_evento
) VALUES
(
    'Festival Sons da Cidade', '05001000',
    'Avenida Francisco Matarazzo, Água Branca, São Paulo - SP, CEP 05001-000', '1705',
    'Avenida Francisco Matarazzo', 'São Paulo', 'SP',
    'Festival com artistas brasileiros, música ao vivo e experiências culturais.',
    '2026-11-14', '18:00:00', FALSE, 70.00, 180.00,
    'Música, Show Nacional', 'https://example.com/festival-sons-da-cidade',
    'assets/img/prontos/banner/grandes-palcos-01.webp', 'ativo'
),
(
    'Noite Latina Global', '20031050',
    'Avenida Infante Dom Henrique, Glória, Rio de Janeiro - RJ, CEP 20031-050', NULL,
    'Avenida Infante Dom Henrique', 'Rio de Janeiro', 'RJ',
    'Show internacional com ritmos latinos, dança e atrações convidadas.',
    '2026-12-05', '20:30:00', FALSE, 120.00, 350.00,
    'Música, Show Internacional', 'https://example.com/noite-latina-global',
    'assets/img/prontos/banner/grandes-palcos-02.webp', 'ativo'
),
(
    'Mostra de Cinema Brasileiro', '30130010',
    'Avenida Afonso Pena, Centro, Belo Horizonte - MG, CEP 30130-010', '1537',
    'Avenida Afonso Pena', 'Belo Horizonte', 'MG',
    'Exibições de produções nacionais acompanhadas de conversas sobre criação audiovisual.',
    '2026-11-21', '16:00:00', TRUE, NULL, NULL,
    'Cinema, Workshop', 'https://example.com/mostra-cinema-brasileiro',
    'assets/img/prontos/banner/luzes-noite-01.webp', 'ativo'
),
(
    'Workshop de Produção Musical', '80010000',
    'Rua XV de Novembro, Centro, Curitiba - PR, CEP 80010-000', '1299',
    'Rua XV de Novembro', 'Curitiba', 'PR',
    'Formação prática sobre arranjos, gravação, mixagem e produção independente.',
    '2027-01-16', '09:00:00', FALSE, 90.00, 150.00,
    'Workshop, Música', 'https://example.com/workshop-producao-musical',
    'assets/img/prontos/banner/luzes-noite-02.webp', 'ativo'
),
(
    'Oficina de Fotografia de Palco', '50010000',
    'Praça do Marco Zero, Recife Antigo, Recife - PE, CEP 50010-000', NULL,
    'Praça do Marco Zero', 'Recife', 'PE',
    'Atividade gratuita sobre enquadramento, luz e registro de apresentações culturais.',
    '2027-02-06', '14:00:00', TRUE, NULL, NULL,
    'Oficina, Workshop', 'https://example.com/oficina-fotografia-palco',
    'assets/img/prontos/banner/luzes-noite-03.webp', 'ativo'
),
(
    'Festival Sabores do Brasil', '40020000',
    'Praça da Sé, Centro Histórico, Salvador - BA, CEP 40020-000', '1',
    'Praça da Sé', 'Salvador', 'BA',
    'Experiência gastronômica com cozinhas regionais e oficinas de preparo.',
    '2027-02-20', '11:00:00', FALSE, 35.00, 90.00,
    'Gastronômico, Oficina', 'https://example.com/festival-sabores-brasil',
    'assets/img/prontos/banner/multidoes-01.webp', 'ativo'
),
(
    'Cinema e Gastronomia ao Ar Livre', '90010150',
    'Avenida Borges de Medeiros, Centro Histórico, Porto Alegre - RS, CEP 90010-150', NULL,
    'Avenida Borges de Medeiros', 'Porto Alegre', 'RS',
    'Sessão de cinema ao ar livre com feira de produtores gastronômicos locais.',
    '2027-03-13', '18:30:00', TRUE, NULL, NULL,
    'Cinema, Gastronômico', 'https://example.com/cinema-gastronomia',
    'assets/img/prontos/banner/multidoes-02.webp', 'ativo'
),
(
    'Vozes do Mundo', '60165011',
    'Avenida Alberto Craveiro, Castelão, Fortaleza - CE, CEP 60165-011', '2901',
    'Avenida Alberto Craveiro', 'Fortaleza', 'CE',
    'Encontro musical com artistas internacionais e convidados brasileiros.',
    '2027-03-27', '19:30:00', FALSE, 160.00, 480.00,
    'Música, Show Internacional', 'https://example.com/vozes-do-mundo',
    'assets/img/prontos/banner/multidoes-03.webp', 'ativo'
),
(
    'Encontro Novos Talentos', '13010001',
    'Praça Carlos Gomes, Centro, Campinas - SP, CEP 13010-001', NULL,
    'Praça Carlos Gomes', 'Campinas', 'SP',
    'Apresentações gratuitas e oficinas conduzidas por novos artistas da cena nacional.',
    '2027-04-10', '15:00:00', TRUE, NULL, NULL,
    'Música, Show Nacional, Oficina', 'https://example.com/encontro-novos-talentos',
    'assets/img/prontos/banner/grandes-palcos-03.webp', 'ativo'
);

INSERT IGNORE INTO artista (nome_artista, genero_artista) VALUES
    ('Coletivo Sons Urbanos', 'Música brasileira'),
    ('Orquesta Horizonte', 'Música latina'),
    ('Luna Rivera', 'Pop latino'),
    ('Vozes do Atlântico', 'World music'),
    ('Coletivo Novos Talentos', 'Música independente');

INSERT IGNORE INTO artista_evento (id_artista, id_evento)
SELECT a.id_artista, e.id_evento
FROM artista a
INNER JOIN evento e ON e.nome_evento = 'Festival Sons da Cidade'
WHERE a.nome_artista = 'Coletivo Sons Urbanos';

INSERT IGNORE INTO artista_evento (id_artista, id_evento)
SELECT a.id_artista, e.id_evento
FROM artista a
INNER JOIN evento e ON e.nome_evento = 'Noite Latina Global'
WHERE a.nome_artista IN ('Orquesta Horizonte', 'Luna Rivera');

INSERT IGNORE INTO artista_evento (id_artista, id_evento)
SELECT a.id_artista, e.id_evento
FROM artista a
INNER JOIN evento e ON e.nome_evento = 'Vozes do Mundo'
WHERE a.nome_artista = 'Vozes do Atlântico';

INSERT IGNORE INTO artista_evento (id_artista, id_evento)
SELECT a.id_artista, e.id_evento
FROM artista a
INNER JOIN evento e ON e.nome_evento = 'Encontro Novos Talentos'
WHERE a.nome_artista = 'Coletivo Novos Talentos';

COMMIT;
