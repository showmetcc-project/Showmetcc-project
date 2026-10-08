# Auditoria atual do projeto ShowMe

Data da revisão: 08/10/2026<br>
Base analisada: branch `Joao`, a partir do commit `27bbab0`<br>
Ambiente: Apache/XAMPP, PHP 8.2 e MySQL local em `http://localhost/Showmetcc-project`

## 1. Resumo executivo

O ShowMe é hoje uma aplicação web PHP + MySQL sem framework, dividida em três camadas reconhecíveis:

1. páginas PHP na raiz, responsáveis pelo HTML e pela proteção inicial das telas;
2. JavaScript no navegador, responsável por consumir a API e atualizar a interface;
3. roteadores REST em `api/`, responsáveis por regras de negócio, autorização e persistência.

A arquitetura está consideravelmente mais organizada do que na auditoria histórica. As páginas principais já não consultam o banco diretamente. A API está achatada, usa um `.htaccess` único, prepared statements, helpers compartilhados, respostas JSON e middlewares diferentes para usuário comum e administrador.

A antiga funcionalidade de Avaliações foi removida por completo e substituída pelo conceito de Comunidades. O sistema atual contempla cadastro e moderação de eventos, favoritos, planejamento de viagens, calendário, comunidades, galeria, denúncias, autenticação tradicional/Google, Google Agenda, contato por e-mail e personalização do perfil.

Os principais pontos ainda incompletos são:

- dados do Spotify são gravados, mas não influenciam recomendações ou outra parte visível do sistema;
- `preferencias` e `evento.num_evento` permanecem no schema sem função atual;
- o endpoint de excluir conta existe, mas não há controle correspondente no Perfil;
- faltam proteção CSRF, limitação de tentativas de login e endurecimento explícito dos cookies de sessão;
- o planejamento depende de serviços externos consultados diretamente pelo navegador;
- “Recomendados para Você” usa os primeiros eventos retornados pela API, não uma recomendação personalizada ou um campo de destaque;
- o Clube de Fidelidade é apenas uma apresentação institucional, sem assinatura ou cobrança funcional.

## 2. Arquitetura e organização

### 2.1 Camada de apresentação

As páginas ficam na raiz e usam PHP principalmente para sessão, includes e configuração inicial. Os dados dinâmicos são carregados via `fetch`:

| Área | Página | JavaScript principal | Proteção |
|---|---|---|---|
| Institucional | `institucional.php` | `main.js`, `odsModais.js`, formulário de contato | Pública |
| Descoberta/Home | `inicio.php` | `inicio.js`, `buscaEventos.js`, `favoritosToggle.js` | Pública, com ações protegidas |
| Login comum | `login.php` | lógica local + `googleLogin.js` | Pública |
| Login administrativo | `loginAdmin.php` | lógica local | Pública |
| Cadastro de usuário | `cadastro.php` | `googleLogin.js` | Pública |
| Perfil | `perfilUsuario.php` | `perfil.js` | Somente usuário comum |
| Meus Eventos | `meusEventos.php` | `meusEventos.js` | Somente usuário comum |
| Cadastro de evento | `cadastroEvento.php` | `cadastroEvento.js` | Somente usuário comum |
| Detalhes de evento | `detalhesEvento.php` | `detalhesEvento.js` | Somente usuário comum |
| Planejamento | `planejamento.php` | script da própria página | Somente usuário comum |
| Comunidades | `comunidade.php` | `comunidade.js` | Qualquer sessão |
| Comunidade do evento | `comunidadeEvento.php` | `comunidadeEvento.js` | Qualquer sessão; admin somente leitura |
| Painel administrativo | `admin.php` | `admin.js` | Somente administrador |

Não foi encontrada página normal consultando MySQL diretamente. As exceções fora de `api/` têm justificativa específica:

- `forms/contact.php`: handler JSON de e-mail, sem acesso ao banco;
- `googleCalendarConectar.php` e `googleCalendarCallback.php`: início/callback OAuth;
- `api_spotify/spotify_callback.php`: integração legada que ainda consulta o banco diretamente e merece reorganização futura.

### 2.2 Camada REST

O `.htaccess` de `api/` publica URLs amigáveis e encaminha para arquivos camelCase:

| Recurso | Roteador | Responsabilidade |
|---|---|---|
| Sessões | `apiSessoes.php` | Login por senha/Google, sessão atual e logout |
| Usuários | `apiUsuarios.php` | Cadastro, perfil, estatísticas, edição, mídia e exclusão |
| Eventos | `apiEventos.php` | Descoberta, busca, solicitação, moderação e administração |
| Favoritos | `apiFavoritos.php` | Lista, inclusão e remoção |
| Planejamento | `apiPlanejamento.php` | CRUD da tabela `rota` |
| Google Agenda | `apiGoogleCalendar.php` | Status, desconexão, conflito e exportação |
| Posts | `apiComunidadePosts.php` | Resumo de comunidades e CRUD de publicações |
| Respostas | `apiComunidadeRespostas.php` | Criação, edição e exclusão de respostas |
| Curtidas | `apiComunidadeCurtidas.php` | Toggle de curtida |
| Mídias | `apiComunidadeMidia.php` | Galeria, upload e exclusão |
| Denúncias | `apiComunidadeDenuncias.php` | Registro, listagem e moderação |

`api/middleware/apiCommon.php` centraliza CORS, `OPTIONS`, sessão, leitura JSON, respostas e ID positivo. `apiHelper.php` centraliza execução de statements, limites e normalização de tipos/datas. Os helpers de login/admin separam 401 de 403.

### 2.3 Banco de dados

O projeto usa um schema único em `assets/banco/showme.sql`, conforme a decisão de não manter migrações separadas.

| Tabela | Uso atual |
|---|---|
| `usuario` | Conta, papel, Google, avatar e banner |
| `solicitacao` | Evento enviado pelo usuário antes da moderação |
| `evento` | Evento aprovado/publicado |
| `artista` / `artista_evento` | Artistas associados ao evento |
| `favoritos` | Toggle de evento salvo |
| `rota` | Planejamento finalizado, custos e deslocamento |
| `comunidade_post` | Publicações textuais por evento |
| `comunidade_resposta` | Respostas de um nível |
| `comunidade_curtida` | Curtida única por usuário/post |
| `comunidade_midia` | Fotos da galeria |
| `comunidade_denuncia` | Denúncia de post ou mídia e decisão administrativa |
| `google_calendar_token` | Tokens da Agenda por usuário |
| `spotify` | Dados importados do Spotify, ainda sem consumo funcional posterior |
| `preferencias` | Sem leitura ou escrita no código atual |

As relações principais usam `ON DELETE CASCADE`. A origem do evento aprovado é preservada por `evento.id_solicitacao_origem`, com FK e `UNIQUE`. `num_evento` não é mais usado para esse vínculo.

## 3. Navegação

### 3.1 Entrada pública

O domínio raiz serve `institucional.php` via `DirectoryIndex`. Não existe mais `index.php`.

Sem sessão, o visitante pode:

- navegar pela Institucional;
- abrir a Home e pesquisar eventos;
- usar o formulário de contato;
- abrir login comum, login administrativo ou cadastro;
- visualizar o conteúdo público da vitrine.

Entradas para Meus Eventos, Cadastrar Evento e detalhes protegidos são interceptadas visualmente por um modal de autenticação. O bloqueio real continua no servidor, portanto digitar a URL diretamente não contorna a sessão.

### 3.2 Quatro tipos de cabeçalho

`cabecalho.php` implementa as variantes compartilhadas:

- **Tipo A:** navbar completa e busca. É usada na Home e, com contexto próprio, na listagem de Comunidades.
- **Tipo B:** navbar completa sem a busca de eventos, usada na Institucional, com dropdown de âncoras.
- **Tipo C:** seta, ícone/título e ação opcional. É usado em Perfil, Meus Eventos, Cadastro de Evento, Planejamento e Painel Admin. O painel omite a seta.
- **Tipo D:** apenas seta de retorno. É usado em Detalhes e Comunidade do Evento. Login/Cadastro/Login Admin mantêm seta própria porque não passam pelo include.

No desktop a navegação completa aparece a partir de `xl`; abaixo disso o menu Bootstrap colapsa. A busca possui comportamento específico para telas estreitas. A seta usa histórico e destino fixo de fallback.

### 3.3 Navegação por papel

**Usuário comum:** Cadastrar Evento, Meus Eventos, Comunidade, Institucional, Início e Perfil.<br>
**Administrador:** Painel Admin, Início/Institucional e Comunidade em modo somente leitura. A interface esconde ações comuns e os middlewares também recusam escrita administrativa na comunidade.<br>
**Sem sessão:** Login/Cadastro é um dropdown com “Sou usuário” e “Sou administrador”.

### 3.4 Rodapé e acessibilidade

O rodapé aparece atualmente em Home, Institucional e Comunidades. As páginas de tarefa não o usam.

`acessibilidade.php` é carregado pelo cabeçalho compartilhado e diretamente nas três telas de autenticação. Ele reúne VLibras, o bundle local da biblioteca de acessibilidade e os ajustes próprios do ShowMe. O widget oferece tamanho de texto, espaçamento, altura de linha, guia de leitura, inversão, escala de cinza, cursor ampliado, redução de animação, sublinhado e fonte para dislexia.

O Swagger UI foi corretamente mantido sem esses widgets por ser documentação técnica.

## 4. Design system atual

### 4.1 Cores

Os tokens principais estão no começo de `assets/css/main.css`:

| Papel | Valor | Uso predominante |
|---|---|---|
| Fundo | `#0a0a0a` | Body e áreas principais |
| Superfície | `#141414` / `#232323` | Cards, inputs e modais |
| Verde | `#00ff00` | Primário, confirmação, links/localização e foco |
| Verde hover | `#00dd00` | Hover de controles verdes |
| Rosa | `#ff006e` | Edição, badges, destaques e ações destrutivas |
| Rosa hover | `#dd0060` | Hover de controles rosas |
| Texto | `#ffffff` / `#bdbdbd` | Texto principal/secundário |
| Admin | `#ffd84f` / `#00d4ff` | Destaques restritos ao painel |

Cores oficiais de Google, Spotify e ODS são tratadas como exceções de marca.

A semântica atual é razoavelmente consistente:

- verde: continuar, salvar, confirmar e ação principal segura;
- rosa: editar, remover/denunciar, badge ou destaque secundário;
- cinza escuro: ação neutra/cancelamento;
- amarelo/azul: informação administrativa.

### 4.2 Tipografia

- títulos `h1`–`h4` e cabeçalhos de subpágina: Anton/Oswald;
- corpo, navegação, formulários e botões: Open Sans/Poppins/Jost/Segoe UI;
- Bootstrap Icons é a biblioteca visual padrão; não foram encontradas referências Font Awesome nas páginas atuais.

### 4.3 Formas, caixas e bordas

O projeto usa três famílias visuais:

1. **Cards:** superfícies escuras, borda normalmente verde de 2px, raio entre 18px e 25px e hover rosa/elevação.
2. **Pílulas:** filtros, categorias, badges e abas compactas usam raio alto (`999px`).
3. **Círculos:** voltar, favoritar, câmera, fechar, redes sociais e ações apenas com ícone.

Inputs usam fundo escuro, raio aproximado de 14px e foco verde. Modais mantêm fundo preto/escuro, borda temática e overlay. A aplicação aproveita o `box-sizing: border-box` global do Bootstrap; o CSS próprio complementa dimensões e `max-width` para evitar overflow.

### 4.4 Responsividade

O grid continua baseado no Bootstrap. Cards empilham em telas pequenas, formulários usam largura total e botões têm alvo mínimo de toque. Home usa Swiper para banner e carrosséis; poucos cards são centralizados e listas maiores usam loop. O carrossel de um único Show Internacional foi confirmado centralizado.

A regra global bloqueia scroll horizontal. Carrosséis são uma exceção controlada dentro do próprio wrapper, com máscara/fade lateral responsivo.

## 5. Fluxos funcionais

### 5.1 Autenticação e sessão

O cadastro público sempre grava `tipo_usuario='comum'`. Administradores só existem por inserção/controladoria do banco.

Login comum aceita:

- e-mail e senha com `password_verify`;
- ID token do Google validado pela biblioteca oficial, com vínculo por `google_id` ou e-mail;
- Spotify, por um fluxo separado e ainda legado.

Conta criada somente pelo Google recebe `senha_user=NULL` e o login tradicional devolve mensagem específica. O login regenera o ID da sessão. Logout usa `DELETE /api/sessoes` após confirmação visual.

O login administrativo usa a mesma sessão, mas só aceita usuário cujo papel vindo do banco seja `admin`. `admin.php` redireciona ausência/falha de papel para `loginAdmin.php`.

### 5.2 Perfil

O Perfil carrega dados pela API e permite edição inline de nome, sobrenome e e-mail. O botão muda de “Editar Perfil” rosa para “Salvar alterações” verde.

Avatar e banner podem ser:

- enviados, recortados no navegador e redimensionados no servidor;
- escolhidos na galeria de imagens prontas;
- ampliados em lightbox ao clicar.

As estatísticas são reais:

- eventos aprovados originados de solicitações do usuário;
- total histórico de viagens planejadas;
- posts ativos + mídias ativas da comunidade.

O Perfil também conecta/desconecta o Google Agenda. O endpoint de excluir a conta existe e limpa arquivos/cascatas, mas não há botão na tela.

### 5.3 Cadastro, aprovação e vida do evento

1. O usuário abre `cadastroEvento.php`.
2. Informa CEP; BrasilAPI preenche o endereço e o usuário complementa o número quando existir.
3. Seleciona uma ou mais categorias, gratuidade/preço, artista, descrições, data/horário, link e foto.
4. `POST /api/eventos` grava uma `solicitacao` pendente e salva a foto validada.
5. O admin pesquisa, filtra por período/status e recebe os registros em lotes de 10.
6. Antes da decisão, o admin pode corrigir a solicitação.
7. Ao aprovar, a API cria `evento`, grava `id_solicitacao_origem`, procura/cria o artista sem duplicação por caixa/espaços e cria `artista_evento`.
8. Ao reprovar, a foto é removida fisicamente e a solicitação permanece como histórico recusado.
9. Aprovação/reprovação envia e-mail; falha de SMTP é registrada sem desfazer a decisão.

Depois de publicado, o admin pode editar ou excluir o evento com confirmação. O autor recebe e-mail. Evento excluído deixa “Aprovados” e aparece no filtro virtual “Removidos”: a solicitação continua aprovada, mas seu `LEFT JOIN` já não encontra `evento`.

Eventos ativos futuros aparecem na Home/busca. Eventos passados deixam a descoberta, mas continuam acessíveis diretamente, em Favoritos, Planejados e Comunidades, com visual encerrado. Sua comunidade não expira. Evento cancelado é omitido da API pública; a API permite ao admin incluí-lo explicitamente.

### 5.4 Busca e Home

A busca considera nome, cidade, categoria e artista com prepared statement e escape dos curingas de `LIKE`. O autocomplete limita resultados; a confirmação troca os carrosséis por um grid dentro da própria Home e atualiza `?busca=` sem navegar para outra página.

A Home organiza os mesmos eventos em categorias sobrepostas: Música, Perto de Você, Cinema, Workshops, Oficinas, Gastronômicos, Literatura, Shows Internacionais, Shows Nacionais e Em Breve. Apesar do nome, “Perto de Você” ainda procura termos como `local` e `regional` na categoria; não calcula proximidade geográfica do visitante.

O banner “Recomendados para Você” usa até os cinco primeiros eventos válidos retornados pela API. Não existe coluna `destaque` nem algoritmo baseado em Spotify, preferências, localização ou histórico. Portanto o nome “Recomendados” ainda representa curadoria cronológica, não personalização.

### 5.5 Favoritos e Meus Eventos

Favoritar é um toggle protegido por sessão. A chave única `(id_user,id_evento)` evita duplicidade.

Meus Eventos possui abas Favoritos, Planejados e Calendário:

- favoritos podem ser removidos;
- planejados mostram todo o histórico, ordenado dos mais recentes para os mais antigos;
- o contador de Planejados considera somente eventos ainda ativos/futuros;
- passados ficam em escala de cinza e não permitem editar o planejamento;
- o calendário usa apenas `rota`, soma os custos do mês e permite abrir o resumo ou remover.

### 5.6 Planejamento

O planejamento é um fluxo em etapas: orçamento, transporte, rota, hospedagem e resumo.

Ele persiste em `rota`:

- transporte, origem, distância e tempo;
- orçamento total;
- custo de ingresso, transporte e hospedagem;
- necessidade/nome da hospedagem.

Existe apenas um planejamento por usuário/evento. Custos podem ser zero quando o usuário não precisa daquele item. A tela oferece links externos para transportes, mapas e hospedagem, calcula a rota quando possível e permite entrada manual como fallback.

Finalizar é uma ação confirmada. O toast de criação oferece “Ver meus planejados”; edição não repete esse link. O resumo final pode ser impresso/salvo em PDF com layout branco próprio. Eventos encerrados podem ter o resumo consultado, mas não editado.

Com Google Agenda conectado, a API verifica conflito na finalização e permite continuar ou cancelar. Depois é possível exportar uma vez para a agenda. Não existe sincronização contínua.

### 5.7 Comunidades

Cada evento é sua própria comunidade; não existe tabela de adesão ou entrar/sair.

`comunidade.php` separa eventos salvos/planejados dos demais e pesquisa em tempo real. `comunidadeEvento.php` oferece:

- feed mais recente primeiro;
- filtros Dúvida, Dica, Transporte, Hospedagem, Companhia e Relato;
- criar, editar e excluir post próprio;
- responder, editar e excluir resposta própria;
- curtir/descurtir uma vez;
- denunciar post alheio com motivo padronizado;
- galeria com foto, legenda, permissão de download, lightbox, exclusão própria e denúncia.

Uma denúncia só pode ser feita uma vez pelo mesmo usuário para o mesmo alvo. O admin lista posts e fotos denunciados, pesquisa, filtra por período/status, pagina em lotes de 10 e decide manter/remover. Denunciante recebe e-mail sobre a decisão; autor também recebe quando o conteúdo é removido.

Administrador pode visualizar comunidades, mas os roteadores de escrita usam `exigirUsuarioComum`, portanto o bloqueio não depende apenas de esconder botões.

### 5.8 Painel administrativo

O painel possui duas abas:

- Eventos enviados: pendentes, todas, aprovados, reprovados e removidos;
- Conteúdos denunciados: pendentes, todas, mantidos e removidos.

A busca ocupa a largura disponível e o filtro de período vale para as duas abas. A paginação injeta lotes de 10 via “Ver mais”. Edição, exclusão e decisões de moderação usam confirmação sensível e toasts.

### 5.9 Contato e e-mails

O formulário do rodapé valida nome/e-mail/mensagem no backend e envia com PHPMailer usando `config/email.php`. Esse arquivo existe apenas no ambiente e está ignorado pelo Git.

O mesmo helper SMTP é reutilizado para:

- aprovação/reprovação de evento;
- edição/remoção de evento publicado;
- decisão de denúncia e remoção de conteúdo.

Falhas de envio não revertem transações de negócio. Não foi feito envio real durante esta auditoria para evitar e-mails externos; foi verificada a configuração local presente e o tratamento por `try/catch`.

## 6. Segurança e integridade

### Proteções presentes

- prepared statements em toda a API principal;
- senhas com `password_hash`/`password_verify`;
- regeneração de sessão no login;
- criação pública sempre como usuário comum;
- 401 para ausência de sessão e 403 para papel incorreto;
- validação central de IDs inteiros positivos;
- validação de comprimento antes do MySQL;
- JSON com tipos normalizados;
- exceções de infraestrutura convertidas em JSON 500 genérico e registradas no servidor;
- upload com `finfo`, nome aleatório, limite, `getimagesize` e bloqueio de PHP;
- validação mínima estrutural de MP4/WebM preservada no helper, embora Comunidades atualmente aceite apenas imagem;
- redimensionamento de avatar/banner no servidor com GD;
- remoção de arquivos físicos ao excluir conteúdo, evento ou usuário, sem falhar a operação caso `unlink` não seja possível;
- checks e índices únicos no banco para valores de ingresso, favoritos, planejamento, curtidas e denúncias;
- texto de usuário renderizado principalmente por `textContent`/`htmlspecialchars`.

### Lacunas atuais

1. **CSRF:** mutações autenticadas por cookie não usam token CSRF.
2. **Cookies:** HttpOnly, SameSite, Secure e `session.use_strict_mode` não são configurados explicitamente pelo projeto.
3. **Rate limiting:** login, cadastro, contato e denúncias não limitam frequência/tentativas.
4. **Papel em sessão:** o middleware admin confia em `$_SESSION['tipo_usuario']`; rebaixar o usuário no banco não invalida uma sessão já aberta.
5. **CORS:** `Access-Control-Allow-Origin: *` é adequado ao uso atual na mesma origem, mas não suporta cookies em front separado.
6. **Serviços externos no cliente:** BrasilAPI, Nominatim, Overpass/OSRM e mapas podem sofrer indisponibilidade, CORS ou limites de uso sem controle do servidor ShowMe.
7. **Segredos locais:** os arquivos reais de e-mail/Google estão ignorados corretamente, mas `api_spotify/spotify_config.php` ainda segue um padrão legado separado que merece revisão antes de publicar.

## 7. Código ou dados sem função completa

### Confirmadamente legados ou sem consumidor

| Item | Situação atual | Recomendação |
|---|---|---|
| `evento.num_evento` | Sempre pode voltar `null`; não participa mais do vínculo solicitação→evento | Remover do schema/API após confirmar que nenhuma base externa usa a coluna |
| `preferencias` | Nenhum PHP/JS lê ou grava a tabela | Implementar preferências reais ou remover |
| Dados da tabela `spotify` | Callback grava artistas/gêneros, mas Home/API não consome | Integrar à recomendação ou retirar o botão/fluxo |
| `api_spotify/` | Backend próprio fora do padrão REST, consulta banco diretamente e possui configuração independente | Refatorar para a API ou declarar formalmente como integração legada |
| Clube de Fidelidade | Cards e preços são somente informativos | Deixar explícito “em breve” ou implementar assinatura/pagamento |
| `DELETE /api/usuarios/{id}` | Backend completo, sem botão no Perfil | Adicionar ação sensível ou retirar do escopo público documentado |
| Cancelar evento (`status_evento`) | API aceita edição para `cancelado`; painel não oferece campo específico | Adicionar ação “Cancelar” ou manter como recurso técnico |
| `artista.genero_artista` / `imagem_artista` | Detalhes exibem se houver, mas aprovação cria apenas o nome | Criar manutenção administrativa ou aceitar preenchimento só via SQL |

### Não é código morto

- `acessibilidade.php`, `rodape.php` e `cabecalho.php` são includes reutilizados;
- middlewares pequenos são separados por responsabilidade;
- `forms/contact.php` é uma exceção deliberada, não uma API esquecida;
- `googleCalendarCallback.php` precisa ficar fora do roteador porque é destino OAuth;
- assets de avatar/banner prontos são consumidos pela configuração de mídia de perfil;
- os documentos em `docs/historico/` são registros antigos deliberadamente preservados.

### Arquivos grandes

`planejamento.php` continua muito extenso e mistura HTML, estado, mapas, consultas externas, cálculos e persistência. Funciona, mas é o maior risco de manutenção. A divisão segura seria:

- `assets/js/planejamento/estado.js`;
- `rota.js`;
- `hospedagem.js`;
- `resumo.js`;
- `googleAgenda.js`.

`assets/js/comunidadeEvento.js` e `assets/js/admin.js` também cresceram e já justificam módulos menores, porém ainda têm seções lógicas compreensíveis.

## 8. Consistência da documentação

`api/swagger.yaml` cobre todas as rotas publicadas pelo `.htaccess`, inclusive Comunidades, denúncias, Google Agenda, filtros administrativos e paginação. Não há referência restante a Avaliações no código ou Swagger atual.

`api/TESTES.md` acompanha os casos novos, incluindo:

- papéis e autopromoção;
- upload inválido;
- fluxo completo de solicitação/aprovação;
- eventos passados/cancelados;
- planejamento e Google Agenda;
- posts, respostas, mídia, denúncias e moderação;
- filtros, busca e lotes administrativos;
- categoria virtual de eventos removidos.

A auditoria antiga permanece em `docs/historico/auditoriaBackend.md` justamente por descrever uma fase anterior com Avaliações e lacunas já corrigidas.

## 9. Evidências desta revisão

- 52/52 arquivos PHP passaram em `php -l`.
- `/`, `institucional.php`, `inicio.php`, `/api/eventos` e `/api/docs/` responderam HTTP 200.
- `perfilUsuario.php` sem sessão respondeu 302 para `login.php`.
- `admin.php` sem sessão respondeu 302 para `loginAdmin.php`.
- A listagem administrativa foi exercitada com sessão de teste: evento aprovado sem linha correspondente em `evento` apareceu em `removido`, deixou `aprovado` e os contadores permaneceram coerentes.
- Home foi renderizada em Chrome headless; carrosséis de um, dois e três itens ficaram centralizados.
- `git diff --check` não apontou erro de whitespace antes do commit funcional.

Não foram executadas ações destrutivas contra a base atual nem chamadas reais a SMTP/OAuth externo durante esta atualização documental.

## 10. Prioridades recomendadas

1. Implementar CSRF e endurecer cookies de sessão.
2. Decidir o destino do Spotify e da tabela `preferencias`; hoje não existe recomendação personalizada real.
3. Remover `num_evento` depois da confirmação final de compatibilidade.
4. Adicionar exclusão de conta à interface ou retirar o caso de uso visível da documentação do produto.
5. Modularizar `planejamento.php` sem alterar seu comportamento.
6. Criar uma decisão de produto para “destaque/recomendação” e “perto de você” baseada em dado real.
7. Adicionar manutenção administrativa de artista e ação explícita de cancelar evento, se permanecerem no escopo.
8. Criar rate limiting para login, contato e denúncias.
9. Automatizar no CI pelo menos lint PHP e validação do OpenAPI.
