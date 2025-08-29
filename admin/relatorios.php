<?php
include 'includes/header.php';

// --- LÓGICA PARA BUSCAR DADOS PARA OS RELATÓRIOS ---
try {
    // 1. Faturamento mensal (últimos 12 meses)
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
    
    // Formatar os labels para exibição (MM/YYYY)
    $faturamento_labels_formatted = [];
    foreach ($faturamento_data as $item) {
        $faturamento_labels_formatted[] = date('m/Y', strtotime($item['mes'] . '-01'));
    }
    
    $faturamento_labels = json_encode($faturamento_labels_formatted);
    $faturamento_valores = json_encode(array_column($faturamento_data, 'faturamento'));

    // 2. Produtos mais vendidos (Top 10)
    $stmtMaisVendidos = $pdo->query("
        SELECT 
            p.nome, 
            SUM(ip.quantidade) AS total_vendido
        FROM itens_pedido ip
        JOIN produtos p ON ip.id_produto = p.id_produto
        JOIN pedidos o ON ip.id_pedido = o.id_pedido
        WHERE o.status = 'entregue'
        GROUP BY p.nome
        ORDER BY total_vendido DESC
        LIMIT 10
    ");
    $mais_vendidos_data = $stmtMaisVendidos->fetchAll(PDO::FETCH_ASSOC);
    $mais_vendidos_labels = json_encode(array_column($mais_vendidos_data, 'nome'));
    $mais_vendidos_valores = json_encode(array_column($mais_vendidos_data, 'total_vendido'));

    // 3. Estatísticas adicionais
    $stmtTotalVendas = $pdo->query("SELECT COUNT(*) as total FROM pedidos WHERE status = 'entregue'");
    $total_vendas = $stmtTotalVendas->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtFaturamentoTotal = $pdo->query("SELECT SUM(total) as total FROM pedidos WHERE status = 'entregue'");
    $faturamento_total = $stmtFaturamentoTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtMediaPedido = $pdo->query("SELECT AVG(total) as media FROM pedidos WHERE status = 'entregue'");
    $media_pedido = $stmtMediaPedido->fetch(PDO::FETCH_ASSOC)['media'];

} catch (PDOException $e) {
    $faturamento_labels = $faturamento_valores = '[]';
    $mais_vendidos_labels = $mais_vendidos_valores = '[]';
    $total_vendas = $faturamento_total = $media_pedido = 0;
    error_log("Erro ao buscar dados para relatórios: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Relatórios de Vendas</h1>
        <div class="header-actions">
            <button class="btn-admin-secondary" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir Relatório
            </button>
        </div>
    </div>

    <!-- Cards de Estatísticas Rápidas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(40, 167, 69, 0.1);">
                <i class="fas fa-shopping-bag" style="color: #28a745;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($total_vendas, 0, ',', '.') ?></h3>
                <p>Total de Vendas</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(0, 123, 255, 0.1);">
                <i class="fas fa-chart-line" style="color: #007bff;"></i>
            </div>
            <div class="stat-info">
                <h3>R$ <?= number_format($faturamento_total, 2, ',', '.') ?></h3>
                <p>Faturamento Total</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1);">
                <i class="fas fa-receipt" style="color: #ffc107;"></i>
            </div>
            <div class="stat-info">
                <h3>R$ <?= number_format($media_pedido, 2, ',', '.') ?></h3>
                <p>Ticket Médio</p>
            </div>
        </div>
    </div>

    <div class="charts-grid">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-chart-bar"></i> Faturamento Mensal (Pedidos Entregues)</h4>
                <span class="card-subtitle">Últimos 12 meses</span>
            </div>
            <div class="card-body">
                <canvas id="monthlyRevenueChart"></canvas>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-star"></i> Produtos Mais Vendidos</h4>
                <span class="card-subtitle">Top 10 produtos</span>
            </div>
            <div class="card-body chart-pie-container">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabela de produtos mais vendidos para melhor visualização -->
    <div class="card" style="margin-top: 30px;">
        <div class="card-header">
            <h4><i class="fas fa-list"></i> Detalhes dos Produtos Mais Vendidos</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Posição</th>
                            <th>Produto</th>
                            <th>Quantidade Vendida</th>
                            <th>Percentual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_vendido = array_sum(array_column($mais_vendidos_data, 'total_vendido'));
                        $position = 1;
                        foreach ($mais_vendidos_data as $produto): 
                            $percentual = $total_vendido > 0 ? ($produto['total_vendido'] / $total_vendido) * 100 : 0;
                        ?>
                        <tr>
                            <td><?= $position++ ?></td>
                            <td><?= htmlspecialchars($produto['nome']) ?></td>
                            <td><?= number_format($produto['total_vendido'], 0, ',', '.') ?> unidades</td>
                            <td>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?= $percentual ?>%">
                                        <span><?= number_format($percentual, 1) ?>%</span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                    backgroundColor: 'rgba(0, 123, 255, 0.7)',
                    borderColor: 'rgba(0, 123, 255, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    hoverBackgroundColor: 'rgba(0, 123, 255, 0.9)'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'R$ ' + context.raw.toLocaleString('pt-BR', {minimumFractionDigits: 2});
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) { 
                                return 'R$ ' + value.toLocaleString('pt-BR'); 
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
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
                        '#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d',
                        '#17a2b8', '#343a40', '#fd7e14', '#20c997', '#6f42c1'
                    ],
                    borderWidth: 1,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((value / total) * 100).toFixed(1);
                                return `${label}: ${value} unidades (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>