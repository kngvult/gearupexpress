<?php
include 'includes/header.php';

$produto = ['id_produto' => '', 'nome' => '', 'descricao' => '', 'preco' => '', 'estoque' => '', 'id_categoria' => '', 'imagem' => '', 'marca' => '', 'codigo_produto' => ''];
$categorias = [];
$erro = '';
$titulo_pagina = 'Adicionar Novo Produto';

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
    // *** NOVOS CAMPOS ***
    $marca = $_POST['marca'];
    $codigo_produto = $_POST['codigo_produto'];
    // ... (lógica de upload de imagem) ...

    if ($id_produto) { // Atualizar
        $sql = "UPDATE produtos SET nome = ?, descricao = ?, preco = ?, estoque = ?, id_categoria = ?, marca = ?, codigo_produto = ? WHERE id_produto = ?";
        $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $marca, $codigo_produto, $id_produto];
    } else { // Inserir
        $sql = "INSERT INTO produtos (nome, descricao, preco, estoque, id_categoria, marca, codigo_produto) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $marca, $codigo_produto];
    }
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        header('Location: produtos.php?status=success');
        exit;
    } catch (PDOException $e) {
        $erro = "Erro ao salvar produto: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <h1 class="page-title"><?= $titulo_pagina ?></h1>

    <?php if ($erro): ?><div class="alert alert-danger"><?= $erro ?></div><?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form action="produto_form.php<?= $produto['id_produto'] ? '?id='.$produto['id_produto'] : '' ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id_produto" value="<?= htmlspecialchars($produto['id_produto']) ?>">
                
                <div class="form-group">
                    <label for="nome">Nome do Produto</label>
                    <input type="text" class="form-control" id="nome" name="nome" value="<?= htmlspecialchars($produto['nome']) ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="marca">Marca</label>
                        <input type="text" class="form-control" id="marca" name="marca" value="<?= htmlspecialchars($produto['marca']) ?>">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="codigo_produto">Código do Produto</label>
                        <input type="text" class="form-control" id="codigo_produto" name="codigo_produto" value="<?= htmlspecialchars($produto['codigo_produto']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="descricao">Descrição</label>
                    <textarea class="form-control" id="descricao" name="descricao" rows="5" required><?= htmlspecialchars($produto['descricao']) ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="preco">Preço (R$)</label>
                        <input type="number" step="0.01" class="form-control" id="preco" name="preco" value="<?= htmlspecialchars($produto['preco']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="estoque">Estoque</label>
                        <input type="number" class="form-control" id="estoque" name="estoque" value="<?= htmlspecialchars($produto['estoque']) ?>" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="id_categoria">Categoria</label>
                        <select class="form-control" id="id_categoria" name="id_categoria" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id_categoria'] ?>" <?= ($produto['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-success">Salvar Produto</button>
                <a href="produtos.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>