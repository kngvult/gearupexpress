<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

// ======================================================================
// 1. GUARDA DE ACESSO (VERIFICA SE O USUÁRIO ESTÁ LOGADO)
// ======================================================================
if (!isset($_SESSION['usuario']['id'])) {
    $_SESSION['redirect_url'] = 'checkout.php'; 
    header('Location: login.php');
    exit;
}

// ======================================================================
// 2. INICIALIZAÇÃO DAS VARIÁVEIS
// É crucial inicializar todas as variáveis aqui para evitar erros de "Undefined variable".
// ======================================================================
$id_usuario = $_SESSION['usuario']['id'];
$itensCarrinho = [];
$total_carrinho = 0;
$pedido_sucesso = false; // Define como 'false' por padrão
$num_pedido_sucesso = 0;
$erro = '';

// ======================================================================
// 3. BUSCA DOS DADOS DO CARRINHO (PARA EXIBIR O RESUMO)
// Este bloco é executado sempre que a página carrega.
// ======================================================================
try {
    $stmt = $pdo->prepare("
        SELECT p.nome, p.imagem, c.quantidade, c.preco_unitario, (c.quantidade * c.preco_unitario) AS total_item, p.id_produto
        FROM carrinho c
        JOIN produtos p ON c.id_produto = p.id_produto
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$id_usuario]);
    $itensCarrinho = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Se o carrinho estiver vazio após a busca, redireciona de volta
    if (empty($itensCarrinho) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: carrinho.php');
        exit;
    }

    // Calcula o total do carrinho
    foreach ($itensCarrinho as $item) {
        $total_carrinho += $item['total_item'];
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar os itens do carrinho.";
    error_log("Erro ao buscar carrinho: " . $e->getMessage());
}


// ======================================================================
// 4. PROCESSAMENTO DO FORMULÁRIO (QUANDO O USUÁRIO CLICA EM 'CONFIRMAR E PAGAR')
// Este bloco só é executado em uma requisição POST.
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    if (empty($metodo_pagamento)) {
        $erro = "Por favor, selecione um método de pagamento.";
    } else {
        $pdo->beginTransaction();
        try {
            // PASSO A: Inserir o pedido na tabela `pedidos`
            $stmtPedido = $pdo->prepare(
                "INSERT INTO public.pedidos (id_usuario, total, metodo_pagamento) VALUES (?, ?, ?) RETURNING id_pedido"
            );
            $stmtPedido->execute([$id_usuario, $total_carrinho, $metodo_pagamento]);
            $id_novo_pedido = $stmtPedido->fetchColumn();

            // PASSO B: Inserir cada item na tabela `itens_pedido`
            $stmtItem = $pdo->prepare(
                "INSERT INTO public.itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)"
            );
            
            foreach ($itensCarrinho as $item) {
                $stmtItem->execute([
                    $id_novo_pedido,
                    $item['id_produto'],
                    $item['quantidade'],
                    $item['preco_unitario']
                ]);
            }

            // PASSO C: Limpar o carrinho do usuário
            $stmtLimpaCarrinho = $pdo->prepare("DELETE FROM public.carrinho WHERE usuario_id = ?");
            $stmtLimpaCarrinho->execute([$id_usuario]);

            // Se tudo deu certo, confirma a transação
            $pdo->commit();
            $pedido_sucesso = true;
            $num_pedido_sucesso = $id_novo_pedido;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Não foi possível finalizar seu pedido. Por favor, tente novamente.";
            error_log("Erro no checkout: " . $e->getMessage());
        }
    }
}
?>

<main class="page-content">
<div class="container">

    <?php if ($pedido_sucesso): ?>
        <div class="checkout-success">
            </div>
    <?php else: ?>
        <h2 class="section-title">Finalizar Compra</h2>
        
        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="checkout.php">
            <div class="checkout-grid">
                <div class="summary-card">
                    <h4>Resumo do Pedido</h4>
                    <?php foreach ($itensCarrinho as $item): ?>
                        <div class="summary-item">
                            <img src="assets/img/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="summary-item-image">
                            <div class="summary-item-details">
                                <span class="summary-item-name"><?= htmlspecialchars($item['nome']) ?> (x<?= $item['quantidade'] ?>)</span>
                                <span class="summary-item-price">R$ <?= number_format($item['total_item'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>R$ <?= number_format($total_carrinho, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div class="payment-card">
                    <h4>Escolha o método de pagamento</h4>
                    
                    <div class="payment-options">
                        <div class="payment-option">
                            <input type="radio" id="pix" name="metodo_pagamento" value="Pix" data-target="pix-content" checked>
                            <label for="pix">
                                <svg width="24" height="24" viewBox="0 0 24 24">...</svg> Pix
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="boleto" name="metodo_pagamento" value="Boleto" data-target="boleto-content">
                            <label for="boleto">
                                <svg width="24" height="24" viewBox="0 0 24 24">...</svg> Boleto Bancário
                            </label>
                        </div>
                        <div class="payment-option">
                            <input type="radio" id="cartao" name="metodo_pagamento" value="Cartao de Credito" data-target="cartao-content">
                            <label for="cartao">
                                <svg width="24" height="24" viewBox="0 0 24 24">...</svg> Cartão de Crédito
                            </label>
                        </div>
                    </div>

                    <div class="payment-content-wrapper">
                        <div id="pix-content" class="payment-content active">
                            <p>Ao finalizar, um QR Code será gerado para pagamento. Válido por 30 minutos.</p>
                            </div>
                        <div id="boleto-content" class="payment-content">
                            <p>O boleto bancário será gerado com vencimento em 2 dias úteis.</p>
                        </div>
                        <div id="cartao-content" class="payment-content">
                            <div class="form-group">
                                <label for="card-number">Número do Cartão</label>
                                <input type="text" id="card-number" placeholder="0000 0000 0000 0000">
                            </div>
                            <div class="form-group">
                                <label for="card-name">Nome no Cartão</label>
                                <input type="text" id="card-name" placeholder="Como aparece no cartão">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="card-expiry">Validade (MM/AA)</label>
                                    <input type="text" id="card-expiry" placeholder="MM/AA">
                                </div>
                                <div class="form-group">
                                    <label for="card-cvv">CVV</label>
                                    <input type="text" id="card-cvv" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-checkout">Confirmar e Pagar</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</main>

<style>
.checkout-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: flex-start; }
.summary-card, .payment-card { background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.07); }
.summary-item { display: flex; align-items: center; gap: 15px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #e9ecef; }
.summary-item:last-of-type { border-bottom: none; }
.summary-item-image { width: 60px; height: 60px; border-radius: 6px; object-fit: cover; }
.summary-item-details { flex-grow: 1; display: flex; justify-content: space-between; }
.summary-total { margin-top: 10px; padding-top: 20px; border-top: 2px solid #343a40; font-size: 1.5rem; font-weight: bold; display: flex; justify-content: space-between; }
.payment-options { display: flex; flex-direction: column; gap: 10px; margin-bottom: 25px; border: 1px solid #dee2e6; border-radius: 8px; }
.payment-option { position: relative; }
.payment-option input[type="radio"] { position: absolute; opacity: 0; }
.payment-option label { display: flex; align-items: center; gap: 10px; padding: 15px; border-bottom: 1px solid #dee2e6; cursor: pointer; transition: background-color 0.2s ease; }
.payment-option:last-child label { border-bottom: none; }
.payment-option input[type="radio"]:checked + label { background-color: #e8f7fa; font-weight: bold; }
.payment-content { display: none; }
.payment-content.active { display: block; animation: fadeIn 0.4s ease; }
.form-row { display: flex; gap: 15px; }
.form-row .form-group { flex: 1; }
@media (max-width: 992px) { .checkout-grid { grid-template-columns: 1fr; } .summary-card { grid-row: 2; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentOptions = document.querySelectorAll('input[name="metodo_pagamento"]');
    const contentPanels = document.querySelectorAll('.payment-content');

    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Esconde todos os painéis
            contentPanels.forEach(panel => {
                panel.classList.remove('active');
            });

            // Mostra o painel alvo
            const targetId = this.dataset.target;
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>