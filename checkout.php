<?php
// ETAPA 1: LÓGICA DE BACKEND (ANTES DE QUALQUER HTML)
session_start(); 
include 'includes/conexao.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];
$erro = '';

// Se a página foi submetida (botão "Finalizar Compra" foi clicado)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Busca os itens do carrinho do usuário a partir do banco de dados
    $stmtCarrinhoPost = $pdo->prepare("SELECT id_produto, quantidade, preco_unitario FROM carrinho WHERE usuario_id = ?");
    $stmtCarrinhoPost->execute([$id_usuario]);
    $itensCarrinhoPost = $stmtCarrinhoPost->fetchAll(PDO::FETCH_ASSOC);

    if (empty($itensCarrinhoPost)) {
        header('Location: carrinho.php'); // Impede checkout com carrinho vazio
        exit;
    }

    // Coleta dos dados do formulário
    $metodo_pagamento = $_POST['metodo_pagamento'] ?? '';
    $cep = $_POST['cep'] ?? '';
    $rua = $_POST['rua'] ?? '';
    $numero = $_POST['numero'] ?? '';
    $bairro = $_POST['bairro'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $estado = $_POST['estado'] ?? '';
    
    if (empty($metodo_pagamento) || empty($cep) || empty($rua) || empty($numero)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        // Recalcula o total no backend para segurança
        $total_carrinho_post = 0;
        foreach ($itensCarrinhoPost as $item) {
            $total_carrinho_post += $item['quantidade'] * $item['preco_unitario'];
        }
        
        $valor_frete = isset($_POST['valor_frete']) ? floatval(str_replace(',', '.', $_POST['valor_frete'])) : 0;
        $total_final = $total_carrinho_post + $valor_frete;

        $pdo->beginTransaction();
    try {
        // 1. Insere o pedido principal
        $stmtPedido = $pdo->prepare(
            "INSERT INTO public.pedidos (id_usuario, total, metodo_pagamento, valor_frete, endereco_cep, endereco_rua, endereco_numero, endereco_bairro, endereco_cidade, endereco_estado) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtPedido->execute([$id_usuario, $total_final, $metodo_pagamento, $valor_frete, $cep, $rua, $numero, $bairro, $cidade, $estado]);
        $id_novo_pedido = $pdo->lastInsertId();

        // 2. Prepara as queries para os próximos passos
        $stmtItens = $pdo->prepare("INSERT INTO public.itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtEstoque = $pdo->prepare("UPDATE public.produtos SET estoque = estoque - ? WHERE id_produto = ?");

        // 3. Itera sobre os itens do carrinho para inseri-los E atualizar o estoque
        foreach ($itensCarrinhoPost as $item) {
            // Insere o item na tabela 'itens_pedido'
            $stmtItens->execute([
                $id_novo_pedido, 
                $item['id_produto'], 
                $item['quantidade'], 
                $item['preco_unitario']
            ]);
            // Diminui a quantidade do estoque na tabela 'produtos'
            $stmtEstoque->execute([
                $item['quantidade'], 
                $item['id_produto']
            ]);
        }

        // 4. Limpa o carrinho do usuário
        $stmtLimpaCarrinho = $pdo->prepare("DELETE FROM carrinho WHERE usuario_id = ?");
        $stmtLimpaCarrinho->execute([$id_usuario]);

        // 5. Insere o log de atividade
        $logDescricao = "Novo pedido (#{$id_novo_pedido}) recebido.";
        $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
        $stmtLog->execute([$logDescricao]);

        // 6. Confirma todas as operações no banco de dados
        $pdo->commit();
        
        // 7. Redireciona para a página de sucesso
        header('Location: meus_pedidos.php?order=success');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack(); // Desfaz tudo se ocorrer qualquer erro
        $erro = "Não foi possível finalizar seu pedido. Por favor, tente novamente.";
        error_log("Erro crítico no checkout: " . $e->getMessage());
    }
    }
}

// ETAPA 2: PREPARAÇÃO DOS DADOS PARA EXIBIÇÃO (se a página não foi submetida ou se deu erro)
$stmtCarrinho = $pdo->prepare("
    SELECT p.nome, p.imagem, c.quantidade, c.preco_unitario, (c.quantidade * c.preco_unitario) AS total_item
    FROM carrinho c
    JOIN produtos p ON c.id_produto = p.id_produto
    WHERE c.usuario_id = ?
");
$stmtCarrinho->execute([$id_usuario]);
$itensCarrinho = $stmtCarrinho->fetchAll(PDO::FETCH_ASSOC);

if (empty($itensCarrinho)) {
    header('Location: carrinho.php');
    exit;
}
$total_carrinho = 0;
foreach ($itensCarrinho as $item) {
    $total_carrinho += $item['total_item'];
}

include 'includes/header.php';
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
                    <div id="resumo-parcelamento" style="display:none; margin-top:8px; color:var(--primary-color); font-weight:600;"></div> 
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
                            <div class="form-group" id="parcelamento-group">
                                <label for="parcelas">Parcelamento</label>
                                <select id="parcelas" name="parcelas" class="form-control">
                                    <option value="1">1x sem juros</option>
                                    <option value="2">2x sem juros</option>
                                    <option value="3">3x sem juros</option>
                                </select>
                                <div id="valor-parcela" style="margin-top:8px; color:var(--primary-color); font-weight:600;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="checkout-card checkout-address-full" style="margin-top: 20px;">
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
    const parcelasSelect = document.getElementById('parcelas');
    const resumoParcelamentoDiv = document.getElementById('resumo-parcelamento');
    const parcelamentoGroup = document.getElementById('parcelamento-group');
    const shippingCostSpan = document.getElementById('shipping-cost');
    const totalCarrinho = <?= $total_carrinho ?>;
    
    function atualizarValorParcela() {
        let total = totalCarrinho;
        // Se o frete já foi calculado, soma ao total
        if (shippingCostSpan && shippingCostSpan.textContent && shippingCostSpan.textContent.startsWith('R$')) {
            let frete = parseFloat(shippingCostSpan.textContent.replace('R$','').replace(',','.'));
            if (!isNaN(frete)) total += frete;
        }
        const parcelas = parseInt(parcelasSelect.value);
        const valor = total / parcelas;
        const isCartao = document.querySelector('input[name="metodo_pagamento"]:checked')?.value === 'Cartao de Credito';

        if (isCartao) {
            resumoParcelamentoDiv.style.display = 'block';
            resumoParcelamentoDiv.textContent = `${parcelas}x de R$ ${valor.toFixed(2).replace('.', ',')} sem juros`;
        } else {
            resumoParcelamentoDiv.style.display = 'none';
        }
        //valorParcelaDiv.textContent = `Valor da parcela: R$ ${valor.toFixed(2).replace('.', ',')} (${parcelas}x)`;
    }

    if (parcelasSelect) {
        parcelasSelect.addEventListener('change', atualizarValorParcela);
        
    }

    document.querySelectorAll('input[name="metodo_pagamento"]').forEach(radio => {
        radio.addEventListener('change', atualizarValorParcela);
    });

    // Atualiza valor da parcela quando o frete é calculado
    if (shippingCostSpan) {
        const observer = new MutationObserver(atualizarValorParcela);
        observer.observe(shippingCostSpan, { childList: true });
    }
    atualizarValorParcela();
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
    function ajustarAlturaCards() {
    const summary = document.querySelector('.summary-card');
    const payment = document.querySelector('.payment-card');
    if (summary && payment) {
        summary.style.height = 'auto';
        payment.style.height = 'auto';
        // Mede maior altura atual
        const maiorAltura = Math.max(summary.scrollHeight, payment.scrollHeight);
        summary.style.height = maiorAltura + 'px';
        payment.style.height = maiorAltura + 'px';
    }
}
    // Ajusta no carregamento
    window.addEventListener('load', ajustarAlturaCards);
    // Ajusta quando troca o método de pagamento
    document.querySelectorAll('input[name="metodo_pagamento"]').forEach(radio => {
        radio.addEventListener('change', () => {
            setTimeout(ajustarAlturaCards, 50); // espera animação abrir
        });
    });
    window.addEventListener('resize', ajustarAlturaCards);
});

// ======================================================================
//  SEÇÃO 4: MÁSCARAS PARA CAMPOS DE FORMULÁRIO
// ======================================================================

// Máscara para número do cartão
const cardNumberInput = document.getElementById('card-number');
if (cardNumberInput) {
    cardNumberInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '').slice(0,16); // Apenas números, máximo 16 dígitos
        let formatted = value.replace(/(.{4})/g, '$1 ').trim(); // Espaço a cada 4 dígitos
        this.value = formatted;
    });
}

// Máscara para validade MM/AA
const cardExpiryInput = document.getElementById('card-expiry');
if (cardExpiryInput) {
    cardExpiryInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '').slice(0,4); // Apenas números, máximo 4 dígitos
        if (value.length > 2) {
            value = value.slice(0,2) + '/' + value.slice(2);
        }
        this.value = value;
    });
}

// Máscara para CEP 00000-000
const cepInput = document.getElementById('cep');
if (cepInput) {
    cepInput.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '').slice(0,8); // Apenas números, máximo 8 dígitos
        if (value.length > 5) {
            value = value.slice(0,5) + '-' + value.slice(5);
        }
        this.value = value;
    });
}
</script>