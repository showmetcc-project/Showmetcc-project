<?php

try {

    $db = new PDO(
        'sqlite:' . __DIR__ . 'showme.sql'
    );

    $db->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    echo "<h1>SQLite funcionando! ✅</h1>";
    echo "<p>Banco conectado com sucesso.</p>";

} catch (PDOException $erro) {

    echo "<h1>Erro ❌</h1>";
    echo "<p>" . $erro->getMessage() . "</p>";

}