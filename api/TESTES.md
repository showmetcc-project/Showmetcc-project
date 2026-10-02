# Testes com curl — API ShowMe

Os exemplos abaixo usam `curl.exe` no PowerShell. Ajuste a URL para a pasta/porta em que o projeto estiver sendo servido.

```powershell
$BASE = 'http://localhost/SEU-DIRETORIO/api'
$SITE = 'http://localhost/SEU-DIRETORIO'
$RAIZ_PROJETO = 'C:\xampp\htdocs\SEU-DIRETORIO'
$COOKIE_COMUM = "$env:TEMP\showme-comum.txt"
$COOKIE_OUTRO_USUARIO = "$env:TEMP\showme-outro-usuario.txt"
$COOKIE_ADMIN = "$env:TEMP\showme-admin.txt"
$COOKIE_CONTA_DESCARTAVEL = "$env:TEMP\showme-conta-descartavel.txt"
$COOKIE_GOOGLE = "$env:TEMP\showme-google.txt"
$GOOGLE_TOKEN = 'COLE_UM_ID_TOKEN_VALIDO_RECEBIDO_PELO_GOOGLE_IDENTITY_SERVICES'
$GOOGLE_JSON = '{\"google_token\":\"' + $GOOGLE_TOKEN + '\"}'
$FOTO_1 = 'C:\CAMINHO\foto1.jpg'
$FOTO_2 = 'C:\CAMINHO\foto2.png'
$FOTO_3 = 'C:\CAMINHO\foto3.webp'
$VIDEO_1 = 'C:\CAMINHO\video1.mp4'
$VIDEO_2 = 'C:\CAMINHO\video2.webm'
$ARQUIVO_PHP = 'C:\CAMINHO\arquivo.php'
$TEXTO_101 = 'x' * 101
$VIDEO_MP4_CORROMPIDO = Join-Path $env:TEMP 'showme-mp4-corrompido.mp4'

# MP4 com um box ftyp reconhecível, seguido de um box mdat truncado e sem moov.
# Ele simula um arquivo que passa por uma checagem superficial de cabeçalho/MIME.
$bytesMp4Corrompido = [byte[]](@(
  0,0,0,24, 102,116,121,112, 105,115,111,109, 0,0,2,0,
  105,115,111,109, 105,115,111,50,
  0,0,0,20, 109,100,97,116, 1,2,3,4
))
[IO.File]::WriteAllBytes($VIDEO_MP4_CORROMPIDO, $bytesMp4Corrompido)
```

Antes de testar mídias de avaliações, importe a versão atual do schema único
`assets/banco/showme.sql` no banco de desenvolvimento.
Use arquivos pequenos: a aplicação limita fotos a 10 MB, vídeos a 30 MB e a requisição
completa a 38 MB.

Para testar aprovação, deve existir uma conta com `tipo_usuario = 'admin'`. Esse perfil deve ser atribuído diretamente pelo administrador do banco, não pelo endpoint público de cadastro.

Antes dos testes Google, confirme que o banco foi criado com a versão atual de
`assets/banco/showme.sql`, copie `config/google.example.php` para `config/google.php`,
preencha o Client ID e execute `composer install`. O token usado nos comandos deve ter
sido emitido para esse mesmo Client ID e ainda estar dentro do prazo de validade.

## Sessões

### POST /sessoes — sucesso

```powershell
curl.exe -i -c $COOKIE_COMUM -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  -d '{"email":"usuario@exemplo.com","senha":"senha123"}'
```

### POST /sessoes — erro 401

```powershell
curl.exe -i -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  -d '{"email":"usuario@exemplo.com","senha":"incorreta"}'
```

### POST /sessoes — criação de conta nova via Google

Use um token cujo e-mail ainda não exista em `usuario`. A resposta esperada é `201`,
com `tipo_usuario: comum`; no banco, `senha_user` deve ficar `NULL` e `google_id` deve
receber o `sub` do token.

```powershell
curl.exe -i -c $COOKIE_GOOGLE -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  --data-raw $GOOGLE_JSON
```

### POST /sessoes — vínculo de conta existente por e-mail

Primeiro cadastre pelo endpoint de usuários uma conta com o mesmo e-mail confirmado no
token Google e deixe `google_id` nulo. Depois envie o token. A resposta esperada é `201`
com o mesmo `id_user` da conta existente; confira no banco que apenas `google_id` foi
preenchido e que a senha anterior continua válida.

```powershell
curl.exe -i -c $COOKIE_GOOGLE -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  --data-raw $GOOGLE_JSON
```

### POST /sessoes — login Google recorrente

Repita o login com outro ID token válido da mesma conta Google. A resposta deve manter o
mesmo `id_user`, sem criar outro usuário.

```powershell
curl.exe -i -c $COOKIE_GOOGLE -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  --data-raw $GOOGLE_JSON
```

### POST /sessoes — token Google inválido

```powershell
curl.exe -i -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  --data-raw '{\"google_token\":\"token-invalido\"}'
```

A resposta esperada é `401`, sem criação ou alteração de usuário.

### POST /sessoes — senha em conta exclusivamente Google

Use o e-mail de uma conta criada pelo primeiro teste, cuja `senha_user` seja `NULL`.
A resposta esperada é `401` com `Esta conta usa login via Google`.

```powershell
curl.exe -i -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  --data-raw '{\"email\":\"email-da-conta-google@exemplo.com\",\"senha\":\"qualquer-senha\"}'
```

### GET /sessoes — sucesso e erro

```powershell
curl.exe -i -b $COOKIE_COMUM "$BASE/sessoes/"
curl.exe -i "$BASE/sessoes/"
```

### DELETE /sessoes — sucesso e erro

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/sessoes/"
curl.exe -i -X DELETE "$BASE/sessoes/"
```

## Usuários

### POST /usuarios — sucesso

```powershell
curl.exe -i -X POST "$BASE/usuarios/" `
  -H "Content-Type: application/json" `
  -d '{"nome":"Maria","sobrenome":"Silva","email":"maria@exemplo.com","senha":"senha123"}'
```

### POST /usuarios — erro 400

```powershell
curl.exe -i -X POST "$BASE/usuarios/" `
  -H "Content-Type: application/json" `
  -d '{"nome":"Maria","email":"email-invalido","senha":"123"}'
```

### POST /usuarios — nome acima de 100 caracteres retorna 400

```powershell
$USUARIO_LIMITE_JSON = @{
  nome = $TEXTO_101
  sobrenome = 'Teste'
  email = 'limite.usuario@exemplo.com'
  senha = 'senha123'
} | ConvertTo-Json -Compress

curl.exe -i -X POST "$BASE/usuarios/" `
  -H "Content-Type: application/json" `
  --data-raw $USUARIO_LIMITE_JSON
```

A resposta deve ser `400`, mencionar o limite de 100 caracteres e nenhum usuário deve
ser gravado parcialmente no banco.

### POST /usuarios — tentativa de definir administrador é ignorada

O cadastro abaixo envia `tipo_usuario: admin` de propósito. A resposta deve mostrar
`tipo_usuario: comum`.

```powershell
curl.exe -i -X POST "$BASE/usuarios/" `
  -H "Content-Type: application/json" `
  -d '{"nome":"Teste","sobrenome":"Comum","email":"teste.comum@exemplo.com","senha":"senha123","tipo_usuario":"admin"}'
```

Para confirmar o valor gravado no banco, faça login com esse usuário e consulte o ID
devolvido pelo cadastro. O campo `tipo_usuario` também deve ser `comum` no perfil:

```powershell
$COOKIE_TESTE_PERFIL = "$env:TEMP\showme-teste-perfil.txt"

curl.exe -i -c $COOKIE_TESTE_PERFIL -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  -d '{"email":"teste.comum@exemplo.com","senha":"senha123"}'

curl.exe -i -b $COOKIE_TESTE_PERFIL "$BASE/usuarios/ID_RETORNADO_PELO_CADASTRO"
```

### GET /usuarios/{id} — sucesso e erro 403

```powershell
curl.exe -i -b $COOKIE_COMUM "$BASE/usuarios/ID_DO_PROPRIO_USUARIO"
curl.exe -i -b $COOKIE_COMUM "$BASE/usuarios/ID_DE_OUTRO_USUARIO"
```

### PUT /usuarios/{id} — sucesso e erro 403

O primeiro comando atualiza o próprio perfil. Mesmo enviando `tipo_usuario`, esse campo
é ignorado e permanece inalterado. O segundo tenta editar outro usuário.

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/usuarios/ID_DO_PROPRIO_USUARIO" `
  -H "Content-Type: application/json" `
  -d '{"nome":"Maria Atualizada","email":"maria.atualizada@exemplo.com","senha":"novaSenha123","tipo_usuario":"admin"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/usuarios/ID_DE_OUTRO_USUARIO" `
  -H "Content-Type: application/json" `
  -d '{"nome":"Alteração indevida"}'
```

### POST /usuarios/{id} — personalização de foto e banner

Escolha pronta válida:

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/usuarios/ID_DO_PROPRIO_USUARIO" `
  -F "acao=personalizar_midia" `
  -F "foto_perfil_pronta=assets/img/prontos/perfil/vozes-destaque-01.webp" `
  -F "foto_banner_pronta=assets/img/prontos/banner/grandes-palcos-01.webp"
```

Upload válido; os arquivos gravados devem resultar em 500x500 e 1600x400:

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/usuarios/ID_DO_PROPRIO_USUARIO" `
  -F "acao=personalizar_midia" `
  -F "foto_perfil=@C:/caminho/foto.png;type=image/png" `
  -F "foto_banner=@C:/caminho/banner.png;type=image/png"
```

Arquivo PHP disfarçado e caminho pronto fora da lista branca devem retornar 400:

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/usuarios/ID_DO_PROPRIO_USUARIO" `
  -F "acao=personalizar_midia" `
  -F "foto_perfil=@C:/caminho/teste.php;filename=foto.jpg;type=image/jpeg"

curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/usuarios/ID_DO_PROPRIO_USUARIO" `
  -F "acao=personalizar_midia" `
  -F "foto_perfil_pronta=assets/img/arquivo-fora-do-catalogo.svg"
```

### DELETE /usuarios/{id} — sucesso e erro 403

Use uma conta descartável no teste de sucesso, pois a conta e seus relacionamentos em
cascata serão removidos e a sessão será encerrada.

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/usuarios/ID_DE_OUTRO_USUARIO"
curl.exe -i -b $COOKIE_CONTA_DESCARTAVEL -X DELETE "$BASE/usuarios/ID_DA_CONTA_DESCARTAVEL"
```

### DELETE /usuarios/{id} — cascata também remove arquivos físicos

Com a conta descartável, crie uma avaliação com mídia e uma solicitação pendente com
foto. Antes do `DELETE`, anote os caminhos retornados pela API (ou consulte-os no banco)
e confirme que ambos existem:

```powershell
$MIDIA_CONTA = Join-Path $RAIZ_PROJETO 'assets/uploads/avaliacoes/NOME_DO_ARQUIVO'
$FOTO_SOLICITACAO_CONTA = Join-Path $RAIZ_PROJETO 'assets/uploads/eventos/NOME_DO_ARQUIVO'
Test-Path -LiteralPath $MIDIA_CONTA
Test-Path -LiteralPath $FOTO_SOLICITACAO_CONTA

curl.exe -i -b $COOKIE_CONTA_DESCARTAVEL -X DELETE "$BASE/usuarios/ID_DA_CONTA_DESCARTAVEL"

Test-Path -LiteralPath $MIDIA_CONTA
Test-Path -LiteralPath $FOTO_SOLICITACAO_CONTA
```

Os dois primeiros `Test-Path` devem retornar `True`; depois da exclusão, devem retornar
`False`. Se uma foto também estiver vinculada a um evento aprovado, ela permanece no
disco porque o evento ainda a referencia.

## Eventos

### GET /eventos — sucesso e DELETE sem ID com erro 400

```powershell
curl.exe -i "$BASE/eventos/"
curl.exe -i -X DELETE "$BASE/eventos/"
```

### GET /eventos?busca= — busca por nome, cidade e artista

Substitua os termos pelos dados existentes no banco. As três respostas devem conter o
evento correspondente. O último comando também confirma o limite usado pelo autocomplete.

```powershell
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Festival Regional"
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Campinas"
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Nome do Artista"
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Festival" --data-urlencode "limite=5"
```

### GET /eventos?busca= — termo sem resultados

A resposta esperada é `200` com `{"eventos":[]}`.

```powershell
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=evento-que-nao-existe-987654321"
```

### GET /eventos?busca= — aspas e percentual

O termo é enviado com URL encoding. A resposta deve ser JSON válido, sem erro SQL; `%` é
tratado como texto literal pela busca, e não como curinga do `LIKE`.

```powershell
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Festival `"Especial`" 100%"
```

### GET /eventos/{id} — sucesso e erro 404

```powershell
curl.exe -i "$BASE/eventos/ID_EVENTO"
curl.exe -i "$BASE/eventos/999999999"
```

### GET /eventos?solicitacoes= — listagem administrativa e erro 403

O retorno inclui os dados da solicitação, a foto e o usuário solicitante. Os três
primeiros comandos exigem a sessão do administrador; o último confirma a proteção.

```powershell
curl.exe -i -b $COOKIE_ADMIN "$BASE/eventos/?solicitacoes=pendente"
curl.exe -i -b $COOKIE_ADMIN "$BASE/eventos/?solicitacoes=aprovado"
curl.exe -i -b $COOKIE_ADMIN "$BASE/eventos/?solicitacoes=recusado"
curl.exe -i -b $COOKIE_ADMIN "$BASE/eventos/?solicitacoes=todas"
curl.exe -i -b $COOKIE_COMUM "$BASE/eventos/?solicitacoes=pendente"
```

### POST /eventos — sucesso

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=Festival Regional" `
  -F "local_evento=Praça Central" `
  -F "rua_evento=Rua das Artes, 100" `
  -F "cidade_evento=Sao Paulo" `
  -F "uf=SP" `
  -F "categoria_evento=Musica" `
  -F "link_oficial=https://example.com/festival" `
  -F "data_evento=2026-12-20" `
  -F "horario_evento=20:00" `
  -F "gratuidade=true" `
  -F "descricao_evento=Evento cultural" `
  -F "descricao_artista=Artistas locais" `
  -F "nome_artista_solicitado=Banda Exemplo" `
  -F "foto=@$FOTO_1"
```

### POST /eventos — erro 401

```powershell
curl.exe -i -X POST "$BASE/eventos/" `
  -F "nome_evento=Festival sem sessão" `
  -F "foto=@$FOTO_1"
```

### POST /eventos — nome acima de 100 caracteres retorna 400

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=$TEXTO_101" `
  -F "foto=@$FOTO_1"
```

A resposta deve ser `400` antes de qualquer gravação ou salvamento da foto.

### POST /eventos — PHP disfarçado e vídeo são rejeitados com 400

O parâmetro `filename` simula a troca do nome para `.jpg`, e o `type` simula um MIME
declarado pelo cliente. A API deve detectar o conteúdo PHP real com `finfo`.

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=Arquivo malicioso" `
  -F "foto=@$ARQUIVO_PHP;filename=disfarce.jpg;type=image/jpeg"

curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=Evento com vídeo" `
  -F "foto=@$VIDEO_1"
```

### PUT /eventos/{id_solicitacao} — corrigir uma solicitação pendente

A foto original é mantida; o painel permite corrigir os demais dados antes da decisão.

```powershell
curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar_solicitacao","nome_evento":"Nome corrigido","local_evento":"Local corrigido","data_evento":"2026-12-21","horario_evento":"21:00","gratuidade":true,"descricao_evento":"Descrição corrigida","descricao_artista":"Artista corrigido"}'

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar_solicitacao","rua_evento":"Rua Corrigida, 200","cidade_evento":"Sao Paulo","uf":"SP","categoria_evento":"Musica","link_oficial":"https://example.com/evento-corrigido","nome_artista_solicitado":"Artista Corrigido"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar_solicitacao","nome_evento":"Alteração sem permissão"}'
```

Os dois primeiros comandos devem retornar `200`; o terceiro deve retornar `403`. Depois de aprovar
ou recusar a solicitação, repetir `editar_solicitacao` deve retornar `409`.

### PUT /eventos/{id_solicitacao} — aprovar e recusar com sucesso

```powershell
curl.exe -i -c $COOKIE_ADMIN -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@exemplo.com","senha":"senha-admin"}'

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"aprovar"}'

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/OUTRA_ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"recusar"}'
```

### Recusar solicitação — remove a foto física

Antes de recusar uma solicitação pendente, copie o valor de `foto` retornado na criação e
monte o caminho físico. Depois da recusa, o arquivo deve sumir sem alterar o sucesso da
moderação; a coluna `foto` da solicitação fica `NULL` para não manter referência inválida.

```powershell
$FOTO_SOLICITACAO_RECUSADA = Join-Path $RAIZ_PROJETO 'assets/uploads/eventos/NOME_DO_ARQUIVO'
Test-Path -LiteralPath $FOTO_SOLICITACAO_RECUSADA

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO_PENDENTE" `
  -H "Content-Type: application/json" `
  -d '{"acao":"recusar"}'

Test-Path -LiteralPath $FOTO_SOLICITACAO_RECUSADA
```

Os resultados esperados do `Test-Path` são `True` antes e `False` depois. Confirmação
opcional no banco: `SELECT status_solicitacao, foto FROM solicitacao WHERE
id_solicitacao = ID_SOLICITACAO_PENDENTE;` deve retornar `recusado` e `foto = NULL`.

### Aprovação completa com artista novo e artista já existente

O primeiro `POST /eventos` da seção anterior deve criar uma solicitação com
`nome_artista_solicitado=Banda Exemplo`. Anote `id_solicitacao` e aprove-a:

```powershell
curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO_ARTISTA_NOVO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"aprovar"}'
```

A resposta deve trazer `id_evento`. Em seguida, crie outra solicitação completa usando
o mesmo artista com diferenças de caixa e espaços. Isso testa a reutilização do artista:

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=Festival Reuso de Artista" `
  -F "local_evento=Teatro Municipal" `
  -F "rua_evento=Rua do Teatro, 200" `
  -F "cidade_evento=Campinas" `
  -F "uf=SP" `
  -F "categoria_evento=Musica" `
  -F "link_oficial=https://example.com/festival-reuso" `
  -F "data_evento=2026-12-22" `
  -F "horario_evento=21:30" `
  -F "gratuidade=false" `
  -F "descricao_evento=Segundo evento completo" `
  -F "descricao_artista=Mesmo artista do primeiro evento" `
  -F "nome_artista_solicitado=  banda   exemplo  " `
  -F "foto=@$FOTO_2"

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO_ARTISTA_EXISTENTE" `
  -H "Content-Type: application/json" `
  -d '{"acao":"aprovar"}'
```

Consulte os dois eventos. O JSON deve conter todos os campos promovidos,
`id_solicitacao_origem`, `horario_evento`, `num_evento: null` e o mesmo artista:

```powershell
curl.exe -i "$BASE/eventos/ID_EVENTO_ARTISTA_NOVO"
curl.exe -i "$BASE/eventos/ID_EVENTO_ARTISTA_EXISTENTE"
```

Confirmação adicional no MySQL/phpMyAdmin:

```sql
SELECT
    e.id_evento,
    e.id_solicitacao_origem,
    e.num_evento,
    e.local_evento,
    e.rua_evento,
    e.cidade_evento,
    e.uf,
    e.data_evento,
    e.horario_evento,
    e.categoria_evento,
    e.link_oficial,
    a.id_artista,
    a.nome_artista
FROM evento AS e
INNER JOIN artista_evento AS ae ON ae.id_evento = e.id_evento
INNER JOIN artista AS a ON a.id_artista = ae.id_artista
WHERE e.id_evento IN (ID_EVENTO_ARTISTA_NOVO, ID_EVENTO_ARTISTA_EXISTENTE);

SELECT LOWER(TRIM(nome_artista)) AS nome_normalizado, COUNT(*) AS quantidade
FROM artista
WHERE LOWER(TRIM(nome_artista)) = LOWER('Banda Exemplo')
GROUP BY LOWER(TRIM(nome_artista));
```

O segundo `SELECT` deve retornar `quantidade = 1`, e o primeiro deve mostrar o mesmo
`id_artista` ligado aos dois eventos.

### Busca do evento aprovado por cidade, categoria e artista

```powershell
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Sao Paulo"
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=Musica"
curl.exe -i -G "$BASE/eventos/" --data-urlencode "busca=banda exemplo"
```

Cada resposta deve incluir o evento aprovado correspondente, sem duplicá-lo quando mais
de um critério combinar com o mesmo evento.

### PUT /eventos/{id_solicitacao} — erro 403

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"moderar","status_solicitacao":"recusado"}'
```

### PUT /eventos/{id_solicitacao} — solicitação já analisada retorna 409

Repita a moderação de uma das solicitações processadas no teste anterior.

```powershell
curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"moderar","status_solicitacao":"aprovado"}'
```

### PUT /eventos/{id_evento} — editar com sucesso e erro 403

Com `acao: editar`, o ID da URL representa um evento aprovado, não uma solicitação.

```powershell
curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_EVENTO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar","nome_evento":"Festival Regional Atualizado","cidade_evento":"Campinas","uf":"SP","gratuidade":false,"status_evento":"ativo"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/eventos/ID_EVENTO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar","nome_evento":"Alteração indevida"}'
```

### DELETE /eventos/{id} — sucesso e erro 403

Use um evento descartável para o caso de sucesso, pois favoritos, avaliações, rotas e
relações com artistas vinculados a ele serão removidos em cascata.

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/eventos/ID_EVENTO"
curl.exe -i -b $COOKIE_ADMIN -X DELETE "$BASE/eventos/ID_EVENTO_DESCARTAVEL"
```

### DELETE /eventos/{id} — cascata também remove mídias das avaliações

Crie uma avaliação com mídia no evento descartável, anote `caminho_arquivo` e confirme o
arquivo antes e depois da exclusão administrativa:

```powershell
$MIDIA_EVENTO = Join-Path $RAIZ_PROJETO 'assets/uploads/avaliacoes/NOME_DO_ARQUIVO'
Test-Path -LiteralPath $MIDIA_EVENTO

curl.exe -i -b $COOKIE_ADMIN -X DELETE "$BASE/eventos/ID_EVENTO_DESCARTAVEL"

Test-Path -LiteralPath $MIDIA_EVENTO
```

O primeiro `Test-Path` deve retornar `True` e o segundo, `False`. A imagem principal do
evento só é apagada se nenhuma solicitação ou outro evento ainda apontar para o mesmo
caminho.

### GET /eventos — eventos cancelados ficam fora da listagem pública

Use um evento descartável e altere seu status como administrador:

```powershell
curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_EVENTO_DESCARTAVEL" `
  -H "Content-Type: application/json" `
  -d '{"acao":"editar","status_evento":"cancelado"}'

curl.exe -i "$BASE/eventos/"
curl.exe -i -b $COOKIE_COMUM "$BASE/eventos/?incluir_cancelados=1"
curl.exe -i -b $COOKIE_ADMIN "$BASE/eventos/?incluir_cancelados=1"
```

A listagem pública deve omitir o evento cancelado. A tentativa do usuário comum deve
retornar `403`; a listagem administrativa deve retornar `200` e incluir o evento.

## Planejamentos

Substitua os IDs pelos registros do seu banco. A tabela `rota` possui a restrição única
`(id_user, id_evento)`, portanto remova um planejamento anterior do mesmo evento antes do
teste de criação.

### POST /planejamento — sucesso

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/planejamento/" `
  -H "Content-Type: application/json" `
  --data-raw '{\"id_evento\":1,\"meio_transporte\":\"Carro\",\"distancia_km\":125.5,\"tempo_estimado\":150}'
```

A resposta esperada é `201`. Guarde o `id_rota` retornado para os testes de edição e
remoção.

### POST /planejamento — erro 409 ao repetir o evento

Repita o mesmo comando anterior sem remover o planejamento criado:

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/planejamento/" `
  -H "Content-Type: application/json" `
  --data-raw '{\"id_evento\":1,\"meio_transporte\":\"Carro\",\"distancia_km\":125.5,\"tempo_estimado\":150}'
```

A resposta esperada é `409`, pois o mesmo usuário não pode finalizar dois planejamentos
para o mesmo evento.

### POST /planejamento — erro 401 sem login

```powershell
curl.exe -i -X POST "$BASE/planejamento/" `
  -H "Content-Type: application/json" `
  --data-raw '{\"id_evento\":1,\"meio_transporte\":\"Ônibus\",\"distancia_km\":90,\"tempo_estimado\":120}'
```

### GET /planejamento — sucesso e erro 401

```powershell
curl.exe -i -b $COOKIE_COMUM "$BASE/planejamento/"
curl.exe -i "$BASE/planejamento/"
```

A resposta autenticada deve incluir os dados da rota e do evento, incluindo
`nome_evento`, `data_evento` e `imagem_evento`.

### PUT /planejamento/{id_rota} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/planejamento/ID_ROTA" `
  -H "Content-Type: application/json" `
  --data-raw '{\"meio_transporte\":\"Ônibus\"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/planejamento/999999999" `
  -H "Content-Type: application/json" `
  --data-raw '{\"meio_transporte\":\"Carro\"}'
```

### DELETE /planejamento/{id_rota} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/planejamento/ID_ROTA"
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/planejamento/999999999"
```

## Favoritos

### GET /favoritos — sucesso e erro 401

A resposta inclui `id_favorito` e `id_evento`; o frontend usa esses campos para decidir
entre adicionar com POST ou remover com DELETE ao clicar no mesmo botão.

```powershell
curl.exe -i -b $COOKIE_COMUM "$BASE/favoritos/"
curl.exe -i "$BASE/favoritos/"
```

### POST /favoritos — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/favoritos/" `
  -H "Content-Type: application/json" `
  -d '{"id_evento":1}'

curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/favoritos/" `
  -H "Content-Type: application/json" `
  -d '{"id_evento":999999999}'
```

### DELETE /favoritos/{id_favorito} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/favoritos/ID_FAVORITO"
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/favoritos/999999999"
```

## Avaliações

### GET /avaliacoes?evento_id= — sucesso e erro 400

A resposta de sucesso deve trazer `midias` como array em cada avaliação.

```powershell
curl.exe -i "$BASE/avaliacoes/?evento_id=1"
curl.exe -i "$BASE/avaliacoes/"
```

### GET /avaliacoes/{id} — sucesso e erro 404

A resposta de sucesso traz a avaliação, o nome do evento e o array `midias`.

```powershell
curl.exe -i "$BASE/avaliacoes/ID_AVALIACAO"
curl.exe -i "$BASE/avaliacoes/999999999"
```

### POST /avaliacoes — PHP disfarçado é rejeitado com 400

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=5" `
  -F "comentario=Teste de tipo real" `
  -F "midias[]=@$ARQUIVO_PHP;filename=disfarce.jpg;type=image/jpeg"
```

### POST /avaliacoes — sucesso com 3 fotos e 2 vídeos

Use um usuário que ainda não tenha avaliado o evento indicado.

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=5" `
  -F "comentario=Excelente evento" `
  -F "midias[]=@$FOTO_1" `
  -F "midias[]=@$FOTO_2" `
  -F "midias[]=@$FOTO_3" `
  -F "midias[]=@$VIDEO_1" `
  -F "midias[]=@$VIDEO_2"
```

### POST /avaliacoes — MP4 com container corrompido é rejeitado com 400

O arquivo criado no bloco inicial possui um `ftyp` de MP4 suficiente para enganar uma
checagem superficial, mas não contém a estrutura mínima válida (`moov` + `mdat`
coerentes). A resposta esperada é `400` com a mensagem de estrutura de vídeo inválida.

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=5" `
  -F "comentario=Teste de MP4 corrompido" `
  -F "midias[]=@$VIDEO_MP4_CORROMPIDO;type=video/mp4"
```

A validação confirma a estrutura do container MP4/WebM, além de MIME e tamanho. Ela não
substitui a decodificação integral de todos os frames; para essa garantia seria necessário
instalar uma ferramenta externa como FFmpeg/ffprobe no servidor.

### POST /avaliacoes — avaliação duplicada retorna 409

Depois do POST de sucesso acima, repita a criação com o mesmo usuário e evento. A resposta
deve informar que é necessário editar a avaliação existente e retornar seu
`id_avaliacao`.

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=4" `
  -F "comentario=Segunda avaliação do mesmo evento" `
  -F "midias[]=@$FOTO_1"
```

### POST /avaliacoes — uma 6ª mídia é rejeitada com 400

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=5" `
  -F "comentario=Mídias demais" `
  -F "midias[]=@$FOTO_1" `
  -F "midias[]=@$FOTO_2" `
  -F "midias[]=@$FOTO_3" `
  -F "midias[]=@$VIDEO_1" `
  -F "midias[]=@$VIDEO_2" `
  -F "midias[]=@$FOTO_1"
```

### POST /avaliacoes — nota inválida retorna 400

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -F "id_evento=ID_EVENTO" `
  -F "nota=9" `
  -F "comentario=Nota inválida" `
  -F "midias[]=@$FOTO_1"
```

### PUT /avaliacoes/{id} — sucesso, erro 403 e erro 404

O PUT altera somente `nota` e `comentario`; as mídias existentes permanecem associadas.

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/avaliacoes/ID_AVALIACAO" `
  -H "Content-Type: application/json" `
  -d '{"nota":4,"comentario":"Comentário atualizado"}'

curl.exe -i -b $COOKIE_OUTRO_USUARIO -X PUT "$BASE/avaliacoes/ID_AVALIACAO" `
  -H "Content-Type: application/json" `
  -d '{"nota":3,"comentario":"Alteração sem permissão"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/avaliacoes/999999999" `
  -H "Content-Type: application/json" `
  -d '{"nota":4,"comentario":"Avaliação inexistente"}'
```

### DELETE /avaliacoes/{id} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/avaliacoes/ID_AVALIACAO"
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/avaliacoes/999999999"
```

## Tipos e formatos das respostas JSON

Prepare pelo menos um favorito, planejamento e avaliação com mídia para o usuário comum.
Substitua os IDs e execute:

```powershell
$SESSAO_TIPOS = (curl.exe -sS -b $COOKIE_COMUM "$BASE/sessoes/") | ConvertFrom-Json
$USUARIO_TIPOS = (curl.exe -sS -b $COOKIE_COMUM "$BASE/usuarios/ID_DO_PROPRIO_USUARIO") | ConvertFrom-Json
$EVENTO_TIPOS = (curl.exe -sS "$BASE/eventos/ID_EVENTO") | ConvertFrom-Json
$FAVORITO_TIPOS = (curl.exe -sS -b $COOKIE_COMUM "$BASE/favoritos/") | ConvertFrom-Json
$PLANEJAMENTO_TIPOS = (curl.exe -sS -b $COOKIE_COMUM "$BASE/planejamento/") | ConvertFrom-Json
$AVALIACAO_TIPOS = (curl.exe -sS "$BASE/avaliacoes/ID_AVALIACAO") | ConvertFrom-Json

$SESSAO_TIPOS.usuario.id_user.GetType().Name
$USUARIO_TIPOS.usuario.id_user.GetType().Name
$EVENTO_TIPOS.evento.id_evento.GetType().Name
$EVENTO_TIPOS.evento.gratuidade.GetType().Name
$FAVORITO_TIPOS.favoritos[0].id_favorito.GetType().Name
$FAVORITO_TIPOS.favoritos[0].gratuidade.GetType().Name
$PLANEJAMENTO_TIPOS.planejamentos[0].id_rota.GetType().Name
$PLANEJAMENTO_TIPOS.planejamentos[0].distancia_km.GetType().Name
$AVALIACAO_TIPOS.avaliacao.id_avaliacao.GetType().Name
$AVALIACAO_TIPOS.avaliacao.nota.GetType().Name
$AVALIACAO_TIPOS.avaliacao.midias[0].id_midia.GetType().Name

$EVENTO_TIPOS.evento.data_evento -match '^\d{4}-\d{2}-\d{2}$'
$USUARIO_TIPOS.usuario.data_cadastro -match '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-03:00$'
$AVALIACAO_TIPOS.avaliacao.midias[0].data_upload -match '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}-03:00$'
```

Os IDs, notas e tempos devem aparecer como `Int32` ou `Int64`; booleanos como `Boolean`;
`distancia_km` como número (`Decimal` ou `Double`); datas como `YYYY-MM-DD`; e datas/horas como ISO
8601 com o fuso de São Paulo. Campos nulos continuam sendo `null` e não devem ser
forçados para zero ou string vazia.

## Formulário de contato

Antes do teste de sucesso, preencha `config/email.php` com as credenciais SMTP do
provedor de testes e execute `composer install` na raiz do projeto.

### POST /forms/contact.php — envio com sucesso

A resposta esperada é HTTP `200` com `{"sucesso":true}`, e a mensagem deve aparecer na
caixa de entrada do provedor de testes.

```powershell
curl.exe -i -X POST "$SITE/forms/contact.php" `
  -H "Accept: application/json" `
  -F "nome=Usuário de Teste" `
  -F "email=usuario@exemplo.com" `
  -F "mensagem=Mensagem enviada pelo teste automatizado do formulário ShowMe."
```

### POST /forms/contact.php — erro de validação

A resposta esperada é HTTP `422` com um objeto JSON contendo `erro`.

```powershell
curl.exe -i -X POST "$SITE/forms/contact.php" `
  -H "Accept: application/json" `
  -F "nome=A" `
  -F "email=email-invalido" `
  -F "mensagem=curta"
```

## Preflight CORS

```powershell
curl.exe -i -X OPTIONS "$BASE/sessoes/" `
  -H "Origin: http://localhost:5502" `
  -H "Access-Control-Request-Method: POST"
```

## Falha de infraestrutura — conexão com o banco

Faça este teste somente no ambiente local. Guarde o conteúdo atual de
`config/conexao.php`, altere temporariamente o usuário ou a senha para um valor inválido e
chame qualquer endpoint:

```powershell
curl.exe -i "$BASE/eventos/"
```

A resposta esperada é exatamente um status `500`, com `Content-Type: application/json`
e sem nome do banco, host, usuário, senha ou mensagem do `mysqli` no corpo:

```json
{"erro":"Erro interno do servidor"}
```

Restaure imediatamente a credencial correta e repita o endpoint; ele deve voltar a
responder normalmente. O detalhe técnico da falha deve aparecer apenas no log do PHP/
Apache. Não faça commit da credencial temporariamente inválida nem de credenciais reais.
