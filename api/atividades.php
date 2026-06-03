<?php
/**
 * GET /construtora/api/atividades.php
 * Params: ?data=2026-04-17  (padrão: hoje)
 *         ?obra_id=UUID      (opcional)
 *         ?todas=1           (admin/gestor: retorna de todos; padrão: só do responsável logado)
 *
 * Retorna OS com data_prevista <= data e status em aberto (pendente ou em_andamento).
 * Retorna: { "data": "...", "atividades": [...] }
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonErr('Método não permitido.', 405);
}

$data   = $_GET['data']    ?? date('Y-m-d');
$obraId = (int) ($_GET['obra_id'] ?? 0);
$todas  = isset($_GET['todas']) && $currentUser['nivel_acesso'] <= 2;

// Valida formato da data
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
    jsonErr('Formato de data inválido. Use YYYY-MM-DD.');
}

$supabase = new Supabase();

$params = [
    'tenant_id'    => 'eq.' . $currentUser['tenant_id'],
    'data_prevista' => 'lte.' . $data,
    'status'       => 'not.in.(concluida,cancelada)',
    'select'       => 'id,obra_id,area,titulo,descricao,responsavel_id,data_prevista,status,prioridade',
    'order'        => 'prioridade.desc,criado_em.asc',
];

// Técnicos/Encarregados veem só as OS atribuídas a eles
if (!$todas) {
    $params['responsavel_id'] = 'eq.' . $currentUser['id'];
}

if ($obraId > 0) {
    $params['obra_id'] = 'eq.' . $obraId;
}

$ordens = $supabase->request('GET', '/rest/v1/ordens_servico', null, $params) ?: [];

// Busca nomes das obras referenciadas
$obraIds = array_unique(array_column($ordens, 'obra_id'));
$obras   = [];
if (!empty($obraIds)) {
    $obraRows = $supabase->request('GET', '/rest/v1/obras', null, [
        'id'        => 'in.(' . implode(',', $obraIds) . ')',
        'tenant_id' => 'eq.' . $currentUser['tenant_id'],
        'select'    => 'id,nome',
    ]) ?: [];
    $obras = array_column($obraRows, 'nome', 'id');
}

// Enriquece com nome da obra
foreach ($ordens as &$os) {
    $os['obra_nome'] = $obras[$os['obra_id']] ?? null;
    unset($os['responsavel_id']); // não expõe IDs desnecessários
}
unset($os);

jsonOk([
    'data'       => $data,
    'total'      => count($ordens),
    'atividades' => $ordens,
]);
