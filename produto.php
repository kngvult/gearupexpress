<?php
include 'includes/header.php';
include 'includes/conexao.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$stmt->execute([$id]);
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    echo "<p>Produto não encontrado.</p>";
} else {
?>
    <h1><?= $produto['nome'] ?></h1>
    <img src="assets/img/<?= $produto['imagem'] ?>" alt="<?= $produto['nome'] ?>">
    <p><?= $produto['descricao'] ?></p>
    <p><strong>Preço:</strong> R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
    <form action="carrinho.php" method="post">
        <input type="hidden" name="id" value="<?= $produto['id'] ?>">
        <button type="submit" name="adicionar">Adicionar ao carrinho</button>
    </form>
<?php
}
include 'includes/footer.php';
?>
