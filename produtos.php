
<?php
include 'includes/header.php'; 
include 'includes/conexao.php';
// --- LÓGICA DE BUSCA E FILTROS ---
// 1. Busca todas as categorias para a barra lateral de navegação
$categorias = [];
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nome FROM public.categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Erro ao buscar categorias: " . $e->getMessage());
}
// 2. Parâmetros de filtro
$id_categoria_selecionada = $_GET['categoria'] ?? null;
$termo_busca = $_GET['busca'] ?? '';
$preco_min = $_GET['preco_min'] ?? '';
$preco_max = $_GET['preco_max'] ?? '';
$ordenacao = $_GET['ordenar'] ?? 'nome_asc';
$pagina = max(1, $_GET['pagina'] ?? 1);
$limite = 12; // Produtos por página
// 3. Título e breadcrumb
$nome_categoria_selecionada = "Todos os Produtos";
if ($id_categoria_selecionada && is_numeric($id_categoria_selecionada)) {
    $id_categoria_selecionada = (int)$id_categoria_selecionada;
    $stmtNomeCat = $pdo->prepare("SELECT nome FROM public.categorias WHERE id_categoria = ?");
    $stmtNomeCat->execute([$id_categoria_selecionada]);
    $nome_temp = $stmtNomeCat->fetchColumn();
    if ($nome_temp) {
        $nome_categoria_selecionada = $nome_temp;
    }
}
// 4. Monta a query com filtros
$sql_where = [];
$params = [];
$sql_count = "SELECT COUNT(*) FROM public.produtos p WHERE 1=1";
$sql_produtos = "SELECT p.id_produto, p.nome, p.preco, p.imagem, p.estoque, p.marca, 
                        c.nome as categoria_nome 
                FROM public.produtos p 
                LEFT JOIN public.categorias c ON p.id_categoria = c.id_categoria 
                WHERE 1=1";
// Filtro por categoria
if ($id_categoria_selecionada) {
    $sql_where[] = "p.id_categoria = ?";
    $params[] = $id_categoria_selecionada;
}
// Filtro por busca
if (!empty($termo_busca)) {
    $sql_where[] = "(p.nome ILIKE ? OR p.descricao ILIKE ? OR p.marca ILIKE ?)";
    $termo_like = "%$termo_busca%";
    $params[] = $termo_like;
    $params[] = $termo_like;
    $params[] = $termo_like;
}
// Filtro por preço
if (!empty($preco_min) && is_numeric($preco_min)) {
    $sql_where[] = "p.preco >= ?";
    $params[] = floatval($preco_min);
}
if (!empty($preco_max) && is_numeric($preco_max)) {
    $sql_where[] = "p.preco <= ?";
    $params[] = floatval($preco_max);
}
// Aplica WHERE clauses
if (!empty($sql_where)) {
    $where_clause = " AND " . implode(" AND ", $sql_where);
    $sql_count .= $where_clause;
    $sql_produtos .= $where_clause;
}
// Ordenação
switch ($ordenacao) {
    case 'preco_asc':
        $sql_produtos .= " ORDER BY p.preco ASC";
        break;
    case 'preco_desc':
        $sql_produtos .= " ORDER BY p.preco DESC";
        break;
    case 'nome_asc':
    default:
        $sql_produtos .= " ORDER BY p.nome ASC";
        break;
}
// Paginação
$offset = ($pagina - 1) * $limite;
$sql_produtos .= " LIMIT ? OFFSET ?";
$params[] = $limite;
$params[] = $offset;
// 5. Executa as queries
try {
    // Conta total de produtos
    $stmtCount = $pdo->prepare($sql_count);
    $stmtCount->execute($params_count = array_slice($params, 0, count($params) - 2));
    $total_produtos = $stmtCount->fetchColumn();
    
    // Busca produtos
    $stmtProd = $pdo->prepare($sql_produtos);
    $stmtProd->execute($params);
    $produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
    
    $total_paginas = ceil($total_produtos / $limite);
} catch (PDOException $e) {
    error_log("Erro ao buscar produtos: " . $e->getMessage());
    $produtos = [];
    $total_produtos = 0;
    $total_paginas = 1;
}
?>
<main class="page-content">
<div class="container">
    <!-- Breadcrumb -->
    <nav class="breadcrumb" aria-label="Navegação estrutural">
        <ol>
            <li><a href="index.php"><i class="fas fa-home"></i> Página Inicial</a></li>
            <li><a href="produtos.php">Produtos</a></li>
            <?php if ($id_categoria_selecionada): ?>
                <li aria-current="page"><?= htmlspecialchars($nome_categoria_selecionada) ?></li>
            <?php endif; ?>
        </ol>
    </nav>
    <div class="shop-layout">
        <aside class="shop-sidebar">
            <!-- Filtro Mobile Toggle -->
            <button class="filter-toggle" aria-expanded="false" aria-controls="shop-filters">
                <i class="fas fa-filter"></i> Filtros
                <span class="filter-count" id="filter-count">0</span>
            </button>
            <div class="filters-container" id="shop-filters">
                <!-- Filtro de Categorias -->
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-tags"></i> Categorias
                    </h3>
                    <nav class="category-nav">
                        <ul>
                            <li>
                                <a href="produtos.php" class="<?= !$id_categoria_selecionada ? 'active' : '' ?>">
                                    <i class="fas fa-th-large"></i> Todos os Produtos
                                    <span class="category-count">(<?= $total_produtos ?>)</span>
                                </a>
                            </li>
                            <?php foreach ($categorias as $categoria): ?>
                                <li>
                                    <a href="produtos.php?categoria=<?= $categoria['id_categoria'] ?>" 
                                        class="<?= ($id_categoria_selecionada == $categoria['id_categoria']) ? 'active' : '' ?>">
                                        <i class="fas fa-chevron-right"></i> <?= htmlspecialchars($categoria['nome']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                </div>
                <!-- Filtro de Preço -->
                <div class="filter-group">
                    <h3 class="filter-title">
                        <i class="fas fa-dollar-sign"></i> Faixa de Preço
                    </h3>
                    <form class="price-filter" id="price-filter">
                        <div class="price-inputs">
                            <input type="number" name="preco_min" placeholder="Mín" value="<?= htmlspecialchars($preco_min) ?>" 
                                    min="0" step="0.01" class="price-input">
                            <input type="number" name="preco_max" placeholder="Máx" value="<?= htmlspecialchars($preco_max) ?>" 
                                    min="0" step="0.01" class="price-input">
                        </div>
                        <button type="submit" class="btn-filter">Aplicar</button>
                    </form>
                </div>
                <!-- Limpar Filtros -->
                <?php if ($id_categoria_selecionada || $termo_busca || $preco_min || $preco_max): ?>
                <div class="filter-actions">
                    <a href="produtos.php" class="btn-clear-filters">
                        <i class="fas fa-times"></i> Limpar Filtros
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </aside>
        <div class="shop-content">
            <!-- Header da Página de Produtos -->
            <div class="shop-header">
                <div class="shop-title-section">
                    <h1 class="category-main-title"><?= htmlspecialchars($nome_categoria_selecionada) ?></h1>
                    <p class="results-count"><?= $total_produtos ?> produto(s) encontrado(s)</p>
                </div>
                
                <div class="shop-controls">
                    <!-- Ordenação -->
                    <div class="sort-control">
                        <label for="sort-select">Ordenar por:</label>
                        <select id="sort-select" class="sort-select">
                            <option value="nome_asc" <?= $ordenacao === 'nome_asc' ? 'selected' : '' ?>>Nome A-Z</option>
                            <option value="preco_asc" <?= $ordenacao === 'preco_asc' ? 'selected' : '' ?>>Menor Preço</option>
                            <option value="preco_desc" <?= $ordenacao === 'preco_desc' ? 'selected' : '' ?>>Maior Preço</option>
                        </select>
                    </div>
                    <!-- Visualização (Grid/Lista) -->
                    <div class="view-controls">
                        <button class="view-btn active" data-view="grid" aria-label="Visualização em grade">
                            <i class="fas fa-th"></i>
                        </button>
                        <button class="view-btn" data-view="list" aria-label="Visualização em lista">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php if (empty($produtos)): ?>
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Nenhum produto encontrado</h3>
                    <p>Tente ajustar os filtros ou buscar em outras categorias.</p>
                    <a href="produtos.php" class="btn btn-primary">Ver Todos os Produtos</a>
                </div>
            <?php else: ?>
                <!-- Grade de Produtos -->
                <div class="product-grid" id="product-grid" data-view="grid">
                    <?php foreach ($produtos as $produto): ?>
                        <article class="product-card">
                            <div class="product-image-container">
                                <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                    <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                                        alt="<?= htmlspecialchars($produto['nome']) ?>" 
                                        class="product-image"
                                        loading="lazy">
                                        
                                        <?php
                                    // Verifica se o ID do produto atual está na lista que buscamos no header
                                    $isInWishlist = in_array($produto['id_produto'], $wishlistProductIds);
                                    ?>

                                    <button class="wishlist-btn" 
                                            data-product-id="<?= $produto['id_produto'] ?>" 
                                            title="Adicionar aos favoritos">
                                        <i class="far fa-heart"></i>
                                    </button>

                                    <?php if ($produto['estoque'] <= 0): ?>
                                        <span class="out-of-stock-badge">Esgotado</span>
                                    <?php elseif ($produto['estoque'] <= 5): ?>
                                        <span class="low-stock-badge">Últimas unidades</span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-meta">
                                    <?php if (!empty($produto['marca'])): ?>
                                        <span class="product-brand"><?= htmlspecialchars($produto['marca']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($produto['categoria_nome'])): ?>
                                        <span class="product-category"><?= htmlspecialchars($produto['categoria_nome']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h3 class="product-name">
                                    <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>">
                                        <?= htmlspecialchars($produto['nome']) ?>
                                    </a>
                                </h3>
                                
                                <div class="product-price-section">
                                    <p class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                                    <?php if ($produto['estoque'] > 0): ?>
                                        <span class="stock-info">Em estoque</span>
                                    <?php else: ?>
                                        <span class="stock-info out-of-stock">Indisponível</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-card-actions">
                                    <a href="detalhes_produto.php?id=<?= $produto['id_produto'] ?>" 
                                        class="btn btn-secondary">Detalhes</a>
                                    
                                    <?php if ($produto['estoque'] > 0): ?>
                                        <form method="post" action="carrinho_adicionar.php" class="ajax-add-to-cart-form">
                                            <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                                            <button type="submit" class="btn btn-primary" 
                                                    <?= $produto['estoque'] <= 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-cart-plus"></i> Adicionar
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-disabled" disabled>Indisponível</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <!-- Paginação -->
                <?php if ($total_paginas > 1): ?>
                <nav class="pagination" aria-label="Navegação de páginas">
                    <ul>
                        <?php if ($pagina > 1): ?>
                            <li><a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>" 
                                    aria-label="Página anterior">&laquo; Anterior</a></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li>
                                <a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>" 
                                    class="<?= $i == $pagina ? 'active' : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($pagina < $total_paginas): ?>
                            <li><a href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>" 
                                    aria-label="Próxima página">Próxima &raquo;</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle de filtros mobile
    const filterToggle = document.querySelector('.filter-toggle');
    const filtersContainer = document.querySelector('.filters-container');
    
    if (filterToggle && filtersContainer) {
        filterToggle.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            filtersContainer.classList.toggle('show');
        });
    }
    // Ordenação
    const sortSelect = document.getElementById('sort-select');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            const url = new URL(window.location);
            url.searchParams.set('ordenar', this.value);
            window.location.href = url.toString();
        });
    }
    // Alternar entre grid e lista
    const viewBtns = document.querySelectorAll('.view-btn');
    const productGrid = document.getElementById('product-grid');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;
            
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            productGrid.setAttribute('data-view', view);
        });
    });
    // Filtro de preço
    const priceFilter = document.getElementById('price-filter');
    if (priceFilter) {
        priceFilter.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const params = new URLSearchParams(formData);
            
            const url = new URL(window.location);
            url.search = params.toString();
            window.location.href = url.toString();
        });
    }
});
</script>
<script>
// Função Wishlist
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const productId = this.dataset.productId;
        const icon = this.querySelector('i');
        
        console.log('Clicou no wishlist, produto:', productId);
        
        // Alternar estado visual
        const isActive = icon.classList.contains('fas');
        
        if (isActive) {
            console.log('Removendo da wishlist');
            icon.classList.replace('fas', 'far');
            icon.style.color = '';
            removeFromWishlist(productId);
        } else {
            console.log('Adicionando à wishlist');
            icon.classList.replace('far', 'fas');
            icon.style.color = '#e74c3c';
            addToWishlist(productId);
        }
    });
});

function addToWishlist(productId) {
    const formData = new FormData();
    formData.append('id_produto', productId);
    formData.append('acao', 'adicionar');
    
    console.log('Enviando requisição para adicionar...');
    
    fetch('wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Resposta recebida:', response);
        return response.json();
    })
    .then(data => {
        console.log('Dados recebidos:', data);
        if (!data.success) {
            console.error('Erro ao adicionar:', data.message);
            // Reverter visual se erro
            const btn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
            const icon = btn.querySelector('i');
            icon.classList.replace('fas', 'far');
            icon.style.color = '';
        } else {
            console.log('Produto adicionado com sucesso!');
        }
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
    });
}

function removeFromWishlist(productId) {
    const formData = new FormData();
    formData.append('id_produto', productId);
    formData.append('acao', 'remover');
    
    fetch('wishlist.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Resposta remoção:', data);
    })
    .catch(error => {
        console.error('Erro:', error);
    });
}
</script>
<?php include 'includes/footer.php'; ?>