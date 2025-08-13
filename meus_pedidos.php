<?php
include 'includes/conexao.php';
include 'includes/header.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Busca pedidos do usuário
$stmt = $pdo->prepare("
    SELECT id_pedido, data_pedido, status, total
    FROM pedidos
    WHERE id_usuario = ?
    ORDER BY data_pedido DESC
");
$stmt->execute([$id_usuario]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Meus Pedidos</h2>

<?php if (empty($pedidos)): ?>
    <p>Você ainda não realizou nenhum pedido.</p>
<?php else: ?>
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; max-width:800px;">
        <thead>
            <tr>
                <th>ID Pedido</th>
                <th>Data</th>
                <th>Status</th>
                <th>Total</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td><?= htmlspecialchars($pedido['id_pedido']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></td>
                    <td><?= htmlspecialchars($pedido['status']) ?></td>
                    <td>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></td>
                    <td>
                        <a href="detalhes_pedido.php?id=<?= $pedido['id_pedido'] ?>">Detalhes</a> |
                        <a href="rastrear_pedido.php?id=<?= $pedido['id_pedido'] ?>">Rastrear</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
