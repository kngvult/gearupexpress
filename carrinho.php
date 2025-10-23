<?php

include 'includes/header.php';
include 'includes/conexao.php';

$usuarioLogado = $_SESSION['usuario']['id'] ?? null;
$statusMessage = null; // Variável para armazenar mensagens de status

// ======================================================================
// LÓGICA DE PROCESSAMENTO DO FORMULÁRIO
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['adicionar'])) {
        $id_produto = $_POST['id_produto'];
        $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
        if ($usuarioLogado) {
            adicionarOuAtualizarBanco($pdo, $usuarioLogado, $id_produto, $quantidade);
        } else {
            if (!isset($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
            $_SESSION['carrinho'][$id_produto] = ($_SESSION['carrinho'][$id_produto] ?? 0) + $quantidade;
        }
        $statusMessage = ['text' => 'Produto adicionado com sucesso!', 'type' => 'success'];
    } elseif (isset($_POST['atualizar'])) {
        if (!empty($_POST['quantidades'])) {
            if ($usuarioLogado) {
                atualizarBanco($pdo, $_POST['quantidades']);
            } else {
                foreach ($_POST['quantidades'] as $id_produto => $qtd) {
                    $_SESSION['carrinho'][$id_produto] = max(1, (int)$qtd);
                }
            }
            $statusMessage = ['text' => 'Carrinho atualizado!', 'type' => 'info'];
        }
    }
}

if (isset($_GET['remover'])) {
    $id_remover = $_GET['remover'];
    if ($usuarioLogado) {
        removerBanco($pdo, $id_remover);
    } else {
        unset($_SESSION['carrinho'][$id_remover]);
    }
    $statusMessage = ['text' => 'Item removido do carrinho.', 'type' => 'danger'];
}

// ======================================================================
// LÓGICA DE BUSCA DOS ITENS DO CARRINHO
// ======================================================================
if ($usuarioLogado) {
    $itensCarrinho = buscarCarrinhoBanco($pdo, $usuarioLogado);
} else {
    $itensCarrinho = [];
    if (!empty($_SESSION['carrinho'])) {
        foreach ($_SESSION['carrinho'] as $id_produto => $quantidade) {
            $stmt = $pdo->prepare("SELECT nome, preco, imagem FROM produtos WHERE id_produto = ?");
            $stmt->execute([$id_produto]);
            $produto = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$produto) continue;
            $itensCarrinho[] = [
                'id' => $id_produto,
                'id_produto' => $id_produto,
                'nome' => $produto['nome'],
                'imagem' => $produto['imagem'],
                'quantidade' => (int)$quantidade,
                'preco_unitario' => (float)$produto['preco'],
                'total_item' => ((float)$produto['preco']) * ((int)$quantidade)
            ];
        }
    }
}
?>

<main class="page-content">
<div class="container">
    <h2 class="section-title">Meu Carrinho</h2>

    <?php if ($statusMessage): ?>
        <div class="alert alert-<?= $statusMessage['type'] ?>">
            <?= htmlspecialchars($statusMessage['text']) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($itensCarrinho)): ?>
        <div class="cart-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-cart-x" viewBox="0 0 16 16"><path d="M7.354 5.646a.5.5 0 1 0-.708.708L7.293 7 6.646 7.646a.5.5 0 1 0 .708.708L8 7.707l.646.647a.5.5 0 1 0 .708-.708L8.707 7l.647-.646a.5.5 0 1 0-.708-.708L8 6.293 7.354 5.646z"/><path d="M.5 1a.5.5 0 0 0 0 1h1.11l.401 1.607 1.498 7.985A.5.5 0 0 0 4 12h1a2 2 0 1 0 0 4 2 2 0 0 0 0-4h7a2 2 0 1 0 0 4 2 2 0 0 0 0-4h1a.5.5 0 0 0 .491-.408l1.5-8A.5.5 0 0 0 14.5 3H2.89l-.405-1.621A.5.5 0 0 0 2 1H.5zm3.915 10L3.102 4h10.796l-1.313 7h-8.17zM6 14a1 1 0 1 1-2 0 1 1 0 0 1 2 0zm7 0a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/></svg>
            <h3>Seu carrinho está vazio</h3>
            <p>Adicione produtos para vê-los aqui.</p>
            <a href="index.php" class="btn btn-primary">Continuar Comprando</a>
        </div>
    <?php else: ?>
        <form method="post" action="carrinho.php">
            <div class="cart-layout">
                <div class="cart-items-list">
                    <?php
                    $total_geral = 0;
                    foreach ($itensCarrinho as $item):
                        $total_geral += $item['total_item'];
                        // A chave para os arrays de quantidade e remoção deve ser o ID do item no carrinho (c.id)
                        $itemId = $usuarioLogado ? $item['id'] : $item['id_produto'];
                    ?>
                        <div class="cart-item">
                            <img src="assets/img/produtos/<?= htmlspecialchars($item['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="cart-item-image">
                            <div class="cart-item-details">
                                <span class="cart-item-name"><?= htmlspecialchars($item['nome']) ?></span>
                                <span class="cart-item-price">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                            </div>
                            <div class="cart-item-quantity">
    <div class="quantity-stepper">
        <button type="button" class="quantity-btn minus" aria-label="Diminuir quantidade">-</button>
        <input type="number" id="qtd_<?= $itemId ?>" name="quantidades[<?= $itemId ?>]" value="<?= $item['quantidade'] ?>" min="1" class="quantity-input" readonly>
        <button type="button" class="quantity-btn plus" aria-label="Aumentar quantidade">+</button>
    </div>
</div>
                            <div class="cart-item-total">
                                <span>R$ <?= number_format($item['total_item'], 2, ',', '.') ?></span>
                            </div>
                            <div class="cart-item-actions">
                                <a href="#" class="remove-btn" data-id="<?= $itemId ?>" title="Remover item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-summary">
                    <h4>Resumo do Pedido</h4>
                    <div class="summary-row"><span>Subtotal</span><span>R$ <?= number_format($total_geral, 2, ',', '.') ?></span></div>
                    <div class="summary-row"><span>Frete</span><span>A calcular</span></div>
                    <div class="summary-total"><span>Total</span><span>R$ <?= number_format($total_geral, 2, ',', '.') ?></span></div>
                    <a href="checkout.php" class="btn btn-primary btn-checkout">Finalizar Compra</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const quantitySteppers = document.querySelectorAll('.quantity-stepper');
        const cartForm = document.querySelector('.cart-layout').closest('form');
        let updateTimeout;

        quantitySteppers.forEach(stepper => {
            const input = stepper.querySelector('.quantity-input');
            const btnMinus = stepper.querySelector('.quantity-btn.minus');
            const btnPlus = stepper.querySelector('.quantity-btn.plus');

            btnMinus.addEventListener('click', function() {
                let currentValue = parseInt(input.value);
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                    // Dispara o evento para o auto-update
                    input.dispatchEvent(new Event('change'));
                }
            });

            btnPlus.addEventListener('click', function() {
                let currentValue = parseInt(input.value);
                input.value = currentValue + 1;
                // Dispara o evento para o auto-update
                input.dispatchEvent(new Event('change'));
            });

            // Evento de 'change' para acionar o envio do formulário
            input.addEventListener('change', function() {
                // "Debounce": Cancela o envio anterior e agenda um novo
                // Isso evita múltiplos envios se o usuário clicar rápido
                clearTimeout(updateTimeout);
                
                updateTimeout = setTimeout(() => {
                    const updateInput = document.createElement('input');
                    updateInput.type = 'hidden';
                    updateInput.name = 'atualizar';
                    updateInput.value = '1';
                    cartForm.appendChild(updateInput);
                    
                    cartForm.submit();
                }, 1000); // Espera 1 segundo após o último clique para atualizar
            });
        });
    document.querySelectorAll('.remove-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                document.getElementById('modalRemoverId').value = this.dataset.id;
                document.getElementById('modal-remover-carrinho').style.display = 'flex';
            });
        });

        const cancelBtn = document.getElementById('confirmModalNo');
        if (cancelBtn) {
            cancelBtn.onclick = function() {
                document.getElementById('modal-remover-carrinho').style.display = 'none';
            };
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        
        document.body.addEventListener('click', function(e) {
            
            if (e.target.closest('.remove-btn')) { 
                e.preventDefault();
                const itemRow = e.target.closest('.carrinho-item');
                const productId = itemRow.dataset.productId;
                
                if (confirm('Deseja realmente remover este item?')) {
                    handleCartUpdate(productId, 0, 'remover', itemRow);
                }
            }
        });

        // Ouve todas as mudanças de quantidade
        document.body.addEventListener('change', function(e) {
            if (e.target.closest('.input-quantidade-item')) {
                const itemRow = e.target.closest('.carrinho-item');
                const productId = itemRow.dataset.productId;
                const newQuantity = parseInt(e.target.value);
                
                handleCartUpdate(productId, newQuantity, 'atualizar', itemRow);
            }
        });

        // Função central que faz o AJAX
        function handleCartUpdate(productId, quantity, action, itemRowElement) {
            
            itemRowElement.style.opacity = '0.5'; // Efeito de "carregando"
            const formData = new FormData();
            formData.append('id_produto', productId);
            formData.append('quantidade', quantity);
            formData.append('acao', action);

            fetch('carrinho_atualizar.php', { 
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    
                    if (data.totalItensCarrinho !== undefined) {
                        // Chama a função global que está no footer.php
                        updateCartBadge(data.totalItensCarrinho); 
                    }
                    
                    // Remove o item da tela
                    if (action === 'remover') {
                        itemRowElement.remove();
                    } else {
                        itemRowElement.style.opacity = '1';
                    }

                    // Se o carrinho ficou vazio, recarrega a página
                    if (data.totalItensCarrinho === 0) {
                        location.reload();
                    }
                    
                } else {
                    alert(data.message || 'Erro ao atualizar o carrinho.');
                    itemRowElement.style.opacity = '1';
                }
            })
            .catch(error => {
                console.error('Erro no fetch:', error);
                itemRowElement.style.opacity = '1';
            });
        }
    });
    </script>

    <!-- Modal de confirmação de remoção do item do carrinho -->
    <div id="modal-remover-carrinho" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3); align-items:center; justify-content:center; z-index:9999;">
        <div style="background:#fff; padding:30px; border-radius:12px; max-width:400px; margin:auto; text-align:center;">
            <h4 style="margin-top:0;">Remover Item</h4>
            <p>Tem certeza que deseja remover este item do carrinho?</p>
            <form id="form-remover-carrinho" method="get" action="carrinho.php" style="margin-bottom:0;">
            <input type="hidden" name="remover" id="modalRemoverId">
            <button type="submit" id="confirmModalYes" class="btn btn-danger" style="margin-right:10px; background-color: #dc3545; color: #fff;">Confirmar</button>
            <button type="button" id="confirmModalNo" class="btn btn-secondary">Cancelar</button>
            </form>
        </div>
    </div>
<?php include 'includes/footer.php'; ?>