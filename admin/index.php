<?php
include 'includes/header.php';

try {
    // --- LÓGICA DO GRÁFICO DE VENDAS ---
    $periodo = $_GET['periodo'] ?? '7dias'; // Padrão: últimos 7 dias
    $mes_selecionado = $_GET['mes'] ?? ''; // Para o filtro mensal
    $sales_chart_title = 'Vendas nos Últimos 7 Dias';
    
    $vendas_sql = "";
    $params = [];

    switch ($periodo) {
        case 'hoje':
            $sales_chart_title = 'Vendas de Hoje (por hora)';
            $vendas_sql = "SELECT TO_CHAR(data_pedido, 'HH24:00') as hora, SUM(total) as total_vendas 
                            FROM pedidos WHERE status = 'entregue' AND DATE(data_pedido) = CURRENT_DATE 
                            GROUP BY hora ORDER BY hora ASC";
            break;
        case 'semana':
            $sales_chart_title = 'Vendas desta Semana';
            $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM-DD') as dia, SUM(total) as total_vendas 
                            FROM pedidos WHERE status = 'entregue' AND data_pedido >= date_trunc('week', CURRENT_DATE)
                            GROUP BY dia ORDER BY dia ASC";
            break;
        case 'mes':
            if (!empty($mes_selecionado)) {
                $sales_chart_title = 'Vendas de ' . date('m/Y', strtotime($mes_selecionado . '-01'));
                $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM-DD') as dia, SUM(total) as total_vendas 
                                FROM pedidos WHERE status = 'entregue' AND TO_CHAR(data_pedido, 'YYYY-MM') = ?
                                GROUP BY dia ORDER BY dia ASC";
                $params[] = $mes_selecionado;
            }
            break;
        default: // '7dias'
            $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM-DD') as dia, SUM(total) as total_vendas 
                            FROM pedidos WHERE status = 'entregue' AND data_pedido >= CURRENT_DATE - INTERVAL '6 days' 
                            GROUP BY dia ORDER BY dia ASC";
            break;
    }

    if ($vendas_sql) {
        $stmtVendas = $pdo->prepare($vendas_sql);
        $stmtVendas->execute($params);
        $vendas_data = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);
        $vendas_labels = json_encode(array_column($vendas_data, key($vendas_data[0] ?? ['dia'])));
        $vendas_valores = json_encode(array_column($vendas_data, 'total_vendas'));
    }

    // --- LÓGICA PARA POPULAR O DROPDOWN DE MESES ---
    $stmtMeses = $pdo->query("SELECT DISTINCT TO_CHAR(data_pedido, 'YYYY-MM') as mes_ano FROM pedidos ORDER BY mes_ano DESC");
    $meses_disponiveis = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

    // --- LÓGICA DO GRÁFICO DE STATUS ---
    $status_sql = "SELECT status, COUNT(*) as count FROM public.pedidos GROUP BY status";
    $stmtStatus = $pdo->query($status_sql);
    $status_data = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
    // Os dados serão processados no JavaScript para incluir as novas cores/legendas

    // --- LÓGICA DOS CARDS ADICIONAIS ---
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
if (!isset($vendas_labels)) $vendas_labels = json_encode([]);
if (!isset($vendas_valores)) $vendas_valores = json_encode([]);
$status_labels = json_encode(array_column($status_data ?? [], 'status'));
$status_valores = json_encode(array_column($status_data ?? [], 'count'));
?>

<div class="container-fluid">
    <h1 class="page-title" style="margin-bottom: 43px;">Dashboard</h1>

    <div class="charts-grid">
        <div class="card">
            <div class="card-header chart-header">
                <h4><?= $sales_chart_title ?></h4>
                <div class="chart-filters">
                    <a href="index.php?periodo=hoje" class="filter-btn <?= $periodo == 'hoje' ? 'active' : '' ?>">Hoje</a>
                    <a href="index.php?periodo=semana" class="filter-btn <?= $periodo == 'semana' ? 'active' : '' ?>">Semana</a>
                    <a href="index.php?periodo=7dias" class="filter-btn <?= $periodo == '7dias' ? 'active' : '' ?>">7 Dias</a>
                    <form id="month-filter-form" method="GET" class="filter-form">
                        <input type="hidden" name="periodo" value="mes">
                        <select name="mes" id="month-select" class="filter-select">
                            <option value="">Por Mês</option>
                            <?php foreach ($meses_disponiveis as $mes): ?>
                                <option value="<?= $mes['mes_ano'] ?>" <?= $mes_selecionado == $mes['mes_ano'] ? 'selected' : '' ?>>
                                    <?= date('m/Y', strtotime($mes['mes_ano'] . '-01')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
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
    const vendasLabels = <?= $vendas_labels ?>;
    const vendasValores = <?= $vendas_valores ?>;
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
        const statusDataFromPHP = <?= json_encode($status_data) ?>;
        
        // Mapeamento de status para cores e legendas personalizadas
        const statusConfig = {
            'pendente': { label: 'Pendente', color: 'rgb(255, 193, 7)' },
            'enviado': { label: 'Enviado', color: 'rgb(23, 162, 184)' },
            'entregue': { label: 'Entregue', color: 'rgb(40, 167, 69)' },
            'cancelado': { label: 'Cancelado', color: 'rgb(220, 53, 69)' },
            'reembolso_solicitado': { label: 'Reembolso Solicitado', color: 'rgb(253, 126, 20)' } // Nova cor (Laranja)
        };

        const chartLabels = [];
        const chartData = [];
        const chartColors = [];

        statusDataFromPHP.forEach(item => {
            if (statusConfig[item.status]) {
                chartLabels.push(statusConfig[item.status].label);
                chartData.push(item.count);
                chartColors.push(statusConfig[item.status].color);
            }
        });

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Pedidos',
                    data: chartData,
                    backgroundColor: chartColors,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    // --- LÓGICA PARA O FILTRO DE MÊS ---
    const monthSelect = document.getElementById('month-select');
    if (monthSelect) {
        monthSelect.addEventListener('change', function() {
            if (this.value) {
                document.getElementById('month-filter-form').submit();
            }
        });
    }
});
</script>

<?php
include 'includes/footer.php';
?>