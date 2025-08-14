<?php

include 'includes/header.php';
include 'includes/conexao.php';

$usuarioLogado = $_SESSION['usuario']['id'] ?? null;
$statusMessage = null; // Variável para armazenar mensagens de status

// ======================================================================
// FUNÇÕES PHP COMPLETAS E FUNCIONAIS
// ======================================================================

/**
 * Busca os itens do carrinho de um usuário específico no banco de dados.
 */
function buscarCarrinhoBanco($pdo, $id_usuario) {
    $stmt = $pdo->prepare("
        SELECT 
            c.id, 
            c.id_produto,
            p.nome, 
            p.imagem, 
            c.quantidade, 
            c.preco_unitario,
            (c.quantidade * c.preco_unitario) AS total_item
        FROM carrinho c
        JOIN produtos p ON c.id_produto = p.id_produto
        WHERE c.usuario_id = ?
    ");
    $stmt->execute([$id_usuario]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Adiciona um novo produto ao carrinho ou atualiza a quantidade se ele já existir.
 */
function adicionarOuAtualizarBanco($pdo, $id_usuario, $id_produto, $quantidade) {
    // Primeiro, busca o preço atual do produto para garantir consistência.
    $stmtPreco = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
    $stmtPreco->execute([$id_produto]);
    $preco = $stmtPreco->fetchColumn();

    if (!$preco) {
        return false; // Produto não encontrado, não faz nada.
    }

    // Verifica se o usuário já tem este produto no carrinho.
    $stmt = $pdo->prepare("SELECT id, quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
    $stmt->execute([$id_usuario, $id_produto]);
    $itemExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($itemExistente) {
        // Se já existe, atualiza a quantidade.
        $novaQuantidade = $itemExistente['quantidade'] + $quantidade;
        $stmtUpdate = $pdo->prepare("UPDATE carrinho SET quantidade = ?, atualizado_em = NOW() WHERE id = ?");
        $stmtUpdate->execute([$novaQuantidade, $itemExistente['id']]);
    } else {
        // Se não existe, insere um novo registro.
        $stmtInsert = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$id_usuario, $id_produto, $quantidade, $preco]);
    }
    return true;
}

/**
 * Remove um item específico do carrinho no banco de dados.
 */
function removerBanco($pdo, $id_item_carrinho) {
    $stmt = $pdo->prepare("DELETE FROM carrinho WHERE id = ?");
    $stmt->execute([$id_item_carrinho]);
}

/**
 * Atualiza as quantidades de múltiplos itens no carrinho.
 */
function atualizarBanco($pdo, $quantidades) {
    $stmt = $pdo->prepare("UPDATE carrinho SET quantidade = ?, atualizado_em = NOW() WHERE id = ?");
    foreach ($quantidades as $id_item_carrinho => $quantidade) {
        $quantidade = max(1, (int)$quantidade); // Garante que a quantidade seja no mínimo 1.
        $stmt->execute([$quantidade, $id_item_carrinho]);
    }
}

// ======================================================================
// LÓGICA DE PROCESSAMENTO (nenhuma alteração necessária aqui)
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
// LÓGICA DE BUSCA (nenhuma alteração necessária aqui)
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
                'id' => $id_produto, 'nome' => $produto['nome'], 'imagem' => $produto['imagem'],
                'quantidade' => $quantidade, 'preco_unitario' => $produto['preco'],
                'total_item' => $produto['preco'] * $quantidade
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
                            <img src="assets/img/<?= htmlspecialchars($item['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($item['nome']) ?>" class="cart-item-image">
                            <div class="cart-item-details">
                                <span class="cart-item-name"><?= htmlspecialchars($item['nome']) ?></span>
                                <span class="cart-item-price">R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></span>
                            </div>
                            <div class="cart-item-quantity">
                                <label for="qtd_<?= $itemId ?>" class="sr-only">Quantidade</label>
                                <input type="number" id="qtd_<?= $itemId ?>" name="quantidades[<?= $itemId ?>]" value="<?= $item['quantidade'] ?>" min="1" class="quantity-input">
                            </div>
                            <div class="cart-item-total">
                                <span>R$ <?= number_format($item['total_item'], 2, ',', '.') ?></span>
                            </div>
                            <div class="cart-item-actions">
                                <a href="carrinho.php?remover=<?= $itemId ?>" class="remove-btn" title="Remover item" onclick="return confirm('Tem certeza que quer remover este item?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
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
                    <button type="submit" name="atualizar" class="btn btn-secondary">Atualizar Carrinho</button>
                    <a href="checkout.php" class="btn btn-primary btn-checkout">Finalizar Compra</a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
</main>

<?php include 'includes/footer.php'; ?>