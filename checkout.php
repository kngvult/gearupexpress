<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

// ... Lógica inicial de verificação de login e busca do carrinho ...
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

// Inicialização de todas as variáveis para evitar erros
$id_usuario = $_SESSION['usuario']['id'];
$itensCarrinho = [];
$total_carrinho = 0;
$pedido_sucesso = false; // Define como 'false' por padrão
$num_pedido_sucesso = 0;
$erro = '';

// Busca os dados do carrinho SEMPRE que a página carrega
try {
    $stmt = $pdo->prepare("
        SELECT p.nome, p.imagem, c.quantidade, c.preco_unitario, (c.quantidade * c.preco_unitario) AS total_item, p.id_produto
        FROM carrinho c
        JOIN produtos p ON c.id_produto = p.id_produto
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$id_usuario]);
    $itensCarrinho = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($itensCarrinho) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: carrinho.php');
        exit;
    }

    foreach ($itensCarrinho as $item) {
        $total_carrinho += $item['total_item'];
    }
} catch (PDOException $e) {
    $erro = "Erro ao buscar os itens do carrinho.";
    error_log("Erro ao buscar carrinho: " . $e->getMessage());
}

// ======================================================================
// PROCESSAMENTO DO PEDIDO COM ENDEREÇO E FRETE
// ======================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta dos dados do formulário
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    // Validações
    if (empty($metodo_pagamento) || empty($cep) || empty($rua) || empty($numero) || empty($cidade) || empty($estado)) {
        $erro = "Por favor, preencha todos os campos de endereço e pagamento.";
    } else {
        // LÓGICA DE FRETE (SIMULADO)
        // Em um site real, aqui você chamaria uma API dos Correios.
        // Para nossa simulação, vamos usar um valor fixo.
        $valor_frete = 25.00; 
        $total_final = $total_carrinho + $valor_frete;

        $pdo->beginTransaction();
        try {
            $stmtPedido = $pdo->prepare(
                "INSERT INTO public.pedidos 
                (id_usuario, total, metodo_pagamento, valor_frete, endereco_cep, endereco_rua, endereco_numero, endereco_bairro, endereco_cidade, endereco_estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) RETURNING id_pedido"
            );
            $stmtPedido->execute([
                $id_usuario, $total_final, $metodo_pagamento, $valor_frete,
                $cep, $rua, $numero, $bairro, $cidade, $estado
            ]);
            $id_novo_pedido = $stmtPedido->fetchColumn();

            // ... Lógica para inserir itens_pedido e limpar carrinho ...

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
<div class="container checkout-container">

    <?php if ($pedido_sucesso ?? false): ?>
        <div class="checkout-success">
            <h2>Pedido Realizado com Sucesso!</h2>
            </div>
    <?php else: ?>
        <h2 class="section-title">Finalizar Compra</h2>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="checkout.php" id="checkout-form">
            <div class="checkout-layout-grid">
                <div class="checkout-card summary-card">
                    <h4>Resumo do Pedido</h4>
                    <?php foreach ($itensCarrinho as $item): ?>
                        <div class="summary-item">
                            <img src="assets/img/produtos/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="summary-item-image">
                            <div class="summary-item-details">
                                <span class="summary-item-name"><?= htmlspecialchars($item['nome']) ?> (x<?= $item['quantidade'] ?>)</span>
                                <span class="summary-item-price">R$ <?= number_format($item['total_item'], 2, ',', '.') ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-row" id="shipping-row" style="display: none;">
                        <span>Frete</span>
                        <span id="shipping-cost">--</span>
                    </div>

                    <div class="summary-total">
                        <span>Total</span>
                        <span id="total-cost">R$ <?= number_format($total_carrinho ?? 0, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div class="checkout-card payment-card">
    <h4>Método de Pagamento</h4>
    <div class="payment-options">
    <div class="payment-option">
        <input type="radio" id="pix" name="metodo_pagamento" value="Pix" data-target="pix-content" required>
        <label for="pix">
            <i class="fas fa-qrcode"></i> Pix
        </label>
    </div>

    <div class="payment-option">
        <input type="radio" id="cartao" name="metodo_pagamento" value="Cartao de Credito" data-target="cartao-content" required>
        <label for="cartao">
            <i class="fas fa-credit-card"></i> Cartão de Crédito
        </label>
    </div>

    <div class="payment-option">
        <input type="radio" id="boleto" name="metodo_pagamento" value="Boleto" data-target="boleto-content" required>
        <label for="boleto">
            <i class="fas fa-barcode"></i> Boleto
        </label>
    </div>
</div>

    <div class="payment-content-wrapper">
        <div id="pix-content" class="payment-content">
            <p>Ao finalizar, um QR Code será gerado para pagamento. Válido por 30 minutos.</p>
        </div>
        <div id="boleto-content" class="payment-content">
            <p>O boleto bancário será gerado com vencimento em 2 dias úteis.</p>
        </div>
        <div id="cartao-content" class="payment-content">
            <div class="form-group">
                <label for="card-number">Número do Cartão</label>
                <input type="text" id="card-number" class="form-control" placeholder="0000 0000 0000 0000">
            </div>
            <div class="form-group">
                <label for="card-name">Nome no Cartão</label>
                <input type="text" id="card-name" class="form-control" placeholder="Como aparece no cartão">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="card-expiry">Validade (MM/AA)</label>
                    <input type="text" id="card-expiry" class="form-control" placeholder="MM/AA">
                </div>
                <div class="form-group">
                    <label for="card-cvv">CVV</label>
                    <input type="text" id="card-cvv" class="form-control" placeholder="123">
                </div>
            </div>
        </div>
    </div>
</div>

                <div class="checkout-card checkout-address-full">
                    <h4>Endereço de Entrega</h4>
                    <div class="address-form-container">
                        <div class="form-group">
                            <label for="cep">CEP</label>
                            <input type="text" id="cep" name="cep" placeholder="00000-000" required>
                        </div>
                        <div class="form-group">
                            <label for="rua">Rua / Logradouro</label>
                            <input type="text" id="rua" name="rua" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" required>
                            </div>
                            <div class="form-group">
                                <label for="bairro">Bairro</label>
                                <input type="text" id="bairro" name="bairro" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" required>
                            </div>
                            <div class="form-group">
                                <label for="estado">Estado (UF)</label>
                                <input type="text" id="estado" name="estado" maxlength="2" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="checkout-actions">
                <button type="submit" id="submit-button" class="btn btn-primary btn-checkout" disabled>Finalizar Compra</button>
                <small id="submit-helper-text" class="form-helper-text">Por favor, preencha o CEP para calcular o frete e continuar.</small>
            </div>
        </form>
    <?php endif; ?>
</div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cepInput = document.getElementById('cep');
    const shippingRow = document.getElementById('shipping-row');
    const shippingCostEl = document.getElementById('shipping-cost');
    const totalCostEl = document.getElementById('total-cost');
    const submitButton = document.getElementById('submit-button');
    const helperText = document.getElementById('submit-helper-text');
    const subtotal = <?= $total_carrinho ?>;
    
    cepInput.addEventListener('input', function() {
        // Remove caracteres não numéricos
        this.value = this.value.replace(/\D/g, '');

        if (this.value.length === 8) {
            // CEP válido, simula cálculo
            shippingCostEl.textContent = 'Calculando...';
            shippingRow.style.display = 'flex';
            submitButton.disabled = true;
            helperText.style.display = 'block';

            setTimeout(() => {
                const frete = 25.00; // Valor de frete simulado
                const totalFinal = subtotal + frete;
                
                shippingCostEl.textContent = 'R$ ' + frete.toFixed(2).replace('.', ',');
                totalCostEl.textContent = 'R$ ' + totalFinal.toFixed(2).replace('.', ',');
                
                // Habilita o botão de finalizar
                submitButton.disabled = false;
                helperText.style.display = 'none';

            }, 1500); // Simula 1.5s de chamada de API
        } else {
            // CEP inválido, reseta os valores
            shippingRow.style.display = 'none';
            totalCostEl.textContent = 'R$ ' + subtotal.toFixed(2).replace('.', ',');
            submitButton.disabled = true;
            helperText.style.display = 'block';
        }
    });
});
document.addEventListener('DOMContentLoaded', function() {
    // Lógica para o seletor de método de pagamento
    const paymentOptions = document.querySelectorAll('input[name="metodo_pagamento"]');
    const contentPanels = document.querySelectorAll('.payment-content');

    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            // Esconde todos os painéis de conteúdo
            contentPanels.forEach(panel => {
                panel.classList.remove('active');
            });

            // Mostra o painel alvo correspondente à opção selecionada
            const targetId = this.dataset.target;
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });

    // Aciona o 'change' na primeira opção para inicializar a visualização
    document.querySelector('input[name="metodo_pagamento"]:checked')?.dispatchEvent(new Event('change'));
});

</script>