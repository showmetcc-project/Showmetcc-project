# Auditoria completa da arquitetura de backend

Data da auditoria: 10/09/2026  
Ambiente testado: Apache/XAMPP em `http://localhost/Showmetcc-project`, PHP e MySQL locais.

## Escopo e método

- Foram percorridos os arquivos PHP, JavaScript da aplicação, roteadores, middlewares, `.htaccess`, schema `assets/banco/showme.sql`, `api/swagger.yaml` e `api/TESTES.md`.
- Os endpoints foram exercitados por HTTP com usuários, solicitações, evento, artista, favorito, planejamento, avaliação e mídias temporários.
- Para testar administração, um usuário criado pela API foi promovido diretamente no banco, reproduzindo a regra real de criação manual de administradores.
- Toda massa temporária e todos os uploads de teste foram removidos. O banco voltou a 1 usuário e nenhum admin, evento, solicitação, avaliação ou mídia.
- Um token Google válido exige a janela interativa do Google. Para esse caso, foram considerados o teste manual recente informado pelo usuário, a renderização de um `client_id` configurado e um teste de rejeição de token inválido.
- Nenhuma correção foi aplicada durante esta auditoria.

## 1. Tudo que deveria ser API está em `api/`?

### 1.1 Acesso direto ao MySQL fora da API

Não foi encontrada página PHP fora de `api/` executando `SELECT`, `INSERT`, `UPDATE` ou `DELETE` diretamente. Também não há página incluindo `config/conexao.php` para consultar o banco.

| Ocorrência | Avaliação | Endpoint substituto |
|---|---|---|
| `config/conexao.php:3-19` | É o arquivo compartilhado que cria o `mysqli`; seu uso está concentrado nos roteadores da API, como esperado. | Não se aplica. |
| Páginas PHP da raiz | Nenhuma consulta direta encontrada. Home, detalhe, cadastro de evento, favoritos, perfil, planejamento, avaliações e admin usam a API. | Já usam os recursos correspondentes. |

Conclusão: a migração das consultas MySQL para a API está concluída no código atual.

### 1.2 Backend fora de `api/`

| Arquivo | Situação | Observação |
|---|---|---|
| `forms/contact.php:1-134` | Exceção intencional | Handler JSON sem banco, mantido fora da API por decisão anterior. O front o chama em `assets/js/contactForm.js:31`. |
| `forms/newsletter.php:1-39` | Órfão e inoperante | Não é referenciado por tela alguma, usa `$_POST` sem validação e depende de `assets/vendor/php-email-form/php-email-form.php`, que não existe. |
| `config/verifica_login.php:3-10` e `config/verifica_admin.php:3-8` | Correto para páginas | Fazem redirecionamento HTML; os middlewares de API respondem JSON 401/403. São responsabilidades diferentes. |
| `planejamento.php:1321-1332`, `2291-2295`, `2829-2844` e `3076-3089` | Lógica relevante no navegador | Geocodificação, busca de locais e rota usam Nominatim, Overpass e OSRM diretamente. A API só persiste o resultado; não existe endpoint de cálculo. |

### 1.3 Lógica duplicada

Não foi encontrada regra de negócio duplicada em PHP entre páginas e endpoints. Há validação repetida no JavaScript para feedback antecipado, mas o servidor continua autoritativo:

- `assets/js/cadastroEvento.js:31-47` verifica quantidade, tipo declarado e tamanho da foto; `api/middleware/uploadHelper.php:78-121` repete a segurança com `finfo`, limite e `getimagesize`.
- Login, cadastro e perfil usam validação HTML/JS, enquanto `apiSessoes.php` e `apiUsuarios.php` validam novamente no backend.
- `loginAdmin.php:127-130` encerra a sessão de conta comum; a autorização real continua em `config/verifica_admin.php` e `api/middleware/verifica_admin.php`.

Ponto para decisão futura: distância e tempo são calculados pelo cliente, e `api/apiPlanejamento.php:36-69` só confere se os valores enviados são positivos. Um cliente pode enviar valores inventados diretamente.

## 2. Casos de uso do diagrama

> O diagrama chama o ator de “Visitante”, mas cadastrar evento, avaliar, planejar e favoritar exigem conta autenticada pelas regras atuais. Nesses itens, “visitante” foi interpretado como usuário comum.

### Ator Visitante/usuário comum

| Caso de uso | Estado | Evidência e lacuna |
|---|---|---|
| Entrar em contato | **Parcial** | Entrada inválida retornou 422. Um envio válido retornou 503 porque `config/email.php` não existe; o fluxo não chega ao SMTP. |
| Cadastrar eventos | **Parcial** | A página é protegida; POST com foto retornou 201, sem login 401, vídeo e PHP disfarçado 400. Porém formulário/API só contemplam nome, foto, horário, data, local, gratuidade e descrições (`cadastro-evento.php:70-164`, `api/apiEventos.php:303-393`). Cidade, UF, categoria e link não entram na solicitação. Na aprovação, horário e descrição do artista não são promovidos e não se cria `artista`/`artista_evento` (`api/apiEventos.php:589-638`). |
| Fazer login com e-mail/senha e Google | **Completo** | Login correto retornou 201, senha errada 401 e conta somente Google recebeu a mensagem específica. Token inválido retornou 401. Google usa `Google\Client::verifyIdToken` (`api/apiSessoes.php:146-167`) e o fluxo válido foi confirmado manualmente pelo usuário. |
| Fazer logout | **Completo** | DELETE retornou 200; depois, a sessão retornou 401 e uma página protegida redirecionou para `login.php`. |
| Editar o perfil | **Completo** | GET/PUT próprios retornaram 200; editar outra conta retornou 403. `tipo_usuario` enviado no PUT foi ignorado. A API aceita senha, mas a tela envia apenas nome, sobrenome e e-mail (`assets/js/perfil.js:74-82`). |
| Excluir o perfil | **Parcial** | `DELETE /api/usuarios/{id}` funciona, encerra a sessão e aciona as cascatas. Entretanto `perfilUsuario.php`/`assets/js/perfil.js` não oferecem botão nem chamada DELETE. |
| Cadastrar/criar conta | **Completo** | POST retornou 201 e o login posterior funcionou. Payload com `tipo_usuario=admin` persistiu como `comum`. |
| Buscar eventos | **Completo** | Nome, cidade, categoria e artista retornaram o evento temporário. `%`, aspas e `_` não quebraram a query. A busca usa parâmetros e escape de curingas em `api/apiEventos.php:228-268`. |
| Visualizar eventos/detalhes | **Completo** | O detalhe retornou evento e `artistas` como array. A tela busca evento/avaliações pela API e trata 404 (`assets/js/detalhesEvento.js:615-636`). |
| Avaliar eventos/locais | **Completo** | Foto+vídeo retornaram 201; listagem trouxe as duas mídias; detalhe, edição e exclusão retornaram 200; edição alheia 403; duplicata 409; seis arquivos e PHP disfarçado 400. A edição atual não troca mídias. |
| Planejar eventos | **Completo** | POST 201, duplicata 409, GET e PUT 200. A página usa o evento e o planejamento reais. Ressalva: o cálculo da rota ocorre no navegador. |
| Favoritar eventos | **Completo** | POST 201 e repetição 409; detalhes e cards usam toggle baseado no GET. |
| Visualizar seus eventos (favoritos e planejados) | **Completo** | `favoritos.php` tem as duas abas; `assets/js/favoritos.js:151` e `:336` chamam os dois recursos e os testes retornaram os dados de evento necessários. |
| Remover planejados | **Completo** | DELETE 200; a tela oferece “Desfazer planejamento” (`assets/js/favoritos.js:294-304`). |
| Remover favoritos | **Completo** | DELETE 200 e repetição 404; há ação nos cards e no toggle. |

### Ator Administrador

| Caso de uso | Estado | Evidência e lacuna |
|---|---|---|
| Editar eventos | **Parcial** | Corrigir solicitação pendente antes da aprovação está completo: a tela envia `acao=editar_solicitacao` (`assets/js/admin.js:214-234`) e o teste retornou 200. O backend aceita `acao=editar` para evento publicado, mas nenhuma página usa esse caso. |
| Aprovar ou reprovar eventos | **Completo** | Listagem admin 200 e usuário comum 403. Editar→aprovar retornou 200, reanalisar 409 e recusar outra solicitação 200. A tela atualiza os cards sem recarregar (`assets/js/admin.js:179-185`). |

Observação operacional: o banco real tinha **zero administradores** antes e depois da auditoria. O recurso foi testado com admin temporário criado pela regra manual, mas `admin.php` não será utilizável até existir um admin real.

## 3. Organização, redundância, contratos e segurança

### 3.1 Endpoints existentes sem consumidor no front

Não são necessariamente inúteis, pois estão documentados e podem servir clientes futuros, mas nenhuma página os chama hoje:

| Endpoint/caso | Situação |
|---|---|
| `GET /api/sessoes` | Usado por testes/introspecção; o front infere login pela sessão renderizada em PHP. |
| `DELETE /api/usuarios/{id}` | Funciona, mas falta a ação de excluir conta na tela. |
| `PUT /api/eventos/{id}` com `acao=editar` | Edita evento publicado; admin só edita solicitações pendentes. |
| `DELETE /api/eventos/{id}` | Funciona para admin, sem controle correspondente na interface. |
| `GET /api/eventos?solicitacoes=pendente|aprovado|recusado` | Admin pede `todas` e filtra no cliente. |
| GET de outro usuário por admin | Permitido pela API, sem tela administrativa consumidora. |
| Alteração de senha no PUT de usuário | Implementada, mas o formulário de perfil não envia senha. |

### 3.2 Chamadas do front sem endpoint correspondente

Não foi encontrada chamada a rota interna inexistente. Todas as URLs internas usadas por `fetch` têm regra em `api/.htaccess` e handler correspondente.

Nominatim, Overpass, OSRM e Carto são chamadas externas diretas. Podem falhar por disponibilidade, limite ou CORS, mas não representam uma rota ShowMe ausente.

### 3.3 Contratos e respostas JSON

Pontos consistentes:

- Erros normais usam `erro` e status adequados.
- Criações usam 201; duplicidade 409; falta de sessão 401; falta de permissão 403.
- Listas usam chaves previsíveis: `eventos`, `favoritos`, `planejamentos` e `avaliacoes`.

Inconsistências:

1. `forms/contact.php` responde `{"sucesso":true}`, enquanto mutações da API usam `mensagem`. É uma exceção externa à API, mas impede contrato genérico único.
2. `apiUsuarios.php` não normaliza tipos vindos do MySQL como os demais recursos. IDs de SELECT podem sair como string e IDs recém-criados como inteiro. `apiEventos.php:31-35` converte `id_evento` e `gratuidade`, mas não `num_evento`, embora o Swagger o declare inteiro.
3. Limites do Swagger não são sempre validados no PHP. Um nome de usuário com 101 caracteres foi aceito com 201; o MySQL truncou para 100, mas a resposta devolveu os 101 recebidos (`api/apiUsuarios.php:84-132`).
4. Não há paginação em eventos, favoritos, planejamentos, avaliações ou solicitações; apenas busca possui `limite` opcional.
5. `GET /api/eventos` inclui `status_evento=cancelado`; não há filtro público, então a home pode exibir cancelados como normais.

### 3.4 Segurança e integridade

#### Proteções confirmadas

- IDs de path são inteiros positivos nos seis roteadores.
- Favoritos e planejamento exigem login; POST de eventos/avaliações exige login; moderação, edição e exclusão de evento exigem admin antes da lógica (`api/apiEventos.php:291`, `:420`, `:798`).
- `tipo_usuario` não é gravável pelo recurso de usuários; testes confirmaram.
- O papel admin vem da sessão criada a partir do banco, não do payload atual.
- Entrada do cliente em SQL usa prepared statements; busca usa `LIKE ?` e escapa `%`/`_`.
- Uploads usam nome aleatório, `finfo`, limites e validação adicional para imagens. PHP disfarçado foi rejeitado; diretórios têm `.htaccess` bloqueando PHP.
- Login regenera a sessão e Google valida assinatura/audiência com biblioteca.
- `config/google.php` e `config/email.php` estão ignorados; Google real não está versionado. Não apareceu nova credencial exposta fora das exceções combinadas (`config/conexao.php` e `api_spotify/spotify_config.php`).

#### Lacunas

1. **Sem CSRF:** nenhuma mutação autenticada valida token CSRF. A proteção depende do comportamento do cookie no navegador.
2. **Cookie sem endurecimento explícito:** no PHP local, HttpOnly, Secure e strict mode estão desligados e SameSite está vazio. `Secure` deve depender de HTTPS, mas HttpOnly/SameSite/strict mode podem ser definidos no projeto.
3. **Papel admin pode ficar obsoleto:** `api/middleware/verifica_admin.php:5-13` confia na sessão. Se um admin for rebaixado no banco, mantém privilégio até terminar a sessão.
4. **Sem rate limiting:** login e cadastro não limitam tentativas.
5. **IDs de query desiguais:** `GET /api/avaliacoes?evento_id=-1` retornou 200 vazio (`api/apiAvaliacoes.php:128-131`). Favoritos também valida `id_evento` sem `min_range` (`api/apiFavoritos.php:87-101`).
6. **Uploads órfãos:** DELETE explícito de avaliação remove arquivos, mas cascatas de usuário/evento removem apenas banco. Fotos de solicitações recusadas, usuários excluídos e eventos excluídos também não são apagadas. A auditoria reproduziu e removeu manualmente um órfão.
7. **Vídeo validado só por MIME/tamanho:** imagens recebem `getimagesize`; vídeo não passa por parser. Um arquivo mínimo só com cabeçalho reconhecido como MP4 foi aceito, embora possa não ser reproduzível.
8. **Erro de infraestrutura pode não ser JSON:** `config/conexao.php:15-17` usa `die` com detalhe da conexão, e vários INSERT/UPDATE não têm `try/catch` padronizado.
9. **CORS depende da mesma origem:** os fetches relativos funcionam com `Access-Control-Allow-Origin: *`. Separar front/API exigirá origem explícita, credentials e política de cookie.
10. **Vínculo solicitação→evento frágil:** aprovação grava `id_solicitacao` em `evento.num_evento`; a listagem faz JOIN nesse campo (`api/apiEventos.php:156-166`, `:613-627`), mas ele não é FK/UNIQUE e pode ser editado.

### 3.5 Perda de dados no cadastro/aprovação de evento

| Etapa | Dados preservados |
|---|---|
| Formulário → `solicitacao` | nome, foto, horário, data, local, gratuidade, descrição do evento e texto sobre artista. |
| `solicitacao` → `evento` | número da solicitação, nome, local, descrição, data, gratuidade e imagem. |
| Perdidos/nunca coletados | horário não existe em `evento`; descrição de artista não vira artista; rua, cidade, UF, categoria e link não entram na solicitação. |

Consequência: evento aprovado pode ficar sem cidade/categoria, prejudicando busca, e sem `artista_evento`, prejudicando busca/detalhe por artista. Esses campos só podem ser preenchidos depois pelo PUT admin da API, que não possui tela.

### 3.6 Swagger versus implementação

Cobertura positiva:

- Todos os recursos reais estão documentados e não foi encontrado path documentado inexistente.
- Artistas, mídias, Google, moderação, edição de solicitação e edição de evento aparecem nos schemas.
- Swagger UI e YAML responderam 200.

Divergências:

1. `api/swagger.yaml:270` diz que não existe edição de solicitação pendente, mas `PUT` com `acao=editar_solicitacao` existe e é documentado em `:314-365`; o documento se contradiz.
2. Schemas declaram `maxLength`, mas parte desses limites não é validada. O teste de 101 caracteres retornou 201 e resposta diferente da persistência.
3. Path IDs são documentados com mínimo 1, mas queries equivalentes não seguem sempre isso (`evento_id=-1`).
4. Cascatas são documentadas sem alertar que os arquivos físicos permanecem órfãos.

### 3.7 Redundância interna

- CORS, OPTIONS, `responder`, `lerJson`, sessão e ID são repetidos nos roteadores. Isso segue o exemplo didático, mas aumenta o risco de correções divergentes.
- `PUT /eventos/{id}` representa três operações e duas tabelas; o significado do ID depende de `acao`.
- `planejamento.php` tem 3.688 linhas e mistura markup, mapa, chamadas externas e persistência, dificultando teste e manutenção.
- `forms/newsletter.php` é código morto com biblioteca ausente.

## Evidência resumida dos testes HTTP

| Fluxo | Resultado |
|---|---|
| Cadastro / tentativa de autopromoção | 201 / permaneceu `comum` |
| Login correto / senha errada / conta só Google | 201 / 401 / 401 específico |
| Perfil próprio / alheio / edição alheia | 200 / 403 / 403 |
| Solicitação / sem login / vídeo / PHP disfarçado | 201 / 401 / 400 / 400 |
| Listar solicitações admin / comum | 200 / 403 |
| Editar / aprovar / reaprovar / recusar | 200 / 200 / 409 / 200 |
| Busca nos quatro critérios | 200 com um resultado em cada |
| Favoritar / duplicar / remover / repetir | 201 / 409 / 200 / 404 |
| Planejar / duplicar / listar / editar / remover | 201 / 409 / 200 / 200 / 200 |
| Avaliar foto+vídeo / seis / PHP disfarçado / duplicata | 201 / 400 / 400 / 409 |
| Detalhar / editar / excluir avaliação | 200 / 200 / 200; alheia 403 |
| Excluir conta / consultar sessão | 200 / 401 |
| Contato inválido / válido sem configuração | 422 / 503 |
| Logout / sessão / página protegida | 200 / 401 / 302 para login |

Todos os arquivos PHP da aplicação passaram em `php -l`.

## Prioridades sugeridas

1. **Completar solicitação→evento.** A perda silenciosa afeta cadastro, busca, artistas e detalhe.
2. **Endurecer sessão e mutações.** Cookie HttpOnly/SameSite/strict mode, CSRF, HTTPS/Secure e limite de login.
3. **Fechar o ciclo dos uploads.** Remover arquivos em cascatas/recusas/exclusões e validar vídeo reproduzível.
4. **Completar interfaces suportadas.** Exclusão de perfil e decisão sobre editar/excluir evento publicado no admin.
5. **Configurar/testar SMTP.** Sem `config/email.php`, contato continua indisponível.
6. **Alinhar Swagger, validações e tipos.** Corrigir contradição, limites e normalização JSON.
7. **Revalidar admin e padronizar falhas.** Evitar papel obsoleto e garantir JSON em falhas de infraestrutura.
8. **Decidir arquitetura de mapas.** Manter cálculo no navegador ou criar serviço/proxy controlado.
9. **Remover código morto/reduzir boilerplate.** Newsletter e repetição que não seja exigida pelo padrão do professor.
