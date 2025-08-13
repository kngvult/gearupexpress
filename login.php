<?php
include 'includes/conexao.php';
include 'includes/header.php';

$erro = "";

// Função para adicionar ou atualizar item no carrinho do banco
function adicionarOuAtualizarBanco($pdo, $id_usuario, $id_produto, $quantidade) {
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

// Processa login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id_usuario, nome, senha FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario'] = [
            'id' => $usuario['id_usuario'],
            'nome' => $usuario['nome'],
            'email' => $email
        ];

        if (!empty($_SESSION['carrinho'])) {
            foreach ($_SESSION['carrinho'] as $id_produto => $quantidade) {
                adicionarOuAtualizarBanco($pdo, $_SESSION['usuario']['id'], $id_produto, $quantidade);
            }
            unset($_SESSION['carrinho']);
        }

        header('Location: index.php');
        exit;
    } else {
        $erro = "Email ou senha inválidos.";
    }
}
?>

<style>
    .container-login {
        max-width: 400px;
        margin: 60px auto;
        padding: 30px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        font-family: Arial, sans-serif;
    }

    .container-login h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }

    .alert {
        padding: 10px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-size: 14px;
    }

    .alert-danger {
        background: #ffdddd;
        color: #a94442;
        border: 1px solid #a94442;
    }

    .form-login {
        display: flex;
        flex-direction: column;
    }

    .form-login label {
        font-weight: bold;
        margin-top: 10px;
        color: #555;
    }

    .form-login input {
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-login button {
        margin-top: 20px;
        padding: 12px;
        background-color: #28a745;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }

    .form-login button:hover {
        background-color: #218838;
    }

    .texto-cadastro {
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
    }

    .texto-cadastro a {
        color: #007bff;
        text-decoration: none;
    }

    .texto-cadastro a:hover {
        text-decoration: underline;
    }

    /* Responsivo */
    @media (max-width: 500px) {
        .container-login {
            margin: 20px;
            padding: 20px;
        }
    }
</style>

<div class="container-login">
    <h2>Login</h2>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" class="form-login">
        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <button type="submit">Entrar</button>
    </form>

    <p class="texto-cadastro">Não tem conta? <a href="cadastro.php">Cadastre-se aqui</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
