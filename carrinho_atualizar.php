<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'includes/conexao.php';
// PUXA AS FUNÇÕES GLOBAIS DO CARRINHO
include 'includes/funcoes_carrinho.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Erro desconhecido.'];

// DADOS DO JAVASCRIPT
$id_usuario = $_SESSION['usuario']['id'] ?? null;
$id_produto = (int)($_POST['id_produto'] ?? 0);
$acao = $_POST['acao'] ?? ''; // 'remover' ou 'atualizar'

try {
    if ($id_produto > 0 && !empty($acao)) {
        
        if ($id_usuario) {
            // --- LÓGICA PARA USUÁRIO LOGADO (BANCO DE DADOS) ---
            
            if ($acao === 'remover') {
                $stmt = $pdo->prepare("DELETE FROM carrinho WHERE usuario_id = ? AND id_produto = ?");
                $stmt->execute([$id_usuario, $id_produto]);
            
            } elseif ($acao === 'atualizar') {
                $quantidade = max(1, (int)($_POST['quantidade'] ?? 1)); // Garante no mínimo 1
                $stmt = $pdo->prepare("UPDATE carrinho SET quantidade = ? WHERE usuario_id = ? AND id_produto = ?");
                $stmt->execute([$quantidade, $id_usuario, $id_produto]);
            }
            
        } else {
            // --- LÓGICA PARA VISITANTE (SESSÃO) ---
            
            if (isset($_SESSION['carrinho'][$id_produto])) {
                if ($acao === 'remover') {
                    unset($_SESSION['carrinho'][$id_produto]);
                
                } elseif ($acao === 'atualizar') {
                    $quantidade = max(1, (int)($_POST['quantidade'] ?? 1));
                    $_SESSION['carrinho'][$id_produto]['quantidade'] = $quantidade;
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = 'Carrinho atualizado com sucesso.';
        
    } else {
        $response['message'] = 'Ação ou produto inválido.';
    }

} catch (PDOException $e) {
    error_log("Erro no carrinho_atualizar.php: " . $e->getMessage());
    $response['message'] = 'Erro no servidor.';
}

if ($response['success']) {
    $response['totalItensCarrinho'] = contarItensCarrinho($pdo);
    $response['novoTotalCarrinho'] = 'R$ ' . number_format(calcularTotalCarrinho($pdo, $id_usuario), 2, ',', '.');
}

echo json_encode($response);
exit;
?>