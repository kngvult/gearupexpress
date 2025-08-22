<?php
// Se a variável $pdo não existir (caso o footer seja chamado em uma página simples),
// incluímos a conexão para buscar as categorias.
if (!isset($pdo)) {
    include_once 'includes/conexao.php';
}

// Busca 4 categorias para exibir no rodapé
try {
    $stmtFooterCat = $pdo->query("SELECT id_categoria, nome FROM categorias WHERE nome != 'Todos os Departamentos' ORDER BY nome LIMIT 4");
    $categoriasFooter = $stmtFooterCat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categoriasFooter = []; // Define como vazio em caso de erro
    error_log("Erro ao buscar categorias para o footer: " . $e->getMessage());
}
?>

<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            
            <div class="footer-section">
                <h4>🚗 GearUP Express</h4>
                <p>Sua paixão por carros, nossa dedicação em peças. Qualidade e confiança desde 2025.</p>
                <ul class="footer-contact">
                    <li>📍 Manaus, AM</li>
                    <li>📞 (92) 4002-8922</li>
                    <li>✉️ contato@gearup.com.br</li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>🧰 Departamentos</h4>
                <ul class="footer-links">
                    <?php foreach ($categoriasFooter as $cat): ?>
                        <li><a href="produtos.php?categoria=<?= $cat['id_categoria'] ?>"><?= htmlspecialchars($cat['nome']) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="produtos.php">Ver todos...</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>📞 Atendimento</h4>
                <ul class="footer-links">
                    <li><a href="#">Fale Conosco</a></li>
                    <li><a href="#">Trocas e Devoluções</a></li>
                    <li><a href="#">Política de Privacidade</a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4>🌐 Redes Sociais</h4>
                <ul class="footer-social">
                    <li><a href="#" aria-label="Facebook">📘</a></li>
                    <li><a href="#" aria-label="Instagram">📸</a></li>
                    <li><a href="#" aria-label="Twitter">🐦</a></li>
                    <li><a href="#" aria-label="YouTube">🎥</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> GearUP Express. Todos os direitos reservados.</p>
        </div>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
</body>
</html>