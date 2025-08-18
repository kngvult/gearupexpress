<?php
session_start();

// Se o admin já estiver logado, redireciona para o painel
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

include '../includes/conexao.php'; // Usa '..' para voltar um nível e encontrar a pasta includes
$erro = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha_formulario = $_POST['senha'];

    if (empty($email) || empty($senha_formulario)) {
        $erro = "Preencha todos os campos.";
    } else {
        // Query de login que verifica se o usuário é um 'admin'
        $stmt = $pdo->prepare("
            SELECT p.id, p.nome, u.encrypted_password
            FROM public.perfis p
            JOIN auth.users u ON p.id = u.id
            WHERE u.email = ? AND p.tipo = 'admin'
        ");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // A verificação de senha usa a função crypt, assim como no login do cliente
        if ($admin && password_verify($senha_formulario, $admin['encrypted_password'])) {
            // Sucesso! Armazena os dados do admin em uma sessão separada
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            header('Location: index.php'); // Redireciona para o dashboard do admin
            exit;
        } else {
            $erro = "Acesso negado. Verifique suas credenciais.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Painel Administrativo</title>
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/admin_style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-box">
            <h2>Painel Administrativo</h2>
            <p>GearUp Express</p>
            <?php if (!empty($erro)): ?>
                <div class="alert-admin alert-danger"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <button type="submit" class="btn-admin">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>