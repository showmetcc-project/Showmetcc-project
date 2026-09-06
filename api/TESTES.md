# Testes com curl — API ShowMe

Os exemplos abaixo usam `curl.exe` no PowerShell. Ajuste a URL para a pasta/porta em que o projeto estiver sendo servido.

```powershell
$BASE = 'http://localhost/SEU-DIRETORIO/api'
$COOKIE_COMUM = "$env:TEMP\showme-comum.txt"
$COOKIE_ADMIN = "$env:TEMP\showme-admin.txt"
$COOKIE_CONTA_DESCARTAVEL = "$env:TEMP\showme-conta-descartavel.txt"
```

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
  -H "Content-Type: application/json" `
  -d '{"nome_evento":"Festival Regional","local_evento":"Praça Central","data_evento":"2026-12-20","horario_evento":"20:00","gratuidade":true,"descricao_evento":"Evento cultural","descricao_artista":"Artistas locais"}'
```

### POST /eventos — erro 401

```powershell
curl.exe -i -X POST "$BASE/eventos/" `
  -H "Content-Type: application/json" `
  -d '{"nome_evento":"Festival sem sessão"}'
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

```powershell
curl.exe -i "$BASE/avaliacoes/?evento_id=1"
curl.exe -i "$BASE/avaliacoes/"
```

### POST /avaliacoes — sucesso e erro 400

```powershell
curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -H "Content-Type: application/json" `
  -d '{"id_evento":1,"nota":5,"comentario":"Excelente evento"}'

curl.exe -i -b $COOKIE_COMUM -X POST "$BASE/avaliacoes/" `
  -H "Content-Type: application/json" `
  -d '{"id_evento":1,"nota":9,"comentario":"Nota inválida"}'
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
