<?php
include 'includes/header.php'; 
include 'includes/conexao.php';

if (isset($_SESSION['usuario']['id'])) {
    header('Location: index.php');
    exit;
}

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter no mínimo 6 caracteres.";
    } else {
        $env = parse_ini_file(__DIR__ . '/includes/.env');

        // Usa as variáveis
        $supabaseUrl = $env['PHP_SITE_SUPABASE_URL'];
        $serviceRoleKey = $env['PHP_SITE_SERVICE_ROLE_KEY'];

        // Exemplo de chamada cURL usando as variáveis
        $ch = curl_init($supabaseUrl . '/auth/v1/signup');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => $email,
            'password' => $senha,
            'data' => ['nome' => $nome]
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'apikey: ' . $serviceRoleKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['user'])) {
            $id = $data['user']['id'];
            // Insere na tabela perfis
            $stmt = $pdo->prepare("INSERT INTO public.perfis (id, nome) VALUES (?, ?)");
            $stmt->execute([$id, $nome]);
            $sucesso = "Cadastro realizado! Verifique seu e-mail para ativar a conta.";
        } elseif (isset($data['msg']) && str_contains($data['msg'], 'already registered')) {
            $erro = "Este e-mail já está cadastrado.";
        } else {
            $erro = "<strong>Erro:</strong> " . htmlspecialchars($response);
        }
    }
}
?>

<main class="page-content">
<div class="container">
    <div class="register-container">
        <h2 class="section-title">Criar Conta</h2>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($sucesso) ?></div>
        <?php else: ?>
            <form method="post" action="cadastro.php" class="form-layout">
                <div class="form-group">
                    <label for="nome">Nome Completo:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="senha">Senha (mínimo 6 caracteres):</label>
                    <input type="password" id="senha" name="senha" required>
                </div>
                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </form>
        <?php endif; ?>

        <p class="form-footer-text">Já tem uma conta? <a href="login.php">Faça o login</a>.</p>
    </div>
</div>
</main>

<style>
.login-container, .register-container { max-width: 450px; margin: 60px auto; padding: 40px; background: var(--white); border-radius: 12px; box-shadow: var(--soft-shadow); }
.form-layout .form-group { margin-bottom: 20px; }
.form-layout label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark-text); }
.form-layout input { width: 100%; padding: 12px 15px; border: 1px solid var(--border-color); border-radius: 8px; font-size: 1rem; box-sizing: border-box; transition: all 0.2s ease; }
.form-layout input:focus { outline: none; border-color: var(--primary-color); box-shadow: var(--subtle-glow); }
.form-layout .btn { width: 100%; padding: 15px; font-size: 1rem; font-weight: 600; border: none; cursor: pointer; margin-top: 10px; }
.form-footer-text { text-align: center; margin-top: 25px; color: var(--light-text); }
.form-footer-text a { color: var(--primary-color); text-decoration: none; font-weight: 600; }
.form-footer-text a:hover { text-decoration: underline; }
.alert { padding: 1rem 1.5rem; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
.alert-success { background-color: #e9f7ef; color: #1d6a3c; }
.alert-danger { background-color: #fdeeee; color: #a51825; }
</style>

<?php include 'includes/footer.php'; ?>