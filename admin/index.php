<?php
include 'includes/header.php';

// --- LÓGICA PARA BUSCAR DADOS PARA OS GRÁFICOS ---
try {
    // 1. Dados para o gráfico de Vendas nos Últimos 7 Dias
    $vendas_sql = "
        SELECT
            d.day::date AS sale_date,
            COALESCE(SUM(p.total), 0) AS total_sales
        FROM
            generate_series(
                CURRENT_DATE - INTERVAL '6 days',
                CURRENT_DATE,
                INTERVAL '1 day'
            ) AS d(day)
        LEFT JOIN
            public.pedidos p ON d.day::date = p.data_pedido::date AND p.status = 'entregue'
        GROUP BY
            d.day
        ORDER BY
            d.day ASC;
    ";
    $stmtVendas = $pdo->query($vendas_sql);
    $vendas_data = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);

    // Formata os dados para o JavaScript
    $vendas_labels = json_encode(array_map(fn($row) => date('d/m', strtotime($row['sale_date'])), $vendas_data));
    $vendas_valores = json_encode(array_column($vendas_data, 'total_sales'));

    // 2. Dados para o gráfico de Pedidos por Status
    $status_sql = "SELECT status, COUNT(*) as count FROM public.pedidos GROUP BY status";
    $stmtStatus = $pdo->query($status_sql);
    $status_data = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

    $status_labels = json_encode(array_column($status_data, 'status'));
    $status_valores = json_encode(array_column($status_data, 'count'));

} catch (PDOException $e) {
    // Em caso de erro, define dados vazios para não quebrar o JS
    $vendas_labels = $vendas_valores = $status_labels = $status_valores = '[]';
    error_log("Erro no dashboard (gráficos): " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="page-title">Dashboard</h1>
    
    <div class="charts-grid">
        <div class="card">
            <div class="card-header">
                <h4>Vendas nos Últimos 7 Dias (Pedidos Entregues)</h4>
            </div>
            <div class="card-body">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h4>Pedidos por Status</h4>
            </div>
            <div class="card-body chart-pie-container">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>

    <div class="quick-actions card">
        <div class="card-header"><h4>Ações Rápidas</h4></div>
        <div class="card-body">
            <a href="produto_form.php" class="btn-admin">Adicionar Novo Produto</a>
            <a href="pedidos.php" class="btn-admin">Ver Todos os Pedidos</a>
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
                        'rgb(255, 193, 7)',   // Pendente (Amarelo)
                        'rgb(23, 162, 184)', // Enviado (Azul)
                        'rgb(40, 167, 69)',  // Entregue (Verde)
                        'rgb(220, 53, 69)'   // Cancelado (Vermelho)
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