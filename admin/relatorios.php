<?php
include 'includes/header.php';

// --- LÓGICA PARA BUSCAR DADOS PARA OS RELATÓRIOS ---
try {
    // 1. Faturamento mensal (últimos 12 meses) - Sem alterações
    $stmtFaturamento = $pdo->query("
        SELECT 
            TO_CHAR(DATE_TRUNC('month', data_pedido), 'YYYY-MM') AS mes,
            SUM(total) as faturamento
        FROM pedidos
        WHERE status = 'entregue' AND data_pedido >= CURRENT_DATE - INTERVAL '12 months'
        GROUP BY mes
        ORDER BY mes ASC
    ");
    $faturamento_data = $stmtFaturamento->fetchAll(PDO::FETCH_ASSOC);
    $faturamento_labels = json_encode(array_column($faturamento_data, 'mes'));
    $faturamento_valores = json_encode(array_column($faturamento_data, 'faturamento'));

    // 2. Produtos mais vendidos (Top 5) - CORREÇÃO APLICADA AQUI
    $stmtMaisVendidos = $pdo->query("
        SELECT 
            p.nome, 
            SUM(ip.quantidade) AS total_vendido
        FROM itens_pedido ip
        JOIN produtos p ON ip.id_produto = p.id_produto
        JOIN pedidos o ON ip.id_pedido = o.id_pedido -- Adiciona a tabela de pedidos
        WHERE o.status = 'entregue'                 -- Filtra apenas por pedidos entregues
        GROUP BY p.nome
        ORDER BY total_vendido DESC
        LIMIT 5
    ");
    $mais_vendidos_data = $stmtMaisVendidos->fetchAll(PDO::FETCH_ASSOC);
    $mais_vendidos_labels = json_encode(array_column($mais_vendidos_data, 'nome'));
    $mais_vendidos_valores = json_encode(array_column($mais_vendidos_data, 'total_vendido'));

} catch (PDOException $e) {
    $faturamento_labels = $faturamento_valores = '[]';
    $mais_vendidos_labels = $mais_vendidos_valores = '[]';
    error_log("Erro ao buscar dados para relatórios: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="page-title">Relatórios de Vendas</h1>

    <div class="charts-grid">
        <div class="card">
            <div class="card-header"><h4>Faturamento Mensal (Pedidos Entregues)</h4></div>
            <div class="card-body">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Top 5 Produtos Mais Vendidos (de Pedidos Entregues)</h4></div>
            <div class="card-body chart-pie-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // --- GRÁFICO 1: FATURAMENTO MENSAL (BARRAS) ---
    const monthlyCtx = document.getElementById('monthlyRevenueChart');
    if (monthlyCtx) {
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= $faturamento_labels ?>,
                datasets: [{
                    label: 'Faturamento R$',
                    data: <?= $faturamento_valores ?>,
                    backgroundColor: 'rgba(0, 123, 255, 0.6)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1
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

    // --- GRÁFICO 2: PRODUTOS MAIS VENDIDOS (ROSCA/DONUT) ---
    const topProductsCtx = document.getElementById('topProductsChart');
    if (topProductsCtx) {
        new Chart(topProductsCtx, {
            type: 'doughnut',
            data: {
                labels: <?= $mais_vendidos_labels ?>,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: <?= $mais_vendidos_valores ?>,
                    backgroundColor: [
                        '#007bff', '#28a745', '#ffc107', '#17a2b8', '#6c757d'
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