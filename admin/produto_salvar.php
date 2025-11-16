<?php

include 'includes/auth_check.php';

// Verifica se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- 1. COLETA E LIMPEZA DOS DADOS DO FORMULÁRIO ---
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = $_POST['preco'];
    $estoque = (int)$_POST['estoque'];
    $id_categoria = (int)$_POST['id_categoria'];
    $id_produto = isset($_POST['id_produto']) ? (int)$_POST['id_produto'] : null;
    $imagem_atual = $_POST['imagem_atual'] ?? null;
    $nome_imagem = $imagem_atual; // Por padrão, mantém a imagem atual

    // --- 2. LÓGICA DE UPLOAD DA IMAGEM ---
    // Verifica se um novo arquivo de imagem foi enviado
    if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
        $imagem_tmp = $_FILES['imagem']['tmp_name'];
        // Cria um nome de arquivo único para evitar sobreposição
        $nome_imagem = uniqid() . '-' . basename($_FILES['imagem']['name']);
        
        // Define o caminho de destino (voltando um nível para a pasta raiz do site)
        $caminho_destino = '../assets/img/produtos/' . $nome_imagem;

        // Move o arquivo enviado para o diretório de imagens
        if (move_uploaded_file($imagem_tmp, $caminho_destino)) {
        } else {
            die("Erro ao fazer upload da imagem.");
        }
    }

    // --- 3. DECIDE ENTRE INSERT (CRIAR) E UPDATE (EDITAR) ---
    try {
        if ($id_produto) {
            // MODO EDIÇÃO (UPDATE)
            $sql = "UPDATE public.produtos SET 
                        nome = ?, 
                        descricao = ?, 
                        preco = ?, 
                        estoque = ?, 
                        id_categoria = ?, 
                        imagem = ? 
                    WHERE id_produto = ?";
            $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $nome_imagem, $id_produto];
        } else {
            // MODO CRIAÇÃO (INSERT)
            $sql = "INSERT INTO public.produtos (nome, descricao, preco, estoque, id_categoria, imagem) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $params = [$nome, $descricao, $preco, $estoque, $id_categoria, $nome_imagem];
        }
        
        // Executa a query
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Redireciona de volta para a lista de produtos
        $mensagem = urlencode('Produto salvo com sucesso!');
header("Location: produtos.php?status=success&msg={$mensagem}");
        exit;

    } catch (PDOException $e) {
        // Em caso de erro no banco, exibe uma mensagem
        die("Erro ao salvar produto: " . $e->getMessage());
    }

} else {
    // Se o arquivo for acessado diretamente sem POST, redireciona
    header('Location: produtos.php');
    exit;
}