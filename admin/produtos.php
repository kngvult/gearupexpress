<?php
include 'includes/header.php';
// Busca todos os produtos, juntando com o nome da categoria para exibição
try {
    $stmt = $pdo->query("
        SELECT 
            p.id_produto, 
            p.nome, 
            p.preco, 
            p.estoque, 
            p.imagem, 
            c.nome AS nome_categoria
        FROM public.produtos p
        LEFT JOIN public.categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.id_produto DESC
    ");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    $produtos = [];
    error_log("Erro ao buscar produtos: " . $e->getMessage());
}

// Busca estatísticas para os cards
try {
    $stmtTotal = $pdo->query("SELECT COUNT(*) as total FROM produtos");
    $total_produtos = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtBaixoEstoque = $pdo->query("SELECT COUNT(*) as total FROM produtos WHERE estoque <= 5 AND estoque > 0");
    $baixo_estoque = $stmtBaixoEstoque->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmtSemEstoque = $pdo->query("SELECT COUNT(*) as total FROM produtos WHERE estoque = 0");
    $sem_estoque = $stmtSemEstoque->fetch(PDO::FETCH_ASSOC)['total'];
} catch (PDOException $e) {
    $total_produtos = $baixo_estoque = $sem_estoque = 0;
    error_log("Erro ao buscar estatísticas: " . $e->getMessage());
}

// Define os scripts para a biblioteca DataTables
$scripts_adicionais = "
<script>
$(document).ready(function() {
    $('#tabela-produtos').DataTable({
        \"language\": {
            \"url\": \"https://cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json\"
        },
        \"columnDefs\": [
            { \"orderable\": false, \"targets\": [0, 6] } // Desabilita ordenação na coluna de imagem e ações
        ]
    });
});
</script>
";
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Gerenciar Produtos</h1>
        <div class="header-actions">
            <a href="produto_form.php" class="btn-admin">
                <i class="fas fa-plus"></i> Adicionar Novo Produto
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(0, 123, 255, 0.1);">
                <i class="fas fa-cube" style="color: #007bff;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($total_produtos, 0, ',', '.') ?></h3>
                <p>Total de Produtos</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(255, 193, 7, 0.1);">
                <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($baixo_estoque, 0, ',', '.') ?></h3>
                <p>Produtos com Estoque Baixo</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background-color: rgba(220, 53, 69, 0.1);">
                <i class="fas fa-times-circle" style="color: #dc3545;"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($sem_estoque, 0, ',', '.') ?></h3>
                <p>Produtos Sem Estoque</p>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h4><i class="fas fa-list"></i> Lista de Produtos</h4>
            <span class="card-subtitle"><?= count($produtos) ?> produto(s) cadastrado(s)</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabela-produtos" class="data-table">
                    <thead>
                        <tr>
                            <th width="80">Imagem</th>
                            <th width="80">ID</th>
                            <th>Nome do Produto</th>
                            <th>Categoria</th>
                            <th width="120">Preço</th>
                            <th width="100">Estoque</th>
                            <th width="150" class="text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produtos)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="empty-state">
                                        <i class="fas fa-box-open" style="font-size: 48px; color: #dee2e6;"></i>
                                        <p class="mt-3">Nenhum produto cadastrado.</p>
                                        <a href="produto_form.php" class="btn-admin mt-2">
                                            <i class="fas fa-plus"></i> Adicionar Primeiro Produto
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $produto): 
                                $estoque_class = '';
                                if ($produto['estoque'] == 0) {
                                    $estoque_class = 'text-danger';
                                } elseif ($produto['estoque'] <= 5) {
                                    $estoque_class = 'text-warning';
                                }
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <img src="<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                                                alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                                class="table-img"
                                                style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    </td>
                                    <td class="text-center">
                                        <span class="badge-id">#<?= htmlspecialchars($produto['id_produto']) ?></span>
                                    </td>
                                    <td>
                                        <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge-category"><?= htmlspecialchars($produto['nome_categoria'] ?? 'Sem categoria') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="stock-value <?= $estoque_class ?>">
                                            <?= htmlspecialchars($produto['estoque']) ?>
                                            <?php if ($produto['estoque'] <= 5): ?>
                                                <i class="fas fa-exclamation-circle ml-1"></i>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group-actions">
                                            <a href="produto_form.php?id=<?= $produto['id_produto'] ?>" class="btn-action btn-edit" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="#" class="btn-action btn-delete" 
                                                data-id="<?= $produto['id_produto'] ?>" 
                                                data-nome="<?= htmlspecialchars($produto['nome']) ?>"
                                                title="Excluir">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php

include 'includes/footer.php';
?>