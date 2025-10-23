<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function inicializarCarrinhoSessao() {
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
}

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


/**
 * Calcula o número total de itens no carrinho,
 * seja para um usuário logado (via BD) ou um visitante (via Sessão).
 *
 * @param PDO $pdo A conexão com o banco de dados.
 * @return int O número total de itens.
 */
function contarItensCarrinho($pdo) {
    $totalItens = 0;
    
    // Use a sua variável de sessão correta aqui
    $id_usuario = $_SESSION['usuario']['id'] ?? null; 

    try {
        if ($id_usuario) {
            // --- USUÁRIO LOGADO ---
            // Conta os itens no banco de dados
            $stmt = $pdo->prepare("SELECT SUM(quantidade) FROM carrinho WHERE usuario_id = ?");
            $stmt->execute([$id_usuario]);
            $totalItens = (int)$stmt->fetchColumn();
            
        } else if (!empty($_SESSION['carrinho']) && is_array($_SESSION['carrinho'])) {
            // --- VISITANTE ---
            // Itera pelo array da sessão e soma as quantidades
            foreach ($_SESSION['carrinho'] as $item) {
                if (isset($item['quantidade'])) {
                    $totalItens += (int)$item['quantidade'];
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao contar itens no carrinho: " . $e->getMessage());
        return 0; // Retorna 0 em caso de erro de banco
    }
    
    return (int)$totalItens;
}

/**
 * Sincroniza o carrinho da sessão (visitante) com o carrinho do banco de dados (usuário logado).
 *
 * @param PDO $pdo A conexão com o banco de dados.
 * @param string $id_usuario O ID (UUID) do usuário que acabou de logar.
 */
function sincronizarCarrinho($pdo, $id_usuario) {
    // 1. Verifica se existe um carrinho de visitante na sessão
    if (empty($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        return; 
    }

    try {
        foreach ($_SESSION['carrinho'] as $id_produto_sessao => $item_sessao) {
            
            // 2. Obter dados básicos da sessão
            $id_produto = $item_sessao['id_produto'] ?? 0;
            $quantidade = $item_sessao['quantidade'] ?? 0;
            
            // 3. Verificar o preço (aqui está a causa do bug)
            $preco = $item_sessao['preco_unitario'] ?? 0.0;

            if ($preco <= 0.0 && $id_produto > 0) {
                $stmtPreco = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
                $stmtPreco->execute([$id_produto]);
                $produto_db = $stmtPreco->fetch(PDO::FETCH_ASSOC);
                
                if ($produto_db) {
                    $preco = (float)$produto_db['preco'];
                }
            }
            

            // 4. Pula o item se algum dado essencial ainda estiver faltando
            if ($id_produto <= 0 || $quantidade <= 0 || $preco <= 0.0) {
                continue; 
            }

            // 5. Verificar se o item já existe no carrinho do usuário (no BD)
            $stmtCheck = $pdo->prepare("SELECT quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
            $stmtCheck->execute([$id_usuario, $id_produto]);
            $item_existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($item_existente) {
                // Item existe: Somar quantidades
                $nova_quantidade = $item_existente['quantidade'] + $quantidade;
                $stmtUpdate = $pdo->prepare("UPDATE carrinho SET quantidade = ? WHERE usuario_id = ? AND id_produto = ?");
                $stmtUpdate->execute([$nova_quantidade, $id_usuario, $id_produto]);
            } else {
                // Item não existe: Inserir novo (agora com o preço correto)
                $stmtInsert = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                $stmtInsert->execute([$id_usuario, $id_produto, $quantidade, $preco]);
            }
        }
        
        // 76. Limpar o carrinho de visitante após a sincronização
        unset($_SESSION['carrinho']);

    } catch (PDOException $e) {
        error_log("Erro ao sincronizar carrinho: " . $e->getMessage());
        // Não limpa o carrinho de visitante se a sincronização falhar
    }
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