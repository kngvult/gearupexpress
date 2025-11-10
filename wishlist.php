<?php
session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    $_SESSION['redirect_url'] = 'wishlist.php';
    header('Location: login.php');
    exit;
}

$usuarioLogado = $_SESSION['usuario']['nome'] ?? null;
$idUsuarioLogado = $_SESSION['usuario']['id'] ?? null;

// Adicionar/remover produto da wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_produto'])) {
    $id_produto = $_POST['id_produto'];
    $acao = $_POST['acao'] ?? 'adicionar';

    include 'includes/conexao.php';
    
    try {
        if ($acao === 'adicionar') {
            // Verificar se já está na wishlist
            $stmt = $pdo->prepare("SELECT id FROM wishlist WHERE id_usuario = ? AND id_produto = ?");
            $stmt->execute([$idUsuarioLogado, $id_produto]);
            
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO wishlist (id_usuario, id_produto) VALUES (?, ?)");
                $stmt->execute([$idUsuarioLogado, $id_produto]);
            }
        } elseif ($acao === 'remover') {
            $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id_usuario = ? AND id_produto = ?");
            $stmt->execute([$idUsuarioLogado, $id_produto]);
        }
        
        echo json_encode(['success' => true]);
        exit;
        
    } catch (PDOException $e) {
        error_log("Erro wishlist: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

include 'includes/header.php';
include 'includes/conexao.php';

// Buscar produtos da wishlist
try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id_produto, p.nome, p.preco, p.imagem, p.estoque, p.marca, p.descricao,
            c.nome as categoria
        FROM wishlist w
        INNER JOIN produtos p ON w.id_produto = p.id_produto
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE w.id_usuario = ?
        ORDER BY w.data_adicao DESC
    ");
    $stmt->execute([$idUsuarioLogado]);
    $produtosWishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $produtosWishlist = [];
    error_log("Erro ao buscar wishlist: " . $e->getMessage());
}

// Função para calcular parcelas
function calcularParcelas($preco, $parcelas = 3) {
    return number_format($preco / $parcelas, 2, ',', '.');
}
?>

<main class="wishlist-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Minha Lista de Desejos</h1>
            <p class="page-subtitle">Seus produtos favoritados em um só lugar</p>
        </div>

        <?php if (empty($produtosWishlist)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Sua lista de desejos está vazia</h3>
                <p>Comece a adicionar produtos aos seus favoritos clicando no coração ❤️</p>
                <div class="empty-actions">
                    <a href="produtos.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Explorar Produtos
                    </a>
                    <a href="index.php" class="btn btn-outline">
                        <i class="fas fa-home"></i> Voltar para Home
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="wishlist-stats">
                <div class="stats-card">
                    <i class="fas fa-heart"></i>
                    <div class="stats-info">
                        <span class="stats-number"><?= count($produtosWishlist) ?></span>
                        <span class="stats-label">Produtos Favoritos</span>
                    </div>
                </div>
                <div class="stats-card">
                    <i class="fas fa-tags"></i>
                    <div class="stats-info">
                        <span class="stats-number">R$ <?= number_format(array_sum(array_column($produtosWishlist, 'preco')), 2, ',', '.') ?></span>
                        <span class="stats-label">Valor Total</span>
                    </div>
                </div>
            </div>

            <div class="wishlist-products">
                <?php foreach($produtosWishlist as $produto): ?>
                    <div class="wishlist-item" data-product-id="<?= $produto['id_produto'] ?>">
                        <div class="product-image">
                            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                                    alt="<?= htmlspecialchars($produto['nome']) ?>"
                                    loading="lazy">
                            </a>
                        </div>
                        
                        <div class="product-details">
                            <?php if (!empty($produto['marca'])): ?>
                                <div class="product-brand"><?= htmlspecialchars($produto['marca']) ?></div>
                            <?php endif; ?>
                            
                            <h3 class="product-name">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                    <?= htmlspecialchars($produto['nome']) ?>
                                </a>
                            </h3>
                            
                            <?php if (!empty($produto['categoria'])): ?>
                                <div class="product-category">
                                    <i class="fas fa-tag"></i> <?= htmlspecialchars($produto['categoria']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-pricing">
                            <div class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                            <div class="product-installments">
                                em até 3x de R$ <?= calcularParcelas($produto['preco']) ?>
                            </div>
                            
                            <div class="product-stock <?= $produto['estoque'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                                <i class="fas <?= $produto['estoque'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?>"></i>
                                <?= $produto['estoque'] > 0 ? 'Em estoque' : 'Indisponível' ?>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn-remove-wishlist" title="Remover dos favoritos">
                                <i class="fas fa-heart-broken"></i> Remover
                            </button>
                            
                            <?php if ($produto['estoque'] > 0): ?>
                                <form method="post" action="carrinho_adicionar.php" class="ajax-add-to-cart-form" data-submitting="false">
                                    <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                    <input type="hidden" name="quantidade" value="1">
                                    <button type="button" class="btn btn-primary ajax-add-to-cart-btn">
                                        <i class="fas fa-cart-plus"></i> Adicionar ao Carrinho
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>
                                    <i class="fas fa-ban"></i> Indisponível
                                </button>
                            <?php endif; ?>
                            
                            <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Ver Detalhes
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="wishlist-actions">
                <button class="btn btn-outline" id="clear-wishlist">
                    <i class="fas fa-trash"></i> Limpar Lista de Desejos
                </button>
                <a href="produtos.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Continuar Comprando
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Remover item da wishlist
    document.querySelectorAll('.btn-remove-wishlist').forEach(btn => {
        btn.addEventListener('click', function() {
            const item = this.closest('.wishlist-item');
            const productId = item.dataset.productId;
            
            removeFromWishlist(productId, item);
        });
    });
    
    // Limpar toda a wishlist
    document.getElementById('clear-wishlist')?.addEventListener('click', function() {
        if (confirm('Tem certeza que deseja limpar toda sua lista de desejos?')) {
            const items = document.querySelectorAll('.wishlist-item');
            items.forEach(item => {
                const productId = item.dataset.productId;
                removeFromWishlist(productId, item);
            });
        }
    });
    
    function removeFromWishlist(productId, element) {
        const formData = new FormData();
        formData.append('id_produto', productId);
        formData.append('acao', 'remover');
        
        fetch('wishlist.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Animação de remoção
                element.style.opacity = '0';
                element.style.transform = 'translateX(100px)';
                element.style.transition = 'all 0.25s ease';
                
                setTimeout(() => {
                    location.reload();
                }, 250);
            } else {
                alert('Erro ao remover produto: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao remover produto da lista de desejos');
        });
    }
    
    
    // AJAX para adicionar ao carrinho
    document.querySelectorAll('.ajax-add-to-cart-form').forEach(form => {
        const btn = form.querySelector('.ajax-add-to-cart-btn');
        if (!btn) return;

        btn.addEventListener('click', function(e) {
            
            if (form.dataset.submitting === 'true') return;
            form.dataset.submitting = 'true';
            
            const formData = new FormData(form);
            const submitBtn = btn;
            const originalHTML = submitBtn.innerHTML;

            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;
            
            fetch(form.action, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) throw new Error('Resposta inválida do servidor');
                return response.json();
            })
            .then(data => {
                if (data && data.success) {
                    submitBtn.innerHTML = '<i class="fas fa-check"></i>';
                    submitBtn.classList.add('btn-success');

                    // Atualizar contador do carrinho se enviado pelo servidor
                    const cartCount = document.querySelector('.cart-count');
                    if (cartCount && typeof data.totalItensCarrinho !== 'undefined') {
                        cartCount.textContent = data.totalItensCarrinho;
                    }
                } else {
                    submitBtn.innerHTML = '<i class="fas fa-times"></i>';
                    submitBtn.classList.add('btn-error');
                    console.error('Adicionar ao carrinho falhou:', data && data.message ? data.message : data);
                }
            })
            .catch(error => {
                console.error('Erro ao adicionar ao carrinho:', error);
                submitBtn.innerHTML = '<i class="fas fa-times"></i>';
                submitBtn.classList.add('btn-error');
            })
            .finally(() => {
                // garante reset do botão mesmo se ocorrer erro/parsing
                form.dataset.submitting = 'false';
                setTimeout(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('btn-success', 'btn-error');
                }, 800);
            });
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>