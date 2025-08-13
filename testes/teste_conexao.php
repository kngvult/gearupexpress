<?php
require_once 'includes/conexao.php';

try {
    // Executa uma consulta simples
    $stmt = $pdo->query("SELECT version()");
    $versao = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h2>✅ Conexão com Supabase bem-sucedida!</h2>";
    echo "<p>Versão do PostgreSQL: " . $versao['version'] . "</p>";
} catch (PDOException $e) {
    echo "<h2>❌ Erro ao consultar o banco:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>