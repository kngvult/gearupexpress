<?php
include 'includes/header.php';

// --- LÓGICA DA PÁGINA ---
$pedido = null;
$itens_pedido = [];
$erro = '';

// 1. Valida o ID do pedido na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $erro = "ID do pedido inválido.";
} else {
    $id_pedido = (int)$_GET['id'];
    
    // 2. LÓGICA PARA ATUALIZAR O STATUS (se o formulário for enviado)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
        $novo_status = $_POST['status'];
        
        $pdo->beginTransaction();
        try {
            // Verifica o status atual do pedido
            $stmtStatusAtual = $pdo->prepare("SELECT status FROM public.pedidos WHERE id_pedido = ?");
            $stmtStatusAtual->execute([$id_pedido]);
            $status_atual = $stmtStatusAtual->fetchColumn();

            // Se o pedido está sendo cancelado (e não estava cancelado antes), restaura o estoque
            if ($novo_status === 'cancelado' && $status_atual !== 'cancelado') {
                $stmtItens = $pdo->prepare("SELECT id_produto, quantidade FROM public.itens_pedido WHERE id_pedido = ?");
                $stmtItens->execute([$id_pedido]);
                $itens_do_pedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

                $stmtEstoque = $pdo->prepare("UPDATE public.produtos SET estoque = estoque + ? WHERE id_produto = ?");
                foreach ($itens_do_pedido as $item) {
                    $stmtEstoque->execute([$item['quantidade'], $item['id_produto']]);
                }
            }
            
            // Atualiza o status do pedido
            $stmtUpdate = $pdo->prepare("UPDATE public.pedidos SET status = ? WHERE id_pedido = ?");
            $stmtUpdate->execute([$novo_status, $id_pedido]);
            
            // Insere o log de atividade
            $logDescricao = "Status do pedido (#{$id_pedido}) alterado para '{$novo_status}'.";
            $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
            $stmtLog->execute([$logDescricao]);

            $pdo->commit(); // Confirma a transação

            header("Location: pedido_detalhes.php?id=$id_pedido&status=success&msg=" . urlencode('Status atualizado!'));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erro = "Erro ao atualizar o status do pedido.";
            error_log($e->getMessage());
        }
    }

    // 3. BUSCA OS DADOS DO PEDIDO E DO CLIENTE
    try {
        $stmtPedido = $pdo->prepare("
            SELECT p.*, u.nome AS nome_cliente, u_auth.email AS email_cliente
            FROM public.pedidos p
            JOIN public.perfis u ON p.id_usuario = u.id
            JOIN auth.users u_auth ON p.id_usuario = u_auth.id
            WHERE p.id_pedido = ?
        ");
        $stmtPedido->execute([$id_pedido]);
        $pedido = $stmtPedido->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $erro = "Pedido não encontrado.";
        } else {
            $stmtItens = $pdo->prepare("
                SELECT i.*, prod.nome AS nome_produto, prod.imagem
                FROM public.itens_pedido i
                JOIN public.produtos prod ON i.id_produto = prod.id_produto
                WHERE i.id_pedido = ?
            ");
            $stmtItens->execute([$id_pedido]);
            $itens_pedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $erro = "Erro ao buscar detalhes do pedido.";
        error_log($e->getMessage());
    }
}
// Verifica se o status do pedido é final e não pode ser alterado
$is_finalizado = ($pedido && ($pedido['status'] === 'cancelado' || $pedido['status'] === 'reembolso_solicitado'));

?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Detalhes do Pedido #<?= htmlspecialchars($id_pedido ?? '') ?></h1>
        <a href="pedidos.php" class="btn-admin-secondary">‹ Voltar para a Lista</a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert-admin alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($pedido): ?>
        
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert-admin alert-success">Status do pedido atualizado com sucesso!</div>
        <?php endif; ?>

        <div class="order-details-grid">
            <div class="card">
                </div>

            <div class="card">
                <div class="card-header"><h4>Resumo do Pedido</h4></div>
                <div class="card-body">
                    <p><strong>Data:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
                    <p><strong>Método de Pagamento:</strong> <?= htmlspecialchars($pedido['metodo_pagamento']) ?></p>
                    <p><strong>Valor dos Produtos:</strong> R$ <?= number_format($pedido['total'] - $pedido['valor_frete'], 2, ',', '.') ?></p>
                    <p><strong>Frete:</strong> R$ <?= number_format($pedido['valor_frete'], 2, ',', '.') ?></p>
                    <p class="order-total"><strong>Total do Pedido:</strong> R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                    <hr>
                    
                    <?php if ($is_finalizado): ?>
                        <div class="alert-admin alert-warning">
                            Este pedido foi marcado como "<?= htmlspecialchars($pedido['status']) ?>" e não pode mais ser alterado.
                        </div>
                    <?php else: ?>
                        <form action="pedido_detalhes.php?id=<?= $id_pedido ?>" method="POST">
                            <div class="form-group">
                                <label for="status"><strong>Alterar Status do Pedido:</strong></label>
                                <select name="status" id="status" class="form-control">
                                    <option value="pendente" <?= $pedido['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="enviado" <?= $pedido['status'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                    <option value="entregue" <?= $pedido['status'] == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                    <option value="cancelado" <?= $pedido['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    <option value="reembolso_solicitado" <?= $pedido['status'] == 'reembolso_solicitado' ? 'selected' : '' ?>>Reembolso Solicitado</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-admin">Atualizar Status</button>
                        </form>
                    <?php endif; ?>
                    </div>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>