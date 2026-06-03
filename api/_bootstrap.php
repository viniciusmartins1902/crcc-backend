<?php
/**
 * Bootstrap das APIs Construtora
 * Carregado por todos os endpoints — configura CORS, helpers e JWT.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../src/Core/TenantManager.php';

// ── CORS ──────────────────────────────────────────────────────────────────────
// Permite requisições do app Capacitor (capacitor://localhost, http://localhost)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ── JWT ───────────────────────────────────────────────────────────────────────
define('JWT_SECRET', hash('sha256', API_KEY . 'construtora-mobile-v1', true));
define('JWT_TTL',    86400 * 30); // 30 dias

function jwtEncode(array $payload): string {
    $header  = _b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['iat'] = time();
    $payload['exp'] = time() + JWT_TTL;
    $body    = _b64url(json_encode($payload));
    $sig     = _b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$header, $body, $sig] = $parts;
    $expected = _b64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $payload = json_decode(_b64url_dec($body), true);
    if (!$payload || ($payload['exp'] ?? 0) < time()) return null;
    return $payload;
}

/**
 * Detecta o tenant_id a partir do subdomínio da requisição.
 * crcc.zetta.net.br → slug "crcc" → tenant_id do banco.
 * Retorna null se o tenant não for encontrado.
 */
function detectTenantId(): ?string {
    static $cached = false;
    if ($cached !== false) return $cached;

    $slug     = TenantManager::detectSlug();
    $supabase = new Supabase();
    $rows     = $supabase->request('GET', '/rest/v1/tenants', null, [
        'slug'   => 'eq.' . $slug,
        'ativa'  => 'eq.true',
        'select' => 'id',
        'limit'  => '1',
    ]);
    $cached = $rows[0]['id'] ?? null;
    return $cached;
}

function _b64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
function _b64url_dec(string $data): string {
    return base64_decode(strtr($data, '-_', '+/'));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function jsonOk(array $data, int $code = 200): never {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function jsonErr(string $mensagem, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['erro' => $mensagem], JSON_UNESCAPED_UNICODE);
    exit;
}

function bodyJson(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw ?: '{}', true) ?? [];
}
