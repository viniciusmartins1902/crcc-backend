<?php
/**
 * Autenticação — CRCC
 * Sessão web para o painel administrativo.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/controle-acesso.php';
require_once __DIR__ . '/supabase.php';

function verificarLogin(string $email, string $senha): bool {
    $supabase = new Supabase();

    $rows = $supabase->request('GET', '/rest/v1/users', null, [
        'email'     => 'eq.' . $email,
        'tenant_id' => 'eq.' . DEFAULT_TENANT_ID,
        'select'    => 'id,nome,email,senha,nivel_acesso,funcao,area,expira_em',
        'limit'     => '1',
    ]);

    if (empty($rows) || !is_array($rows)) return false;

    $usuario = $rows[0];

    if (!password_verify($senha, $usuario['senha'] ?? '')) return false;

    // Bloqueia níveis acima de 4 (apenas 1-4 têm acesso à CRCC)
    if (($usuario['nivel_acesso'] ?? 99) > 4) return false;

    // Verifica expiração
    if (!empty($usuario['expira_em']) && strtotime($usuario['expira_em']) < time()) return false;

    $_SESSION['logado']       = true;
    $_SESSION['email']        = $email;
    $_SESSION['nome']         = $usuario['nome'];
    $_SESSION['nivel_acesso'] = $usuario['nivel_acesso'];
    $_SESSION['funcao']       = $usuario['funcao'];
    $_SESSION['area']         = $usuario['area'];
    $_SESSION['user_id']      = $usuario['id'];

    return true;
}

function logout(): void {
    session_destroy();
    header('Location: /web/login.php');
    exit;
}
