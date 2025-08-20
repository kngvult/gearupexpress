<?php
// ETAPA 1: LÓGICA DE BACKEND E VERIFICAÇÕES (ANTES DE QUALQUER HTML)
// session_start() será chamado pelo header.php, mas podemos garantir aqui.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário está logado usando a chave correta da sessão
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/conexao.php';

$id_usuario = $_SESSION['usuario']['id'];
// Busca os pedidos do usuário no banco de dados
$pedidos = [];
try {
    $stmt = $pdo->prepare("
        SELECT id_pedido, data_pedido, status, total, metodo_pagamento
        FROM public.pedidos
        WHERE id_usuario = ?
        ORDER BY data_pedido DESC
    ");
    $stmt->execute([$id_usuario]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
}

// ETAPA 2: RENDERIZAÇÃO DO HTML (SÓ AGORA COMEÇAMOS A ENVIAR CONTEÚDO)
include 'includes/header.php';
?>

<main class="page-content">
<div class="container">
    <h2 class="section-title">Meus Pedidos</h2>

    <?php if (isset($_GET['order']) && $_GET['order'] == 'success'): ?>
        <div class="alert alert-success">
            Seu pedido foi realizado com sucesso e já está disponível abaixo!
        </div>
    <?php endif; ?>

    <?php if (empty($pedidos)): ?>
        <div class="info-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi bi-box-seam" viewBox="0 0 16 16">
                <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2l-2.218-.887zm3.564 1.426L5.596 5 8 5.961 14.154 3.5l-2.404-.961zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923l6.5 2.6zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464L7.443.184z"/>
            </svg>
            <h3>Você ainda não fez nenhum pedido.</h3>
            <p>Explore nossos produtos e encontre o que seu carro precisa!</p>
            <a href="index.php" class="btn btn-primary">Ir para a Loja</a>
        </div>
    <?php else: ?>
        <div class="pedidos-lista">
            <?php foreach ($pedidos as $pedido): ?>
                <div class="pedido-card">
                    <div class="pedido-header">
                        <div class="pedido-id">Pedido #<?= htmlspecialchars($pedido['id_pedido']) ?></div>
                        <div class="pedido-data"><?= date('d/m/Y H:i', strtotime($pedido['data_pedido'])) ?></div>
                    </div>
                    <div class="pedido-body">
                        <div class="pedido-info">
                            <strong>Status:</strong>
                            <span class="status-badge status-<?= strtolower(htmlspecialchars($pedido['status'])) ?>">
                                <?= ucfirst(htmlspecialchars($pedido['status'])) ?>
                            </span>
                        </div>
                        <div class="pedido-info">
                            <strong>Pagamento:</strong>
                            <span><?= htmlspecialchars($pedido['metodo_pagamento']) ?></span>
                        </div>
                        <div class="pedido-info total">
                            <strong>Total:</strong>
                            <span>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></span>
                        </div>
                    </div>
                    <div class="pedido-footer">
                        <a href="detalhes_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-secondary">Ver Detalhes</a>
                        <?php if ($pedido['status'] !== 'cancelado'): ?>
                            <a href="rastrear_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-primary">Rastrear Pedido</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</main>

<?php include 'includes/footer.php'; ?>