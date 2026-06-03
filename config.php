<?php
require_once __DIR__ . '/src/Core/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

define('SUPABASE_URL',      EnvLoader::get('SUPABASE_URL'));
define('SUPABASE_ANON_KEY', EnvLoader::get('SUPABASE_ANON_KEY'));
define('API_KEY',           EnvLoader::get('API_KEY'));
define('DEFAULT_TENANT_SLUG', 'crcc');
define('DEFAULT_TENANT_ID',   EnvLoader::get('CRCC_TENANT_ID', ''));

date_default_timezone_set('America/Sao_Paulo');

if (EnvLoader::get('APP_ENV', 'production') !== 'production') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path('/tmp/crcc_sessions');
    @mkdir('/tmp/crcc_sessions', 0770, true);
    session_start();
}
