<?php
/**
 * Middleware de autenticação JWT
 * Inclua este arquivo em todos os endpoints protegidos.
 * Disponibiliza $currentUser com: id, tenant_id, nivel_acesso, nome
 */

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!str_starts_with($authHeader, 'Bearer ')) {
    jsonErr('Token não fornecido.', 401);
}

$token   = substr($authHeader, 7);
$payload = jwtDecode($token);

if (!$payload) {
    jsonErr('Token inválido ou expirado.', 401);
}

$currentUser = [
    'id'           => $payload['sub'],
    'tenant_id'    => $payload['tenant_id'],
    'nivel_acesso' => $payload['nivel_acesso'],
    'nome'         => $payload['nome'],
];
