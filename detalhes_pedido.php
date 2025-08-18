<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

// Verifica se o usuário está logado de forma consistente
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$pedido = null;
$itens = [];
$erro = '';

// Recebe o ID do pedido via GET e valida
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $erro = "O ID do pedido fornecido é inválido.";
} else {
    $id_pedido = (int)$_GET['id'];

    // Etapa 1: Verifica se o pedido pertence ao usuário logado
    try {
        $stmt = $pdo->prepare("SELECT * FROM public.pedidos WHERE id_pedido = ? AND id_usuario = ?");
        $stmt->execute([$id_pedido, $id_usuario]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $erro = "Pedido não encontrado ou você não tem permissão para visualizá-lo.";
        } else {
            // Etapa 2: Se o pedido for válido, busca os itens dele
            $stmtItens = $pdo->prepare("
                SELECT pi.quantidade, pi.preco_unitario, p.nome, p.imagem
                FROM public.itens_pedido pi
                JOIN public.produtos p ON pi.id_produto = p.id_produto
                WHERE pi.id_pedido = ?
            ");
            $stmtItens->execute([$id_pedido]);
            $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $erro = "Ocorreu um erro ao buscar os detalhes do pedido.";
        error_log("Erro em detalhes_pedido.php: " . $e->getMessage());
    }
}
?>

<main class="page-content">
<div class="container">

    <div class="detalhes-header">
        <h2 class="section-title">Detalhes do Pedido</h2>
        <a href="meus_pedidos.php" class="btn btn-secondary">‹ Voltar para Meus Pedidos</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="info-box">
            <h3><?= htmlspecialchars($erro) ?></h3>
        </div>
    <?php elseif ($pedido): ?>
        <div class="detalhes-grid">
            <div class="pedido-sumario-card">
                <h4>Resumo do Pedido</h4>
                <div class="sumario-info">
                    <span>Nº do Pedido</span>
                    <strong>#<?= htmlspecialchars($pedido['id_pedido']) ?></strong>
                </div>
                <div class="sumario-info">
                    <span>Data</span>
                    <strong><?= date('d/m/Y à\s H:i', strtotime($pedido['data_pedido'])) ?></strong>
                </div>
                <div class="sumario-info">
                    <span>Método de Pagamento</span>
                    <strong><?= htmlspecialchars($pedido['metodo_pagamento']) ?></strong>
                </div>
                <div class="sumario-info">
                    <span>Status</span>
                    <strong class="status-badge status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>">
                        <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                    </strong>
                </div>
                <div class="sumario-info total">
                    <span>Total</span>
                    <strong>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></strong>
                </div>
            </div>

            <div class="produto-item-lista">
                <h4>Itens do Pedido</h4>
                <?php if (empty($itens)): ?>
                    <p>Este pedido não contém itens.</p>
                <?php else: ?>
                    <?php foreach ($itens as $item): 
                        $subtotal = $item['quantidade'] * $item['preco_unitario'];    
                    ?>
                        <div class="produto-item">
                            <img src="assets/img/produtos/<?= htmlspecialchars($item['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="produto-item-image">
                            <div class="produto-item-details">
                                <span class="produto-item-name"><?= htmlspecialchars($item['nome']) ?></span>
                                <span class="produto-item-price-qty">
                                    <?= $item['quantidade'] ?> x R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?>
                                </span>
                            </div>
                            <div class="produto-item-subtotal">
                                <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

</div>
</main>

<style>
/* Adicione este CSS ao seu arquivo estilos.css principal */
.detalhes-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}
.detalhes-header .section-title {
    margin: 0;
}

.detalhes-grid {
    display: grid;
    grid-template-columns: 1fr 2fr; /* 1/3 para o resumo, 2/3 para os itens */
    gap: 40px;
    align-items: flex-start;
}

.pedido-sumario-card, .produto-item-lista {
    background-color: var(--white);
    border-radius: 12px;
    box-shadow: var(--soft-shadow);
    padding: 30px;
}

.pedido-sumario-card h4, .produto-item-lista h4 {
    margin-top: 0;
    margin-bottom: 25px;
    font-size: 1.3rem;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 15px;
}

.sumario-info {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.95rem;
}
.sumario-info span { color: var(--light-text); }
.sumario-info strong { color: var(--dark-text); text-align: right; }
.sumario-info .status-badge { font-size: 0.8rem; }
.sumario-info.total {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid var(--dark-text);
    font-size: 1.5rem;
}

.produto-item {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 15px 0;
    border-bottom: 1px solid var(--border-color);
}
.produto-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.produto-item-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    object-fit: cover;
}
.produto-item-details { flex-grow: 1; }
.produto-item-name { font-weight: 600; display: block; margin-bottom: 5px; }
.produto-item-price-qty { color: var(--light-text); font-size: 0.9rem; }
.produto-item-subtotal { font-weight: 600; font-size: 1.1rem; }

/* Badges de Status (reutilizados de meus_pedidos.php) */
.status-badge{padding:4px 10px;border-radius:20px;font-size:0.8rem;font-weight:600;text-align:center;display:inline-block;width:fit-content}.status-pendente{background-color:#fff3cd;color:#856404}.status-enviado{background-color:#d1ecf1;color:#0c5460}.status-entregue{background-color:#d4edda;color:#155724}.status-cancelado{background-color:#f8d7da;color:#721c24}

/* Bloco de Info/Erro */
.info-box { text-align: center; padding: 60px 40px; background-color: var(--background-light); border-radius: 12px; }

@media (max-width: 992px) {
    .detalhes-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/footer.php'; ?>