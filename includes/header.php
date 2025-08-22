<?php
// A sessão já deve ser iniciada, mas `session_start()` não causa erro se chamada novamente.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'includes/conexao.php';

$usuarioLogado = $_SESSION['usuario']['nome'] ?? null;
$idUsuarioLogado = $_SESSION['usuario']['id'] ?? null;

// 1. Busca as categorias para o menu dropdown
$categoriasNav = [];
try {
    // Excluímos 'Todos os Departamentos', pois não é uma categoria real de produto
    $stmtCategoriasNav = $pdo->query("SELECT id_categoria, nome FROM categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome ASC");
    $categoriasNav = $stmtCategoriasNav->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias para o header: " . $e->getMessage());
}

// 2. Conta os itens no carrinho para o ícone
$totalItensCarrinho = 0;
if ($idUsuarioLogado) {
    // Se o usuário está logado, conta no banco de dados
    $stmtCount = $pdo->prepare("SELECT SUM(quantidade) FROM carrinho WHERE usuario_id = ?");
    $stmtCount->execute([$idUsuarioLogado]);
    $totalItensCarrinho = $stmtCount->fetchColumn();
} else if (!empty($_SESSION['carrinho'])) {
    // Se for visitante, conta na sessão
    $totalItensCarrinho = array_sum($_SESSION['carrinho']);
}
$totalItensCarrinho = (int)$totalItensCarrinho; // Garante que é um número

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GearUP Express - Sua loja online para peças automotivas</title>
    <!-- SEU FAVICON - MANTIDO -->
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
</head>
<body>

<header class="app-bar">
    <div class="app-bar-container container">
        
        <!-- SEU LOGOTIPO - MANTIDO -->
        <a href="index.php" class="logo-link">
            <img src="assets/img/GUPX-logo.png" alt="GearUp Express Logo" class="app-bar-logo" />
        </a>

        <!-- 2. ÁREA DE BUSCA DE PRODUTOS -->
        <div class="search-bar-wrapper">
            <form action="busca.php" method="GET" class="search-bar">
                <input type="search" name="termo" placeholder="O que você está procurando?" required>
                <button type="submit" aria-label="Buscar">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </button>
            </form>
        </div>
        
        <!-- 3. LINKS DE NAVEGAÇÃO APRIMORADOS -->
        <nav class="app-bar-nav">
            <ul class="nav-links">
                <!-- 1. MENU DROPDOWN DEPARTAMENTOS -->
                <li class="dropdown">
                    <a href="produtos.php" class="nav-link-dropdown">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/></svg>
                        Departamentos
                    </a>
                    <div class="dropdown-content">
                        <a href="produtos.php">Ver Todos</a>
                        <?php foreach ($categoriasNav as $categoria): ?>
                            <a href="produtos.php?categoria=<?= $categoria['id_categoria'] ?>">
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </li>

                <!-- 4. CARRINHO COM ANIMAÇÃO (BADGE) -->
                <li>
                    <a href="carrinho.php" class="nav-link-icon" aria-label="Carrinho de Compras">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM3.102 4l1.313 7h8.17l1.313-7H3.102zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/></svg>
                        <?php if ($totalItensCarrinho > 0): ?>
                            <span class="cart-badge"><?= $totalItensCarrinho ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <!-- Menu do Usuário -->
                <?php if ($usuarioLogado): ?>
                    <li class="dropdown">
                        <a href="#" class="nav-link-dropdown">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/></svg>
                            Olá, <?= htmlspecialchars(strtok($usuarioLogado, ' ')) ?>
                        </a>
                         <div class="dropdown-content">
                            <a href="meus_pedidos.php">Meus Pedidos</a>
                            <a href="logout.php">Sair</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link-button">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>