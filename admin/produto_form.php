<?php

include 'includes/header.php';

$produto = ['id_produto' => '', 'nome' => '', 'descricao' => '', 'preco' => '', 'estoque' => '', 'id_categoria' => '', 'imagem' => '', 'marca' => '', 'codigo_produto' => ''];
$categorias = [];
$erro = '';
$titulo_pagina = 'Adicionar Novo Produto';
$success_message = '';

// Busca todas as categorias para o seletor
try {
    $stmtCat = $pdo->query("SELECT id_categoria, nome FROM categorias ORDER BY nome ASC");
    $categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $erro = "Erro ao buscar categorias.";
}

// Lógica de Edição (se um ID for passado)
if (isset($_GET['id'])) {
    $id_produto = (int)$_GET['id'];
    $titulo_pagina = 'Editar Produto';
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id_produto = ?");
    $stmt->execute([$id_produto]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$produto) {
        header('Location: produtos.php');
        exit;
    }
}

// Lógica para Salvar (Adicionar ou Atualizar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_produto = $_POST['id_produto'] ? (int)$_POST['id_produto'] : null;
    $nome = $_POST['nome'];
    $descricao = $_POST['descricao'];
    $preco = $_POST['preco'];
    $estoque = $_POST['estoque'];
    $id_categoria = $_POST['id_categoria'];
    $marca = $_POST['marca'];
    $codigo_produto = $_POST['codigo_produto'];
    $imagem_nome = $produto['imagem'] ?? ''; // Mantém a imagem atual por padrão

    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $permitidas)) {
        $novo_nome = uniqid('prod_') . '.' . $ext;
        $destino = __DIR__ . '/../assets/img/produtos/' . $novo_nome;
        if (move_uploaded_file($_FILES['imagem']['tmp_name'], $destino)) {
            $imagem_nome = $novo_nome;
        } else {
            $erro = "Erro ao salvar a imagem.";
        }
    } else {
        $erro = "Formato de imagem não permitido.";
    }
}

    if ($id_produto) { // Atualizar
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, estoque = ?, id_categoria = ?, marca = ?, codigo_produto = ?, imagem = ? WHERE id_produto = ?";
        $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $marca, $codigo_produto, $imagem_nome, $id_produto];
    } else { // Inserir
        $sql = "INSERT INTO produtos (nome, descricao, preco, estoque, id_categoria, marca, codigo_produto, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $marca, $codigo_produto, $imagem_nome];
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Redirecionamento seguro
        echo '<script>window.location.href = "produtos.php?status=success";</script>';
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao salvar produto: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title"><?= $titulo_pagina ?></h1>
        <a href="produtos.php" class="btn-admin-secondary">
            <i class="fas fa-arrow-left"></i> Voltar para Produtos
        </a>
    </div>

    <?php if ($erro): ?>
        <div class="alert-admin alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?= $erro ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert-admin alert-success">
            <i class="fas fa-check-circle"></i> Produto salvo com sucesso!
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h4><i class="fas <?= $produto['id_produto'] ? 'fa-edit' : 'fa-plus' ?>"></i> 
                <?= $produto['id_produto'] ? 'Editar' : 'Adicionar' ?> Informações Básicas
            </h4>
        </div>
        <div class="card-body">
            <form action="produto_form.php<?= $produto['id_produto'] ? '?id='.$produto['id_produto'] : '' ?>" method="post" enctype="multipart/form-data" class="product-form">
                <input type="hidden" name="id_produto" value="<?= htmlspecialchars($produto['id_produto']) ?>">
                
                <div class="form-section">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="nome" class="form-label">Nome do Produto *</label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                value="<?= htmlspecialchars($produto['nome']) ?>" required
                                placeholder="Digite o nome do produto">
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="id_categoria" class="form-label">Categoria *</label>
                            <select class="form-control" id="id_categoria" name="id_categoria" required>
                                <option value="">Selecione uma categoria...</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= $categoria['id_categoria'] ?>" 
                                        <?= ($produto['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($categoria['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" 
                                    value="<?= htmlspecialchars($produto['marca']) ?>"
                                    placeholder="Ex: Moura, Pirelli etc.">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="codigo_produto" class="form-label">Código do Produto</label>
                            <input type="text" class="form-control" id="codigo_produto" name="codigo_produto" 
                                    value="<?= htmlspecialchars($produto['codigo_produto']) ?>"
                                    placeholder="Código interno">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h5 class="section-title">Detalhes do Produto</h5>
                    <div class="form-group">
                        <label for="descricao" class="form-label">Descrição *</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="5" required
                                    placeholder="Descreva detalhadamente o produto, suas características e benefícios"><?= htmlspecialchars($produto['descricao']) ?></textarea>
                        <small class="form-text">Mínimo de 50 caracteres recomendado.</small>
                    </div>
                </div>

                <div class="form-section">
                    <h5 class="section-title">Preço e Estoque</h5>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="preco" class="form-label">Preço (R$) *</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" class="form-control" id="preco" name="preco" 
                                        value="<?= htmlspecialchars($produto['preco']) ?>" required
                                        placeholder="0,00">
                            </div>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label for="estoque" class="form-label">Estoque *</label>
                            <input type="number" class="form-control" id="estoque" name="estoque" 
                                    value="<?= htmlspecialchars($produto['estoque']) ?>" required min="0"
                                    placeholder="Quantidade disponível">
                            <?php if ($produto['estoque'] <= 5 && $produto['estoque'] > 0): ?>
                                <small class="text-warning">
                                    <i class="fas fa-exclamation-triangle"></i> Estoque baixo
                                </small>
                            <?php elseif ($produto['estoque'] == 0): ?>
                                <small class="text-danger">
                                    <i class="fas fa-times-circle"></i> Sem estoque
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group col-md-4">
                            <label class="form-label">Status do Estoque</label>
                            <div class="stock-status">
                                <?php if ($produto['id_produto']): ?>
                                    <?php if ($produto['estoque'] > 5): ?>
                                        <span class="badge badge-success">Disponível</span>
                                    <?php elseif ($produto['estoque'] > 0): ?>
                                        <span class="badge badge-warning">Estoque Baixo</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Esgotado</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Novo Produto</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h5 class="section-title">Imagem do Produto</h5>
                    <div class="form-group">
                        <label for="imagem" class="form-label">Imagem</label>
                        <input type="file" id="imagem" name="imagem" class="form-control">
                        <?php if (!empty($produto['imagem'])): ?>
                            <div style="margin-top:10px;">
                                <img src="../assets/img/produtos/<?= htmlspecialchars($produto['imagem']) ?>" alt="Imagem do produto" style="max-width:120px; border-radius:8px;">
                                <br>
                                <small>Imagem atual</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-admin btn-save">
                        <i class="fas fa-save"></i> Salvar Produto
                    </button>
                    <a href="produtos.php" class="btn-admin-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
// Validação básica do formulário
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.product-form');
    
    form.addEventListener('submit', function(e) {
        const preco = document.getElementById('preco');
        const estoque = document.getElementById('estoque');
        let isValid = true;
        
        // Validar preço
        if (parseFloat(preco.value) < 0) {
            alert('O preço não pode ser negativo.');
            preco.focus();
            isValid = false;
        }
        
        // Validar estoque
        if (parseInt(estoque.value) < 0) {
            alert('O estoque não pode ser negativo.');
            estoque.focus();
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>