<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

// Verifica se o usuário está logado usando a chave correta da sessão
if (!isset($_SESSION['usuario']['id'])) {
    header('Location: login.php');
    exit;
}

$id_usuario = $_SESSION['usuario']['id']; // Este é o UUID do usuário

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
    // Em caso de erro, podemos logar e mostrar uma mensagem amigável
    error_log("Erro ao buscar pedidos: " . $e->getMessage());
    // A página continuará, mas exibirá a mensagem de "nenhum pedido".
}

?>

<main class="page-content">
<div class="container">
    <h2 class="section-title">Meus Pedidos</h2>

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
                        <?php if ($pedido['status'] === 'enviado'): ?>
                            <a href="rastrear_pedido.php?id=<?= $pedido['id_pedido'] ?>" class="btn btn-primary">Rastrear Pedido</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</main>

<style>
/* Adicione este CSS ao seu arquivo estilos.css principal */
.pedidos-lista {
    display: grid;
    gap: 20px;
}
.pedido-card {
    background-color: var(--white);
    border-radius: 12px;
    box-shadow: var(--soft-shadow);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}
.pedido-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.08);
}
.pedido-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background-color: var(--background-light);
    border-bottom: 1px solid var(--border-color);
    border-radius: 12px 12px 0 0;
}
.pedido-id { font-weight: 600; color: var(--dark-text); }
.pedido-data { font-size: 0.9rem; color: var(--light-text); }
.pedido-body {
    padding: 20px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}
.pedido-info {
    display: flex;
    flex-direction: column;
}
.pedido-info.total {
    font-size: 1.2rem;
    font-weight: 600;
}
.pedido-info strong {
    font-size: 0.8rem;
    color: var(--light-text);
    text-transform: uppercase;
    margin-bottom: 5px;
}
.pedido-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}
.pedido-footer .btn {
    padding: 8px 16px;
    font-size: 0.9rem;
}

/* Badges de Status Coloridos */
.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-align: center;
    display: inline-block;
    width: fit-content;
}
.status-pendente { background-color: #fff3cd; color: #856404; }
.status-enviado { background-color: #d1ecf1; color: #0c5460; }
.status-entregue { background-color: #d4edda; color: #155724; }
.status-cancelado { background-color: #f8d7da; color: #721c24; }

/* Bloco de "Nenhum Pedido" */
.info-box {
    text-align: center;
    padding: 60px 40px;
    background-color: var(--background-light);
    border-radius: 12px;
}
.info-box .bi-box-seam {
    color: #ced4da;
    margin-bottom: 20px;
}
.info-box h3 { font-size: 1.8rem; margin: 0; }
.info-box p { color: var(--light-text); margin: 10px 0 30px 0; }
.info-box .btn { margin-top: 0; }
</style>

<?php include 'includes/footer.php'; ?>