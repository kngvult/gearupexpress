<?php
include_once 'includes/header.php';
include_once 'includes/conexao.php';

// Captura o ID da categoria da URL
$categoriaId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

try {
    if ($categoriaId > 0) {
        // Busca produtos da categoria específica
        $stmt = $pdo->prepare("
            SELECT p.id_produto, p.nome, p.descricao, p.preco, p.imagem, c.nome AS categoria
            FROM produtos p
            INNER JOIN categorias c ON p.id_categoria = c.id_categoria
            WHERE p.id_categoria = :id
            ORDER BY p.nome ASC
        ");
        $stmt->execute(['id' => $categoriaId]);
    } else {
        // Busca todos os produtos
        $stmt = $pdo->query("
            SELECT p.id_produto, p.nome, p.descricao, p.preco, p.imagem, c.nome AS categoria
            FROM produtos p
            INNER JOIN categorias c ON p.id_categoria = c.id_categoria
            ORDER BY p.nome ASC
        ");
    }
    
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erro ao buscar produtos: " . $e->getMessage());
}
?>

<main class="conteudo">
    <div class="container">
        <h1>
            <?php
            if ($categoriaId > 0 && !empty($produtos)) {
                echo "Categoria: " . htmlspecialchars($produtos[0]['categoria']);
            } elseif ($categoriaId > 0 && empty($produtos)) {
                echo "Categoria sem produtos";
            } else {
                echo "Todos os Produtos";
            }
            ?>
        </h1>

        <?php if (!empty($produtos)): ?>
            <div class="grid-produtos">
                <?php foreach ($produtos as $produto): ?>
                    <div class="card-produto">
                        <img src="uploads/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                        <h2><?= htmlspecialchars($produto['nome']) ?></h2>
                        <p class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="btn">Ver Detalhes</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Nenhum produto encontrado.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
