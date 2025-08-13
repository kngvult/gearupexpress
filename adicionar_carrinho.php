<?php
session_start();

$id_produto = (int) $_POST['id_produto'];
$quantidade = (int) $_POST['quantidade'];

if ($id_produto > 0 && $quantidade > 0) {
    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    if (isset($_SESSION['carrinho'][$id_produto])) {
        $_SESSION['carrinho'][$id_produto] += $quantidade;
    } else {
        $_SESSION['carrinho'][$id_produto] = $quantidade;
    }

    echo json_encode(['sucesso' => true]);
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
}