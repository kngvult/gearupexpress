<?php
include '../includes/session_config.php';

$periodo = $_GET['periodo'] ?? 'semana';
$mes_selecionado = $_GET['mes'] ?? date('Y-m');

// Lógica igual ao index.php, mas retorna apenas os dados dos gráficos
if ($periodo === 'dia') {
    $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM-DD') as periodo, SUM(total) as total_vendas FROM pedidos WHERE status = 'entregue' AND data_pedido >= CURRENT_DATE - INTERVAL '6 days' GROUP BY TO_CHAR(data_pedido, 'YYYY-MM-DD') ORDER BY periodo ASC";
} elseif ($periodo === 'semana') {
    $vendas_sql = "SELECT EXTRACT(YEAR FROM data_pedido) as ano, EXTRACT(WEEK FROM data_pedido) as semana, MIN(data_pedido) as data_inicio, MAX(data_pedido) as data_fim, SUM(total) as total_vendas FROM pedidos WHERE status = 'entregue' AND data_pedido >= CURRENT_DATE - INTERVAL '30 days' GROUP BY ano, semana ORDER BY ano DESC, semana DESC LIMIT 6";
} elseif ($periodo === 'mes') {
    $vendas_sql = "SELECT TO_CHAR(data_pedido, 'YYYY-MM') as periodo, SUM(total) as total_vendas FROM pedidos WHERE status = 'entregue' AND TO_CHAR(data_pedido, 'YYYY-MM') = :mes_selecionado GROUP BY TO_CHAR(data_pedido, 'YYYY-MM') ORDER BY periodo ASC";
}

$stmtVendas = $pdo->prepare($vendas_sql);
if ($periodo === 'mes') {
    $stmtVendas->bindParam(':mes_selecionado', $mes_selecionado);
}
$stmtVendas->execute();
$vendas_data = $stmtVendas->fetchAll(PDO::FETCH_ASSOC);

$vendas_labels = [];
$vendas_valores = [];
if ($periodo === 'dia') {
    foreach ($vendas_data as $venda) {
        $vendas_labels[] = date('d/m', strtotime($venda['periodo']));
        $vendas_valores[] = (float)$venda['total_vendas'];
    }
} elseif ($periodo === 'semana') {
    foreach ($vendas_data as $venda) {
        $inicio = date('d/m', strtotime($venda['data_inicio']));
        $fim = date('d/m', strtotime($venda['data_fim']));
        $vendas_labels[] = "Sem " . $venda['semana'] . " ($inicio - $fim)";
        $vendas_valores[] = (float)$venda['total_vendas'];
    }
    $vendas_labels = array_reverse($vendas_labels);
    $vendas_valores = array_reverse($vendas_valores);
} elseif ($periodo === 'mes') {
    foreach ($vendas_data as $venda) {
        $vendas_labels[] = date('m/Y', strtotime($venda['periodo'] . '-01'));
        $vendas_valores[] = (float)$venda['total_vendas'];
    }
}

echo json_encode([
    'labels' => $vendas_labels,
    'valores' => $vendas_valores
]);