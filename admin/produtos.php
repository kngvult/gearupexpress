<?php
// Inclui o cabeçalho, menu e verificador de segurança
include 'includes/header.php';

// Busca todos os produtos, juntando com o nome da categoria para exibição
try {
    $stmt = $pdo->query("
        SELECT 
            p.id_produto, 
            p.nome, 
            p.preco, 
            p.estoque, 
            p.imagem, 
            c.nome AS nome_categoria
        FROM public.produtos p
        LEFT JOIN public.categorias c ON p.id_categoria = c.id_categoria
        ORDER BY p.id_produto DESC
    ");
    $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC); 
} catch (PDOException $e) {
    $produtos = [];
    error_log("Erro ao buscar produtos: " . $e->getMessage());
}

// Define os scripts para a biblioteca DataTables
$scripts_adicionais = "
<script>
$(document).ready(function() {
    $('#tabela-produtos').DataTable({
        \"language\": {
            \"url\": \"//cdn.datatables.net/plug-ins/1.13.6/i18n/pt-BR.json\"
        },
        \"columnDefs\": [
            { \"orderable\": false, \"targets\": [0, 6] } // Desabilita ordenação na coluna de imagem e ações
        ]
    });
});
</script>
";
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">Gerenciar Produtos</h1>
        <a href="produto_form.php" class="btn-admin">Adicionar Novo Produto</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h4>Lista de Produtos</h4>
        </div>
        <div class="card-body">
            <div class="table-wrapper">
                <table id="tabela-produtos" class="table">
                    <thead>
                        <tr>
                            <th>Imagem</th>
                            <th>ID</th>
                            <th>Nome do Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($produtos)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Nenhum produto cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($produtos as $produto): ?>
                                <tr>
                                    <td>
                                        <img src="../assets/img/produtos/<?= htmlspecialchars($produto['imagem'] ?: 'placeholder.jpg') ?>" alt="<?= htmlspecialchars($produto['nome']) ?>" class="table-img">
                                    </td>
                                    <td><?= htmlspecialchars($produto['id_produto']) ?></td>
                                    <td><?= htmlspecialchars($produto['nome']) ?></td>
                                    <td><?= htmlspecialchars($produto['nome_categoria'] ?? 'Sem categoria') ?></td>
                                    <td>R$ <?= number_format($produto['preco'], 2, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($produto['estoque']) ?></td>
                                    <td class="actions">
                                        <a href="produto_form.php?id=<?= $produto['id_produto'] ?>" class="btn-action btn-edit">Editar</a>
                                        <a href="javascript:void(0);" class="btn-action btn-delete" onclick="showConfirmModal('Tem certeza que deseja excluir este produto? Esta ação não pode ser desfeita.', () => { window.location.href = 'produto_deletar.php?id=<?= $produto['id_produto'] ?>' })">Excluir</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
// Inclui o rodapé, que irá carregar os scripts necessários
include 'includes/footer.php';
?>