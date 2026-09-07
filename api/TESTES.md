# Testes com curl — API ShowMe

Os exemplos abaixo usam `curl.exe` no PowerShell. Ajuste a URL para a pasta/porta em que o projeto estiver sendo servido.

```powershell
$BASE = 'http://localhost/SEU-DIRETORIO/api'
$COOKIE_COMUM = "$env:TEMP\showme-comum.txt"
$COOKIE_ADMIN = "$env:TEMP\showme-admin.txt"
$COOKIE_CONTA_DESCARTAVEL = "$env:TEMP\showme-conta-descartavel.txt"
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

## Preflight CORS

```powershell
curl.exe -i -X OPTIONS "$BASE/sessoes/" `
  -H "Origin: http://localhost:5502" `
  -H "Access-Control-Request-Method: POST"
```
