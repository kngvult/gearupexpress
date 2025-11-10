<?php
include 'includes/header.php'; 

// Buscar produtos em destaque, mais vendidos e categorias
try {
    // Sub-query de vendas otimizada
    $sqlVendas = "(SELECT COALESCE(SUM(ip.quantidade), 0) 
                    FROM itens_pedido ip 
                    WHERE ip.id_produto = p.id_produto)";

    // Produtos em Destaque (Mais recentes com estoque)
    $stmtDestaque = $pdo->query("
        SELECT 
            p.id_produto, p.nome, p.preco, p.imagem, p.estoque, p.marca, 
            $sqlVendas as vendas
        FROM produtos p 
        WHERE p.estoque > 0 
        ORDER BY p.id_produto DESC 
        LIMIT 9
    ");
    $produtosDestaque = $stmtDestaque->fetchAll(PDO::FETCH_ASSOC);
    
    // Produtos Mais Vendidos
    $stmtMaisVendidos = $pdo->query("
        SELECT 
        p.id_produto, 
        p.nome, 
        p.preco, 
        p.imagem, 
        p.estoque, 
        p.marca,
        COALESCE(SUM(i.quantidade), 0) AS vendas
    FROM produtos p
    LEFT JOIN itens_pedido i ON p.id_produto = i.id_produto
    LEFT JOIN pedidos ped ON ped.id_pedido = i.id_pedido
    WHERE p.estoque > 0
        AND ped.status IN ('enviado', 'entregue')
    GROUP BY p.id_produto, p.nome, p.preco, p.imagem, p.estoque, p.marca
    ORDER BY vendas DESC, p.id_produto DESC
    LIMIT 9
    ");
    $produtosMaisVendidos = $stmtMaisVendidos->fetchAll(PDO::FETCH_ASSOC);
    
    // Categorias para quick access
    $stmtCat = $pdo->query("SELECT id_categoria, nome FROM categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome LIMIT 6");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $produtosDestaque = [];
    $produtosMaisVendidos = [];
    $categorias = [];
    error_log("Erro ao buscar dados para página inicial: " . $e->getMessage());
}

// Função para determinar badge baseado em vendas
function getProductBadge($vendas, $estoque) {
    if ($estoque <= 0) {
        return ['type' => 'out-of-stock', 'text' => 'Esgotado'];
    } elseif ($vendas > 10) { 
        return ['type' => 'popular', 'text' => 'Mais Vendido'];
    } elseif ($vendas > 5) {
        return ['type' => 'hot', 'text' => 'Em Alta'];
    } elseif ($vendas > 0) {
        return ['type' => 'normal', 'text' => '']; // Para produtos com poucas vendas
    } else {
        return ['type' => 'new', 'text' => 'Novo'];
    }
}
?>

<main class="home-page">
    <!-- Hero Banner -->
    <section class="hero-banner">
        <div class="hero-slides-container">
            <div class="hero-slide active" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/banner/banner-freios.avif');">
                <div class="container">
                    <div class="hero-content">
                        <div class="hero-badge">Oferta Especial</div>
                        <h1 class="hero-title">Até 30% OFF em Freios</h1>
                        <p class="hero-description">Garanta a segurança do seu carro com as melhores marcas de discos e pastilhas. Qualidade e preço imbatível!</p>
                        <div class="hero-actions">
                            <a href="produtos.php?categoria=2" class="btn btn-primary btn-hero">
                                <i class="fas fa-bolt"></i> Ver Ofertas
                            </a>
                            <a href="produtos.php" class="btn btn-outline-white">
                                <i class="fas fa-th-large"></i> Ver Todos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="hero-slide" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/banner/banner-pneus.avif');">
                <div class="container">
                    <div class="hero-content">
                        <div class="hero-badge">Novidade</div>
                        <h1 class="hero-title">Pneus Novos, Preços Imbatíveis</h1>
                        <p class="hero-description">As melhores condições para você trocar os pneus do seu veículo. Marcas premium com garantia extendida.</p>
                        <div class="hero-actions">
                            <a href="produtos.php?categoria=6" class="btn btn-primary btn-hero">
                                <i class="fas fa-car"></i> Conferir Pneus
                            </a>
                            <a href="produtos.php" class="btn btn-outline-white">
                                <i class="fas fa-th-large"></i> Ver Todos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-slide" style="background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('assets/img/banner/banner-oleo.avif');">
                <div class="container">
                    <div class="hero-content">
                        <div class="hero-badge">Novidade</div>
                        <h1 class="hero-title">Troca de Óleo é Aqui!</h1>
                        <p class="hero-description">Mantenha seu motor protegido com nossos óleos de alta performance. Qualidade que você confia!</p>
                        <div class="hero-actions">
                            <a href="produtos.php?categoria=15" class="btn btn-primary btn-hero">
                                <i class="fas fa-oil-can"></i> Conferir Óleos
                            </a>
                            <a href="produtos.php" class="btn btn-outline-white">
                                <i class="fas fa-th-large"></i> Ver Todos
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <button class="hero-nav prev" aria-label="Slide Anterior">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-nav next" aria-label="Próximo Slide">
            <i class="fas fa-chevron-right"></i>
        </button>

        <div class="hero-indicators">
            <span class="dot active" data-slide-to="0"></span>
            <span class="dot" data-slide-to="1"></span>
            <span class="dot" data-slide-to="2"></span>
        </div>
    </section>

    <!-- Seção de Produtos em Destaque -->
<section class="products-section featured-products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Produtos em Destaque</h2>
            <p class="section-subtitle">As novidades que chegaram para revolucionar</p>
            <a href="produtos.php" class="section-link">
                Ver Todos <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($produtosDestaque)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <h3>Nenhum produto em destaque no momento</h3>
                <p>Estamos preparando novidades incríveis para você. Volte em breve!</p>
                <a href="produtos.php" class="btn btn-primary">Explorar Produtos</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach($produtosDestaque as $produto): 
                    $badge = getProductBadge($produto['vendas'] ?? 0, $produto['estoque']);
                ?>
                    <article class="product-card">
                        <div class="product-image">
                            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="product-image-link">
                                <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                                    alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                    class="product-image"
                                    loading="lazy">
                            </a>
                            <span class="product-badge <?= $badge['type'] ?>"><?= $badge['text'] ?></span>
                            
                            <?php
                                // Verifica se o ID do produto atual está na lista de favoritos do usuário
                                $isInWishlist = in_array($produto['id_produto'], $wishlistProductIds);
                            ?>

                            <button class="wishlist-btn" 
                                    data-product-id="<?= $produto['id_produto'] ?>" 
                                    title="Adicionar aos favoritos">
                                <i class="far fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="product-info">
                            <div class="product-meta">
                                <?php if (!empty($produto['marca'])): ?>
                                    <span class="product-brand"><?= htmlspecialchars($produto['marca']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($produto['categoria_nome'])): ?>
                                    <span class="product-category"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="product-name">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                    <?= htmlspecialchars($produto['nome']) ?>
                                </a>
                            </h3>
                            
                            <div class="product-price-section">
                                <p class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                                <div class="product-installments">
                                    em até 3x de R$ <?= number_format($produto['preco'] / 3, 2, ',', '.') ?>
                                </div>
                            </div>
                            
                            <!-- NOVO: Rating e Stock lado a lado -->
                            <div class="product-meta-info">
                                <div class="product-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span class="rating-count">(<?= rand(10, 50) ?>)</span>
                                </div>
                                
                                <div class="product-stock <?= $produto['estoque'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <?php if ($produto['estoque'] > 0): ?>
                                        <i class="fas fa-check-circle"></i> Em estoque
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Indisponível
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="product-card-actions">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                                <?php if ($produto['estoque'] > 0): ?>
                                    <form method="post" action="carrinho_adicionar.php" class="ajax-add-to-cart-form">
                                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                        <input type="hidden" name="quantidade" value="1"> 
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-cart-plus"></i> Adicionar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>
                                        <i class="fas fa-ban"></i> Indisponível
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Seção de Produtos Mais Vendidos -->
<section class="products-section popular-products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Mais Vendidos</h2>
            <p class="section-subtitle">Os produtos preferidos dos nossos clientes</p>
            <a href="produtos.php?ordenar=popular" class="section-link">
                Ver Todos <i class="fas fa-arrow-right"></i>
            </a>
        </div>

        <?php if (empty($produtosMaisVendidos)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Ainda não temos dados de vendas</h3>
                <p>Em breve teremos os produtos mais populares disponíveis aqui.</p>
                <a href="produtos.php" class="btn btn-primary">Explorar Produtos</a>
            </div>
        <?php else: ?>
            <div class="product-grid">
            <?php 
            // Garantir que temos no máximo 9 produtos
            $produtosExibidos = array_slice($produtosMaisVendidos, 0, 9);
            
            foreach($produtosExibidos as $index => $produto): 
                $badge = getProductBadge($produto['vendas'] ?? 0, $produto['estoque']);
                $posicao = $index + 1;
            ?>
                <article class="product-card">
                    <div class="product-image">
                        <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="product-image-link">
                            <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                                alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                class="product-image"
                                loading="lazy">
                        </a>
                        <span class="product-badge <?= $badge['type'] ?>">
                            <?php if ($badge['type'] == 'popular'): ?>
                                <i class="fas fa-fire"></i> 
                            <?php elseif ($badge['type'] == 'hot'): ?>
                                <i class="fas fa-bolt"></i>
                            <?php endif; ?>
                            <?= $badge['text'] ?>
                        </span>
                        
                        <div class="sales-count ranking-badge rank-<?= $posicao ?>">
                            <i class="fas fa-trophy"></i>
                            <?= $posicao ?>º em vendas
                        </div>
                    </div>
                        
                        <div class="product-info">
                            <div class="product-meta">
                                <?php if (!empty($produto['marca'])): ?>
                                    <span class="product-brand"><?= htmlspecialchars($produto['marca']) ?></span>
                                <?php endif; ?>
                                <?php if (!empty($produto['categoria_nome'])): ?>
                                    <span class="product-category"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="product-name">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                    <?= htmlspecialchars($produto['nome']) ?>
                                </a>
                            </h3>
                            
                            <div class="product-price-section">
                                <p class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                                <div class="product-installments">
                                    em até 3x de R$ <?= number_format($produto['preco'] / 3, 2, ',', '.') ?>
                                </div>
                            </div>
                            
                            <!-- Rating e Stock lado a lado -->
                            <div class="product-meta-info">
                                <div class="product-rating">
                                    <div class="stars">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                    </div>
                                    <span class="rating-count">(<?= rand(10, 50) ?>)</span>
                                </div>
                                
                                <div class="product-stock <?= $produto['estoque'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <?php if ($produto['estoque'] > 0): ?>
                                        <i class="fas fa-check-circle"></i> Em estoque
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Indisponível
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="product-card-actions">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="btn btn-outline">
                                    <i class="fas fa-eye"></i> Detalhes
                                </a>
                                <?php if ($produto['estoque'] > 0): ?>
                                    <form method="post" action="carrinho_adicionar.php" class="ajax-add-to-cart-form">
                                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                        <input type="hidden" name="quantidade" value="1"> 
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-cart-plus"></i> Adicionar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-disabled" disabled>
                                        <i class="fas fa-ban"></i> Indisponível
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

    <!-- Seção de Vantagens -->
    <section class="benefits-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Por que Escolher a GearUP Express?</h2>
                <p class="section-subtitle">Tudo que você precisa em um só lugar</p>
            </div>
            
            <div class="benefits-grid">
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Entrega Rápida</h3>
                    <p>Receba seu pedido em até 48h na região metropolitana</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Compra Segura</h3>
                    <p>Seus dados protegidos com criptografia de última geração</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <h3>Parcele em até 4x</h3>
                    <p>Parcele suas compras no cartão com juros baixos</p>
                </div>
                
                <div class="benefit-card">
                    <div class="benefit-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>Suporte 24/7</h3>
                    <p>Nossa equipe está sempre pronta para te ajudar</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="newsletter-section">
        <div class="container">
            <div class="newsletter-content">
                <div class="newsletter-text">
                    <h2>Fique por Dentro das Ofertas</h2>
                    <p>Receba descontos exclusivos e seja o primeiro a saber sobre novidades</p>
                </div>
                <form class="newsletter-form">
                    <div class="input-group">
                        <input type="email" placeholder="Insira seu e-mail" required>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Assinar
                        </button>
                    </div>
                    <div class="form-note">
                        <i class="fas fa-lock"></i>
                        Seus dados estão protegidos. Não compartilhamos suas informações.
                    </div>
                </form>
            </div>
        </div>
    </section>

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Hero Slider (código existente mantido)
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length === 0) return;

    const dots = document.querySelectorAll('.dot');
    const prevBtn = document.querySelector('.hero-nav.prev');
    const nextBtn = document.querySelector('.hero-nav.next');
    
    let currentSlide = 0;
    let slideInterval;

    function showSlide(n) {
        if (n >= slides.length) { currentSlide = 0; }
        if (n < 0) { currentSlide = slides.length - 1; }
        
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        slides[currentSlide].classList.add('active');
        dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        currentSlide++;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide--;
        showSlide(currentSlide);
    }
    
    function startSlideShow() {
        slideInterval = setInterval(nextSlide, 6000);
    }

    function resetInterval() {
        clearInterval(slideInterval);
        startSlideShow();
    }

    nextBtn.addEventListener('click', () => {
        nextSlide();
        resetInterval();
    });

    prevBtn.addEventListener('click', () => {
        prevSlide();
        resetInterval();
    });

    dots.forEach(dot => {
        dot.addEventListener('click', (e) => {
            const slideIndex = parseInt(e.target.dataset.slideTo);
            currentSlide = slideIndex;
            showSlide(currentSlide);
            resetInterval();
        });
    });

    // Pausar slider no hover
    const heroBanner = document.querySelector('.hero-banner');
    heroBanner.addEventListener('mouseenter', () => {
        clearInterval(slideInterval);
    });
    
    heroBanner.addEventListener('mouseleave', () => {
        startSlideShow();
    });

    showSlide(currentSlide);
    startSlideShow();

    // Newsletter form 
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Simular cadastro
            this.innerHTML = `
                <div class="newsletter-success">
                    <i class="fas fa-check-circle"></i>
                    <h3>Inscrição realizada com sucesso!</h3>
                    <p>Em breve você receberá nossas melhores ofertas no email ${email}</p>
                </div>
            `;
        });
    }

    // Animação de entrada das seções
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.products-section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });
});

// Função Wishlist
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const icon = this.querySelector('i');
        
        console.log('Clicou no wishlist, produto:', productId);
        
        // Alternar estado visual
        const isActive = icon.classList.contains('fas');
        
        if (isActive) {
            console.log('Removendo da wishlist');
            icon.classList.replace('fas', 'far');
            icon.style.color = '';
            removeFromWishlist(productId);
        } else {
            console.log('Adicionando à wishlist');
            icon.classList.replace('far', 'fas');
            icon.style.color = '#e74c3c';
            addToWishlist(productId);
        }
    });
});

function addToWishlist(productId) {
    const formData = new FormData();
    formData.append('id_produto', productId);
    formData.append('acao', 'adicionar');
    
    console.log('Enviando requisição para adicionar...');
    
    fetch('wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Resposta recebida:', response);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        if (!data.success) {
            console.error('Erro ao adicionar:', data.message);
            // Reverter visual se erro
            const btn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
            const icon = btn.querySelector('i');
            icon.classList.replace('fas', 'far');
            icon.style.color = '';
        } else {
            console.log('Produto adicionado com sucesso!');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
    });
}

function removeFromWishlist(productId) {
    const formData = new FormData();
    formData.append('id_produto', productId);
    formData.append('acao', 'remover');
    
    fetch('wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta remoção:', data);
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>