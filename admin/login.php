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
    <title>Login - Painel Administrativo | GearUp Express</title>
    <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-page">
        <div class="login-container">
            <div class="login-brand">
                <div class="brand-logo">
                    <img src="../assets/img/GUPX-logo-v2.ico" alt="Logo GearUp Express" style="max-width: 350px; display: block; margin: 0 auto 16px;">
                </div>
                <!--<h1 class="brand-title">GearUp Express</h1> -->
                <p class="brand-subtitle">Sistema de Gerenciamento e Controle de Peças Automotivas</p>
                
                <div class="brand-features">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <span class="feature-text">Acesso seguro e criptografado</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <span class="feature-text">Relatórios e análises em tempo real</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-cube"></i>
                        </div>
                        <span class="feature-text">Gestão completa de inventário</span>
                    </div>
                </div>
            </div>
            
            <div class="login-form-container">
                <div class="form-header">
                    <h2 class="form-title">Acessar Painel Admin</h2>
                    <p class="form-subtitle">Entre com suas credenciais para gerenciar o sistema</p>
                </div>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert-admin alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($erro) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="login.php" class="login-form">
                    <div class="form-group">
                        <label for="email">E-mail administrativo</label>
                        <div class="input-with-icon">
                            <input type="email" name="email" class="form-control">
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <div class="input-with-icon">
                            <input type="password" name="senha" class="form-control">
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Entrar no Painel
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>&copy; <?= date('Y'); ?> GearUP Express. Todos os direitos reservados.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Efeito de foco no primeiro campo ao carregar a página
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });

        // Validação básica do formulário
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const senha = document.getElementById('senha');
            
            if (!email.value || !senha.value) {
                e.preventDefault();
                if (!email.value) {
                    email.focus();
                } else {
                    senha.focus();
                }
            }
        });
    </script>
</body>
</html>