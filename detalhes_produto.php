<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

$produto = null;
$produtos_relacionados = [];
$erro = '';

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $erro = "Produto não especificado.";
} else {
    $id_produto = (int)$_GET['id'];

    // Busca o produto principal e o nome da sua categoria
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS nome_categoria
        FROM public.produtos p
        JOIN public.categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_produto = ?
    ");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $erro = "Produto não encontrado.";
    } else {
        // Busca produtos relacionados (da mesma categoria, excluindo o atual)
        $stmtRel = $pdo->prepare("
            SELECT id_produto, nome, preco, imagem 
            FROM public.produtos 
            WHERE id_categoria = ? AND id_produto != ? 
            ORDER BY RANDOM() LIMIT 4
        ");
        $stmtRel->execute([$produto['id_categoria'], $id_produto]);
        $produtos_relacionados = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<main class="page-content">
<div class="container">
    <?php if (!empty($erro)): ?>
        <div class="info-box">
            <h3><?= htmlspecialchars($erro) ?></h3>
            <a href="produtos.php" class="btn btn-primary">Ver todos os produtos</a>
        </div>
    <?php elseif ($produto): ?>
        <nav class="breadcrumbs">
            <a href="index.php">Início</a> >
            <a href="produtos.php">Produtos</a> >
            <a href="produtos.php?categoria=<?= $produto['id_categoria'] ?>"><?= htmlspecialchars($produto['nome_categoria']) ?></a> >
            <span><?= htmlspecialchars($produto['nome']) ?></span>
        </nav>

        <div class="product-detail-layout">
            <div class="product-gallery">
                <div class="main-image-container">
                    <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" id="main-product-image">
                </div>
                </div>

            <div class="product-purchase-panel">
                <h1><?= htmlspecialchars($produto['nome']) ?></h1>
                
                <div class="product-price-stock-wrapper">
                    <p class="product-price-large">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    <p class="product-stock <?= $produto['estoque'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $produto['estoque'] > 0 ? '✓ Em estoque' : '✗ Indisponível' ?>
                    </p>
                </div>
                
                <form method="post" action="carrinho.php" class="add-to-cart-form">
                    <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                    <div class="form-group quantity-selector">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" name="quantidade" id="quantidade" value="1" min="1" max="<?= htmlspecialchars($produto['estoque']) ?>">
                    </div>
                    <button type="submit" name="adicionar" class="btn btn-primary btn-lg" <?= $produto['estoque'] <= 0 ? 'disabled' : '' ?>>
                        Adicionar ao Carrinho
                    </button>
                </form>
            </div>
        </div>

        <div class="product-info-tabs">
            <h3>Descrição Completa</h3>
            <div class="tab-content">
                <p><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
            </div>
            
            <h3>Especificações Técnicas</h3>
            <div class="tab-content">
                <table class="specs-table">
                    <tr>
                        <td>Marca</td>
                        <td>Marca Fictícia</td>
                    </tr>
                    <tr>
                        <td>Código do Produto</td>
                        <td>SKU-<?= str_pad($produto['id_produto'], 6, '0', STR_PAD_LEFT) ?></td>
                    </tr>
                     <tr>
                        <td>Categoria</td>
                        <td><?= htmlspecialchars($produto['nome_categoria']) ?></td>
                    </tr>
                    <tr>
                        <td>Garantia</td>
                        <td>3 meses</td>
                    </tr>
                </table>
            </div>

            <h3>Avaliações de Clientes</h3>
            <div class="tab-content">
                <div class="reviews-placeholder">
                    <p>Ainda não há avaliações para este produto.</p>
                    <span>Seja o primeiro a avaliar!</span>
                </div>
            </div>
        </div>

        <?php if (!empty($produtos_relacionados)): ?>
        <div class="related-products-section">
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
</main>

<?php include 'includes/footer.php'; ?>