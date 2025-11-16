<?php

include 'session_config.php';

// Verifica se o utilizador está logado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}
$id_usuario = $_SESSION['usuario']['id'];

// Valida a ação e o ID do pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && isset($_POST['id_pedido'])) {
    $acao = $_POST['acao'];
    $id_pedido = (int)$_POST['id_pedido'];

    $pdo->beginTransaction();
    try {
        // Busca o pedido, garantindo que pertence ao usuário logado
        $stmt = $pdo->prepare("SELECT id_pedido, status FROM pedidos WHERE id_pedido = ? AND id_usuario = ?");
        $stmt->execute([$id_pedido, $id_usuario]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            throw new Exception("Pedido não encontrado ou não pertence a este usuário.");
        }

        // Lógica para CANCELAR
        if ($acao === 'cancelar' && $pedido['status'] === 'pendente') {
            // Busca todos os itens do pedido que será cancelado
            $stmtItens = $pdo->prepare("SELECT id_produto, quantidade FROM itens_pedido WHERE id_pedido = ?");
            $stmtItens->execute([$id_pedido]);
            $itens_do_pedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            // Devolve cada item ao estoque
            $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id_produto = ?");
            foreach ($itens_do_pedido as $item) {
                $stmtEstoque->execute([$item['quantidade'], $item['id_produto']]);
            }

            // Atualiza o status do pedido para 'cancelado'
            $stmtUpdate = $pdo->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id_pedido = ?");
            $stmtUpdate->execute([$id_pedido]);

            // Insere o log de atividade
            $logDescricao = "Pedido (#{$id_pedido}) cancelado pelo cliente.";
            $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
            $stmtLog->execute([$logDescricao]);
        } 
        // Lógica para PEDIR REEMBOLSO
        elseif ($acao === 'reembolso' && $pedido['status'] === 'entregue') {
            // Atualiza o status do pedido
            $stmtUpdate = $pdo->prepare("UPDATE pedidos SET status = 'reembolso_solicitado' WHERE id_pedido = ?");
            $stmtUpdate->execute([$id_pedido]);
            
            // Insere o log de atividade
            $logDescricao = "Solicitação de reembolso para o pedido (#{$id_pedido}) pelo cliente.";
            $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
            $stmtLog->execute([$logDescricao]);
        } 
        else {
            throw new Exception("Ação inválida para o status atual do pedido.");
        }

        $pdo->commit(); // Confirma todas as alterações
        header('Location: meus_pedidos.php?acao=sucesso');

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Erro em pedido_acao.php: " . $e->getMessage());
        header('Location: meus_pedidos.php?acao=erro');
    }
} else {
    header('Location: meus_pedidos.php');
}
exit;