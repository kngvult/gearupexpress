<?php
$env = parse_ini_file(__DIR__ . '/.env');

$host = $env['PHP_SITE_DB_HOST'];
$dbname = $env['PHP_SITE_DB_NAME'];
$user = $env['PHP_SITE_DB_USER'];
$password = $env['PHP_SITE_DB_PASSWORD'];

try {
    $pdo = new PDO(
        "pgsql:host=$host;dbname=$dbname;port=5432;sslmode=require",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>