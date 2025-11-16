<?php
include 'includes/header.php';
include 'includes/session_config.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}
$id_usuario = $_SESSION['usuario']['id'];

// Valida o ID do pedido na URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Redireciona se o ID for inválido
    header('Location: meus_pedidos.php');
    exit;
}
$id_pedido = (int)$_GET['id'];

// Busca o pedido no banco, garantindo que ele pertence ao usuário logado (SEGURANÇA)
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE id_pedido = ? AND id_usuario = ?");
$stmt->execute([$id_pedido, $id_usuario]);
$pedido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    // Se o pedido não for encontrado ou não pertencer ao usuário, redireciona
    header('Location: meus_pedidos.php');
    exit;
}

// Lógica para definir os estágios do rastreio
$status_atual = $pedido['status'];
$data_pedido = new DateTime($pedido['data_pedido']);

$estagios = [
    'pendente' => [
        'titulo' => 'Pedido Realizado',
        'data' => $data_pedido->format('d/m/Y H:i'),
        'descricao' => 'Seu pedido foi recebido e está aguardando aprovação do pagamento.'
    ],
    'processando' => [ // Status intermediário que vamos simular
        'titulo' => 'Pagamento Aprovado',
        'data' => (clone $data_pedido)->modify('+30 minutes')->format('d/m/Y H:i'),
        'descricao' => 'O pagamento foi confirmado. Estamos preparando seu pedido.'
    ],
    'enviado' => [
        'titulo' => 'Pedido Enviado',
        'data' => (clone $data_pedido)->modify('+1 day')->format('d/m/Y H:i'),
        'descricao' => 'Seu pedido foi despachado e está a caminho do seu endereço.'
    ],
    'entregue' => [
        'titulo' => 'Pedido Entregue',
        'data' => (clone $data_pedido)->modify('+4 days')->format('d/m/Y H:i'),
        'descricao' => 'Seu pedido foi entregue com sucesso!'
    ]
];

// Define quais estágios foram concluídos com base no status do banco
$status_concluidos = [];
if ($status_atual == 'pendente') {
    $status_concluidos = ['pendente'];
} elseif ($status_atual == 'enviado') {
    $status_concluidos = ['pendente', 'processando', 'enviado'];
} elseif ($status_atual == 'entregue') {
    $status_concluidos = ['pendente', 'processando', 'enviado', 'entregue'];
} else { // Para 'cancelado' ou outros status
    $status_concluidos = ['pendente']; 
}
?>

<main class="page-content">
    <div class="container">
        <div class="tracking-header">
            <h2 class="section-title">Rastreio do Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?></h2>
            <p>Status atual: 
                <span class="status-badge status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>">
                    <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                </span>
            </p>
        </div>

        <div class="checkout-card">
            <ul class="tracking-timeline">
                <?php foreach ($estagios as $key => $estagio): ?>
                    <?php $is_concluido = in_array($key, $status_concluidos); ?>
                    <li class="<?= $is_concluido ? 'complete' : '' ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <h4><?= $estagio['titulo'] ?></h4>
                            <?php if ($is_concluido): ?>
                                <small><?= $estagio['data'] ?></small>
                                <p><?= $estagio['descricao'] ?></p>
                            <?php else: ?>
                                <p>Aguardando atualização.</p>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="back-link-container">
            <a href="meus_pedidos.php" class="btn btn-secondary">‹ Voltar para Meus Pedidos</a>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>