<?php
/**
 * Autenticação — CRCC (independente)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/supabase.php';
require_once __DIR__ . '/controle-acesso.php';

function verificarLogin(string $email, string $senha): bool {
    $supabase = new Supabase();
    $rows = $supabase->request('GET', '/rest/v1/users', null, [
        'email'     => 'eq.' . $email,
        'tenant_id' => 'eq.' . DEFAULT_TENANT_ID,
        'select'    => 'id,nome,email,senha,nivel_acesso,funcao,area,expira_em',
        'limit'     => '1',
    ]);

    if (empty($rows)) return false;
    $u = $rows[0];

    if (!password_verify($senha, $u['senha'] ?? '')) return false;
    if (($u['nivel_acesso'] ?? 99) > 4) return false;
    if (!empty($u['expira_em']) && strtotime($u['expira_em']) < time()) return false;

    $_SESSION['logado']       = true;
    $_SESSION['email']        = $email;
    $_SESSION['nome']         = $u['nome'];
    $_SESSION['nivel_acesso'] = (int) $u['nivel_acesso'];
    $_SESSION['funcao']       = $u['funcao'] ?? '';
    $_SESSION['area']         = $u['area']   ?? '';
    $_SESSION['user_id']      = $u['id'];

    return true;
}

