<?php
// A senha que você quer usar para o seu usuário de teste
$senha_para_teste = 'user123'; 

// Gera o hash seguro
$hash = password_hash($senha_para_teste, PASSWORD_DEFAULT);

// Exibe o hash para você copiar
echo "Use este hash no seu comando SQL UPDATE:<br><br>";
echo "<strong>" . htmlspecialchars($hash) . "</strong>";
?>