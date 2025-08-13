<?php
function exibirProduto($produto) {
    ?>
    <div class="produto-card">
        <div class="produto-imagem">
            <img src="<?php echo htmlspecialchars($produto['imagem']); ?>" alt="<?php echo htmlspecialchars($produto['nome']); ?>">
        </div>
        <div class="produto-info">
            <h3 class="produto-nome"><?php echo htmlspecialchars($produto['nome']); ?></h3>
            <p class="produto-descricao"><?php echo htmlspecialchars($produto['descricao']); ?></p>
            <span class="produto-preco">R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></span>
        </div>
        <div class="produto-acoes">
            <a href="detalhes_produto.php?id=<?php echo $produto['id_produto']; ?>" class="btn-detalhes">Ver Detalhes</a>
            <a href="adicionar_carrinho.php?id=<?php echo $produto['id_produto']; ?>" class="btn-comprar">Adicionar ao Carrinho</a>
        </div>
    </div>
    <?php
}
?>
