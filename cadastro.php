<?php
include 'includes/conexao.php';
include 'includes/header.php';

$erro = "";
$sucesso = "";

// Processa o cadastro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Verifica se as senhas coincidem
    if ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } else {
        // Verifica se o e-mail já está cadastrado
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            // Criptografa a senha
            $hashSenha = password_hash($senha, PASSWORD_DEFAULT);

            // Insere o novo usuário
            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)");
            if ($stmt->execute([$nome, $email, $hashSenha])) {
                $sucesso = "Cadastro realizado com sucesso! Você já pode fazer login.";
            } else {
                $erro = "Erro ao cadastrar. Tente novamente.";
            }
        }
    }
}
?>

<style>
    .container-cadastro {
        max-width: 400px;
        margin: 60px auto;
        padding: 30px;
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        font-family: Arial, sans-serif;
    }

    .container-cadastro h2 {
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

    .alert-success {
        background: #ddffdd;
        color: #3c763d;
        border: 1px solid #3c763d;
    }

    .form-cadastro {
        display: flex;
        flex-direction: column;
    }

    .form-cadastro label {
        font-weight: bold;
        margin-top: 10px;
        color: #555;
    }

    .form-cadastro input {
        padding: 10px;
        margin-top: 5px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
    }

    .form-cadastro button {
        margin-top: 20px;
        padding: 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
    }

    .form-cadastro button:hover {
        background-color: #0069d9;
    }

    .texto-login {
        text-align: center;
        margin-top: 15px;
        font-size: 14px;
    }

    .texto-login a {
        color: #28a745;
        text-decoration: none;
    }

    .texto-login a:hover {
        text-decoration: underline;
    }

    /* Responsivo */
    @media (max-width: 500px) {
        .container-cadastro {
            margin: 20px;
            padding: 20px;
        }
    }
</style>

<div class="container-cadastro">
    <h2>Cadastro</h2>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>

    <form method="post" action="cadastro.php" class="form-cadastro">
        <label>Nome:</label>
        <input type="text" name="nome" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Senha:</label>
        <input type="password" name="senha" required>

        <label>Confirmar Senha:</label>
        <input type="password" name="confirmar_senha" required>

        <button type="submit">Cadastrar</button>
    </form>

    <p class="texto-login">Já tem conta? <a href="login.php">Entre aqui</a>.</p>
</div>

<?php include 'includes/footer.php'; ?>
