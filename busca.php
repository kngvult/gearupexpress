<?php
include 'includes/header.php'; 
// A conexão $pdo já está disponível a partir do header

// Inicializa as variáveis para evitar erros
$termo_busca = '';
$produtos = [];
$resultados_count = 0;

// Verifica se um termo de busca foi enviado via GET
if (!empty($_GET['termo'])) {
    // Limpa e armazena o termo de busca
    $termo_busca = trim($_GET['termo']);

    // Prepara a query para buscar no nome E na descrição dos produtos.
    // ILIKE faz uma busca case-insensitive (não diferencia maiúsculas de minúsculas).
    // Os '%' são wildcards, significando que o termo pode estar em qualquer parte do texto.
    $sql = "SELECT id_produto, nome, preco, imagem 
            FROM public.produtos 
            WHERE nome ILIKE ? OR descricao ILIKE ?";
    
    try {
        $stmt = $pdo->prepare($sql);
        // Executa a query passando o termo de busca formatado para o ILIKE
        $stmt->execute(['%' . $termo_busca . '%', '%' . $termo_busca . '%']);
        $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $resultados_count = $stmt->rowCount();
    } catch (PDOException $e) {
        error_log("Erro na busca: " . $e->getMessage());
        // Deixa os produtos como um array vazio em caso de erro
    }
}
?>

<main class="page-content">
<div class="container">
    
    <?php if (!empty($termo_busca)): ?>
        <h2 class="section-title">
            Resultados da busca por: "<?= htmlspecialchars($termo_busca) ?>"
        </h2>
        <p class="search-results-count">
            <?= $resultados_count ?> produto(s) encontrado(s).
        </p>

        <?php if (empty($produtos)): ?>
            <div class="info-box">
                <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16" style="color: #ced4da; margin-bottom: 20px;">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
                <h3>Nenhum resultado encontrado.</h3>
                <p>Tente usar termos de busca diferentes ou navegue por nossas categorias.</p>
                <a href="produtos.php" class="btn btn-primary">Ver todos os produtos</a>
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

    <?php else: ?>
        <div class="info-box">
            <h3>O que você está procurando?</h3>
            <p>Utilize a barra de pesquisa no topo da página para encontrar peças, pneus, acessórios e muito mais.</p>
        </div>
    <?php endif; ?>
    
</div>
</main>

<style>
    /* Estilo para a contagem de resultados. Adicione ao seu style.css se preferir. */
    .search-results-count {
        text-align: center;
        margin-top: -20px;
        margin-bottom: 40px;
        font-size: 1.1rem;
        color: var(--light-text);
    }
</style>

<?php include 'includes/footer.php'; ?>