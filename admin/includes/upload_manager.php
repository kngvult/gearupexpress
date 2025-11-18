<?php
// ARQUIVO: admin/includes/upload_manager.php

function enviarImagemSupabase(array $file_array, string $bucket_nome): string|false
{
    $supabase_url = getenv('PHP_SITE_SUPABASE_URL');
    $service_key = getenv('PHP_SITE_SERVICE_ROLE_KEY');

    if (empty($supabase_url) || empty($service_key)) {
        error_log("Erro upload: Variáveis de ambiente não configuradas.");
        return false; 
    }

    $tmp_name = $file_array['tmp_name'];
    // Cria um nome seguro para URL
    $nome_final_arquivo = uniqid() . '-' . basename($file_array['name']);
    $file_name_codificado = 'produtos/' . rawurlencode($nome_final_arquivo);
    
    // URL DA API (PARA O UPLOAD - POST) - SEM '/public'
    // Esta era a causa do erro 404. O endpoint de upload NÃO tem 'public'.
    $api_upload_url = $supabase_url . '/storage/v1/object/' . $bucket_nome . '/' . $file_name_codificado;
        
    // URL PÚBLICA (PARA O BANCO DE DADOS - GET) - COM '/public'
    $public_url = $supabase_url . '/storage/v1/object/public/' . $bucket_nome . '/' . $file_name_codificado;

    $ch = curl_init($api_upload_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($tmp_name));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $service_key,
        'Content-Type: application/octet-stream', // Tipo genérico para evitar erro 400
        'x-upsert: true' 
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        return $public_url; // SUCESSO: Retorna a URL completa para salvar no banco
    } else {
        error_log("Supabase Upload Falhou (Código: $http_code): " . $response);
        return false;
    }
}
?>