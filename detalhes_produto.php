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
                    <div class="price-details">
                        <p class="product-price-large">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        
                        <p class="product-installments-main">
                            ou em até <strong>3x</strong> de <strong>R$ <?= number_format($produto['preco'] / 3, 2, ',', '.') ?></strong>
                        </p>
                    </div>
                    <p class="product-stock <?= $produto['estoque'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $produto['estoque'] > 0 ? '✓ Em estoque' : '✗ Indisponível' ?>
                    </p>
                </div>
                
                <form method="post" action="carrinho_adicionar.php" id="add-to-cart-form" class="add-to-cart-form">
                    <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                    <div class="form-group quantity-selector">
                        <label for="quantidade">Quantidade:</label>
                        <input type="number" name="quantidade" id="quantidade" value="1" min="1" max="<?= htmlspecialchars($produto['estoque']) ?>">
                    </div>
                    <button type="submit" id="add-to-cart-btn" name="adicionar" class="btn btn-primary btn-lg" <?= $produto['estoque'] <= 0 ? 'disabled' : '' ?>>
                        <i class="fas fa-shopping-cart"></i> Adicionar ao Carrinho
                    </button>
                </form>
            </div>
        </div>

        <div class="product-info-section">
    <ul class="tabs-nav">
        <li><button class="tab-link active" data-tab="tab-descricao">Descrição</button></li>
        <li><button class="tab-link" data-tab="tab-especificacoes">Especificações</button></li>
        <li><button class="tab-link" data-tab="tab-avaliacoes">Avaliações</button></li>
    </ul>

    <div class="tabs-content">
        <div id="tab-descricao" class="tab-pane active">
            <p><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
        </div>
        <div id="tab-especificacoes" class="tab-pane">
            <table class="specs-table">
                <tr>
                    <td>Marca</td>
                    <td><?= htmlspecialchars($produto['marca'] ?: 'Não informada') ?></td>
                </tr>
                <tr>
                    <td>Código do Produto</td>
                    <td><?= htmlspecialchars($produto['codigo_produto'] ?: 'Não informado') ?></td>
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
        <div id="tab-avaliacoes" class="tab-pane">
            <div class="reviews-placeholder">
                <p>Ainda não há avaliações para este produto.</p>
                <span>Seja o primeiro a avaliar!</span>
            </div>
        </div>
    </div>
</div>
        <!-- Carrossel de Produtos Relacionados -->
        <?php if (!empty($produtos_relacionados)): ?>
    <div class="related-products-section">
        <h2 class="section-title">Produtos Relacionados</h2>

        <div class="swiper related-products-carousel">
            <div class="swiper-wrapper">
                <?php foreach ($produtos_relacionados as $relacionado): ?>
                    <div class="swiper-slide">
                        <div class="product-card">
                            <img src="assets/img/produtos/<?= htmlspecialchars($relacionado['imagem']) ?>" alt="<?= htmlspecialchars($relacionado['nome']) ?>">
                            
                            <h4><?= htmlspecialchars($relacionado['nome']) ?></h4>

                            <?php if (!empty($relacionado['preco_antigo'])): ?>
                                <div class="product-old-price">
                                    De: R$ <?= number_format($relacionado['preco_antigo'], 2, ',', '.') ?>
                                </div>
                            <?php endif; ?>

                            <div class="product-price">
                                Por: R$ <?= number_format($relacionado['preco'], 2, ',', '.') ?>
                            </div>

                            <div class="product-installments">
                                Em até 3x de R$ <?= number_format($relacionado['preco']/3, 2, ',', '.') ?>
                            </div>

                            <div class="product-actions">
                                <form method="post" action="carrinho.php" style="margin:0;">
                                    <input type="hidden" name="id_produto" value="<?= $relacionado['id_produto'] ?>">
                                    <input type="hidden" name="quantidade" value="1">
                                    <button type="submit" name="adicionar" class="btn btn-cart">
                                        Adicionar ao Carrinho
                                    </button>
                                </form>
                                <a href="detalhes_produto.php?id=<?= $relacionado['id_produto'] ?>" class="btn btn-details">
                                    Ver Detalhes
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>    
    </div>
<?php endif; ?>

    <?php endif; ?>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const tabId = this.dataset.tab;

            // Desativa todos os links e painéis
            tabLinks.forEach(l => l.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            // Ativa o link clicado e o painel correspondente
            this.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        });
    });
    const addToCartForm = document.getElementById('add-to-cart-form');
    const addToCartBtn = document.getElementById('add-to-cart-btn');

    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o recarregamento da página

            // Pega os dados do formulário
            const formData = new FormData(this);
            const originalButtonHtml = addToCartBtn.innerHTML;

            // Desativa o botão e mostra estado de "carregando"
            addToCartBtn.disabled = true;
            addToCartBtn.innerHTML = '<span class="spinner-sm"></span> Adicionando...';

            // Envia os dados para o backend com fetch API
            fetch('carrinho_adicionar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Feedback de sucesso
                    addToCartBtn.classList.add('btn-success');
                    addToCartBtn.innerHTML = '<i class="fas fa-check"></i> Adicionado!';
                    
                    // Atualiza o contador do carrinho no header
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.totalItensCarrinho;
                        cartBadge.style.display = 'flex'; // Garante que fica visível
                    } else {
                        // Se não houver badge, cria um
                        const cartLink = document.querySelector('a[href="carrinho.php"]');
                        const newBadge = document.createElement('span');
                        newBadge.className = 'cart-badge';
                        newBadge.textContent = data.totalItensCarrinho;
                        cartLink.appendChild(newBadge);
                    }
                } else {
                    // Feedback de erro
                    addToCartBtn.innerHTML = 'Erro ao adicionar';
                }

                // Volta ao estado original após 2 segundos
                setTimeout(() => {
                    addToCartBtn.disabled = false;
                    addToCartBtn.innerHTML = originalButtonHtml;
                    addToCartBtn.classList.remove('btn-success');
                }, 2000);
            })
            .catch(error => {
                console.error('Erro:', error);
                // Volta ao estado original em caso de erro de rede
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = originalButtonHtml;
            });
        });
    }
    const swiper = new Swiper('.related-products-carousel', {
        // Configurações do Swiper
        loop: false, // Se deve voltar ao início
        spaceBetween: 20, // Espaço entre os slides
        slidesPerView: 2, // Quantos slides visíveis em telas pequenas
        
        // Botões de navegação
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },

        // Responsividade: ajusta o número de slides por tamanho de tela
        breakpoints: {
            768: {
                slidesPerView: 3,
                spaceBetween: 30
            },
            992: {
                slidesPerView: 4,
                spaceBetween: 30
            }
        }
    });
});
</script>
<?php include 'includes/footer.php'; ?>