<?php
/**
 * GET   /construtora/api/os.php
 *   Params: ?data=2026-04-30  (opcional — filtra por data_prevista)
 *           ?obra_id=UUID      (opcional)
 *           ?todas=1           (admin/gestor: retorna de todos os responsáveis)
 *   Retorna: { "total": N, "ordens": [...] }
 *
 * PATCH /construtora/api/os.php
 *   Body: { "id": 12, "status": "concluida" }
 *   Status válidos: pendente | em_andamento | concluida | cancelada
 *   Retorna: { "sucesso": true, "os": { id, status } }
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $data   = $_GET['data']    ?? null;
    $obraId = $_GET['obra_id'] ?? null;
    $todas  = isset($_GET['todas']) && $currentUser['nivel_acesso'] <= 2;

    if ($data && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        jsonErr('Formato de data inválido. Use YYYY-MM-DD.');
    }

    $params = [
        'tenant_id' => 'eq.' . $currentUser['tenant_id'],
        'select' => 'id,obra_id,area,titulo,descricao,responsavel_id,data_prevista,status,prioridade,criado_em,obra:obras(id,nome)',
        'order'     => 'prioridade.desc,criado_em.asc',
    ];

    if (!$todas) {
        $params['responsavel_id'] = 'eq.' . $currentUser['id'];
    }
    if ($data) {
        $params['data_prevista'] = 'eq.' . $data;
    }
    if ($obraId) {
        $params['obra_id'] = 'eq.' . $obraId;
    }

    $supabase = new Supabase();
    $ordens   = $supabase->request('GET', '/rest/v1/ordens_servico', null, $params) ?: [];

    jsonOk(['total' => count($ordens), 'ordens' => $ordens]);
}

if ($method !== 'PATCH') {
    jsonErr('Método não permitido.', 405);
}

$body   = bodyJson();
$id     = (int) ($body['id'] ?? 0);
$status = trim($body['status'] ?? '');

$statusValidos = ['pendente', 'em_andamento', 'concluida', 'cancelada'];

if (!$id) {
    jsonErr('ID da OS é obrigatório.');
}
if (!in_array($status, $statusValidos, true)) {
    jsonErr('Status inválido. Use: ' . implode(', ', $statusValidos));
}

$supabase = new Supabase();

// Verifica se a OS pertence ao tenant e se o usuário tem acesso
$rows = $supabase->request('GET', '/rest/v1/ordens_servico', null, [
    'id'        => 'eq.' . $id,
    'tenant_id' => 'eq.' . $currentUser['tenant_id'],
    'select'    => 'id,responsavel_id,status',
    'limit'     => '1',
]) ?: [];

if (empty($rows)) {
    jsonErr('OS não encontrada.', 404);
}

$os = $rows[0];

// Técnico só pode atualizar a própria OS
if ($currentUser['nivel_acesso'] >= 3 && $os['responsavel_id'] !== $currentUser['id']) {
    jsonErr('Sem permissão para atualizar esta OS.', 403);
}

// Atualiza status
$supabase->request(
    'PATCH',
    '/rest/v1/ordens_servico?id=eq.' . $id . '&tenant_id=eq.' . $currentUser['tenant_id'],
    ['status' => $status, 'atualizado_em' => date('Y-m-d\TH:i:sP')]
);

jsonOk([
    'sucesso' => true,
    'os'      => ['id' => $id, 'status' => $status],
]);
