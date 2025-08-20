<?php
include_once 'auth_check.php';
include_once '../includes/conexao.php';

$admin_nome = $_SESSION['admin_nome'] ?? 'Admin';

// Pega o nome do arquivo atual para destacar o link no menu
$pagina_atual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <title>Painel Administrativo - GearUP Express</title>
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>
<body>
    <div id="toast-container"></div>
    <div class="admin-wrapper">
        <aside class="sidebar">
    <div class="sidebar-header">
        <a href="index.php">
            <img src="../assets/img/GUPX-logo-v2.png" alt="GearUp Express Logo" class="sidebar-logo">
        </a>
    </div>
    <nav class="sidebar-nav">
    <ul>
        <li><a href="index.php" class="<?= ($pagina_atual == 'index.php') ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="produtos.php" class="<?= ($pagina_atual == 'produtos.php' || $pagina_atual == 'produto_form.php') ? 'active' : '' ?>"><i class="fas fa-box"></i> Produtos</a></li>
        <li><a href="pedidos.php" class="<?= ($pagina_atual == 'pedidos.php' || $pagina_atual == 'pedido_detalhes.php') ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Pedidos</a></li>
        <li><a href="relatorios.php" class="<?= ($pagina_atual == 'relatorios.php') ? 'active' : '' ?>"><i class="fas fa-file"></i> Relatórios</a></li>
    </ul>
</nav>
        </aside>

        <div class="main-content">
            <header class="top-bar">
                <div class="user-info">
                    <span>Olá, <?= htmlspecialchars(strtok($admin_nome, ' ')) ?></span>
                    <a href="logout.php" class="btn-logout" title="Encerrar sessão">
    <i class="fas fa-sign-out-alt"></i> Sair
</a>

                </div>
            </header>
            
            <main class="content-area">