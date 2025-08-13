<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GearUp Express - Sua loja online para peças automotivas</title>
    <link rel="stylesheet" href="assets/css/style.css">
    </head>
    <body>
<?php
include 'includes/header.php';
include 'includes/conexao.php';
// Buscar categorias
$stmtCat = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC");
$categorias = $stmtCat->fetchAll(PDO::FETCH_ASSOC);
// Buscar produtos em destaque (exemplo: os 6 primeiros)
$stmtProd = $pdo->query("SELECT id_produto, nome, preco, imagem FROM produtos ORDER BY id_produto LIMIT 6");
$produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
?>
<main class="container">
<div class="hero-banner">
    <div class="hero-slide active" style="background-image: url('assets/img/promo1.jpg');">
        <div class="hero-text">
            <h1>Descontos imperdíveis!</h1>
            <p>Aproveite até 50% off em autopeças selecionadas.</p>
        </div>
    </div>
    <div class="hero-slide" style="background-image: url('assets/img/promo2.jpg');">
        <div class="hero-text">
            <h1>Frete grátis</h1>
            <p>Para compras acima de R$ 199 em todo o Brasil.</p>
        </div>
    </div>
    <div class="hero-slide" style="background-image: url('assets/img/promo3.jpg');">
        <div class="hero-text">
            <h1>Novidades em som automotivo</h1>
            <p>Confira os lançamentos com qualidade premium.</p>
        </div>
    </div>
</div>
</div>    
<section aria-label="Categorias" class="categorias-section">
    <h2>Categorias</h2>
    <nav class="nav nav-pills flex-wrap">
        <?php
        // Ícones por categoria
        $iconesCategorias = [
            'Todos os Departamentos' => '📦',
            'Pneus' => '🛞',
            'Autopeças' => '🔧',
            'Elétrica' => '⚡',
            'Som e Vídeo' => '🔊',
            'Acessórios Externos' => '🚗',
            'Acessórios Internos' => '🪑',
            'Farol e Iluminação' => '💡',
            'Alarme e Segurança' => '🔒',
            'Ferramentas e Equipamentos' => '🧰',
            'Lubrificantes e Aditivos' => '🛢️',
            'Itens Promocionais' => '🏷️'
        ];

        // "Todos os Departamentos" primeiro
        echo '<a class="nav-link categoria-pill active" href="categoria.php?id=0">' . $iconesCategorias['Todos os Departamentos'] . ' Todos os Departamentos</a>';

        foreach ($categorias as $categoria):
            $nome = $categoria['nome'];
            $icone = $iconesCategorias[$nome] ?? '📁';
        ?>
            <a class="nav-link categoria-pill" href="categoria.php?id=<?= $categoria['id_categoria'] ?>">
                <?= $icone ?> <?= htmlspecialchars($nome) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</section>

    <section aria-label="Produtos em destaque" style="margin-top: 40px;">
        <h2>Produtos em Destaque</h2>
        <div style="display:flex; flex-wrap: wrap; gap: 20px;">
            <?php foreach($produtos as $produto): ?>
                <article style="border: 1px solid #ddd; border-radius: 4px; padding: 10px; width: calc(33% - 20px); box-sizing: border-box;">
                    <a href="produto.php?id=<?= $produto['id_produto'] ?>" style="text-decoration:none; color:inherit;">
                        <img src="assets/img/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" style="width:100%; height:auto; border-radius:4px;">
                        <h3 style="margin-top:10px; font-size: 1.1rem;"><?= htmlspecialchars($produto['nome']) ?></h3>
                        <p style="color:#28a745; font-weight:bold;">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                    </a>
                    <form method="post" action="carrinho.php" style="margin-top:10px;">
                        <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
                        <label for="qtd_<?= $produto['id_produto'] ?>" class="sr-only">Quantidade</label>
                        <input id="qtd_<?= $produto['id_produto'] ?>" type="number" name="quantidade" value="1" min="1" style="width:50px;">
                        <button class="btn" type="submit" name="adicionar">Adicionar</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<?php include 'includes/footer.php'; ?>
<script>
let slides = document.querySelectorAll('.hero-slide');
let currentSlide = 0;

setInterval(() => {
    slides[currentSlide].classList.remove('active');
    currentSlide = (currentSlide + 1) % slides.length;
    slides[currentSlide].classList.add('active');
}, 10000); // 10 segundos
</script>
</body>
</html>
