<?php
/**
 * POST /construtora/api/login.php
 * Body: { "email": "...", "senha": "..." }
 * Retorna: { "token": "...", "usuario": { id, nome, nivel_acesso, area, funcao } }
 */

require_once __DIR__ . '/_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonErr('Método não permitido.', 405);
}

$body = bodyJson();
$email = trim($body['email'] ?? '');
$senha = trim($body['senha'] ?? '');

if (!$email || !$senha) {
    jsonErr('E-mail e senha são obrigatórios.');
}

// Detecta tenant pelo subdomínio (crcc.zetta.net.br → crcc)
$tenantId = detectTenantId();
if (!$tenantId) {
    jsonErr('Tenant não identificado. Verifique o endereço de acesso.', 403);
}

$supabase = new Supabase();

// Busca usuário pelo e-mail dentro do tenant correto
$params = [
    'email'     => 'eq.' . $email,
    'tenant_id' => 'eq.' . $tenantId,
    'select'    => 'id,nome,email,senha,nivel_acesso,funcao,area,tenant_id,expira_em',
    'limit'     => '1',
];
$rows = $supabase->request('GET', '/rest/v1/users', null, $params);

if (empty($rows) || !is_array($rows)) {
    jsonErr('Credenciais inválidas.', 401);
}

$usuario = $rows[0];

// Verifica senha
if (!password_verify($senha, $usuario['senha'] ?? '')) {
    jsonErr('Credenciais inválidas.', 401);
}

// Verifica expiração da conta
if (!empty($usuario['expira_em']) && strtotime($usuario['expira_em']) < time()) {
    jsonErr('Conta expirada.', 403);
}

// Verifica se tem acesso ao módulo construtora (nivel 1, 2 ou 3)
if (($usuario['nivel_acesso'] ?? 99) > 3) {
    jsonErr('Sem permissão de acesso.', 403);
}

// Gera token JWT
$token = jwtEncode([
    'sub'          => $usuario['id'],
    'tenant_id'    => $usuario['tenant_id'],
    'nivel_acesso' => $usuario['nivel_acesso'],
    'nome'         => $usuario['nome'],
]);

jsonOk([
    'token'   => $token,
    'usuario' => [
        'id'           => $usuario['id'],
        'nome'         => $usuario['nome'],
        'email'        => $usuario['email'],
        'nivel_acesso' => $usuario['nivel_acesso'],
        'funcao'       => $usuario['funcao'],
        'area'         => $usuario['area'],
    ],
]);
