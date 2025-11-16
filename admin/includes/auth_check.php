<?php
// Inicia a sessão para acessar os dados do admin
include_once __DIR__ . '/../../includes/session_config.php';

// Verifica se o ID do admin não está definido na sessão
if (!isset($_SESSION['admin_id'])) {
    // Se não estiver logado, redireciona para a página de login e encerra o script
    header('Location: login.php');
    exit;
}