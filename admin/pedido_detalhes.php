<?php
include '../includes/conexao.php';
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
        // Busca o status atual do pedido
        $stmtStatusAtual = $pdo->prepare("SELECT status FROM public.pedidos WHERE id_pedido = ?");
        $stmtStatusAtual->execute([$id_pedido]);
        $status_atual = $stmtStatusAtual->fetchColumn();

        // Se o status está mudando para "cancelado", restaura o estoque
        if ($novo_status === 'cancelado' && $status_atual !== 'cancelado') {
            $stmtItens = $pdo->prepare("SELECT id_produto, quantidade FROM public.itens_pedido WHERE id_pedido = ?");
            $stmtItens->execute([$id_pedido]);
            $itens_do_pedido = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

            if ($itens_do_pedido) {
                $stmtEstoque = $pdo->prepare("UPDATE public.produtos SET estoque = estoque + ? WHERE id_produto = ?");
                foreach ($itens_do_pedido as $item) {
                    $stmtEstoque->execute([$item['quantidade'], $item['id_produto']]);
                }
            }
        }
        
        // Atualiza o status do pedido
        $stmtUpdate = $pdo->prepare("UPDATE public.pedidos SET status = ? WHERE id_pedido = ?");
        $stmtUpdate->execute([$novo_status, $id_pedido]);
        
        // Insere o log de atividade
        $logDescricao = "Status do pedido (#{$id_pedido}) alterado para '{$novo_status}'.";
        $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
        $stmtLog->execute([$logDescricao]);

        $pdo->commit();

        header("Location: pedido_detalhes.php?id=$id_pedido&status=success&msg=" . urlencode('Status atualizado!'));
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $erro = "Erro ao atualizar o status do pedido.";
        error_log("Erro ao atualizar status (Admin): " . $e->getMessage());
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

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="page-title">Detalhes do Pedido #<?= htmlspecialchars($id_pedido ?? '') ?></h1>
        <a href="pedidos.php" class="btn-admin-secondary">‹ Voltar para a Lista</a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert-admin alert-success" style="margin-bottom: 20px;">
            <i class="fas fa-check-circle"></i> 
            <?= isset($_GET['msg']) ? htmlspecialchars(urldecode($_GET['msg'])) : 'Status do pedido atualizado com sucesso!' ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($erro)): ?>
        <div class="alert-admin alert-danger" style="margin-bottom: 20px;">
            <i class="fas fa-exclamation-circle"></i> 
            <?= htmlspecialchars($erro) ?>
        </div>
    <?php elseif ($pedido): ?>
        
        <div class="order-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
            <!-- Informações do Cliente -->
            <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                <div class="card-header" style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                    <h4 style="margin: 0; font-size: 18px; color: #333;">
                        <i class="fas fa-user" style="margin-right: 8px;"></i> Informações do Cliente
                    </h4>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Nome:</strong>
                        <span><?= htmlspecialchars($pedido['nome_cliente']) ?></span>
                    </div>
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Email:</strong>
                        <span><?= htmlspecialchars($pedido['email_cliente']) ?></span>
                    </div>
                    <div class="info-group">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Endereço de Entrega:</strong>
                        <span>
                            <?= htmlspecialchars($pedido['endereco_rua']) ?>, <?= htmlspecialchars($pedido['endereco_numero']) ?><br>
                            <?= htmlspecialchars($pedido['endereco_bairro']) ?> - <?= htmlspecialchars($pedido['endereco_cidade']) ?>/<?= htmlspecialchars($pedido['endereco_estado']) ?><br>
                            CEP: <?= htmlspecialchars($pedido['endereco_cep']) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Resumo do Pedido -->
            <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden;">
                <div class="card-header" style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                    <h4 style="margin: 0; font-size: 18px; color: #333;">
                        <i class="fas fa-receipt" style="margin-right: 8px;"></i> Resumo do Pedido
                    </h4>
                </div>
                <div class="card-body" style="padding: 20px;">
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Data:</strong>
                        <span><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></span>
                    </div>
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Método de Pagamento:</strong>
                        <span><?= htmlspecialchars($pedido['metodo_pagamento']) ?></span>
                    </div>
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Valor dos Produtos:</strong>
                        <span>R$ <?= number_format($pedido['total'] - $pedido['valor_frete'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-group" style="margin-bottom: 15px;">
                        <strong style="display: block; color: #666; margin-bottom: 5px;">Frete:</strong>
                        <span>R$ <?= number_format($pedido['valor_frete'], 2, ',', '.') ?></span>
                    </div>
                    <div class="info-group" style="margin-bottom: 20px; padding-top: 15px; border-top: 1px dashed #e0e0e0;">
                        <strong style="display: block; color: #333; margin-bottom: 5px; font-size: 16px;">Total do Pedido:</strong>
                        <span style="font-size: 18px; font-weight: bold; color: #28a745;">R$ <?= number_format($pedido['total'], 2, ',', '.') ?></span>
                    </div>
                    
                    <?php if ($is_finalizado): ?>
                        <div class="alert-admin alert-warning" style="background-color: #fff3cd; color: #856404; padding: 12px; border-radius: 6px; border-left: 4px solid #ffc107; margin-top: 15px;">
                            <i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>
                            Este pedido foi marcado como "<?= htmlspecialchars($pedido['status']) ?>" e não pode mais ser alterado.
                        </div>
                    <?php else: ?>
                        <form action="pedido_detalhes.php?id=<?= $id_pedido ?>" method="POST">
                            <div class="form-group" style="margin-bottom: 15px;">
                                <label for="status" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    <i class="fas fa-sync-alt" style="margin-right: 8px;"></i> Alterar Status do Pedido:
                                </label>
                                <select name="status" id="status" class="form-control" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px;">
                                    <option value="pendente" <?= $pedido['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                    <option value="enviado" <?= $pedido['status'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                    <option value="entregue" <?= $pedido['status'] == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                    <option value="cancelado" <?= $pedido['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                                    <option value="reembolso_solicitado" <?= $pedido['status'] == 'reembolso_solicitado' ? 'selected' : '' ?>>Reembolso Solicitado</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-admin" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500;">
                                <i class="fas fa-save" style="margin-right: 8px;"></i> Atualizar Status
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Itens do Pedido -->
        <div class="card" style="border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; margin-top: 30px;">
            <div class="card-header" style="background-color: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #e0e0e0;">
                <h4 style="margin: 0; font-size: 18px; color: #333;">
                    <i class="fas fa-shopping-cart" style="margin-right: 8px;"></i> Itens do Pedido
                </h4>
            </div>
            <div class="card-body" style="padding: 20px;">
                <?php if (!empty($itens_pedido)): ?>
                    <div class="order-items-list">
                        <?php foreach ($itens_pedido as $item): ?>
                            <div class="order-item" style="display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #f0f0f0;">
                                <img src="../assets/img/produtos/<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome_produto']) ?>" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; margin-right: 15px;">
                                <div class="item-details" style="flex: 1;">
                                    <h5 style="margin: 0 0 5px 0; font-size: 16px; color: #333;"><?= htmlspecialchars($item['nome_produto']) ?></h5>
                                    <p style="margin: 0; color: #666;">Quantidade: <?= $item['quantidade'] ?></p>
                                    <p style="margin: 0; color: #666;">Preço unitário: R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></p>
                                </div>
                                <div class="item-total" style="font-weight: bold; color: #28a745;">
                                    R$ <?= number_format($item['quantidade'] * $item['preco_unitario'], 2, ',', '.') ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="margin: 0; color: #666;">Nenhum item encontrado para este pedido.</p>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>