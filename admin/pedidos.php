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
$scripts_adicionais = "
<script>
$(document).ready(function() {
    $('#tabela-pedidos').DataTable({
        \"language\": {
            \"url\": \"https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json\"
        }
    });
});
</script>
";
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Gerenciar Pedidos</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Pedido</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pedidos)): ?>
                        <tr>
                            <td colspan="6" class="text-center">Nenhum pedido encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pedidos as $pedido): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($pedido['id_pedido']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                                <td><?= htmlspecialchars($pedido['nome_cliente']) ?></td>
                                <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>">
                                        <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="pedido_detalhes.php?id=<?= $pedido['id_pedido'] ?>" class="btn-action btn-view">Ver Detalhes</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    $('#tabela-pedidos').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json"
        }
    });
});
</script>

<?php
include 'includes/footer.php';
?>