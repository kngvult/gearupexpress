<?php
session_start();
include_once 'includes/conexao.php';

$usuarioLogado = $_SESSION['usuario']['nome'] ?? null;

try {
    $stmtCategoriasNav = $pdo->query("SELECT id_categoria, nome FROM categorias ORDER BY nome ASC");
    $categoriasNav = $stmtCategoriasNav->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoriasNav = [];
}


?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>GearUP Express - Sua loja online para peças automotivas</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>

<header class="app-bar">
    <div class="app-bar-container container">
        <a href="index.php">
            <img src="assets/img/GUPX-logo.png" alt="GearUp Express" class="app-bar-logo" style="height:70px; vertical-align:middle; margin-right:8px;" />
        </a>
       <!-- <a href="index.php" class="app-bar-logo">GearUp Express</a> -->

        <nav class="app-bar-nav">
            <ul class="nav-links">
                <li><a href="index.php">🏠 Início</a></li>

                <li><a href="carrinho.php">🛒 Carrinho</a></li>

                <?php if ($usuarioLogado): ?>
                    <li class="dropdown">
                        <a href="#" class="nav-link-dropdown">👤 Olá, <?= htmlspecialchars(strtok($usuarioLogado, ' ')) ?> &#9662;</a>
                        <div class="dropdown-content">
                            <a href="meus_pedidos.php">📦 Meus Pedidos</a>
                            <a href="logout.php">🚪 Sair</a>
                        </div>
                    </li>
                <?php else: ?>
                    <li><a href="login.php" class="nav-link-button">🔐 Login</a></li>
                    <li><a href="cadastro.php" class="nav-link-button">📝 Cadastro</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>