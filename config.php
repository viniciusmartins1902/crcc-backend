<?php
/**
 * Configuração do CRCC Backend
 * Lê variáveis de ambiente — nunca deixe segredos hardcoded aqui.
 */

require_once __DIR__ . '/src/Core/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

// ── Supabase ──────────────────────────────────────────────────────────────────
define('SUPABASE_URL',      EnvLoader::get('SUPABASE_URL'));
define('SUPABASE_ANON_KEY', EnvLoader::get('SUPABASE_ANON_KEY'));
define('API_KEY',           EnvLoader::get('API_KEY'));

// ── Tenant fixo CRCC ──────────────────────────────────────────────────────────
define('DEFAULT_TENANT_SLUG', 'crcc');
define('DEFAULT_TENANT_ID',   EnvLoader::get('CRCC_TENANT_ID', ''));

// ── Configurações gerais ──────────────────────────────────────────────────────
date_default_timezone_set('America/Sao_Paulo');

if (EnvLoader::get('APP_ENV', 'production') !== 'production') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Sessões ───────────────────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $redisHost = EnvLoader::get('REDIS_HOST', '');
    if ($redisHost !== '' && extension_loaded('redis')) {
        ini_set('session.save_handler', 'redis');
        ini_set('session.save_path', 'tcp://' . $redisHost . ':' . EnvLoader::get('REDIS_PORT', '6379'));
    }
    session_start();
}
