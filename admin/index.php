<?php
include 'includes/header.php';

// --- LÓGICA PARA BUSCAR DADOS PARA O DASHBOARD ---
try {
    // 1. Dados para os gráficos (já existentes)
    $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM-DD') as dia, SUM(total) as total_vendas FROM pedidos WHERE status = 'entregue' AND data_pedido >= CURRENT_DATE - INTERVAL '6 days' GROUP BY dia ORDER BY dia ASC";
    $stmtVendas = $pdo->query($vendas_sql);
    $vendas_data = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
    $vendas_labels = json_encode(array_column($vendas_data, 'dia'));
    $vendas_valores = json_encode(array_column($vendas_data, 'total_vendas'));

    $status_sql = "SELECT status, COUNT(*) as count FROM public.pedidos GROUP BY status";
    $stmtStatus = $pdo->query($status_sql);
    $status_data = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
    $status_labels = json_encode(array_column($status_data, 'status'));
    $status_valores = json_encode(array_column($status_data, 'count'));

    // 2. NOVA LÓGICA: Buscar produtos com estoque baixo (limite: 5 unidades)
    $limite_estoque = 5;
    $stmtEstoque = $pdo->prepare("SELECT id_produto, nome, estoque, imagem FROM produtos WHERE estoque <= ? AND estoque > 0 ORDER BY estoque ASC");
    $stmtEstoque->execute([$limite_estoque]);
    $produtos_baixo_estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

    $stmtLogs = $pdo->query("SELECT descricao, data_log FROM logs_atividade ORDER BY data_log DESC LIMIT 5");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Em caso de erro, define dados vazios para não quebrar o JS
    $vendas_labels = $vendas_valores = $status_labels = $status_valores = '[]';
    $produtos_baixo_estoque = [];
    error_log("Erro no dashboard: " . $e->getMessage());
    $logs = [];
}
?>

<div class="container-fluid">
    <h1 class="page-title" style="margin-bottom: 43px;">Dashboard</h1>

    <div class="charts-grid">
        <div class="card">
            <div class="card-header"><h4>Vendas nos Últimos 7 Dias</h4></div>
            <div class="card-body"><canvas id="salesChart"></canvas></div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Pedidos por Status</h4></div>
            <div class="card-body chart-pie-container"><canvas id="statusChart"></canvas></div>
        </div>
    </div>

    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h4>Atividades Recentes</h4>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <p>Nenhuma atividade registrada ainda.</p>
            <?php else: ?>
                <ul class="activity-log-list">
                    <?php foreach ($logs as $log): ?>
                        <li class="activity-log-item">
                            <div class="log-icon"><i class="fas fa-history"></i></div>
                            <div class="log-details">
                                <span class="log-description"><?= htmlspecialchars($log['descricao']) ?></span>
                                <span class="log-time"><?= date('d/m/Y H:i', strtotime($log['data_log'])) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>⚠️ Alerta de Estoque Baixo (igual ou inferior a <?= $limite_estoque ?> unidades)</h4>
        </div>
        <div class="card-body">
            <?php if (empty($produtos_baixo_estoque)): ?>
                <p>Nenhum produto com baixo estoque no momento. Bom trabalho!</p>
            <?php else: ?>
                <ul class="low-stock-list">
                    <?php foreach ($produtos_baixo_estoque as $produto): ?>
                        <li class="low-stock-item">
                            <img src="../assets/img/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="table-img">
                            <div class="item-details">
                                <span class="item-name"><?= htmlspecialchars($produto['nome']) ?></span>
                                <span class="item-stock">Restam apenas <strong><?= $produto['estoque'] ?></strong> unidades</span>
                            </div>
                            <a href="produto_form.php?id=<?= $produto['id_produto'] ?>" class="btn-action btn-edit">Gerenciar</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- GRÁFICO 1: VENDAS (LINHA) ---
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?= $vendas_labels ?>,
                datasets: [{
                    label: 'Faturamento R$',
                    data: <?= $vendas_valores ?>,
                    fill: true,
                    borderColor: 'rgb(0, 123, 255)',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return 'R$ ' + value.toLocaleString('pt-BR'); }
                        }
                    }
                }
            }
        });
    }

    // --- GRÁFICO 2: STATUS (ROSCA/DONUT) ---
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= $status_labels ?>,
                datasets: [{
                    label: 'Pedidos',
                    data: <?= $status_valores ?>,
                    backgroundColor: [
                        'rgb(220, 53, 69)',  // Cancelado (Vermelho)
                        'rgb(255, 193, 7)',   // Pendente (Amarelo)
                        'rgb(23, 162, 184)', // Enviado (Azul)
                        'rgb(40, 167, 69)'  // Entregue (Verde)
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>