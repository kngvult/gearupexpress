<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

// --- LÓGICA DE BUSCA ---

// 1. Busca todas as categorias para a barra lateral de navegação
$categorias = [];
try {
    // Excluímos 'Todos os Departamentos' da lista se existir, pois não é uma categoria real de produto.
    $stmtCat = $pdo->query("SELECT id_categoria, nome FROM public.categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
}

// 2. Verifica se uma categoria específica foi selecionada na URL
$id_categoria_selecionada = null;
$nome_categoria_selecionada = "Todos os Produtos"; // Título padrão

if (!empty($_GET['categoria']) && is_numeric($_GET['categoria'])) {
    $id_categoria_selecionada = (int)$_GET['categoria'];

    // Busca o nome da categoria selecionada para usar no título
    $stmtNomeCat = $pdo->prepare("SELECT nome FROM public.categorias WHERE id_categoria = ?");
    $stmtNomeCat->execute([$id_categoria_selecionada]);
    $nome_temp = $stmtNomeCat->fetchColumn();
    if ($nome_temp) {
        $nome_categoria_selecionada = $nome_temp;
    }
}

// 3. Monta a query de produtos baseada na seleção
$produtos = [];
$sql_produtos = "SELECT id_produto, nome, preco, imagem FROM public.produtos";
$params = [];

if ($id_categoria_selecionada) {
    $sql_produtos .= " WHERE id_categoria = ?";
    $params[] = $id_categoria_selecionada;
}
$sql_produtos .= " ORDER BY nome ASC";

try {
    $stmtProd = $pdo->prepare($sql_produtos);
    $stmtProd->execute($params);
    $produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
}
?>

<main class="page-content">
<div class="container">
    <div class="shop-layout">
        <aside class="shop-sidebar">
            <h3>Categorias</h3>
            <nav class="category-nav">
                <ul>
                    <li>
                        <a href="produtos.php" class="<?= !$id_categoria_selecionada ? 'active' : '' ?>">
                            Todos os Produtos
                        </a>
                    </li>
                    <?php foreach ($categorias as $categoria): ?>
                        <li>
                            <a href="produtos.php?categoria=<?= $categoria['id_categoria'] ?>" class="<?= ($id_categoria_selecionada == $categoria['id_categoria']) ? 'active' : '' ?>">
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>
        </aside>

        <div class="shop-content">
            <h2 class="category-main-title"><?= htmlspecialchars($nome_categoria_selecionada) ?></h2>
            
            <?php if (empty($produtos)): ?>
                <div class="info-box">
                    <h3>Nenhum produto encontrado nesta categoria.</h3>
                    <p>Navegue por outras categorias ou volte em breve!</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($produtos as $produto): ?>
                        <article class="product-card">
    <div class="product-image-container">
        <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
            <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="product-image">
        </a>
    </div>
    <div class="product-info">
        <h4 class="product-name">
            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                <?= htmlspecialchars($produto['nome']) ?>
            </a>
        </h4>
        <p class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
        
        <div class="product-card-actions">
            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="btn btn-secondary">Detalhes</a>
            
            <form method="post" action="carrinho.php">
                <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                <button type="submit" name="adicionar" class="btn btn-primary">Adicionar</button>
            </form>
        </div>
    </div>
</article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>

<?php include 'includes/footer.php'; ?>