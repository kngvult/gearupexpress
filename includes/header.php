<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'includes/conexao.php';
include_once 'includes/funcoes_carrinho.php';

$usuarioLogado = $_SESSION['usuario']['nome'] ?? null;
$idUsuarioLogado = $_SESSION['usuario']['id'] ?? null;

// 1. Busca as categorias para o menu dropdown
$categoriasNav = [];
try {
    $stmtCategoriasNav = $pdo->query("SELECT id_categoria, nome FROM categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome ASC");
    $categoriasNav = $stmtCategoriasNav->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias para o header: " . $e->getMessage());
}

// --- INÍCIO DA LÓGICA DA WISHLIST ---
$wishlistCount = 0;
$wishlistProductIds = []; // Guarda os IDs dos produtos na wishlist

if (isset($idUsuarioLogado)) {
    try {
        $stmt = $pdo->prepare("SELECT id_produto FROM wishlist WHERE id_usuario = ?");

        $stmt->execute([$idUsuarioLogado]);
        $wishlistProductIds = $stmt->fetchAll(PDO::FETCH_COLUMN); 
        $wishlistCount = count($wishlistProductIds);
    } catch (PDOException $e) {
        error_log("Erro ao buscar wishlist header: " . $e->getMessage());
    }
}
// --- FIM DA LÓGICA DA WISHLIST ---

// 2. Conta os itens no carrinho para o ícone
$totalItensCarrinho = contarItensCarrinho($pdo);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- Google Fonts - Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <title>GearUP Express - Sua loja online para peças automotivas</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
</head>
<body>

<header class="app-bar">
    <div class="app-bar-container container">
        <!-- Menu Mobile Toggle -->
        <button class="mobile-menu-toggle" aria-label="Abrir menu" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>
        
        <a href="index.php" class="logo-link">
            <img src="assets/img/GUPX-logo.png" alt="GearUp Express Logo" class="app-bar-logo" />
        </a>

        <!-- ÁREA DE BUSCA DE PRODUTOS COM AUTO-COMPLETE -->
        <div class="search-bar-wrapper">
            <form action="busca.php" method="GET" class="search-bar" id="search-form">
                <div class="search-input-container">
                    <input type="search" name="termo" id="search-input" 
                            placeholder="O que você está procurando?" 
                            required autocomplete="off"
                            aria-label="Buscar produtos">
                    <button type="submit" class="search-btn" aria-label="Buscar">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div class="search-suggestions" id="search-suggestions">
                    <div class="suggestions-header">
                        <i class="fas fa-clock"></i>
                        <span>Buscas recentes</span>
                    </div>
                    <div class="suggestions-list">
                        <a href="busca.php?termo=motor" class="suggestion-item">
                            <i class="fas fa-search"></i>
                            <span>motor</span>
                        </a>
                        <a href="busca.php?termo=freio" class="suggestion-item">
                            <i class="fas fa-search"></i>
                            <span>freio</span>
                        </a>
                        <a href="busca.php?termo=suspensão" class="suggestion-item">
                            <i class="fas fa-search"></i>
                            <span>suspensão</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- LINKS DE NAVEGAÇÃO APRIMORADOS -->
        <nav class="app-bar-nav" id="main-nav">
            <ul class="nav-links">
                <!-- MENU DROPDOWN DEPARTAMENTOS -->
                <li class="dropdown">
                    <a href="produtos.php" class="nav-link-dropdown">
                        <i class="fas fa-th-large"></i>
                        <span class="nav-text">Departamentos</span>
                        <i class="fas fa-chevron-down dropdown-arrow"></i>
                    </a>
                    <div class="dropdown-content">
                        <div class="dropdown-header">
                            <i class="fas fa-tags"></i>
                            <span>Categorias</span>
                        </div>
                        <a href="produtos.php" class="dropdown-item highlight">
                            <i class="fas fa-grid"></i>
                            <span>Ver Todos os Produtos</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <?php foreach ($categoriasNav as $categoria): ?>
                            <a href="produtos.php?categoria=<?= $categoria['id_categoria'] ?>" class="dropdown-item">
                                <i class="fas fa-cog"></i>
                                <span><?= htmlspecialchars($categoria['nome']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <!-- CARRINHO COM ANIMAÇÃO -->
                <li class="nav-cart">
                    <a href="carrinho.php" class="nav-link-icon" aria-label="Carrinho de Compras">
                        <div class="cart-icon-container">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-badge <?= ($totalItensCarrinho > 0) ? 'visible' : '' ?>">
                                <?= $totalItensCarrinho ?>
                            </span>
                        </div>
                        <span class="nav-text">Carrinho</span>
                    </a>
                </li>
                
                <!-- Menu do Usuário -->
                <?php if ($usuarioLogado): ?>
                    <li class="dropdown user-menu">
                        <a href="#" class="nav-link-dropdown user-link">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="nav-text user-name">Olá, <?= htmlspecialchars(strtok($usuarioLogado, ' ')) ?></span>
                            <i class="fas fa-chevron-down dropdown-arrow"></i>
                        </a>
                        <div class="dropdown-content user-dropdown">
                            <div class="user-info">
                                <div class="user-avatar-large">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="user-details">
                                    <strong><?= htmlspecialchars($usuarioLogado) ?></strong>
                                    <span>Bem-vindo de volta!</span>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="meus_pedidos.php" class="dropdown-item">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Meus Pedidos</span>
                            </a>
                            <a href="wishlist.php" class="header-action-btn">
                                <i class="fas fa-heart"></i>
                                <span class="count-badge wishlist-count">Lista de Desejos (<?= $wishlistCount ?>)</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="logout.php" class="dropdown-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Sair</span>
                            </a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-auth">
                        <a href="login.php" class="nav-link-button">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="nav-text">Entrar</span>
                        </a>
                        <a href="cadastro.php" class="nav-link-button secondary">
                            <i class="fas fa-user-plus"></i>
                            <span class="nav-text">Cadastrar</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <!-- Overlay para mobile -->
        <div class="nav-overlay" id="nav-overlay"></div>
    </div>
</header>

<script>
    window.isUserLoggedIn = <?= isset($idUsuarioLogado) ? 'true' : 'false' ?>;

    window.wishlistProductIds = new Set(<?= json_encode($wishlistProductIds) ?>);
</script>