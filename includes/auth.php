<?php
function autenticarUsuario(PDO $pdo, string $email, string $senha): ?array {
    $stmt = $pdo->prepare("SELECT id_usuario, nome, senha FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        return [
            'id' => $usuario['id_usuario'],
            'nome' => $usuario['nome'],
            'email' => $email
        ];
    }

    return null;
}