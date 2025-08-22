<?php
// includes/funcoes_carrinho.php
function sincronizarCarrinho($pdo, $id_usuario) {
    if (!isset($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return;
    }
    foreach ($_SESSION['carrinho'] as $item) {
        $id_produto = $item['id_produto'];
        $quantidade = $item['quantidade'];
        $preco_unitario = $item['preco_unitario'];

        // Verifica se já existe esse produto no carrinho do usuário
        $stmt = $pdo->prepare("SELECT quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
        $stmt->execute([$id_usuario, $id_produto]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existente) {
            // Atualiza a quantidade
            $nova_quantidade = $existente['quantidade'] + $quantidade;
            $stmtUp = $pdo->prepare("UPDATE carrinho SET quantidade = ? WHERE usuario_id = ? AND id_produto = ?");
            $stmtUp->execute([$nova_quantidade, $id_usuario, $id_produto]);
        } else {
            // Insere novo item
            $stmtIn = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmtIn->execute([$id_usuario, $id_produto, $quantidade, $preco_unitario]);
        }
    }
    // Limpa o carrinho da sessão
    unset($_SESSION['carrinho']);
}

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

function adicionarOuAtualizarBanco($pdo, $id_usuario, $id_produto, $quantidade) {

    if (is_array($id_usuario) || is_array($id_produto) || is_array($quantidade)) {
        error_log('Parâmetro array passado para adicionarOuAtualizarBanco');
        return false;

    }
        $id_usuario = (int)$id_usuario;
        $id_produto = (int)$id_produto;
        $quantidade = (int)$quantidade;

        $stmtPreco = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
        $stmtPreco->execute([$id_produto]);
        $preco = $stmtPreco->fetchColumn();

    if (!$preco) return false;

        $stmt = $pdo->prepare("SELECT id, quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
        $stmt->execute([$id_usuario, $id_produto]);
        $itemExistente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($itemExistente) {
        $novaQuantidade = $itemExistente['quantidade'] + $quantidade;
        $stmtUpdate = $pdo->prepare("UPDATE carrinho SET quantidade = ?, atualizado_em = NOW() WHERE id = ?");
        $stmtUpdate->execute([$novaQuantidade, $itemExistente['id']]);
    } else {
        $stmtInsert = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtInsert->execute([$id_usuario, $id_produto, $quantidade, $preco]);
    }
    return true;
}

function removerBanco($pdo, $id_item_carrinho) {
    $stmt = $pdo->prepare("DELETE FROM carrinho WHERE id = ?");
    $stmt->execute([$id_item_carrinho]);
}

function atualizarBanco($pdo, $quantidades) {
    $stmt = $pdo->prepare("UPDATE carrinho SET quantidade = ?, atualizado_em = NOW() WHERE id = ?");
    foreach ($quantidades as $id_item_carrinho => $quantidade) {
        $quantidade = max(1, (int)$quantidade);
        $stmt->execute([$quantidade, $id_item_carrinho]);
    }
}
