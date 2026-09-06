<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saindo - ShowMe</title>
</head>
<body>
    <p>Encerrando sessão...</p>
    <script>
        fetch('api/sessoes/', {method: 'DELETE'})
            .finally(() => {
                window.location.replace('login.php');
            });
    </script>
</body>
</html>
