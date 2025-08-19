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
        $valor_frete = 5.00; 
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

            // Limpa o carrinho do usuário
            $stmtLimpaCarrinho = $pdo->prepare("DELETE FROM carrinho WHERE usuario_id = ?");
            $stmtLimpaCarrinho->execute([$id_usuario]);

            $pdo->commit();
            $pedido_sucesso = true;
            $num_pedido_sucesso = $id_novo_pedido;

        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Não foi possível finalizar seu pedido. Por favor, tente novamente.";
            error_log("Erro no checkout: " . $e->getMessage());

            header('Location: meus_pedidos.php?order=success');
            exit;
        }
    }
}
?>

<main class="page-content">
    <div id="processing-overlay" style="display: none;">
    <div class="spinner"></div>
    <p>Processando pagamento, por favor aguarde...</p>
</div>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.0/css/all.min.css">
<div class="container checkout-container">

    <?php if ($pedido_sucesso ?? false): ?>
    <div class="checkout-success-container">
        <div class="checkout-card success-card">
            <i class="fas fa-check-circle success-icon"></i>
            <h2 class="section-title">Pedido realizado com sucesso!</h2>
            <p class="success-message">
                Seu pedido foi registrado e em breve será preparado para envio.<br>
                O número do seu pedido é: <strong>#<?= htmlspecialchars($num_pedido_sucesso) ?></strong>
            </p>
            <a href="meus_pedidos.php" class="btn btn-primary">Acompanhar meus pedidos</a>
        </div>
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
        <div class="cep-wrapper">
            <input type="text" id="cep" name="cep" placeholder="00000-000" required maxlength="9">
            <span id="cep-feedback"></span>
        </div>
    </div>
    <div class="form-group">
        <label for="rua">Rua / Logradouro</label>
        <input type="text" id="rua" name="rua" class="form-control" required>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="numero">Número</label>
            <input type="text" id="numero" name="numero" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="bairro">Bairro</label>
            <input type="text" id="bairro" name="bairro" class="form-control" required>
        </div>
    </div>
    <div class="form-row">
        <div class="form-group">
            <label for="cidade">Cidade</label>
            <input type="text" id="cidade" name="cidade" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="estado">Estado (UF)</label>
            <input type="text" id="estado" name="estado" class="form-control" maxlength="2" required>
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

    // ======================================================================
    // SEÇÃO 1: LÓGICA DO CEP, FRETE E HABILITAÇÃO DO BOTÃO
    // ======================================================================
    const cepInput = document.getElementById('cep');
    if (cepInput) {
        const ruaInput = document.getElementById('rua');
        const bairroInput = document.getElementById('bairro');
        const cidadeInput = document.getElementById('cidade');
        const estadoInput = document.getElementById('estado');
        const numeroInput = document.getElementById('numero');
        const cepFeedback = document.getElementById('cep-feedback');
        const submitButton = document.getElementById('submit-button');
        const helperText = document.getElementById('submit-helper-text');
        const shippingRow = document.getElementById('shipping-row');
        const shippingCostSpan = document.getElementById('shipping-cost');
        const totalCostSpan = document.getElementById('total-cost');
        const totalCarrinho = <?= $total_carrinho ?>;

        function habilitarBotao() {
            if(submitButton) {
                submitButton.disabled = false;
                helperText.style.display = 'none';
                cepFeedback.textContent = '';
            }
        }
        function desabilitarBotao() {
            if(submitButton) {
                submitButton.disabled = true;
                helperText.style.display = 'block';
                shippingRow.style.display = 'none';
                totalCostSpan.textContent = `R$ ${totalCarrinho.toFixed(2).replace('.', ',')}`;
            }
        }
        function limparCamposEndereco() {
            ruaInput.value = '';
            bairroInput.value = '';
            cidadeInput.value = '';
            estadoInput.value = '';
        }

        cepInput.addEventListener('blur', function() {
            let cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                cepFeedback.textContent = 'Buscando...';
                desabilitarBotao();
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.erro) {
                            cepFeedback.textContent = 'CEP não encontrado.';
                        } else {
                            ruaInput.value = data.logradouro;
                            bairroInput.value = data.bairro;
                            cidadeInput.value = data.localidade;
                            estadoInput.value = data.uf;
                            const frete = parseFloat((Math.random() * 30 + 15).toFixed(2));
                            const totalFinal = totalCarrinho + frete;
                            shippingCostSpan.textContent = `R$ ${frete.toFixed(2).replace('.', ',')}`;
                            totalCostSpan.textContent = `R$ ${totalFinal.toFixed(2).replace('.', ',')}`;
                            shippingRow.style.display = 'flex';
                            habilitarBotao();
                            numeroInput.focus();
                        }
                    });
            }
        });
    }

    // ======================================================================
    // SEÇÃO 2: LÓGICA DA SIMULAÇÃO DE PAGAMENTO
    // ======================================================================
    const form = document.getElementById('checkout-form');
    if (form) {
        const overlay = document.getElementById('processing-overlay');
        form.addEventListener('submit', function(event) {
            const paymentMethod = document.querySelector('input[name="metodo_pagamento"]:checked');
            if (paymentMethod && paymentMethod.value === 'Cartao de Credito') {
                event.preventDefault();
                overlay.style.display = 'flex';
                const cardNumberInput = document.getElementById('card-number');
                const cardNumber = cardNumberInput ? cardNumberInput.value.replace(/\s/g, '') : '';
                setTimeout(() => {
                    overlay.style.display = 'none';
                    if (cardNumber.endsWith('4242')) {
                        form.submit();
                    } else {
                        alert('Pagamento recusado. Verifique os dados do cartão.');
                    }
                }, 2500);
            }
        });
    }

    // ======================================================================
    // SEÇÃO 3: LÓGICA PARA EXIBIR/ESCONDER OPÇÕES DE PAGAMENTO
    // ======================================================================
    const paymentOptions = document.querySelectorAll('input[name="metodo_pagamento"]');
    const contentPanels = document.querySelectorAll('.payment-content');
    paymentOptions.forEach(option => {
        option.addEventListener('change', function() {
            contentPanels.forEach(panel => panel.classList.remove('active'));
            const targetId = this.dataset.target;
            const targetPanel = document.getElementById(targetId);
            if (targetPanel) {
                targetPanel.classList.add('active');
            }
        });
    });
});
</script>