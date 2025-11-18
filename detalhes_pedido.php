<?php
include 'includes/header.php'; 

// Verifica se o usuário está logado
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
                            <img src="<?= htmlspecialchars($item['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="produto-item-image">
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

<?php include 'includes/footer.php'; ?>