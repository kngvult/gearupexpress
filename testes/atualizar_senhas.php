<?php
include_once 'includes/conexao.php';

try {
    // Seleciona todos os usuários
    $stmt = $pdo->query("SELECT id_usuario, senha FROM usuarios");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($usuarios as $usuario) {
        $senhaAtual = $usuario['senha'];

        // Se já está com hash (começa com $2y$), pula
        if (strpos($senhaAtual, '$2y$') === 0) {
            continue;
        }

        // Gera o hash seguro
        $hash = password_hash($senhaAtual, PASSWORD_DEFAULT);

        // Atualiza no banco
        $stmtUpdate = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id_usuario = ?");
        $stmtUpdate->execute([$hash, $usuario['id_usuario']]);

        echo "Senha do usuário ID {$usuario['id_usuario']} atualizada com hash.\n";
    }

    echo "✅ Todas as senhas foram atualizadas para hash com sucesso.";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
