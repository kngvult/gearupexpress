<?php
include 'includes/header.php';
include 'includes/conexao.php';

// Busca todos os produtos
$stmt = $pdo->query("SELECT id_produto, nome, preco, imagem FROM produtos ORDER BY nome");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Produtos</h2>

<div class="produtos-lista" style="display:flex; flex-wrap: wrap; gap: 20px;">
<?php foreach ($produtos as $produto): ?>
    <div class="produto-item" style="border:1px solid #ccc; padding:10px; width:200px;">
        <a href="produto.php?id=<?= $produto['id_produto'] ?>">
            <img src="assets/img/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" style="width:100%; height:auto;">
            <h3><?= htmlspecialchars($produto['nome']) ?></h3>
        </a>
        <p>Preço: R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
        <form method="post" action="carrinho.php">
            <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
            <input type="number" name="quantidade" value="1" min="1" style="width:50px;">
            <button type="submit" name="adicionar">Adicionar ao carrinho</button>
        </form>
    </div>
<?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
