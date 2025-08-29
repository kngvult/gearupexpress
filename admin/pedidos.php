<?php
// Inclui o cabeçalho, menu e verificador de segurança
include 'includes/header.php';

// Busca todos os pedidos, juntando com o nome do cliente para exibição
try {
    $stmt = $pdo->query("
        SELECT 
            p.id_pedido, 
            p.data_pedido, 
            p.status, 
            p.total, 
            u.nome AS nome_cliente
        FROM public.pedidos p
        JOIN public.perfis u ON p.id_usuario = u.id
        ORDER BY p.data_pedido DESC
    ");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pedidos = [];
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
}

// Buscar estatísticas para os cards
try {
    $stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM pedidos");
    $total_pedidos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtPendentes = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status = 'pendente'");
    $pedidos_pendentes = $stmtPendentes->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtEntregues = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status = 'entregue'");
    $pedidos_entregues = $stmtEntregues->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtFaturamento = $pdo->query("SELECT SUM(total) as total FROM pedidos WHERE status = 'entregue'");
    $faturamento_total = $stmtFaturamento->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_pedidos = $pedidos_pendentes = $pedidos_entregues = $faturamento_total = 0;
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}

$scripts_adicionais = "
<script>
$(document).ready(function() {
    $('#tabela-pedidos').DataTable({
        \"language\": {
            \"url\": \"https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json\"
        },
        \"order\": [[1, 'desc']], // Ordenar por data decrescente
        \"responsive\": true,
        \"pageLength\": 25,
        \"dom\": '<\"top\"lf>rt<\"bottom\"ip><\"clear\">',
        \"columnDefs\": [
            { \"className\": \"dt-center\", \"targets\": [0, 3, 4, 5] }
        ]
    });
});
</script>
";
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Gerenciar Pedidos</h1>
        <div class="header-actions">
            <button class="btn-admin-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(0, 123, 255, 0.1);">
                <i class="fas fa-shopping-cart" style="color: #007bff;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($total_pedidos, 0, ',', '.') ?></h3>
                <p>Total de Pedidos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1);">
                <i class="fas fa-clock" style="color: #ffc107;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($pedidos_pendentes, 0, ',', '.') ?></h3>
                <p>Pedidos Pendentes</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(40, 167, 69, 0.1);">
                <i class="fas fa-check-circle" style="color: #28a745;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($pedidos_entregues, 0, ',', '.') ?></h3>
                <p>Pedidos Entregues</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(111, 66, 193, 0.1);">
                <i class="fas fa-chart-line" style="color: #6f42c1;"></i>
            </div>
            <div class="stat-info">
                <h3>R$ <?= number_format($faturamento_total, 2, ',', '.') ?></h3>
                <p>Faturamento Total</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-list"></i> Lista de Pedidos</h4>
            <span class="card-subtitle"><?= count($pedidos) ?> pedido(s) encontrado(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-pedidos" class="data-table">
                    <thead>
                        <tr>
                            <th width="100">ID Pedido</th>
                            <th width="150">Data</th>
                            <th>Cliente</th>
                            <th width="120">Total</th>
                            <th width="140">Status</th>
                            <th width="120" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-shopping-cart" style="font-size: 48px; color: #dee2e6;"></i>
                                        <p class="mt-3">Nenhum pedido encontrado.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pedidos as $pedido): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-id">#<?= htmlspecialchars($pedido['id_pedido']) ?></span>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?>
                                        <small class="text-muted d-block"><?= date('H:i', strtotime($pedido['data_pedido'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="customer-name"><?= htmlspecialchars($pedido['nome_cliente']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <span class="order-total">R$ <?= number_format($pedido['total'], 2, ',', '.') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>">
                                            <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-actions">
                                            <a href="pedido_detalhes.php?id=<?= $pedido['id_pedido'] ?>" class="btn-action btn-view" title="Ver Detalhes">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>