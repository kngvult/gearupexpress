<?php
// Arquivo: test_login.php
include 'includes/conexao.php';

try {
    $pdo = new PDO(
        "pgsql:host=$host;dbname=$dbname;port=5432;sslmode=require",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT id_usuario, nome, senha FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $mensagem = "Nenhum usuário encontrado com o email '$email'.";
    } else {
        if (password_verify($senha, $usuario['senha'])) {
            $mensagem = "Login válido! Usuário: {$usuario['nome']}, ID: {$usuario['id_usuario']}";
        } else {
            $mensagem = "Senha inválida para o usuário {$usuario['nome']}.";
            $mensagem .= "<br>Hash no banco: {$usuario['senha']}";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<title>Teste Login Supabase</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; }
    label { display: block; margin-top: 10px; }
    input[type="email"], input[type="password"] { width: 100%; padding: 8px; margin-top: 4px; }
    button { margin-top: 15px; padding: 10px 20px; }
    .mensagem { margin-top: 20px; padding: 12px; border-radius: 6px; background: #eee; }
</style>
</head>
<body>

<h2>Teste de Login - Supabase</h2>

<form method="post" action="test_login.php">
    <label>Email:
        <input type="email" name="email" required>
    </label>
    <label>Senha:
        <input type="password" name="senha" required>
    </label>
    <button type="submit">Testar Login</button>
</form>

<?php if ($mensagem): ?>
    <div class="mensagem"><?= $mensagem ?></div>
<?php endif; ?>

</body>
</html>
