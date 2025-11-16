<?php
include_once __DIR__ . '/auth_check.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

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
            <img src="../assets/img/GUPX-logo-v2.png" alt="GearUp Express Logo" class="sidebar-logo sidebar-logo-large">
            <img src="../assets/img/GUPX-icon-v2.ico" alt="GearUp Express Logo Mini" class="sidebar-logo sidebar-logo-mini" style="display:none;">
            <button id="sidebar-toggle-btn" title="Expandir menu" style="display: none;">
                
            </button>
    </div>
    <nav class="sidebar-nav">
    <ul>
        <li><a href="index.php" class="<?= ($pagina_atual == 'index.php') ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span class="sidebar-text">Dashboard</span></a></li>
        <li><a href="produtos.php" class="<?= ($pagina_atual == 'produtos.php' || $pagina_atual == 'produto_form.php') ? 'active' : '' ?>"><i class="fas fa-box"></i> <span class="sidebar-text">Produtos</span></a></li>
        <li><a href="pedidos.php" class="<?= ($pagina_atual == 'pedidos.php' || $pagina_atual == 'pedido_detalhes.php') ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> <span class="sidebar-text">Pedidos</span></a></li>
        <li><a href="relatorios.php" class="<?= ($pagina_atual == 'relatorios.php') ? 'active' : '' ?>"><i class="fas fa-file"></i> <span class="sidebar-text">Relatórios</span></a></li>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const logoLarge = document.querySelector('.sidebar-logo-large');
    const logoMini = document.querySelector('.sidebar-logo-mini');
    const mainContent = document.querySelector('.main-content');
    
    logoLarge.style.cursor = 'pointer';
    logoMini.style.cursor = 'pointer';

    // Função para recolher a sidebar
    function collapseSidebar() {
        sidebar.classList.add('sidebar-collapsed');
        logoLarge.style.display = 'none';
        logoMini.style.display = 'block';
        mainContent.classList.add('main-content-expanded');
        localStorage.setItem('sidebarCollapsed', 'true');
    }

    // Função para expandir a sidebar
    function expandSidebar() {
        sidebar.classList.remove('sidebar-collapsed');
        logoLarge.style.display = 'block';
        logoMini.style.display = 'none';
        mainContent.classList.remove('main-content-expanded');
        // NOVO: Salva o estado no localStorage
        localStorage.setItem('sidebarCollapsed', 'false');
    }

    // Evento de clique na logo grande (para recolher)
    logoLarge.addEventListener('click', collapseSidebar);

    // Evento de clique na logo mini (para expandir)
    logoMini.addEventListener('click', expandSidebar);
    
    // --- LÓGICA DO LOCALSTORAGE ---
    const isCollapsed = localStorage.getItem('sidebarCollapsed');
    if (isCollapsed === 'true') {
        // Se estava guardado como recolhido, já inicia a página nesse estado
        collapseSidebar();
    }
});
</script>
</body>
</html>