<?php
// Se a variável $pdo não existir (caso o footer seja chamado em uma página simples),
// incluímos a conexão para buscar as categorias.
if (!isset($pdo)) {
    include_once 'includes/conexao.php';
}

// Busca 4 categorias para exibir no rodapé
try {
    $stmtFooterCat = $pdo->query("SELECT id_categoria, nome FROM categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome LIMIT 4");
    $categoriasFooter = $stmtFooterCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoriasFooter = []; // Define como vazio em caso de erro
    error_log("Erro ao buscar categorias para o footer: " . $e->getMessage());
}
?>
<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
</head>
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            
            <div class="footer-section">
                <h4><i class="fas fa-car"></i> GearUP Express</h4>
                <div class="footer-desc">
                    <p>Sua paixão por carros, nossa dedicação em peças. Qualidade e confiança desde 2025.</p>
                </div>
                <ul class="footer-contact">
                    <li><i class="fas fa-map-marker-alt"></i> Manaus, AM</li>
                    <li><i class="fas fa-phone"></i> (92) 4002-8922</li>
                    <li><i class="fas fa-envelope"></i> contato@gearup.com.br</li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><i class="fas fa-toolbox"></i> Departamentos</h4>
                <div class="footer-desc">
                    <p>Principais categorias de peças e acessórios automotivos.</p>
                </div>
                <ul class="footer-links">
                    <?php foreach ($categoriasFooter as $cat): ?>
                        <li><a href="produtos.php?categoria=<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nome']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="produtos.php"><i class="fas fa-arrow-right"></i> Ver todos...</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><i class="fas fa-headset"></i> Atendimento</h4>
                <div class="footer-desc">
                    <p>Suporte dedicado para dúvidas, trocas e políticas.</p>
                </div>
                <ul class="footer-links">
                    <li><a href="#"><i class="fas fa-comments"></i> Fale Conosco</a></li>
                    <li><a href="#"><i class="fas fa-exchange-alt"></i> Trocas e Devoluções</a></li>
                    <li><a href="#"><i class="fas fa-shield-alt"></i> Política de Privacidade</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><i class="fas fa-globe-americas"></i> Redes Sociais</h4>
                <ul class="footer-social">
                    <li><a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a></li>
                    <li><a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a></li>
                    <li><a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a></li>
                    <li><a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a></li>
                </ul>
                
                <div class="payment-methods">
                    <h5><i class="fas fa-credit-card"></i> Formas de Pagamento</h5>
                    <div class="payment-icons">
                        <i class="fab fa-cc-visa" title="Visa"></i>
                        <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                        <i class="fab fa-cc-amex" title="American Express"></i>
                        <i class="fab fa-cc-paypal" title="PayPal"></i>
                        <i class="fab fa-pix" title="PIX"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?= date('Y') ?> GearUP Express. Todos os direitos reservados.</p>
                <div class="security-badges">
                    <span class="security-badge"><i class="fas fa-lock"></i> Site Seguro</span>
                    <span class="security-badge"><i class="fas fa-truck"></i> Entregas em Todo Brasil</span>
                </div>
            </div>
        </div>
    </div>
</footer>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Procura todos os formulários com a nossa classe especial
    const allAddToCartForms = document.querySelectorAll('.add-to-cart-form');

    allAddToCartForms.forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault(); // Impede o recarregamento da página

            const button = form.querySelector('button[type="submit"]');
            const originalButtonHtml = button.innerHTML;
            const formData = new FormData(form);

            // Desativa o botão e mostra "Adicionando..."
            button.disabled = true;
            button.innerHTML = 'Adicionando...';

            // Envia os dados para o nosso script PHP
            fetch('carrinho_adicionar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Feedback de sucesso
                    button.innerHTML = 'Adicionado!';
                    
                    // Atualiza o contador do carrinho no header
                    const cartBadge = document.querySelector('.cart-badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.totalItensCarrinho;
                        if (data.totalItensCarrinho > 0) {
                            cartBadge.style.display = 'flex';
                        }
                    } else if (data.totalItensCarrinho > 0) {
                        const cartLink = document.querySelector('a[href="carrinho.php"]');
                        if(cartLink) {
                            const newBadge = document.createElement('span');
                            newBadge.className = 'cart-badge';
                            newBadge.textContent = data.totalItensCarrinho;
                            cartLink.appendChild(newBadge);
                        }
                    }
                } else {
                    button.innerHTML = 'Erro!';
                }
                // Volta ao estado original após 1.5 segundos
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = originalButtonHtml;
                }, 1500);
            })
            .catch(error => {
                console.error('Erro no AJAX:', error);
                button.disabled = false;
                button.innerHTML = originalButtonHtml;
            });
        });
    });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<!--<script>
var swiper = new Swiper(".mySwiper", {
    slidesPerView: 5,
    spaceBetween: 20,
    navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
    },
    loop: true,
});
</script> -->
</body>
</html>