<?php

include_once __DIR__ . '/conexao.php';
include_once __DIR__ . '/DatabaseSessionHandler.php';

if (!isset($pdo)) {
    die("Falha fatal: A conexão PDO não foi estabelecida em conexao.php.");
}
$sessionHandler = new DatabaseSessionHandler($pdo);

session_set_save_handler($sessionHandler, true);

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
$cookieParams = [
    'lifetime' => 86400, // 24h
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => $secure,
    'httponly' => true,
    'samesite' => 'Lax'
];
session_name('GEARUPSESSID'); // Define um nome personalizado
session_set_cookie_params($cookieParams);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
?>