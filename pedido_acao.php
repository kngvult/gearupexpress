<?php
// pedido_acao.php
session_start();
include 'includes/conexao.php';

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
        // Busca o pedido, garantindo que pertence ao utilizador logado (Segurança)
        $stmt = $pdo->prepare("SELECT id_pedido, status FROM pedidos WHERE id_pedido = ? AND id_usuario = ?");
        $stmt->execute([$id_pedido, $id_usuario]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            throw new Exception("Pedido não encontrado ou não pertence a esse cliente.");
        }

        $novo_status = '';
        $log_descricao = '';

        // Lógica para CANCELAR
        if ($acao === 'cancelar' && $pedido['status'] === 'pendente') {
            $novo_status = 'cancelado';
            $log_descricao = "Pedido (#{$id_pedido}) cancelado pelo cliente.";

            // Restaura o estoque
            $stmtItens = $pdo->prepare("SELECT id_produto, quantidade FROM itens_pedido WHERE id_pedido = ?");
            $stmtItens->execute([$id_pedido]);
            $itens_do_pedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id_produto = ?");
            foreach ($itens_do_pedido as $item) {
                $stmtEstoque->execute([$item['quantidade'], $item['id_produto']]);
            }
        } 
        // Lógica para PEDIR REEMBOLSO
        elseif ($acao === 'reembolso' && $pedido['status'] === 'entregue') {
            $novo_status = 'reembolso_solicitado';
            $log_descricao = "Solicitação de reembolso para o pedido (#{$id_pedido}) pelo cliente.";
        } 
        // Se a ação não for válida para o status atual, lança um erro
        else {
            throw new Exception("Ação inválida para o status atual do pedido.");
        }

        // Atualiza o status do pedido
        $stmtUpdate = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id_pedido = ?");
        $stmtUpdate->execute([$novo_status, $id_pedido]);

        // Insere o log de atividade
        $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
        $stmtLog->execute([$log_descricao]);

        $pdo->commit();
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