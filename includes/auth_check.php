<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário NÃO está logado
if (!isset($_SESSION['id_usuario'])) { 
    // Se não estiver logado, redireciona para a página de login e para a execução.
    header('Location: login.php');
    exit;
}

// Se o script continuar, significa que o usuário está logado.
$id_usuario = $_SESSION['id_usuario'];
?>