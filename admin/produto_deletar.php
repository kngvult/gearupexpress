<?php
// Inclui o verificador de segurança e a conexão com o banco
include 'includes/auth_check.php';
include '../includes/conexao.php';

// Verifica se um ID foi passado pela URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_produto = (int)$_GET['id'];

    try {
        // Prepara e executa a query de exclusão
        $stmt = $pdo->prepare("DELETE FROM public.produtos WHERE id_produto = ?");
        $stmt->execute([$id_produto]);

        // Redireciona de volta para a lista de produtos
        $mensagem = urlencode('Produto excluído com sucesso!');
header("Location: produtos.php?status=danger&msg={$mensagem}");
        exit;
    } catch (PDOException $e) {
        // Em caso de erro, exibe uma mensagem
        // (Por exemplo, se o produto estiver associado a um pedido, pode dar erro de chave estrangeira)
        die("Erro ao excluir produto: " . $e->getMessage());
    }
} else {
    // Se nenhum ID for fornecido, apenas redireciona
    header('Location: produtos.php');
    exit;
}