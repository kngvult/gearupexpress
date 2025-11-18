<?php
include 'includes/header.php'; 

// Inicializa as variáveis para evitar erros
$termo_busca = '';
$produtos = [];
$resultados_count = 0;

// Verifica se um termo de busca foi enviado via GET
if (!empty($_GET['termo'])) {
    // Limpa e armazena o termo de busca
    $termo_busca = trim($_GET['termo']);

    // CORREÇÃO: Incluir a coluna estoque na query
    $sql = "SELECT id_produto, nome, preco, imagem, COALESCE(estoque, 0) AS estoque 
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
                    <?php 
                    // CORREÇÃO: Garantir que estoque seja um inteiro
                    $estoque = (int)($produto['estoque'] ?? 0); 
                    ?>
                    <article class="product-card">
                        <div class="product-image-container">
                            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                <img src="<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="product-image">
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
                                <?php if ($estoque > 0): ?>
                                    <form method="post" action="carrinho_adicionar.php" class="add-to-cart-form">
                                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                        <input type="hidden" name="quantidade" value="1">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-cart-plus"></i> Adicionar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>Indisponível</button>
                                <?php endif; ?>
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

<script>
// AJAX para adicionar ao carrinho na página de busca
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Feedback visual
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adicionando...';
            submitBtn.disabled = true;
            
            fetch('carrinho_adicionar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                    submitBtn.classList.add('btn-success');
                    
                    // Atualizar contador do carrinho se existir
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && data.totalItensCarrinho !== undefined) {
                        cartCount.textContent = data.totalItensCarrinho;
                        cartCount.classList.add('pulse');
                        setTimeout(() => cartCount.classList.remove('pulse'), 500);
                    }
                    
                    // Redirecionar para carrinho após sucesso
                    setTimeout(() => {
                        window.location.href = 'carrinho.php';
                    }, 1000);
                    
                } else {
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erro';
                    submitBtn.classList.add('btn-error');
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.classList.remove('btn-error');
                        submitBtn.disabled = false;
                    }, 2000);
                    
                    alert(data.message || 'Erro ao adicionar produto ao carrinho');
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Erro';
                submitBtn.classList.add('btn-error');
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.classList.remove('btn-error');
                    submitBtn.disabled = false;
                }, 2000);
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>