<?php
/**
 * GET /construtora/api/obras.php
 *   Params: ?status=em_andamento  (opcional — filtra por status)
 *           ?id=UUID              (opcional — retorna uma obra específica)
 *   Retorna: { "total": N, "obras": [...] }
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonErr('Método não permitido.', 405);
}

$id     = $_GET['id']     ?? null;
$status = $_GET['status'] ?? null;

$statusValidos = ['em_andamento', 'paralisada', 'concluida', 'cancelada'];

if ($status && !in_array($status, $statusValidos, true)) {
    jsonErr('Status inválido. Use: ' . implode(', ', $statusValidos));
}

$params = [
    'tenant_id' => 'eq.' . $currentUser['tenant_id'],
    'select'    => 'id,nome,descricao,data_inicio,data_fim_prevista,status,criado_em',
    'order'     => 'criado_em.desc',
];

if ($id) {
    $params['id'] = 'eq.' . $id;
}
if ($status) {
    $params['status'] = 'eq.' . $status;
}

$supabase = new Supabase();
$obras    = $supabase->request('GET', '/rest/v1/obras', null, $params) ?: [];

jsonOk(['total' => count($obras), 'obras' => $obras]);
