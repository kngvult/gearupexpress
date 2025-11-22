<?php
include 'includes/header.php';

// 1. FORÇAR FUSO HORÁRIO NO PHP
date_default_timezone_set('America/Sao_Paulo');

try {
    $periodo = $_GET['periodo'] ?? 'hoje'; 
    $mes_selecionado = $_GET['mes'] ?? date('Y-m'); 
    
    $chart_labels = [];
    $chart_data = [];
    $sales_chart_title = 'Vendas';
    $temp_data = [];

    // =================================================================
    // PREPARAÇÃO DOS ESQUELETOS (Eixo X)
    // =================================================================

    if ($periodo === 'hoje') {
        $sales_chart_title = 'Vendas de Hoje (' . date('d/m') . ')';
        for ($i = 0; $i < 24; $i++) {
            $hora = str_pad($i, 2, '0', STR_PAD_LEFT);
            $chart_labels[] = $hora . ":00";
            $temp_data[$hora] = 0.0; 
        }
        $inicio_periodo = new DateTime(date('Y-m-d 00:00:00'));
        $fim_periodo    = new DateTime(date('Y-m-d 23:59:59'));

    } elseif ($periodo === 'semana') {
        $sales_chart_title = 'Vendas da Semana (Últimos 7 dias)';
        for ($i = 6; $i >= 0; $i--) {
            $dt = new DateTime("-$i days");
            $chave = $dt->format('Y-m-d');
            $label = $dt->format('d/m');
            $chart_labels[] = $label;
            $temp_data[$chave] = 0.0; 
        }
        $inicio_periodo = new DateTime(date('Y-m-d 00:00:00', strtotime('-6 days')));
        $fim_periodo    = new DateTime(date('Y-m-d 23:59:59'));

    } elseif ($periodo === 'mes') {
        $sales_chart_title = 'Vendas em ' . date('m/Y', strtotime($mes_selecionado));
        $dias_no_mes = (int)date('t', strtotime($mes_selecionado . '-01'));
        $ano_mes_arr = explode('-', $mes_selecionado);

        for ($d = 1; $d <= $dias_no_mes; $d++) {
            $dia_str = str_pad($d, 2, '0', STR_PAD_LEFT);
            $chave = $mes_selecionado . '-' . $dia_str;
            $chart_labels[] = $dia_str . '/' . $ano_mes_arr[1];
            $temp_data[$chave] = 0.0;
        }
        $inicio_periodo = new DateTime($mes_selecionado . '-01 00:00:00');
        $fim_periodo    = new DateTime($inicio_periodo->format('Y-m-t 23:59:59'));
    }
    
    $sql = "SELECT data_entrega, total 
            FROM pedidos 
            WHERE status = 'entregue' 
            AND data_entrega >= :inicio_largo
            ORDER BY data_entrega ASC";

    // Margem de segurança para fuso horário
    $inicio_sql = clone $inicio_periodo;
    $inicio_sql->modify('-1 day'); 
    
    $fim_sql = clone $fim_periodo;
    $fim_sql->modify('+1 day');

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':inicio_largo' => $inicio_sql->format('Y-m-d H:i:s')
    ]);

    $raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // =================================================================
    // PROCESSAMENTO LÓGICO NO PHP
    // =================================================================
    
    foreach ($raw_data as $row) {
        // Se data_entrega for null (erro de banco), pula
        if (empty($row['data_entrega'])) continue;

        $dt = new DateTime($row['data_entrega']);
        $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));

        if ($dt >= $inicio_periodo && $dt <= $fim_periodo) {
            if ($periodo === 'hoje') {
                $chave = $dt->format('H'); 
            } else {
                $chave = $dt->format('Y-m-d'); 
            }

            if (isset($temp_data[$chave])) {
                $temp_data[$chave] += (float)$row['total'];
            }
        }
    }
    $chart_data = array_values($temp_data);


    // --- OUTROS DADOS ---
    $stmtMeses = $pdo->query("SELECT DISTINCT TO_CHAR(data_pedido, 'YYYY-MM') as mes_ano FROM pedidos ORDER BY mes_ano DESC");
    $meses_disponiveis = $stmtMeses->fetchAll(PDO::FETCH_ASSOC);

    $stmtStatus = $pdo->query("SELECT status, COUNT(*) as count FROM public.pedidos GROUP BY status");
    $status_data = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

    $stmtEstoque = $pdo->prepare("SELECT id_produto, nome, estoque, imagem FROM produtos WHERE estoque <= 5 AND estoque > 0 ORDER BY estoque ASC");
    $stmtEstoque->execute();
    $produtos_baixo_estoque = $stmtEstoque->fetchAll(PDO::FETCH_ASSOC);

    $stmtLogs = $pdo->query("SELECT descricao, data_log FROM logs_atividade ORDER BY data_log DESC LIMIT 5");
    $logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log("Erro Dashboard: " . $e->getMessage());
    $chart_labels = []; $chart_data = []; $status_data = []; $produtos_baixo_estoque = []; $logs = [];
}
?>

<div class="main-content-header">
    <h1>Dashboard</h1>
</div>

<div class="main-content-body">
    
    <div class="card mb-4">
        <div class="card-header chart-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin:0;"><?= htmlspecialchars($sales_chart_title) ?></h4>
            <div class="chart-filters" style="display: flex; gap: 10px;">
                <a href="index.php?periodo=hoje" class="btn-admin-secondary <?= $periodo == 'hoje' ? 'active' : '' ?>" style="padding: 5px 10px;">Hoje</a>
                <a href="index.php?periodo=semana" class="btn-admin-secondary <?= $periodo == 'semana' ? 'active' : '' ?>" style="padding: 5px 10px;">Semana</a>
                
                <form method="GET" style="margin:0;">
                    <input type="hidden" name="periodo" value="mes">
                    <select name="mes" onchange="this.form.submit()" style="padding: 5px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">Mês</option>
                        <?php foreach ($meses_disponiveis as $mes): ?>
                            <option value="<?= $mes['mes_ano'] ?>" <?= $mes_selecionado == $mes['mes_ano'] ? 'selected' : '' ?>>
                                <?= date('m/Y', strtotime($mes['mes_ano'] . '-01')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>
        <div class="card-body" style="height: 300px;">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        <div class="card">
            <div class="card-header"><h4>Status dos Pedidos</h4></div>
            <div class="card-body" style="height: 250px; display:flex; justify-content:center;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4>Atividades Recentes</h4></div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p style="color: #777;">Nenhuma atividade.</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($logs as $log): ?>
                            <li style="border-bottom: 1px solid #eee; padding: 8px 0;">
                                <div><?= htmlspecialchars($log['descricao']) ?></div>
                                <small style="color: #888;"><?= date('d/m H:i', strtotime($log['data_log'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h4>⚠️ Baixo Estoque</h4></div>
            <div class="card-body">
                <?php if (empty($produtos_baixo_estoque)): ?>
                    <p style="color: green;">Estoque OK!</p>
                <?php else: ?>
                    <ul style="list-style: none; padding: 0;">
                        <?php foreach ($produtos_baixo_estoque as $prod): ?>
                            <li style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                                <img src="<?= htmlspecialchars($prod['imagem']) ?>" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px;">
                                <div>
                                    <div style="font-weight: bold; font-size: 0.9rem;"><?= htmlspecialchars($prod['nome']) ?></div>
                                    <div style="color: #dc3545; font-size: 0.85rem;">Restam: <?= $prod['estoque'] ?></div>
                                </div>
                                <a href="produto_form.php?id=<?= $prod['id_produto'] ?>" class="btn-action btn-edit" style="margin-left: auto; font-size: 0.8rem;">Gerenciar</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const labelsSales = <?= json_encode($chart_labels) ?>;
    const dataSales = <?= json_encode($chart_data) ?>;
    
    const ctxSales = document.getElementById('salesChart').getContext('2d');
    new Chart(ctxSales, {
        type: 'line',
        data: {
            labels: labelsSales,
            datasets: [{
                label: 'Faturamento (R$)',
                data: dataSales,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } }
        }
    });

    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    const statusData = <?= json_encode($status_data) ?>;
    
    const labelsSt = [];
    const dataSt = [];
    const colorsSt = [];
    
    const colorMap = {
        'pendente': '#ffc107',
        'enviado': '#17a2b8',
        'entregue': '#28a745',
        'cancelado': '#dc3545',
        'reembolso_solicitado': '#fd7e14'
    };

    statusData.forEach(item => {
        const stKey = item.status.toLowerCase();
        labelsSt.push(stKey.charAt(0).toUpperCase() + stKey.slice(1));
        dataSt.push(item.count);
        colorsSt.push(colorMap[stKey] || '#6c757d');
    });

    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: labelsSt,
            datasets: [{
                data: dataSt,
                backgroundColor: colorsSt
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>