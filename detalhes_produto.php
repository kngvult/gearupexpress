<?php
include 'includes/header.php'; 
include 'includes/conexao.php';
include 'includes/funcoes_avaliacoes.php';

$produto = null;
$produtos_relacionados = [];
$erro = '';

if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
    $erro = "Produto não especificado.";
} else {
    $id_produto = (int)$_GET['id'];

    // Busca o produto principal e o nome da sua categoria
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS categoria_nome
        FROM public.produtos p
        JOIN public.categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_produto = ?
    ");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $erro = "Produto não encontrado.";
    } else {
        // Busca produtos relacionados
        $stmtRel = $pdo->prepare("
            SELECT id_produto, nome, preco, imagem, estoque, marca
            FROM public.produtos 
            WHERE id_categoria = ? AND id_produto != ? 
            ORDER BY RANDOM() LIMIT 8
        ");
        $stmtRel->execute([$produto['id_categoria'], $id_produto]);
        $produtos_relacionados = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    }
}

// INICIALIZAR VARIÁVEIS PARA EVITAR WARNINGS
$avaliacao_usuario = null;
$avaliacao_geral = ['media_rating' => 0, 'total_avaliacoes' => 0, 'total_comentarios' => 0];
$avaliacoes_recentes = [];

try {
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome as categoria_nome 
        FROM produtos p 
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
        WHERE p.id_produto = ?
    ");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$produto) {
        header('Location: produtos.php');
        exit;
    }
    
    // Buscar avaliações do produto
    $avaliacao_geral = getAvaliacaoProduto($pdo, $id_produto) ?? $avaliacao_geral;
    $avaliacoes_recentes = getAvaliacoesRecentes($pdo, $id_produto) ?? [];
    
    // Verificar se usuário está logado e se já avaliou
    if (isset($_SESSION['usuario']['id'])) {
        $avaliacao_usuario = getAvaliacaoUsuario($pdo, $id_produto, $_SESSION['usuario']['id']);
    }
    
} catch (PDOException $e) {
    error_log("Erro ao carregar produto: " . $e->getMessage());
    $produto = null;
}

// Processar envio de avaliação
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avaliar'])) {
    if (!isset($_SESSION['usuario']['id'])) {
        $_SESSION['erro'] = "Você precisa estar logado para avaliar produtos.";
        header("Location: login.php?redirect=detalhes_produto.php?id=" . $id_produto);
        exit;
    }
    
    $rating = (int)$_POST['rating'];
    $comentario = trim($_POST['comentario'] ?? '');
    
    if ($rating >= 1 && $rating <= 5) {
        try {
            $sucesso = salvarAvaliacao($pdo, $id_produto, $_SESSION['usuario']['id'], $rating, $comentario);
            
            if ($sucesso) {
                $_SESSION['sucesso'] = "Avaliação enviada com sucesso!";
                // Recarregar os dados da avaliação do usuário
                $avaliacao_usuario = getAvaliacaoUsuario($pdo, $id_produto, $_SESSION['usuario']['id']);
                $avaliacao_geral = getAvaliacaoProduto($pdo, $id_produto) ?? $avaliacao_geral;
                $avaliacoes_recentes = getAvaliacoesRecentes($pdo, $id_produto) ?? [];
            } else {
                $_SESSION['erro'] = "Erro ao enviar avaliação.";
            }
        } catch (PDOException $e) {
            $_SESSION['erro'] = "Erro ao salvar avaliação: " . $e->getMessage();
        }
    } else {
        $_SESSION['erro'] = "Por favor, selecione uma avaliação entre 1 e 5 estrelas.";
    }
}

?>

<main class="page-content">
<div class="container">
    <?php if (!empty($erro)): ?>
        <div class="error-state">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2>Produto Não Encontrado</h2>
            <p><?= htmlspecialchars($erro) ?></p>
            <div class="error-actions">
                <a href="produtos.php" class="btn btn-primary">
                    <i class="fas fa-th-large"></i> Ver Todos os Produtos
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Voltar para Home
                </a>
            </div>
        </div>
    <?php elseif ($produto): ?>
        <!-- Breadcrumb Melhorado -->
        <nav class="breadcrumb" aria-label="breadcrumb">
            <ol>
                <li><a href="index.php">Página Inicial</a></li>
                <li><a href="produtos.php">Produtos</a></li>
                <li><a href="produtos.php?categoria=<?= $produto['id_categoria'] ?>"><?= htmlspecialchars($produto['categoria_nome']) ?></a></li>
                <li aria-current="page"><?= htmlspecialchars($produto['nome']) ?></li>
            </ol>
        </nav>

        <!-- Layout Principal do Produto -->
        <div class="product-detail-layout">
            <!-- Galeria de Imagens -->
            <div class="product-gallery">
                <div class="main-image-container">
                    <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                        alt="<?= htmlspecialchars($produto['nome']) ?>" 
                        id="main-product-image"
                        class="product-main-image">
                    <?php if ($produto['estoque'] <= 0): ?>
                        <div class="product-overlay-badge out-of-stock-overlay">
                            <i class="fas fa-times-circle"></i>
                            <span>ESGOTADO</span>
                        </div>
                    <?php elseif ($produto['estoque'] <= 5): ?>
                        <div class="product-overlay-badge low-stock-overlay">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>ÚLTIMAS UNIDADES</span>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Botões de Ação na Imagem -->
                    <div class="image-actions">
                        <button class="wishlist-btn" 
                                            data-product-id="<?= $produto['id_produto'] ?>" 
                                            title="Adicionar aos favoritos">
                                        <i class="far fa-heart"></i>
                                    </button>
                        <button class="image-action-btn zoom-btn" title="Ampliar imagem" onclick="openImageModal(this)">
                            <i class="fas fa-expand"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Miniaturas (para quando tiver múltiplas imagens) -->
                <div class="image-thumbnails">
                    <div class="thumbnail active">
                        <img src="assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" 
                            alt="Miniatura 1"
                            onclick="changeMainImage(this)">
                    </div>
                    <!-- Adicionar mais miniaturas aqui para ter múltiplas imagens -->
                </div>
            </div>

            <!-- Painel de Compra -->
            <div class="product-purchase-panel">
                <!-- Cabeçalho do Produto -->
                <div class="product-header">
                    <div class="product-category-badge">
                        <i class="fas fa-tag"></i>
                        <?= htmlspecialchars($produto['categoria_nome']) ?>
                    </div>
                    <h1 class="product-title"><?= htmlspecialchars($produto['nome']) ?></h1>
                    <div class="product-rating">
                        <div class="stars">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <span class="rating-text">(4.5) • 12 avaliações</span>
                    </div>
                </div>

                <!-- Preço e Estoque -->
                <div class="product-price-stock-wrapper">
                    <div class="price-section">
                        <p class="product-price-large">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                        <div class="price-details">
                            <p class="product-installments-main">
                                <i class="fas fa-credit-card"></i>
                                ou em até <strong>3x</strong> de <strong>R$ <?= number_format($produto['preco'] / 3, 2, ',', '.') ?></strong> sem juros
                            </p>
                            <p class="cash-price">
                                <i class="fas fa-money-bill-wave"></i>
                                À vista: <strong>R$ <?= number_format($produto['preco'] * 0.95, 2, ',', '.') ?></strong> (5% de desconto)
                            </p>
                        </div>
                    </div>
                    
                    <div class="stock-section">
                        <div class="stock-status <?= $produto['estoque'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                            <div class="status-icon">
                                <?php if ($produto['estoque'] > 0): ?>
                                    <i class="fas fa-check-circle"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle"></i>
                                <?php endif; ?>
                            </div>
                            <div class="status-info">
                                <span class="status-text">
                                    <?= $produto['estoque'] > 0 ? 'Em estoque' : 'Indisponível' ?>
                                </span>
                                <span class="stock-quantity">
                                    <?= $produto['estoque'] > 0 ? $produto['estoque'] . ' unidades disponíveis' : 'Produto esgotado' ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($produto['estoque'] > 0 && $produto['estoque'] <= 10): ?>
                            <div class="low-stock-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                Apenas <?= $produto['estoque'] ?> unidade(s) restante(s)!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulário de Compra -->
                <form method="post" action="carrinho_adicionar.php" id="add-to-cart-form" class="add-to-cart-form">
                    <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                    
                    <div class="purchase-controls">
                        <div class="quantity-control">
                            <label for="quantidade" class="quantity-label">Quantidade:</label>
                            <div class="quantity-stepper">
                                <button type="button" class="quantity-btn minus" onclick="decreaseQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" name="quantidade" id="quantidade" value="1" min="1" max="<?= htmlspecialchars($produto['estoque']) ?>" class="quantity-input">
                                <button type="button" class="quantity-btn plus" onclick="increaseQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" id="add-to-cart-btn" name="adicionar" class="btn btn-primary btn-add-to-cart" <?= $produto['estoque'] <= 0 ? 'disabled' : '' ?>>
                            <span class="btn-content">
                                <i class="fas fa-shopping-cart"></i>
                                <span class="btn-text">Adicionar ao Carrinho</span>
                            </span>
                            <span class="btn-loading" style="display: none;">
                                <i class="fas fa-spinner fa-spin"></i>
                                Adicionando...
                            </span>
                            <span class="btn-success" style="display: none;">
                                <i class="fas fa-check"></i>
                                Adicionado!
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Informações de Entrega e Garantia -->
                <div class="product-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shipping-fast"></i>
                        </div>
                        <div class="feature-info">
                            <strong>Entrega Rápida</strong>
                            <span>Receba em até 48h</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-info">
                            <strong>Garantia</strong>
                            <span>3 meses contra defeitos</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-undo"></i>
                        </div>
                        <div class="feature-info">
                            <strong>Devolução</strong>
                            <span>30 dias para trocar</span>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="feature-info">
                            <strong>Pagamento Seguro</strong>
                            <span>Dados criptografados</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seção de Informações do Produto -->
        <div class="product-info-section">
            <div class="tabs-container">
                <ul class="tabs-nav">
                    <li>
                        <button class="tab-link active" data-tab="tab-descricao">
                            <i class="fas fa-file-alt"></i>
                            Descrição
                        </button>
                    </li>
                    <li>
                        <button class="tab-link" data-tab="tab-especificacoes">
                            <i class="fas fa-list-alt"></i>
                            Especificações
                        </button>
                    </li>
                    <li>
                        <button class="tab-link" data-tab="tab-avaliacoes">
                            <i class="fas fa-star"></i>
                            Avaliações
                            <span class="tab-badge"><?= $avaliacao_geral['total_avaliacoes'] ?? 0 ?></span>
                        </button>
                    </li>
                    
                </ul>

                <div class="tabs-content">
                    <!-- Descrição -->
                    <div id="tab-descricao" class="tab-pane active">
                        <div class="tab-content-inner">
                            <h3>Descrição do Produto</h3>
                            <div class="product-description">
                                <?= nl2br(htmlspecialchars($produto['descricao'])) ?>
                            </div>
                            
                            <div class="description-features">
                                <h4>Características Principais</h4>
                                <ul class="features-list">
                                    <li><i class="fas fa-check"></i> Alta qualidade e durabilidade</li>
                                    <li><i class="fas fa-check"></i> Compatível com diversos modelos</li>
                                    <li><i class="fas fa-check"></i> Fácil instalação</li>
                                    <li><i class="fas fa-check"></i> Garantia do fabricante</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Especificações -->
                    <div id="tab-especificacoes" class="tab-pane">
                        <div class="tab-content-inner">
                            <h3>Especificações Técnicas</h3>
                            <div class="specs-grid">
                                <div class="specs-group">
                                    <h4>Informações Básicas</h4>
                                    <table class="specs-table">
                                        <tr>
                                            <td>Marca</td>
                                            <td><?= htmlspecialchars($produto['marca'] ?: 'Não informada') ?></td>
                                        </tr>
                                        <tr>
                                            <td>Código do Produto</td>
                                            <td><?= htmlspecialchars($produto['codigo_produto'] ?: 'Não informado') ?></td>
                                        </tr>
                                        <tr>
                                            <td>Categoria</td>
                                            <td><?= htmlspecialchars($produto['categoria_nome']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Garantia</td>
                                            <td>3 meses</td>
                                        </tr>
                                    </table>
                                </div>
                                
                                <div class="specs-group">
                                    <h4>Detalhes Técnicos</h4>
                                    <table class="specs-table">
                                        <tr>
                                            <td>Material</td>
                                            <td>Aço de alta resistência</td>
                                        </tr>
                                        <tr>
                                            <td>Peso</td>
                                            <td>1.2 kg</td>
                                        </tr>
                                        <tr>
                                            <td>Dimensões</td>
                                            <td>15x10x8 cm</td>
                                        </tr>
                                        <tr>
                                            <td>Origem</td>
                                            <td>Nacional</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção de Avaliações -->
                    <div id="tab-avaliacoes" class="tab-pane">
                        <div class="tab-content-inner">
                            <div class="section-header">
                                <h2>Avaliações do Produto</h2>
                            </div>
                            
                            <div class="avaliacoes-container">
                                <!-- Resumo das Avaliações -->
                                <div class="avaliacao-resumo">
                                    <div class="rating-geral">
                                        <div class="rating-numero">
                                            <span class="nota"><?= number_format($avaliacao_geral['media_rating'] ?? 0, 1) ?></span>
                                            <div class="stars">
                                                <?= gerarStars(round($avaliacao_geral['media_rating'] ?? 0), 'lg') ?>
                                            </div>
                                            <span class="total-avaliacoes">
                                                (<?= $avaliacao_geral['total_avaliacoes'] ?? 0 ?> avaliações)
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="rating-distribuicao">
                                        <!-- Aqui pode adicionar distribuição por estrelas se quiser -->
                                        <p><?= $avaliacao_geral['total_comentarios'] ?? 0 ?> comentários</p>
                                    </div>
                                </div>
                                
                                <!-- Formulário de Avaliação (apenas para usuários logados) -->
                                <?php if (isset($_SESSION['usuario']['id'])): ?>
                                    <div class="avaliacao-form-container" >
                                        <h4><?= $avaliacao_usuario ? 'Editar sua Avaliação' : 'Deixe sua Avaliação' ?></h4>
                                        
                                        <form method="post" class="avaliacao-form">
                                            <div class="rating-input">
                                                <label>Sua avaliação:</label>
                                                <div class="stars-input">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" 
                                                            <?= ($avaliacao_usuario && $avaliacao_usuario['rating'] == $i) ? 'checked' : '' ?>>
                                                        <label for="star<?= $i ?>" class="star-label">
                                                            <i class="far fa-star"></i>
                                                        </label>
                                                    <?php endfor; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="comentario-input">
                                                <label for="comentario">Comentário (opcional):</label>
                                                <textarea name="comentario" id="comentario" rows="4" 
                                                        placeholder="Compartilhe sua experiência com este produto..."><?= htmlspecialchars($avaliacao_usuario['comentario'] ?? '') ?></textarea>
                                            </div>
                                            
                                            <button type="submit" name="avaliar" class="btn btn-primary">
                                                <?= $avaliacao_usuario ? 'Atualizar Avaliação' : 'Enviar Avaliação' ?>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="avaliacao-login-required">
                                        <p>💡 <a href="login.php?redirect=detalhes_produto.php?id=<?= $id_produto ?>" class="btn btn-outline">Faça login</a> para deixar sua avaliação</p>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Lista de Avaliações Recentes -->
                                <?php if (!empty($avaliacoes_recentes)): ?>
                                    <div class="avaliacoes-lista">
                                        <h4>Avaliações Recentes</h4>
                                        
                                        <?php foreach($avaliacoes_recentes as $avaliacao): ?>
                                            <div class="avaliacao-item">
                                                <div class="avaliacao-header">
                                                    <div class="usuario-info">
                                                        <strong><?= htmlspecialchars($avaliacao['usuario_nome']) ?></strong>
                                                        <div class="rating">
                                                            <?= gerarStars($avaliacao['rating']) ?>
                                                        </div>
                                                    </div>
                                                    <span class="data-avaliacao">
                                                        <?= date('d/m/Y', strtotime($avaliacao['data_avaliacao'])) ?>
                                                    </span>
                                                </div>
                                                
                                                <?php if (!empty($avaliacao['comentario'])): ?>
                                                    <div class="comentario">
                                                        <p><?= nl2br(htmlspecialchars($avaliacao['comentario'])) ?></p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="sem-avaliacoes">
                                        <p>Seja o primeiro a avaliar este produto! 📝</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Produtos Relacionados -->
        <?php if (!empty($produtos_relacionados)): ?>
            <div class="related-products-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-random"></i>
                        Produtos Relacionados
                    </h2>
                    <p class="section-subtitle">Clientes que viram este produto também compraram</p>
                </div>

                <div class="swiper related-products-carousel">
                    <div class="swiper-wrapper">
                        <?php foreach ($produtos_relacionados as $relacionado): ?>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-image">
                                        <a href="detalhes_produto.php?id=<?= $relacionado['id_produto'] ?>">
                                            <img src="assets/img/produtos/<?= htmlspecialchars($relacionado['imagem']) ?>" 
                                                alt="<?= htmlspecialchars($relacionado['nome']) ?>">
                                        </a>
                                        <?php if ($relacionado['estoque'] <= 0): ?>
                                            <span class="product-badge out-of-stock">Esgotado</span>
                                        <?php elseif ($relacionado['estoque'] <= 5): ?>
                                            <span class="product-badge low-stock">Últimas unidades</span>
                                        <?php endif; ?>

                                        <?php
                                        // Verifica se o ID do produto atual está na lista que buscamos no header
                                        $isInWishlist = in_array($relacionado['id_produto'], $wishlistProductIds ?? []);
                                        ?>

                                        <button 
                                            class="wishlist-btn wishlist-toggle-btn <?= $isInWishlist ? 'active' : '' ?>" 
                                            title="<?= $isInWishlist ? 'Remover dos favoritos' : 'Adicionar aos favoritos' ?>"
                                            data-product-id="<?= $relacionado['id_produto'] ?>">
                                            
                                            <i class="<?= $isInWishlist ? 'fas fa-heart' : 'far fa-heart' ?>"></i>
                                        </button>
                                    </div>
                                    
                                    <div class="product-info">
                                <div class="product-meta">
                                    <?php if (!empty($relacionado['marca'])): ?>
                                        <span class="product-brand"><?= htmlspecialchars($relacionado['marca']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($relacionado['categoria_nome'])): ?>
                                        <span class="product-category"><?= htmlspecialchars($relacionado['categoria_nome']) ?></span>
                                    <?php endif; ?>
                                </div>
                                        
                                        <h4 class="product-card-title">
                                            <a href="detalhes_produto.php?id=<?= $relacionado['id_produto'] ?>">
                                                <?= htmlspecialchars($relacionado['nome']) ?>
                                            </a>
                                        </h4>
                                        
                                        <div class="product-card-price">
                                            <span class="current-price">R$ <?= number_format($relacionado['preco'], 2, ',', '.') ?></span>
                                        </div>
                                        
                                        <div class="product-card-installments">
                                            em até 3x de R$ <?= number_format($relacionado['preco']/3, 2, ',', '.') ?>
                                        </div>
                                        
                                        <div class="product-stock <?= $relacionado['estoque'] <= 0 ? 'out-of-stock' : '' ?>">
                                    <?php if ($relacionado['estoque'] > 0): ?>
                                        <i class="fas fa-check-circle"></i> Em estoque
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Indisponível
                                    <?php endif; ?>
                                </div>
                            </div>
                                    
                                    <div class="product-card-actions">
                                        <a href="detalhes_produto.php?id=<?= $relacionado['id_produto'] ?>" class="btn btn-outline btn-details">
                                            <i class="fas fa-eye"></i> Detalhes
                                        </a>
                                        <?php if ($relacionado['estoque'] > 0): ?>
                                            <form method="post" action="carrinho_adicionar.php" class="ajax-add-to-cart-form">
                                                <input type="hidden" name="id_produto" value="<?= $relacionado['id_produto'] ?>">
                                                <input type="hidden" name="quantidade" value="1">
                                                <button type="submit" class="btn btn-primary btn-cart">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <button class="btn btn-disabled" disabled>
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>    
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>
</main>

<!-- Modal para Zoom de Imagem -->
<div id="imageModal" class="image-modal">
    <span class="modal-close" onclick="closeImageModal()">&times;</span>
    <img class="modal-content" id="modalImage">
    <div class="modal-caption"></div>
</div>

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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de Abas
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const targetId = this.getAttribute('data-tab');

            // Desativa todos os links e painéis
            tabLinks.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));

            // Ativa o link clicado e o painel correspondente
            this.classList.add('active');
            document.getElementById(targetId).classList.add('active');
        });
    });

    // Sistema de Adicionar ao Carrinho
    const addToCartForm = document.getElementById('add-to-cart-form');
    const addToCartBtn = document.getElementById('add-to-cart-btn');
    const btnContent = addToCartBtn.querySelector('.btn-content');
    const btnLoading = addToCartBtn.querySelector('.btn-loading');
    const btnSuccess = addToCartBtn.querySelector('.btn-success');

    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(event) {
            event.preventDefault();

            const formData = new FormData(this);

            // Mostra estado de carregamento
            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            addToCartBtn.disabled = true;

            fetch('carrinho_adicionar.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Feedback de sucesso
                    btnLoading.style.display = 'none';
                    btnSuccess.style.display = 'flex';
                    
                    // Atualiza o contador do carrinho
                    updateCartBadge(data.totalItensCarrinho);
                    
                    // Animação de confirmação
                    addToCartBtn.classList.add('btn-success-state');
                    
                    setTimeout(() => {
                        btnSuccess.style.display = 'none';
                        btnContent.style.display = 'flex';
                        addToCartBtn.disabled = false;
                        addToCartBtn.classList.remove('btn-success-state');
                    }, 2000);
                } else {
                    // Feedback de erro
                    btnLoading.style.display = 'none';
                    btnContent.style.display = 'flex';
                    addToCartBtn.disabled = false;
                    
                    showNotification('Erro ao adicionar produto ao carrinho', 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                btnLoading.style.display = 'none';
                btnContent.style.display = 'flex';
                addToCartBtn.disabled = false;
                showNotification('Erro de conexão', 'error');
            });
        });
    }

    // Inicializa o carrossel de produtos relacionados
    const relatedSwiper = new Swiper('.related-products-carousel', {
        loop: true,
        spaceBetween: 30,
        slidesPerView: 1,
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        breakpoints: {
            576: {
                slidesPerView: 2,
            },
            768: {
                slidesPerView: 3,
            },
            992: {
                slidesPerView: 4,
            }
        }
    });

    // Sistema de notificação
    function showNotification(message, type = 'info') {
        // Implementar sistema de notificação toast
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    // Atualizar badge do carrinho
    function updateCartBadge(count) {
        let cartBadge = document.querySelector('.cart-badge');
        if (!cartBadge) {
            const cartLink = document.querySelector('a[href="carrinho.php"]');
            cartBadge = document.createElement('span');
            cartBadge.className = 'cart-badge';
            cartLink.appendChild(cartBadge);
        }
        cartBadge.textContent = count;
        cartBadge.style.display = 'flex';
        
        // Animação do badge
        cartBadge.classList.add('badge-pulse');
        setTimeout(() => {
            cartBadge.classList.remove('badge-pulse');
        }, 600);
    }
});

// Funções de quantidade
function increaseQuantity() {
    const input = document.getElementById('quantidade');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQuantity() {
    const input = document.getElementById('quantidade');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

// Funções do modal de imagem
function openImageModal(element) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const mainImage = document.getElementById('main-product-image');
    
    modal.style.display = 'block';
    modalImg.src = mainImage.src;
}

function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

// Fechar modal ao clicar fora da imagem
window.onclick = function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target === modal) {
        closeImageModal();
    }
}

// Trocar imagem principal
function changeMainImage(thumbnail) {
    const mainImage = document.getElementById('main-product-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    
    // Remove classe active de todas as miniaturas
    thumbnails.forEach(thumb => thumb.classList.remove('active'));
    
    // Adiciona classe active à miniatura clicada
    thumbnail.parentElement.classList.add('active');
    
    // Atualiza imagem principal
    mainImage.src = thumbnail.src;
    
    // Efeito de transição
    mainImage.style.opacity = '0';
    setTimeout(() => {
        mainImage.style.opacity = '1';
    }, 150);
}

// Avaliação por estrelas
document.addEventListener('DOMContentLoaded', function() {
    // Sistema de estrelas interativo
    const starInputs = document.querySelectorAll('.stars-input input[type="radio"]');
    const starLabels = document.querySelectorAll('.star-label');
    
    starLabels.forEach((label, index) => {
        label.addEventListener('mouseenter', function() {
            // Preencher estrelas até esta
            for (let i = 0; i <= index; i++) {
                starLabels[i].querySelector('i').className = 'fas fa-star';
            }
            // Esvaziar as restantes
            for (let i = index + 1; i < starLabels.length; i++) {
                starLabels[i].querySelector('i').className = 'far fa-star';
            }
        });
        
        label.addEventListener('click', function() {
            // Manter seleção após clique
            const selectedIndex = index;
            for (let i = 0; i < starLabels.length; i++) {
                if (i <= selectedIndex) {
                    starLabels[i].querySelector('i').className = 'fas fa-star';
                } else {
                    starLabels[i].querySelector('i').className = 'far fa-star';
                }
            }
        });
    });
    
    // Reset ao sair do container (se nenhum estiver selecionado)
    const starsContainer = document.querySelector('.stars-input');
    starsContainer.addEventListener('mouseleave', function() {
        const checkedInput = document.querySelector('.stars-input input[type="radio"]:checked');
        if (!checkedInput) {
            starLabels.forEach(label => {
                label.querySelector('i').className = 'far fa-star';
            });
        } else {
            // Manter a seleção atual
            const checkedIndex = Array.from(starInputs).indexOf(checkedInput);
            for (let i = 0; i < starLabels.length; i++) {
                if (i <= checkedIndex) {
                    starLabels[i].querySelector('i').className = 'fas fa-star';
                } else {
                    starLabels[i].querySelector('i').className = 'far fa-star';
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>