<?php
require 'conexao.php';

// Consulta produtos
$sql = "SELECT p.id_produto, p.nome, p.descricao, p.preco, p.estoque, p.imagem, c.nome AS categoria
        FROM public.produtos p
        LEFT JOIN public.categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.nome ASC";
$stmt = $pdo->query($sql);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lista de Produtos</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f2f2f2; }
        .produto { background: #fff; border-radius: 8px; padding: 16px; margin: 10px; display: inline-block; width: 250px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);}
        img { max-width: 100%; height: auto; }
        h3 { margin: 10px 0; }
        .preco { color: green; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Produtos Disponíveis</h1>
    <?php foreach ($produtos as $p): ?>
        <div class="produto">
            <img src="imagens/<?php echo htmlspecialchars($p['imagem']); ?>" alt="<?php echo htmlspecialchars($p['nome']); ?>">
            <h3><?php echo htmlspecialchars($p['nome']); ?></h3>
            <p><?php echo htmlspecialchars($p['descricao']); ?></p>
            <p><strong>Categoria:</strong> <?php echo htmlspecialchars($p['categoria']); ?></p>
            <p class="preco">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></p>
            <p><strong>Estoque:</strong> <?php echo $p['estoque']; ?></p>
        </div>
    <?php endforeach; ?>
    <a href="carrinho.php?add=<?php echo $p['id_produto']; ?>">Adicionar ao Carrinho</a>
</body>
</html>
