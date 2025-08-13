<?php
include 'includes/conexao.php';
include 'includes/header.php';

// Verifica login
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id'];

// Recebe o ID do pedido via GET e valida
if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>ID do pedido inválido.</p>";
    include 'includes/footer.php';
    exit;
}

$id_pedido = (int)$_GET['id'];

// Verifica se o pedido pertence ao usuário logado
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND id_usuario = ?");
$stmt->execute([$id_pedido, $id_usuario]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    echo "<p>Pedido não encontrado ou você não tem permissão para visualizar.</p>";
    include 'includes/footer.php';
    exit;
}

// Busca os itens do pedido
$stmtItens = $pdo->prepare("
    SELECT pi.quantidade, pi.preco_unitario, p.nome, p.imagem
    FROM itens_pedido pi
    JOIN produtos p ON pi.produto_id = p.id_produto
    WHERE pi.id_pedido = ?
");
$stmtItens->execute([$id_pedido]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Detalhes do Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?></h2>
<p><strong>Data do Pedido:</strong> <?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></p>
<p><strong>Status:</strong> <?= htmlspecialchars($pedido['status']) ?></p>

<?php if (empty($itens)): ?>
    <p>Este pedido não possui itens.</p>
<?php else: ?>
    <table border="1" cellpadding="10" cellspacing="0" style="width:100%; max-width:700px;">
        <thead>
            <tr>
                <th>Produto</th>
                <th>Quantidade</th>
                <th>Preço Unitário</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total = 0;
            foreach ($itens as $item):
                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                $total += $subtotal;
            ?>
            <tr>
                <td>
                    <?php if ($item['imagem']): ?>
                        <img src="<?= htmlspecialchars($item['imagem']) ?>" alt="<?= htmlspecialchars($item['nome']) ?>" style="width:50px; height:auto; vertical-align:middle; margin-right:10px;">
                    <?php endif; ?>
                    <?= htmlspecialchars($item['nome']) ?>
                </td>
                <td><?= $item['quantidade'] ?></td>
                <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                <td>R$ <?= number_format($subtotal, 2, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3" style="text-align:right;"><strong>Total do Pedido:</strong></td>
                <td><strong>R$ <?= number_format($total, 2, ',', '.') ?></strong></td>
            </tr>
        </tbody>
    </table>
<?php endif; ?>

<p><a href="meus_pedidos.php">Voltar para Meus Pedidos</a></p>

<?php include 'includes/footer.php'; ?>
