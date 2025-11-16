<?php
include 'session_config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Erro desconhecido.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_produto'])) {
    $id_usuario = $_SESSION['usuario']['id'] ?? null;
    $id_produto = (int)$_POST['id_produto'];
    $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;

    // Busca o preço do produto para garantir consistência
    $stmtProd = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
    $stmtProd->execute([$id_produto]);
    $produto = $stmtProd->fetch(PDO::FETCH_ASSOC);

    if ($produto && $id_produto > 0 && $quantidade > 0) {
        if ($id_usuario) {
            // Usuário logado: salva no banco
            try {
                $stmtCheck = $pdo->prepare("SELECT quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
                $stmtCheck->execute([$id_usuario, $id_produto]);
                $item_existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($item_existente) {
                    $nova_quantidade = $item_existente['quantidade'] + $quantidade;
                    $stmtUpdate = $pdo->prepare("UPDATE carrinho SET quantidade = ? WHERE usuario_id = ? AND id_produto = ?");
                    $stmtUpdate->execute([$nova_quantidade, $id_usuario, $id_produto]);
                } else {
                    $stmtInsert = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
                    $stmtInsert->execute([$id_usuario, $id_produto, $quantidade, $produto['preco']]);
                }

                $stmtCount = $pdo->prepare("SELECT SUM(quantidade) FROM carrinho WHERE usuario_id = ?");
                $stmtCount->execute([$id_usuario]);
                $totalItensCarrinho = (int)$stmtCount->fetchColumn();

                $response['success'] = true;
                $response['message'] = 'Produto adicionado com sucesso!';
                $response['totalItensCarrinho'] = $totalItensCarrinho;
            } catch (PDOException $e) {
                error_log("Erro no carrinho_adicionar.php: " . $e->getMessage());
                $response['message'] = 'Ocorreu um erro no servidor. Tente novamente.';
            }
        } else {
            // Visitante: salva na sessão
            if (!isset($_SESSION['carrinho'])) {
                $_SESSION['carrinho'] = [];
            }
            // Se já existe, soma a quantidade
            if (isset($_SESSION['carrinho'][$id_produto])) {
                $_SESSION['carrinho'][$id_produto]['quantidade'] += $quantidade;
            } else {
                $_SESSION['carrinho'][$id_produto] = [
                    'id_produto' => $id_produto,
                    'quantidade' => $quantidade,
                    'preco_unitario' => $produto['preco']
                ];
            }
            // Conta o total de itens no carrinho da sessão
            $totalItensCarrinho = 0;
            foreach ($_SESSION['carrinho'] as $item) {
                $totalItensCarrinho += $item['quantidade'];
            }
            $response['success'] = true;
            $response['message'] = 'Produto adicionado com sucesso!';
            $response['totalItensCarrinho'] = $totalItensCarrinho;
        }
    } else {
        $response['message'] = 'Produto não encontrado ou dados inválidos.';
    }
} else {
    $response['message'] = 'Método de requisição inválido.';
}

echo json_encode($response);
exit;