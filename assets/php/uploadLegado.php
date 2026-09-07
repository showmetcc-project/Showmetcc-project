<?php

// Compatibilidade com formulários antigos: preserva o POST e entrega o
// processamento seguro ao cadastro.php da raiz.
header('Location: ../../cadastro.php', true, 307);
exit;
