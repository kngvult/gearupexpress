<?php
// includes/funcoes_carrinho.php

// CORREÇÃO: Função para inicializar o carrinho na sessão
function inicializarCarrinhoSessao() {
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
}

// CORREÇÃO: Função para contar itens no carrinho da sessão
function getCarrinhoCountSessao() {
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['carrinho'] as $item) {
        // Verificar se $item é um array antes de acessar
        if (is_array($item) && isset($item['quantidade'])) {
            $total += $item['quantidade'];
        }
    }
    return $total;
}

// CORREÇÃO: Função para obter total do carrinho da sessão
function getCarrinhoTotalSessao() {
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        return 0;
    }
    
    $total = 0;
    foreach ($_SESSION['carrinho'] as $item) {
        // Verificar se $item é um array antes de acessar
        if (is_array($item) && isset($item['preco']) && isset($item['quantidade'])) {
            $total += $item['preco'] * $item['quantidade'];
        }
    }
    return $total;
}

function sincronizarCarrinho($pdo, $id_usuario) {
    // CORREÇÃO: Verificar se o carrinho da sessão existe e é um array
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho']) || empty($_SESSION['carrinho'])) {
        return;
    }
    
    foreach ($_SESSION['carrinho'] as $item) {
        // CORREÇÃO: Verificar se $item é um array antes de acessar
        if (!is_array($item)) continue;
        
        $id_produto = $item['id_produto'] ?? null;
        $quantidade = $item['quantidade'] ?? 0;
        $preco_unitario = $item['preco'] ?? 0; // CORREÇÃO: mudado de 'preco_unitario' para 'preco'

        if (!$id_produto || $quantidade <= 0) continue;

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
    // CORREÇÃO: Validações mais robustas
    if (is_array($id_usuario) || is_array($id_produto) || is_array($quantidade)) {
        error_log('Parâmetro array passado para adicionarOuAtualizarBanco');
        return false;
    }

    $id_usuario = (int)$id_usuario;
    $id_produto = (int)$id_produto;
    $quantidade = (int)$quantidade;

    // CORREÇÃO: Verificar se quantidade é válida
    if ($quantidade <= 0) {
        return false;
    }

    $stmtPreco = $pdo->prepare("SELECT preco, estoque FROM produtos WHERE id_produto = ?");
    $stmtPreco->execute([$id_produto]);
    $produto = $stmtPreco->fetch(PDO::FETCH_ASSOC);

    if (!$produto || $produto['estoque'] <= 0) {
        return false;
    }

    $preco = $produto['preco'];

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

// CORREÇÃO: Nova função para adicionar à sessão (usuários não logados)
function adicionarAoSessao($id_produto, $nome, $preco, $quantidade = 1) {
    inicializarCarrinhoSessao();
    
    $id_produto = (int)$id_produto;
    $quantidade = (int)$quantidade;
    
    // Verificar se produto já existe no carrinho
    $produto_existe = false;
    foreach ($_SESSION['carrinho'] as &$item) {
        if (is_array($item) && $item['id_produto'] == $id_produto) {
            $item['quantidade'] += $quantidade;
            $produto_existe = true;
            break;
        }
    }
    
    // Se não existe, adicionar novo item
    if (!$produto_existe) {
        $_SESSION['carrinho'][] = [
            'id_produto' => $id_produto,
            'nome' => $nome,
            'preco' => $preco,
            'quantidade' => $quantidade
        ];
    }
    
    return true;
}
?>