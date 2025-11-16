<?php

$host = getenv('PHP_SITE_DB_HOST');
$dbname = getenv('PHP_SITE_DB_NAME');
$user = getenv('PHP_SITE_DB_USER');
$pass = getenv('PHP_SITE_DB_PASSWORD');

if (empty($host) || empty($dbname) || empty($user) || empty($pass)) {
    
    error_log("Erro Fatal: As variáveis de ambiente (PHP_SITE_DB_HOST, etc.) não foram configuradas no DigitalOcean.");
    die("Erro de configuração do servidor.");
}

$dsn = "pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Erro de Conexão com o Supabase: " . $e->getMessage());
    die("Erro ao conectar com o banco de dados.");
}
?>