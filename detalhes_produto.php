<?php
include_once 'includes/header.php';
include_once 'includes/conexao.php';

// Captura o ID do produto
$produtoId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($produtoId <= 0) {
    echo "<p>Produto inválido.</p>";
    include 'footer.php';
    exit;
}

try {
    // Busca os detalhes do produto
    $stmt = $pdo->prepare("
        SELECT p.id_produto, p.nome, p.descricao, p.preco, p.imagem, c.nome AS categoria
        FROM produtos p
        INNER JOIN categorias c ON p.id_categoria = c.id_categoria
        WHERE p.id_produto = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $produtoId]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        echo "<p>Produto não encontrado.</p>";
        include 'footer.php';
        exit;
    }

} catch (PDOException $e) {
    die("Erro ao buscar produto: " . $e->getMessage());
}
?>

<main class="conteudo">
    <div class="container">
        <div class="detalhes-produto">
            <div class="imagem-produto">
                <img src="uploads/<?= htmlspecialchars($produto['imagem']) ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
            </div>

            <div class="info-produto">
                <h1><?= htmlspecialchars($produto['nome']) ?></h1>
                <p class="categoria">Categoria: <?= htmlspecialchars($produto['categoria']) ?></p>
                <p class="preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></p>
                <p class="descricao"><?= nl2br(htmlspecialchars($produto['descricao'])) ?></p>
<form id="form-carrinho">
    <input type="hidden" name="id_produto" value="<?= $produto['id_produto'] ?>">
    <label for="quantidade">Quantidade:</label>
    <input type="number" name="quantidade" id="quantidade" value="1" min="1" required>
    <button type="submit" class="btn">Adicionar ao Carrinho</button>
</form>

<!-- Pop-up estilizado -->
<div id="popup-carrinho" class="popup-carrinho">
    <div class="popup-conteudo">
        <span class="icone">🛒</span>
        <h2>Produto adicionado com sucesso!</h2>
        <p>Você pode continuar comprando ou ir direto para o carrinho.</p>
        <div class="botoes">
            <button onclick="fecharPopup()" class="btn-continuar">Continuar comprando</button>
            <a href="carrinho.php" class="btn-carrinho">Ir para o carrinho</a>
        </div>
    </div>
</div>

<style>
.popup-carrinho {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    animation: fadeIn 0.3s ease-in-out;
}

.popup-conteudo {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    text-align: center;
    max-width: 400px;
    animation: slideUp 0.4s ease-out;
}

.popup-conteudo .icone {
    font-size: 40px;
    margin-bottom: 10px;
    display: block;
}

.popup-conteudo h2 {
    margin: 10px 0;
    font-size: 22px;
    color: #333;
}

.popup-conteudo p {
    font-size: 16px;
    color: #666;
    margin-bottom: 20px;
}

.botoes {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.btn-continuar, .btn-carrinho {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    cursor: pointer;
    transition: background 0.3s;
    text-decoration: none;
}

.btn-continuar {
    background-color: #f0f0f0;
    color: #333;
}

.btn-continuar:hover {
    background-color: #e0e0e0;
}

.btn-carrinho {
    background-color: #4CAF50;
    color: white;
}

.btn-carrinho:hover {
    background-color: #45a049;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideUp {
    from { transform: translate(-50%, 60%); opacity: 0; }
    to { transform: translate(-50%, -50%); opacity: 1; }
}
</style>

<script>
function fecharPopup() {
    document.getElementById('popup-carrinho').style.display = 'none';
}

document.getElementById('form-carrinho').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('adicionar_carrinho.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.sucesso) {
            document.getElementById('popup-carrinho').style.display = 'block';
        } else {
            alert('Erro ao adicionar ao carrinho: ' + (data.erro || ''));
        }
    })
    .catch(err => {
        alert('Erro de conexão.');
        console.error(err);
    });
});
</script>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>
