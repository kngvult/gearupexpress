<?php
include 'includes/header.php';

// --- LÓGICA PARA CARREGAR DADOS (MODO EDIÇÃO) ---
$produto = null;
$edit_mode = false;
$page_title = "Adicionar Novo Produto";

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_produto = (int)$_GET['id'];
    $edit_mode = true;
    $page_title = "Editar Produto";

    // Busca os dados do produto para preencher o formulário
    $stmt = $pdo->prepare("SELECT * FROM public.produtos WHERE id_produto = ?");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        // Se o produto não for encontrado, redireciona ou mostra erro
        echo "<p>Produto não encontrado!</p>";
        include 'includes/footer.php';
        exit;
    }
}

// Busca todas as categorias para o menu dropdown
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nome FROM public.categorias ORDER BY nome ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
    error_log("Erro ao buscar categorias para o form: " . $e->getMessage());
}
?>

<div class="container-fluid">
    <h1 class="page-title"><?= $page_title ?></h1>

    <div class="card">
        <div class="card-body">
            <form action="produto_salvar.php" method="POST" enctype="multipart/form-data">
                
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id_produto" value="<?= htmlspecialchars($produto['id_produto']) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="nome">Nome do Produto</label>
                    <input type="text" id="nome" name="nome" class="form-control" value="<?= htmlspecialchars($produto['nome'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea id="descricao" name="descricao" class="form-control" rows="5" required><?= htmlspecialchars($produto['descricao'] ?? '') ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="preco">Preço (ex: 123.45)</label>
                        <input type="text" id="preco" name="preco" class="form-control" value="<?= htmlspecialchars($produto['preco'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="estoque">Estoque</label>
                        <input type="number" id="estoque" name="estoque" class="form-control" value="<?= htmlspecialchars($produto['estoque'] ?? '0') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_categoria">Categoria</label>
                    <select id="id_categoria" name="id_categoria" class="form-control" required>
                        <option value="">Selecione uma categoria</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= $categoria['id_categoria'] ?>" <?= (isset($produto['id_categoria']) && $produto['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="imagem">Imagem do Produto</label>
                    <input type="file" id="imagem" name="imagem" class="form-control">
                    <?php if ($edit_mode && !empty($produto['imagem'])): ?>
                        <div class="current-image">
                            <p>Imagem atual:</p>
                            <img src="../assets/img/<?= htmlspecialchars($produto['imagem']) ?>" alt="Imagem atual">
                            <input type="hidden" name="imagem_atual" value="<?= htmlspecialchars($produto['imagem']) ?>">
                        </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-admin">Salvar Produto</button>
                    <a href="produtos.php" class="btn-admin-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>