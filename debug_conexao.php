<?php
// Em: debug_conexao.php

// Define o topo como texto simples para vermos todos os erros
header('Content-Type: text/plain; charset=utf-8');

echo "--- INICIANDO TESTE DE CONEXÃO AO SUPABASE ---\n\n";

// --- ETAPA 1: LER AS VARIÁVEIS DE AMBIENTE ---
echo "ETAPA 1: A LER AS VARIÁVEIS DO DIGITALOCEAN (getenv)...\n";

$host = getenv('PHP_SITE_DB_HOST');
$dbname = getenv('PHP_SITE_DB_NAME');
$user = getenv('PHP_SITE_DB_USER');
$pass = getenv('PHP_SITE_DB_PASSWORD'); // A senha não será impressa

echo "HOST: ";
var_dump($host); // Esperamos: string(...) ou bool(false)

echo "DBNAME: ";
var_dump($dbname);

echo "USER: ";
var_dump($user);

echo "PASSWORD (Existe?): ";
var_dump(!empty($pass)); // Só queremos saber se ela existe, não o valor

echo "\n--- ETAPA 2: TENTAR A CONEXÃO (new PDO) ---\n";

if (empty($host) || empty($dbname) || empty($user) || empty($pass)) {
    echo "\n!!! FALHA CRÍTICA (ETAPA 1) !!!\n";
    echo "RESULTADO: As variáveis de ambiente (PHP_SITE_DB_HOST, etc.) NÃO FORAM ENCONTRADAS.\n";
    echo "SOLUÇÃO: Verifique se os NOMES e VALORES estão 100% corretos no painel 'Environment Variables' do DigitalOcean e force um 're-deploy'.\n";
    die();
}

// --- As variáveis existem, vamos tentar conectar ---
try {
    $dsn = "pgsql:host=$host;port=5432;dbname=$dbname;sslmode=require";
    
    // Tenta a conexão
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    echo "\n*** SUCESSO! (ETAPA 2) ***\n";
    echo "RESULTADO: A conexão com o SupABASE (PostgreSQL) foi BEM SUCEDIDA!\n";
    echo "CONCLUSÃO: O problema não é a conexão. O erro 500 está a ser causado por outra coisa (talvez um caminho de 'include' errado noutro ficheiro).\n";

} catch (PDOException $e) {
    echo "\n!!! FALHA CRÍTICA (ETAPA 2) !!!\n";
    echo "RESULTADO: As variáveis foram LIDAS, mas a conexão PDO FALHOU.\n";
    echo "MENSAGEM DE ERRO: " . $e->getMessage() . "\n\n";
    echo "SOLUÇÃO PROVÁVEL:\n";
    if (strpos($e->getMessage(), 'password authentication failed') !== false) {
        echo "-> A senha (PHP_SITE_DB_PASSWORD) está errada no painel do DigitalOcean.\n";
    } elseif (strpos($e->getMessage(), 'could not find driver') !== false) {
        echo "-> O PHP do DigitalOcean não tem a extensão 'pdo_pgsql' instalada.\n";
    } elseif (strpos($e->getMessage(), 'could not resolve host') !== false) {
        echo "-> O Host (PHP_SITE_DB_HOST) está errado ou o Supabase está a bloquear o IP (improvável).\n";
    } else {
        echo "-> Verifique TODAS as suas 4 variáveis de ambiente.\n";
    }
}

echo "\n--- TESTE CONCLUÍDO ---";
?>