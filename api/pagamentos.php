<?php
/**
 * GET /construtora/api/pagamentos.php
 * 
 * API para buscar pagamentos com suporte a filtros.
 * 
 * Campos retornados:
 *   - id (chave primária)
 *   - id_user (referência para users)
 *   - descricao
 *   - valor
 *   - foto_url
 *   - aprovacao (status de aprovação)
 *   - created_at, updated_at (timestamps)
 * 
 * Params opcionais:
 *   ?id=UUID              — retorna um pagamento específico
 *   ?id_user=UUID         — filtra por id_user
 *   ?aprovacao=pendente   — filtra por status de aprovação
 *   ?limit=10             — limita resultados (padrão: 50)
 *   ?offset=0             — paginação
 * 
 * Retorna: { "total": N, "limit": L, "offset": O, "data": [...] }
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonErr('Método não permitido.', 405);
}

// ── Parâmetros da requisição ──────────────────────────────────────────────────
$id         = $_GET['id']         ?? null;
$id_user    = $_GET['id_user']    ?? null;
$aprovacao  = $_GET['aprovacao']  ?? null;
$limit      = (int)($_GET['limit'] ?? 50);
$offset     = (int)($_GET['offset'] ?? 0);

// Validação de limites
$limit  = min(max($limit, 1), 500);
$offset = max($offset, 0);

$aprovacaoValidos = ['pendente', 'Em análise', 'aprovado', 'rejeitado'];

if ($aprovacao && !in_array($aprovacao, $aprovacaoValidos, true)) {
    jsonErr('Aprovação inválida. Use: ' . implode(', ', $aprovacaoValidos), 400);
}

// ── Construir consulta Supabase ───────────────────────────────────────────────
$params = [
    'select' => 'id,id_user,descricao,valor,foto_url,aprovacao,created_at,updated_at',
    'order'  => 'created_at.desc',
    'limit'  => $limit,
    'offset' => $offset,
];

// Filtros
if ($id) {
    $params['id'] = 'eq.' . $id;
}

if ($id_user) {
    $params['id_user'] = 'eq.' . $id_user;
} else {
    // Se não especificar id_user, filtrar pelo usuário autenticado
    $params['id_user'] = 'eq.' . $currentUser['id'];
}

if ($aprovacao) {
    $params['aprovacao'] = 'eq.' . $aprovacao;
}

// ── Executar requisição ───────────────────────────────────────────────────────
$supabase = new Supabase();
$pagamentos = $supabase->request('GET', '/rest/v1/pagamentos', null, $params) ?: [];

// ── Resposta ──────────────────────────────────────────────────────────────────
jsonOk([
    'total'  => count($pagamentos),
    'limit'  => $limit,
    'offset' => $offset,
    'data'   => $pagamentos,
]);
