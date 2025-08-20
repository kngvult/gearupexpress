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
    try {
        $novo_status = $_POST['status'];
        // Atualiza o status
        $stmtUpdate = $pdo->prepare("UPDATE public.pedidos SET status = ? WHERE id_pedido = ?");
        $stmtUpdate->execute([$novo_status, $id_pedido]);
        
        // NOVA AÇÃO: Insere o log de atividade
        $logDescricao = "Status do pedido (#{$id_pedido}) alterado para '{$novo_status}'.";
        $stmtLog = $pdo->prepare("INSERT INTO logs_atividade (descricao) VALUES (?)");
        $stmtLog->execute([$logDescricao]);
        
        // Recarrega a página
        header("Location: pedido_detalhes.php?id=$id_pedido&status=success&msg=" . urlencode('Status atualizado!'));
        exit;
    } catch (PDOException $e) {
            $erro = "Erro ao atualizar o status do pedido.";
            error_log($e->getMessage());
        }
    }

    // 3. BUSCA OS DADOS DO PEDIDO E DO CLIENTE
    try {
        // Busca os dados principais do pedido, incluindo o endereço
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
            // Se encontrou o pedido, busca os itens dele
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
                <div class="card-header"><h4>Cliente e Entrega</h4></div>
                <div class="card-body">
                    <p><strong>Nome:</strong> <?= htmlspecialchars($pedido['nome_cliente']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($pedido['email_cliente']) ?></p>
                    <hr>
                    <p><strong>Endereço de Entrega:</strong></p>
                    <address>
                        <?= htmlspecialchars($pedido['endereco_rua']) ?>, <?= htmlspecialchars($pedido['endereco_numero']) ?><br>
                        <?= htmlspecialchars($pedido['endereco_bairro']) ?><br>
                        <?= htmlspecialchars($pedido['endereco_cidade']) ?> - <?= htmlspecialchars($pedido['endereco_estado']) ?><br>
                        CEP: <?= htmlspecialchars($pedido['endereco_cep']) ?>
                    </address>
                </div>
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
                    <form action="pedido_detalhes.php?id=<?= $id_pedido ?>" method="POST">
                        <div class="form-group">
                            <label for="status"><strong>Alterar Status do Pedido:</strong></label>
                            <select name="status" id="status" class="form-control">
                                <option value="pendente" <?= $pedido['status'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                                <option value="enviado" <?= $pedido['status'] == 'enviado' ? 'selected' : '' ?>>Enviado</option>
                                <option value="entregue" <?= $pedido['status'] == 'entregue' ? 'selected' : '' ?>>Entregue</option>
                                <option value="cancelado" <?= $pedido['status'] == 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                        <button type="submit" class="btn-admin">Atualizar Status</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <div class="card-header"><h4>Itens do Pedido</h4></div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Preço Unit.</th>
                            <th>Qtd.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens_pedido as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-cell">
                                        <img src="../assets/img/produtos/<?= htmlspecialchars($item['imagem']) ?>" alt="" class="table-img">
                                        <span><?= htmlspecialchars($item['nome_produto']) ?></span>
                                    </div>
                                </td>
                                <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                <td><?= htmlspecialchars($item['quantidade']) ?></td>
                                <td>R$ <?= number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>