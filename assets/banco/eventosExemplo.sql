USE showme;

START TRANSACTION;

-- Limpa eventos de teste anteriores se quiser recarregar do zero (OPCIONAL):
-- DELETE FROM evento;

INSERT INTO evento (
    id_evento, nome_evento, cep_evento, endereco_evento, numero_endereco, rua_evento,
    cidade_evento, uf, descricao_evento, data_evento, horario_evento,
    gratuidade, valor_ingresso_minimo, valor_ingresso_maximo, categoria_evento,
    classificacao_indicativa, link_oficial, imagem_evento, status_evento
) VALUES
-- ============================================================================
-- 1. MÚSICA E SHOWS (5 Eventos)
-- ============================================================================
(
    1, 'Festival Sons da Cidade', '05001000',
    'Avenida Francisco Matarazzo, Água Branca, São Paulo - SP', '1705',
    'Avenida Francisco Matarazzo', 'São Paulo', 'SP',
    'Festival com artistas brasileiros, música ao vivo e experiências culturais.',
    '2026-11-14', '18:00:00', FALSE, 70.00, 180.00,
    'Música e Shows', 'Livre', 'https://example.com/festival-sons-da-cidade',
    'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    2, 'Noite Latina Global', '20031050',
    'Avenida Infante Dom Henrique, Glória, Rio de Janeiro - RJ', NULL,
    'Avenida Infante Dom Henrique', 'Rio de Janeiro', 'RJ',
    'Show internacional com ritmos latinos, dança e atrações convidadas.',
    '2026-12-05', '20:30:00', FALSE, 120.00, 350.00,
    'Música e Shows', '16 anos', 'https://example.com/noite-latina-global',
    'https://images.unsplash.com/photo-1470225620780-dba8ba36b745?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    8, 'Vozes do Mundo', '60165011',
    'Avenida Alberto Craveiro, Castelão, Fortaleza - CE', '2901',
    'Avenida Alberto Craveiro', 'Fortaleza', 'CE',
    'Encontro musical com artistas internacionais e convidados brasileiros.',
    '2027-03-27', '19:30:00', FALSE, 160.00, 480.00,
    'Música e Shows', '16 anos', 'https://example.com/vozes-do-mundo',
    'https://images.unsplash.com/photo-1501386761578-eac5c94b800a?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    9, 'Encontro Novos Talentos', '13010001',
    'Praça Carlos Gomes, Centro, Campinas - SP', NULL,
    'Praça Carlos Gomes', 'Campinas', 'SP',
    'Apresentações gratuitas e oficinas conduzidas por novos artistas da cena nacional.',
    '2027-04-10', '15:00:00', TRUE, NULL, NULL,
    'Música e Shows', 'Livre', 'https://example.com/encontro-novos-talentos',
    'https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    10, 'Jazz na Praça Sunset', '01310100',
    'Avenida Paulista, Bela Vista, São Paulo - SP', '1500',
    'Avenida Paulista', 'São Paulo', 'SP',
    'Apresentação ao vivo de quartetos de jazz ao pôr do sol no coração da cidade.',
    '2027-04-18', '17:30:00', TRUE, NULL, NULL,
    'Música e Shows', 'Livre', 'https://example.com/jazz-praca',
    'https://images.unsplash.com/photo-1511192336575-5a79af67a629?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 2. CINEMA E AUDIOVISUAL (5 Eventos)
-- ============================================================================
(
    3, 'Mostra de Cinema Brasileiro', '30130010',
    'Avenida Afonso Pena, Centro, Belo Horizonte - MG', '1537',
    'Avenida Afonso Pena', 'Belo Horizonte', 'MG',
    'Exibições de produções nacionais acompanhadas de conversas sobre criação audiovisual.',
    '2026-11-21', '16:00:00', TRUE, NULL, NULL,
    'Cinema e Audiovisual', '12 anos', 'https://example.com/mostra-cinema-brasileiro',
    'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    7, 'Cinema ao Ar Livre', '90010150',
    'Avenida Borges de Medeiros, Centro Histórico, Porto Alegre - RS', NULL,
    'Avenida Borges de Medeiros', 'Porto Alegre', 'RS',
    'Sessão de cinema ao ar livre com clássicos do cinema sul-americano.',
    '2027-03-13', '18:30:00', TRUE, NULL, NULL,
    'Cinema e Audiovisual', '14 anos', 'https://example.com/cinema-gastronomia',
    'https://images.unsplash.com/photo-1517604931442-7e0c8ed2963c?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    11, 'Cine Debate Contemporâneo', '01310100',
    'Avenida Paulista, Bela Vista, São Paulo - SP', '1000',
    'Avenida Paulista', 'São Paulo', 'SP',
    'Exibição de longas-metragens independentes seguida de debate com diretores.',
    '2027-04-19', '17:00:00', TRUE, NULL, NULL,
    'Cinema e Audiovisual', '12 anos', 'https://example.com/cine-debate',
    'https://images.unsplash.com/photo-1478720568477-152d9b164e26?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    12, 'Festival de Curtas Independentes', '20040002',
    'Rua do Ouvidor, Centro, Rio de Janeiro - RJ', '50',
    'Rua do Ouvidor', 'Rio de Janeiro', 'RJ',
    'Mostra competitiva de curtas-metragens de todo o território nacional.',
    '2027-05-02', '19:00:00', FALSE, 20.00, 40.00,
    'Cinema e Audiovisual', 'Livre', 'https://example.com/festival-curtas',
    'https://images.unsplash.com/photo-1536440136628-849c177e76a1?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    13, 'Noite do Documentário Nacional', '70040010',
    'Esplanada dos Ministérios, Brasília - DF', 'SF',
    'Via N1 Leste', 'Brasília', 'DF',
    'Exibição especial de documentários premiados sobre a cultura brasileira.',
    '2027-05-15', '20:00:00', TRUE, NULL, NULL,
    'Cinema e Audiovisual', 'Livre', 'https://example.com/noite-documentario',
    'https://images.unsplash.com/photo-1524985069026-dd778a71c7b4?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 3. TEATRO E DANÇA (5 Eventos)
-- ============================================================================
(
    14, 'Espetáculo: O Auto da Barca', '01310200',
    'Avenida Paulista, Bela Vista, São Paulo - SP', '900',
    'Avenida Paulista', 'São Paulo', 'SP',
    'Adaptação contemporânea do clássico teatral com elementos de dança moderna.',
    '2027-04-12', '20:00:00', FALSE, 30.00, 80.00,
    'Teatro e Dança', '12 anos', 'https://example.com/auto-da-barca',
    'https://images.unsplash.com/photo-1507676184212-d03ab07a01bf?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    15, 'Festival de Dança Contemporânea', '20021130',
    'Praça Tiradentes, Centro, Rio de Janeiro - RJ', '75',
    'Praça Tiradentes', 'Rio de Janeiro', 'RJ',
    'Apresentações de companhias de dança de todo o país.',
    '2027-04-25', '19:30:00', FALSE, 40.00, 100.00,
    'Teatro e Dança', 'Livre', 'https://example.com/festival-danca',
    'https://images.unsplash.com/photo-1508700115892-45ecd05ae2ad?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    16, 'Mostra de Monólogos Nacionais', '30140071',
    'Rua da Bahia, Lourdes, Belo Horizonte - MG', '1600',
    'Rua da Bahia', 'Belo Horizonte', 'MG',
    'Atores renomados apresentam performances intimistas de solo teatral.',
    '2027-05-10', '21:00:00', TRUE, NULL, NULL,
    'Teatro e Dança', '14 anos', 'https://example.com/mostra-monologos',
    'https://images.unsplash.com/photo-1460723237483-7a6dc9d0b212?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    17, 'Noite de Balé Clássico', '80020320',
    'Rua XV de Novembro, Centro, Curitiba - PR', '550',
    'Rua XV de Novembro', 'Curitiba', 'PR',
    'Apresentação especial do Lago dos Cisnes com orquestra ao vivo.',
    '2027-05-28', '20:00:00', FALSE, 60.00, 150.00,
    'Teatro e Dança', 'Livre', 'https://example.com/bale-classico',
    'https://images.unsplash.com/photo-1518834107812-67b0b7c58434?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    18, 'Encontro de Danças Urbanas', '50030230',
    'Rua do Imperador, Santo Antônio, Recife - PE', '200',
    'Rua do Imperador', 'Recife', 'PE',
    'Batalhas de breakdance, hip hop e oficinas abertas ao público.',
    '2027-06-15', '17:00:00', TRUE, NULL, NULL,
    'Teatro e Dança', 'Livre', 'https://example.com/dancas-urbanas',
    'https://images.unsplash.com/photo-1535525153412-5a42439e210d?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 4. ARTES VISUAIS E EXPOSIÇÕES (5 Eventos)
-- ============================================================================
(
    19, 'Exposição: Cores do Brasil', '01014001',
    'Praça da Sé, Centro, São Paulo - SP', '10',
    'Praça da Sé', 'São Paulo', 'SP',
    'Mostra de pinturas e esculturas modernistas de artistas brasileiros.',
    '2027-04-14', '10:00:00', TRUE, NULL, NULL,
    'Artes Visuais e Exposições', 'Livre', 'https://example.com/cores-do-brasil',
    'https://images.unsplash.com/photo-1579783900882-c0d3dad7b119?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    20, 'Galeria Aberta: Fotografia Urbana', '20040020',
    'Avenida Rio Branco, Centro, Rio de Janeiro - RJ', '200',
    'Avenida Rio Branco', 'Rio de Janeiro', 'RJ',
    'Exposição fotográfica retratando o cotidiano das metrópoles.',
    '2027-04-30', '09:00:00', TRUE, NULL, NULL,
    'Artes Visuais e Exposições', 'Livre', 'https://example.com/foto-urbana',
    'https://images.unsplash.com/photo-1554188248-986adbb73be4?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    21, 'Salão de Arte Contemporânea', '30110010',
    'Avenida Afonso Pena, Centro, Belo Horizonte - MG', '1000',
    'Avenida Afonso Pena', 'Belo Horizonte', 'MG',
    'Instalações artísticas interativas e novas mídias visuais.',
    '2027-05-12', '14:00:00', FALSE, 25.00, 50.00,
    'Artes Visuais e Exposições', 'Livre', 'https://example.com/arte-contemporanea',
    'https://images.unsplash.com/photo-1541701494587-cb58502866ab?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    22, 'Exposição de Esculturas em Bronze', '70070100',
    'Esplanada dos Ministérios, Brasília - DF', '1',
    'Esplanada', 'Brasília', 'DF',
    'Obras de grandes escultores sul-americanos em praça pública.',
    '2027-06-01', '08:00:00', TRUE, NULL, NULL,
    'Artes Visuais e Exposições', 'Livre', 'https://example.com/esculturas-bronze',
    'https://images.unsplash.com/photo-1561839561-b13bcfe95249?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    23, 'Mostra de Ilustração e HQ', '90010240',
    'Rua dos Andradas, Centro Histórico, Porto Alegre - RS', '1001',
    'Rua dos Andradas', 'Porto Alegre', 'RS',
    'Exposição de quadrinhos independentes e artes gráficas originais.',
    '2027-06-20', '11:00:00', TRUE, NULL, NULL,
    'Artes Visuais e Exposições', 'Livre', 'https://example.com/hq-ilustracao',
    'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 5. LITERATURA (5 Eventos)
-- ============================================================================
(
    24, 'Feira do Livro Independente', '01228000',
    'Praça Marechal Deodoro, Higienópolis, São Paulo - SP', '100',
    'Praça Marechal Deodoro', 'São Paulo', 'SP',
    'Encontro com escritores independentes, lançamentos de obras e debates.',
    '2027-04-16', '10:00:00', TRUE, NULL, NULL,
    'Literatura', 'Livre', 'https://example.com/feira-livro-independente',
    'https://images.unsplash.com/photo-1495440153380-375a5ffdd1ac?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    25, 'Noite de Poesia Marginal', '20230060',
    'Lapa, Rio de Janeiro - RJ', '50',
    'Avenida Mem de Sá', 'Rio de Janeiro', 'RJ',
    'Microfone aberto para leitura de poesias e saraus literários.',
    '2027-05-04', '19:00:00', TRUE, NULL, NULL,
    'Literatura', '14 anos', 'https://example.com/poesia-marginal',
    'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    26, 'Encontro de Autores de Ficção', '30190050',
    'Avenida Contorno, Savassi, Belo Horizonte - MG', '6000',
    'Avenida Contorno', 'Belo Horizonte', 'MG',
    'Painéis de discussão sobre literatura fantástica e ficção científica nacional.',
    '2027-05-22', '15:00:00', FALSE, 20.00, 45.00,
    'Literatura', 'Livre', 'https://example.com/autores-ficcao',
    'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    27, 'Clube de Leitura ao Ar Livre', '80010100',
    'Passeio Público, Centro, Curitiba - PR', '1',
    'Rua Carlos Cavalcanti', 'Curitiba', 'PR',
    'Discussão aberta sobre clássicos da literatura brasileira.',
    '2027-06-08', '14:30:00', TRUE, NULL, NULL,
    'Literatura', 'Livre', 'https://example.com/clube-leitura',
    'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    28, 'Festival de Poesia e Música', '60115220',
    'Avenida Beira Mar, Meireles, Fortaleza - CE', '2000',
    'Avenida Beira Mar', 'Fortaleza', 'CE',
    'Declamação de poemas acompanhada de arranjos acústicos.',
    '2027-06-25', '18:00:00', TRUE, NULL, NULL,
    'Literatura', 'Livre', 'https://example.com/poesia-musica',
    'https://images.unsplash.com/photo-1474932430478-367dbb6832c1?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 6. GASTRONOMIA (5 Eventos)
-- ============================================================================
(
    6, 'Festival Sabores do Brasil', '40020000',
    'Praça da Sé, Centro Histórico, Salvador - BA', '1',
    'Praça da Sé', 'Salvador', 'BA',
    'Experiência gastronômica com cozinhas regionais e oficinas de preparo.',
    '2027-02-20', '11:00:00', FALSE, 35.00, 90.00,
    'Gastronomia', 'Livre', 'https://example.com/festival-sabores-brasil',
    'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    29, 'Rota do Vinho e Queijos', '95700000',
    'Avenida Planalto, Centro, Gramado - RS', '400',
    'Avenida Planalto', 'Gramado', 'RS',
    'Degustação guiada de vinhos nacionais e queijos artesanais da serra.',
    '2027-05-20', '18:00:00', FALSE, 150.00, 300.00,
    'Gastronomia', '18 anos', 'https://example.com/rota-vinho-queijos',
    'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    30, 'Feira de Comida de Rua', '60060390',
    'Avenida Beira Mar, Mucuripe, Fortaleza - CE', '3000',
    'Avenida Beira Mar', 'Fortaleza', 'CE',
    'Encontro de food trucks e barracas com o melhor da culinária de rua nordestina.',
    '2027-06-05', '16:00:00', TRUE, NULL, NULL,
    'Gastronomia', 'Livre', 'https://example.com/feira-comida-rua',
    'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    31, 'Encontro de Chefs Regionais', '88015420',
    'Avenida Rio Branco, Centro, Florianópolis - SC', '150',
    'Avenida Rio Branco', 'Florianópolis', 'SC',
    'Jantares harmonizados e aulas show com renomados chefs da região sul.',
    '2027-06-18', '20:00:00', FALSE, 90.00, 220.00,
    'Gastronomia', 'Livre', 'https://example.com/chefs-regionais',
    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    32, 'Mercado de Orgânicos e Artesanais', '01311000',
    'Avenida Paulista, São Paulo - SP', '2000',
    'Avenida Paulista', 'São Paulo', 'SP',
    'Feira artesanal de produtores locais com workshops de alimentação saudável.',
    '2027-07-02', '09:00:00', TRUE, NULL, NULL,
    'Gastronomia', 'Livre', 'https://example.com/mercado-organicos',
    'https://images.unsplash.com/photo-1488459716781-31db52582fe9?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 7. OFICINAS E WORKSHOPS (5 Eventos)
-- ============================================================================
(
    4, 'Workshop de Produção Musical', '80010000',
    'Rua XV de Novembro, Centro, Curitiba - PR', '1299',
    'Rua XV de Novembro', 'Curitiba', 'PR',
    'Formação prática sobre arranjos, gravação, mixagem e produção independente.',
    '2027-01-16', '09:00:00', FALSE, 90.00, 150.00,
    'Oficinas e Workshops', '14 anos', 'https://example.com/workshop-producao-musical',
    'https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    5, 'Oficina de Fotografia de Palco', '50010000',
    'Praça do Marco Zero, Recife Antigo, Recife - PE', NULL,
    'Praça do Marco Zero', 'Recife', 'PE',
    'Atividade gratuita sobre enquadramento, luz e registro de apresentações culturais.',
    '2027-02-06', '14:00:00', TRUE, NULL, NULL,
    'Oficinas e Workshops', 'Livre', 'https://example.com/oficina-fotografia-palco',
    'https://images.unsplash.com/photo-1452587925148-ce544e77e70d?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    33, 'Workshop de Escultura em Cerâmica', '01210000',
    'Rua da Consolação, São Paulo - SP', '500',
    'Rua da Consolação', 'São Paulo', 'SP',
    'Aprenda técnicas básicas de modelagem em argila e esmaltação.',
    '2027-05-18', '14:00:00', FALSE, 50.00, 100.00,
    'Oficinas e Workshops', 'Livre', 'https://example.com/workshop-ceramica',
    'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    34, 'Oficina de Escrita Criativa', '20031000',
    'Cinelândia, Centro, Rio de Janeiro - RJ', '10',
    'Praça Floriano', 'Rio de Janeiro', 'RJ',
    'Exercícios práticos para desenvolvimento de narrativa e criação de personagens.',
    '2027-06-02', '10:00:00', TRUE, NULL, NULL,
    'Oficinas e Workshops', 'Livre', 'https://example.com/oficina-escrita',
    'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    35, 'Workshop de Pintura em Aquarela', '30140000',
    'Praça da Liberdade, Belo Horizonte - MG', '100',
    'Praça da Liberdade', 'Belo Horizonte', 'MG',
    'Técnicas de transparência, misturas de cores e pintura ao ar livre.',
    '2027-06-12', '09:30:00', FALSE, 40.00, 80.00,
    'Oficinas e Workshops', 'Livre', 'https://example.com/workshop-aquarela',
    'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 8. TRADIÇÕES REGIONAIS (5 Eventos)
-- ============================================================================
(
    36, 'Festa do Bumba Meu Boi', '65010000',
    'Praça Dom Pedro II, Centro, São Luís - MA', '1',
    'Praça Dom Pedro II', 'São Luís', 'MA',
    'Apresentação tradicional com bumba meu boi, tambor de crioula e culinária típica.',
    '2027-06-28', '17:00:00', TRUE, NULL, NULL,
    'Tradições Regionais', 'Livre', 'https://example.com/bumba-meu-boi',
    'https://images.unsplash.com/photo-1533105079780-92b9be482077?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    37, 'Encontro de Folia de Reis', '37500000',
    'Praça da Matriz, Itajubá - MG', '10',
    'Praça da Matriz', 'Itajubá', 'MG',
    'Cortejo tradicional de companhias de Folia de Reis com cantos e violas.',
    '2027-07-05', '09:00:00', TRUE, NULL, NULL,
    'Tradições Regionais', 'Livre', 'https://example.com/folia-de-reis',
    'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    38, 'Festival de Capoeira e Jongo', '24120000',
    'Praça Principal, Paraty - RJ', '15',
    'Rua do Comércio', 'Paraty', 'RJ',
    'Rodas de capoeira angola e apresentações de jongo patrimônio cultural.',
    '2027-07-18', '15:00:00', TRUE, NULL, NULL,
    'Tradições Regionais', 'Livre', 'https://example.com/capoeira-jongo',
    'https://images.unsplash.com/photo-1547153760-18fc86324498?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    39, 'Mostra de Artesanato Nordestino', '58400000',
    'Parque do Povo, Campina Grande - PB', '500',
    'Avenida Brasília', 'Campina Grande', 'PB',
    'Exposição de peças em barro, couro, renda de bilro e xilogravura.',
    '2027-08-02', '10:00:00', TRUE, NULL, NULL,
    'Tradições Regionais', 'Livre', 'https://example.com/artesanato-nordestino',
    'https://images.unsplash.com/photo-1567696911980-2eed69a46042?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    40, 'Celebração da Cultura Gaúcha', '90010300',
    'Parque Farroupilha, Porto Alegre - RS', '100',
    'Avenida João Pessoa', 'Porto Alegre', 'RS',
    'Rodas de chimarrão, danças tradicionais gaúchas e apresentações de gaita.',
    '2027-09-20', '10:00:00', TRUE, NULL, NULL,
    'Tradições Regionais', 'Livre', 'https://example.com/cultura-gaucha',
    'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=800&auto=format&fit=crop&q=60', 'ativo'
),

-- ============================================================================
-- 9. GEEK E CULTURA POP (5 Eventos)
-- ============================================================================
(
    41, 'ShowMe Geek Fest', '04101300',
    'Avenida Jabaquara, Saúde, São Paulo - SP', '500',
    'Avenida Jabaquara', 'São Paulo', 'SP',
    'Encontro de cultura pop, campeonatos de e-sports, cosplays e artistas de quadrinhos.',
    '2027-04-20', '12:00:00', FALSE, 45.00, 120.00,
    'Geek e Cultura Pop', 'Livre', 'https://example.com/geek-fest',
    'https://images.unsplash.com/photo-1560932684-5e552e2894e9?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    42, 'Noite de RPG e Jogos de Tabuleiro', '20050030',
    'Rua da Assembleia, Centro, Rio de Janeiro - RJ', '10',
    'Rua da Assembleia', 'Rio de Janeiro', 'RJ',
    'Mesas abertas de RPG de mesa, card games e tabuleiro modernos.',
    '2027-05-08', '14:00:00', TRUE, NULL, NULL,
    'Geek e Cultura Pop', 'Livre', 'https://example.com/noite-rpg',
    'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    43, 'Festival de Animação e Mangá', '30160010',
    'Avenida Amazonas, Centro, Belo Horizonte - MG', '1200',
    'Avenida Amazonas', 'Belo Horizonte', 'MG',
    'Exibições de curtas de animação japonesa e oficinas de desenho estilo mangá.',
    '2027-05-25', '13:00:00', FALSE, 25.00, 60.00,
    'Geek e Cultura Pop', 'Livre', 'https://example.com/animacao-manga',
    'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    44, 'Encontro Retro Gaming', '80030010',
    'Rua Marechal Deodoro, Centro, Curitiba - PR', '300',
    'Rua Marechal Deodoro', 'Curitiba', 'PR',
    'Exposição de consoles clássicos dos anos 80, 90 e campeonatos de fliperama.',
    '2027-06-10', '11:00:00', TRUE, NULL, NULL,
    'Geek e Cultura Pop', 'Livre', 'https://example.com/retro-gaming',
    'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=60', 'ativo'
),
(
    45, 'Convenção de Ficção Científica', '70350000',
    'QI 13, Guará, Brasília - DF', '2',
    'Avenida Central', 'Brasília', 'DF',
    'Palestras com autores de sci-fi, debates sobre universos cinematográficos e exposições temáticas.',
    '2027-06-30', '15:00:00', FALSE, 40.00, 90.00,
    'Geek e Cultura Pop', 'Livre', 'https://example.com/conv-scifi',
    'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&auto=format&fit=crop&q=60', 'ativo'
);

-- ============================================================================
-- REGISTRO DE ARTISTAS E VÍNCULOS
-- ============================================================================
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

-- ============================================================================
-- GERAÇÃO AUTOMÁTICA DE SESSÕES PARA OS EVENTOS
-- ============================================================================
INSERT IGNORE INTO evento_sessoes (evento_id, data_evento, hora_inicio, hora_fim)
SELECT
    id_evento,
    data_evento,
    horario_evento,
    SEC_TO_TIME(LEAST(TIME_TO_SEC(horario_evento) + 7200, 86399))
FROM evento;

-- Sessões adicionais para testar múltiplas datas no planejamento
INSERT IGNORE INTO evento_sessoes (evento_id, data_evento, hora_inicio, hora_fim)
SELECT
    id_evento,
    DATE_ADD(data_evento, INTERVAL 1 DAY),
    horario_evento,
    SEC_TO_TIME(LEAST(TIME_TO_SEC(horario_evento) + 7200, 86399))
FROM evento
WHERE nome_evento IN ('Festival Sons da Cidade', 'Mostra de Cinema Brasileiro', 'ShowMe Geek Fest', 'Rota do Vinho e Queijos');

COMMIT;