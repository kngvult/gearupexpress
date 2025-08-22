<?php
// login.php - VERSÃO FINAL

session_start();
include 'includes/conexao.php';
include_once 'includes/funcoes_carrinho.php'; // Inclui as funções do carrinho

// Redireciona se o usuário já estiver logado
if (isset($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}

$erro = "";

// PROCESSA O FORMULÁRIO DE LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha_formulario = $_POST['senha'];

    if (empty($email) || empty($senha_formulario)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            // AUTENTICAÇÃO DO USUÁRIO NO BANCO DE DADOS DO SUPABASE
            $stmt = $pdo->prepare("SELECT * FROM public.authenticate_user(?, ?)");
            $stmt->execute([$email, $senha_formulario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($usuario) {
                // Define a sessão do usuário
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email']
                ];
                
                // Chama a nossa função "gestora" corrigida para fundir os carrinhos
                sincronizarCarrinho($pdo, $usuario['id']);
                
                // Redireciona para a página do carrinho para o utilizador ver os seus produtos
                header('Location: carrinho.php');
                exit;
    
            } else {
                $erro = "Email ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erro = "Ocorreu um erro no servidor. Tente novamente.";
            error_log("Erro de login: " . $e->getMessage());
        }
    }
}

include 'includes/header.php';
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

<?php include 'includes/footer.php'; ?>