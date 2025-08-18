<?php
// O header.php já inicia o HTML, a sessão e a conexão com o banco.
include 'includes/header.php'; 

// A conexão $pdo já está disponível a partir do header.
// Vamos buscar apenas os produtos em destaque para a página inicial.
try {
    // Busca 8 produtos mais recentes para um grid mais completo.
    $stmtProd = $pdo->query("SELECT id_produto, nome, preco, imagem FROM produtos ORDER BY id_produto DESC LIMIT 8");
    $produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $produtos = []; // Em caso de erro, define um array vazio.
    error_log("Erro ao buscar produtos em destaque: " . $e->getMessage());
}
?>

<main>

    <section class="hero-banner">
        <div class="hero-slides-container">
            <div class="hero-slide active" style="background-image: url('assets/img/banner/banner-freios.avif');">
                <div class="hero-text">
                    <h1>Até 30% OFF em Freios</h1>
                    <p>Garanta a segurança do seu carro com as melhores marcas de discos e pastilhas.</p>
                    <a href="produtos.php?categoria=2" class="btn btn-primary">Ver Ofertas</a>
                </div>
            </div>
            <div class="hero-slide" style="background-image: url('assets/img/banner/banner-pneus.avif');">
                <div class="hero-text">
                    <h1>Pneus Novos, Preços Velhos</h1>
                    <p>As melhores condições para você trocar os pneus do seu veículo. Confira!</p>
                    <a href="produtos.php?categoria=6" class="btn btn-primary">Conferir Pneus</a>
                </div>
            </div>
            <div class="hero-slide" style="background-image: url('assets/img/banner/banner-oleo.avif');">
                <div class="hero-text">
                    <h1>Troca de Óleo é Aqui!</h1>
                    <p>Os melhores lubrificantes e aditivos para manter seu motor sempre novo.</p>
                    <a href="produtos.php?categoria=15" class="btn btn-primary">Ver Lubrificantes</a>
                </div>
            </div>
        </div>

        <button class="hero-nav prev" aria-label="Slide Anterior">&#10094;</button>
        <button class="hero-nav next" aria-label="Próximo Slide">&#10095;</button>

        <div class="hero-indicators">
            <span class="dot active" data-slide-to="0"></span>
            <span class="dot" data-slide-to="1"></span>
            <span class="dot" data-slide-to="2"></span>
        </div>
    </section>

    <div class="container">
        <section class="featured-products-section">
            <h2 class="section-title">Produtos em Destaque</h2>

            <?php if (empty($produtos)): ?>
                <div class="info-box">
                    <h3>Nenhum produto em destaque no momento.</h3>
                    <p>Estamos preparando novidades para você. Volte em breve!</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach($produtos as $produto): ?>
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
        </section>
    </div>

</main>

<?php include 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.hero-slide');
    if (slides.length === 0) return; // Não executa se não houver slides

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
        slideInterval = setInterval(nextSlide, 5000); // Troca a cada 5 segundos
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

    showSlide(currentSlide);
    startSlideShow();
});
</script>