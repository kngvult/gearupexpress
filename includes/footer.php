<?php

if (!isset($pdo)) {
    include_once 'session_config.php';
}

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

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
var swiper = new Swiper(".mySwiper", {
    slidesPerView: 5,
    spaceBetween: 20,
    navigation: {
    nextEl: ".swiper-button-next",
    prevEl: ".swiper-button-prev",
    },
    loop: true,
});
</script>
<script>
// =====================================================
    // FUNÇÃO GLOBAL DE ATUALIZAÇÃO DO BADGE DO CARRINHO
    // =================================================
    function updateCartBadge(newCount) {
        // 1. Encontra o badge no header
        const cartBadge = document.querySelector('.cart-badge');
        
        if (cartBadge) {
            // 2. Converte para número
            const count = parseInt(newCount) || 0;
            
            // 3. Atualiza o texto do contador
            cartBadge.textContent = count;
            
            // 4. Adiciona ou remove a classe 'visible'
            if (count > 0) {
                cartBadge.classList.add('visible');
            } else {
                cartBadge.classList.remove('visible');
            }
        }
    }
    


    // ===============================================
    // INÍCIO DO SCRIPT QUANDO A PÁGINA CARREGA
    // ===============================================
    document.addEventListener('DOMContentLoaded', function() {

        // --- LÓGICA DE ADICIONAR AO CARRINHO (AJAX) ---
        document.querySelectorAll('.ajax-add-to-cart-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                const formData = new FormData(this);
                
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                submitBtn.disabled = true;
                
                fetch('carrinho_adicionar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        submitBtn.innerHTML = '<i class="fas fa-check"></i> Adicionado';
                        submitBtn.classList.add('btn-success');

                        if (data.totalItensCarrinho !== undefined) {
                            updateCartBadge(data.totalItensCarrinho);
                        }
                        
                    } else {
                        submitBtn.innerHTML = '<i class="fas fa-times"></i> Erro!';
                        submitBtn.classList.add('btn-error');
                    }
                    
                    setTimeout(() => {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-success', 'btn-error');
                    }, 2000);
                    
                })
                .catch(error => {
                    console.error('Erro no AJAX do carrinho:', error);
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        });
    });
    // --- WISHLIST ---
document.addEventListener('DOMContentLoaded', function(){
document.body.addEventListener('click', function(e) {
        
        const btn = e.target.closest('.wishlist-toggle-btn');
        
        if (btn) {
            e.preventDefault(); // Impede qualquer ação padrão
            
            // 1. Verificar se usuário está logado (usando a var global)
            if (!window.isUserLoggedIn) {
                // Você pode trocar o alert por um modal se preferir
                alert('Você precisa estar logado para adicionar itens à lista de desejos.');
                window.location.href = 'login.php?redirect=' + window.location.pathname;
                return;
            }

            const productId = btn.dataset.productId;
            const icon = btn.querySelector('i');
            
            // 2. Determinar a ação (adicionar ou remover)
            const isAdding = !window.wishlistProductIds.has(parseInt(productId));
            const acao = isAdding ? 'adicionar' : 'remover';

            const formData = new FormData();
            formData.append('id_produto', productId);
            formData.append('acao', acao);

            // 3. Efeito visual de "carregando"
            btn.disabled = true;
            icon.classList.remove('fas', 'far', 'fa-heart');
            icon.classList.add('fas', 'fa-spinner', 'fa-spin');

            // 4. Fazer a requisição AJAX para wishlist.php
            fetch('wishlist.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                const wishlistCounter = document.querySelector('.wishlist-count');
                let currentCount = parseInt(wishlistCounter.textContent) || 0;
                
                if (data.success) {
                    // 5. Atualizar o estado visual do botão e contador
                    if (isAdding) {
                        window.wishlistProductIds.add(parseInt(productId));
                        btn.classList.add('active');
                        btn.title = 'Remover dos favoritos';
                        icon.classList.remove('fa-spinner', 'fa-spin');
                        icon.classList.add('fas', 'fa-heart'); // Coração preenchido
                        wishlistCounter.textContent = currentCount + 1;
                    } else {
                        window.wishlistProductIds.delete(parseInt(productId));
                        btn.classList.remove('active');
                        btn.title = 'Adicionar aos favoritos';
                        icon.classList.remove('fa-spinner', 'fa-spin');
                        icon.classList.add('far', 'fa-heart'); // Coração vazio
                        wishlistCounter.textContent = Math.max(0, currentCount - 1);
                    }
                } else {
                    alert('Erro: ' + (data.message || 'Tente novamente.'));
                    // Reverte o ícone em caso de erro
                    icon.classList.remove('fa-spinner', 'fa-spin');
                    icon.classList.add(isAdding ? 'far' : 'fas', 'fa-heart');
                }
            })
            .catch(error => {
                console.error('Fetch Error:', error);
                alert('Um erro de conexão ocorreu.');
            })
            .finally(() => {
                // 6. Reabilitar o botão
                btn.disabled = false;
            });
        }
    });
});
</script>
</body>
</html>