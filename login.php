<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

if (isset($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}

$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha_formulario = $_POST['senha'];

    if (empty($email) || empty($senha_formulario)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        // Chama a função de autenticação no banco
        $stmt = $pdo->prepare("SELECT * FROM public.authenticate_user(?, ?)");
    $stmt->execute([$email, $senha_formulario]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        // Se a função retornou um usuário, o login é bem-sucedido
        if ($usuario) {
        $_SESSION['usuario'] = [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email']
        ];
            // Aqui você pode adicionar a lógica para mesclar o carrinho, se necessário
            
            header('Location: index.php');
            exit;
        } else {
            $erro = "Email ou senha inválidos.";
        }
    }
}

// Esta função não deveria estar aqui para evitar duplicação.
// Mova-a para um arquivo central de funções.
function adicionarOuAtualizarBanco($pdo, $id_usuario, $id_produto, $quantidade) {
    // ... (corpo da função como no carrinho.php) ...
    $stmtPreco = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
    $stmtPreco->execute([$id_produto]);
    $preco = $stmtPreco->fetchColumn();
    if (!$preco) return false;
    $stmt = $pdo->prepare("SELECT id, quantidade FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
    $stmt->execute([$id_usuario, $id_produto]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $novaQtd = $item['quantidade'] + $quantidade;
        $stmtUpd = $pdo->prepare("UPDATE carrinho SET quantidade = ?, atualizado_em = NOW() WHERE id = ?");
        $stmtUpd->execute([$novaQtd, $item['id']]);
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO carrinho (usuario_id, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$id_usuario, $id_produto, $quantidade, $preco]);
    }
    return true;
}

?>

<main class="page-content">
<div class="container">
    <div class="login-container">
        <h2 class="section-title">Acessar Conta</h2>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="form-layout">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>
            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        <p class="form-footer-text">Não tem uma conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
    </div>
</div>
</main>

<style>
.login-container, .register-container {
    max-width: 450px;
    margin: 60px auto;
    padding: 40px;
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--soft-shadow);
}
.form-layout .form-group {
    margin-bottom: 20px;
}
.form-layout label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark-text);
}
.form-layout input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 8px;
    font-size: 1rem;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.form-layout input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: var(--subtle-glow);
}
.form-layout .btn {
    width: 100%;
    padding: 15px;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    margin-top: 10px;
}
.form-footer-text {
    text-align: center;
    margin-top: 25px;
    color: var(--light-text);
}
.form-footer-text a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
}
.form-footer-text a:hover {
    text-decoration: underline;
}
</style>

<?php include 'includes/footer.php'; ?>