<?php
// PASSO 1: TODA A LÓGICA PHP VEM PRIMEIRO, ANTES DE QUALQUER HTML

// Inicia a sessão e a conexão com o banco
session_start();
include 'includes/conexao.php';

// Redireciona se o usuário já estiver logado
if (isset($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}

$erro = "";

// Processa o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha_formulario = $_POST['senha'];

    if (empty($email) || empty($senha_formulario)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            // Chama a função de autenticação que criamos no banco de dados
            $stmt = $pdo->prepare("SELECT * FROM public.authenticate_user(?, ?)");
            $stmt->execute([$email, $senha_formulario]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Se a função retornou um usuário, o login é bem-sucedido
            if ($usuario) {
                // Define a sessão do usuário
                $_SESSION['usuario'] = [
                    'id' => $usuario['id'],
                    'nome' => $usuario['nome'],
                    'email' => $usuario['email']
                ];
                
                // Lógica para mesclar o carrinho de visitante com o do banco (se existir)
                if (!empty($_SESSION['carrinho'])) {
                    // Incluímos a função aqui para garantir que ela exista
                    include_once 'includes/funcoes_carrinho.php'; // Crie este arquivo se necessário
                    foreach ($_SESSION['carrinho'] as $id_produto => $quantidade) {
                        adicionarOuAtualizarBanco($pdo, $_SESSION['usuario']['id'], $id_produto, $quantidade);
                    }
                    unset($_SESSION['carrinho']);
                }
                
                // REDIRECIONAMENTO ACONTECE AQUI, ANTES DE QUALQUER HTML
                header('Location: index.php');
                exit; // Encerra o script após o redirecionamento
    
            } else {
                $erro = "Email ou senha inválidos.";
            }
        } catch (PDOException $e) {
            $erro = "Ocorreu um erro no servidor. Tente novamente.";
            error_log("Erro de login: " . $e->getMessage());
        }
    }
}

// PASSO 2: SÓ DEPOIS DE TODA A LÓGICA, INCLUÍMOS O HEADER PARA EXIBIR A PÁGINA
// Se o login foi bem-sucedido, o script já foi encerrado pelo 'exit' acima e esta parte nunca será executada.
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