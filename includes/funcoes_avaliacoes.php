<?php
// includes/funcoes_avaliacoes.php

function getAvaliacaoProduto($pdo, $id_produto) {
    $stmt = $pdo->prepare("
        SELECT 
            AVG(rating) as media_rating,
            COUNT(*) as total_avaliacoes,
            COUNT(comentario) as total_comentarios
        FROM avaliacoes_produtos 
        WHERE id_produto = ?
    ");
    $stmt->execute([$id_produto]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAvaliacaoUsuario($pdo, $id_produto, $id_usuario) {
    $stmt = $pdo->prepare("
        SELECT rating, comentario, data_avaliacao
        FROM avaliacoes_produtos 
        WHERE id_produto = ? AND id_usuario = ?
    ");
    $stmt->execute([$id_produto, $id_usuario]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function salvarAvaliacao($pdo, $id_produto, $id_usuario, $rating, $comentario = null) {
    // Verificar se já existe avaliação do usuário
    $avaliacao_existente = getAvaliacaoUsuario($pdo, $id_produto, $id_usuario);
    
    if ($avaliacao_existente) {
        // Atualizar avaliação existente
        $stmt = $pdo->prepare("
            UPDATE avaliacoes_produtos 
            SET rating = ?, comentario = ?, data_avaliacao = CURRENT_TIMESTAMP
            WHERE id_produto = ? AND id_usuario = ?
        ");
        return $stmt->execute([$rating, $comentario, $id_produto, $id_usuario]);
    } else {
        // Inserir nova avaliação
        $stmt = $pdo->prepare("
            INSERT INTO avaliacoes_produtos (id_produto, id_usuario, rating, comentario)
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$id_produto, $id_usuario, $rating, $comentario]);
    }
}

function getAvaliacoesRecentes($pdo, $id_produto, $limit = 5) {
    $stmt = $pdo->prepare("
        SELECT 
            a.rating,
            a.comentario,
            a.data_avaliacao,
            p.nome as usuario_nome
        FROM avaliacoes_produtos a
        JOIN perfis p ON a.id_usuario = p.id
        WHERE a.id_produto = ? AND a.comentario IS NOT NULL
        ORDER BY a.data_avaliacao DESC
        LIMIT ?
    ");
    $stmt->execute([$id_produto, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function gerarStars($rating, $tamanho = 'sm') {
    $stars = '';
    $tamanhos = ['sm' => '14px', 'md' => '16px', 'lg' => '20px'];
    $tamanho_px = $tamanhos[$tamanho] ?? '16px';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<i class="fas fa-star" style="color: #ffc107; font-size: ' . $tamanho_px . ';"></i>';
        } else {
            $stars .= '<i class="far fa-star" style="color: #ffc107; font-size: ' . $tamanho_px . ';"></i>';
        }
    }
    
    return $stars;
}
?>