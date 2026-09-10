# Testes com curl — API ShowMe

Os exemplos abaixo usam `curl.exe` no PowerShell. Ajuste a URL para a pasta/porta em que o projeto estiver sendo servido.

```powershell
$BASE = 'http://localhost/SEU-DIRETORIO/api'
$SITE = 'http://localhost/SEU-DIRETORIO'
$COOKIE_COMUM = "$env:TEMP\showme-comum.txt"
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
```

Antes de testar mídias de avaliações, aplique manualmente a migração
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

### DELETE /usuarios/{id} — sucesso e erro 403

Use uma conta descartável no teste de sucesso, pois a conta e seus relacionamentos em
cascata serão removidos e a sessão será encerrada.

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/usuarios/ID_DE_OUTRO_USUARIO"
curl.exe -i -b $COOKIE_CONTA_DESCARTAVEL -X DELETE "$BASE/usuarios/ID_DA_CONTA_DESCARTAVEL"
```

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

### POST /eventos — sucesso

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/eventos/" `
  -F "nome_evento=Festival Regional" `
  -F "local_evento=Praça Central" `
  -F "data_evento=2026-12-20" `
  -F "horario_evento=20:00" `
  -F "gratuidade=true" `
  -F "descricao_evento=Evento cultural" `
  -F "descricao_artista=Artistas locais" `
  -F "foto=@$FOTO_1"
```

### POST /eventos — erro 401

```powershell
curl.exe -i -X POST "$BASE/eventos/" `
  -F "nome_evento=Festival sem sessão" `
  -F "foto=@$FOTO_1"
```

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

### PUT /eventos/{id_solicitacao} — sucesso

```powershell
curl.exe -i -c $COOKIE_ADMIN -X POST "$BASE/sessoes/" `
  -H "Content-Type: application/json" `
  -d '{"email":"admin@exemplo.com","senha":"senha-admin"}'

curl.exe -i -b $COOKIE_ADMIN -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"moderar","status_solicitacao":"aprovado"}'
```

### PUT /eventos/{id_solicitacao} — erro 403

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/eventos/ID_SOLICITACAO" `
  -H "Content-Type: application/json" `
  -d '{"acao":"moderar","status_solicitacao":"recusado"}'
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

### PUT /avaliacoes/{id} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/avaliacoes/ID_AVALIACAO" `
  -H "Content-Type: application/json" `
  -d '{"nota":4,"comentario":"Comentário atualizado"}'

curl.exe -i -b $COOKIE_COMUM -X PUT "$BASE/avaliacoes/999999999" `
  -H "Content-Type: application/json" `
  -d '{"nota":4,"comentario":"Avaliação inexistente"}'
```

### DELETE /avaliacoes/{id} — sucesso e erro 404

```powershell
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/avaliacoes/ID_AVALIACAO"
curl.exe -i -b $COOKIE_COMUM -X DELETE "$BASE/avaliacoes/999999999"
```

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
