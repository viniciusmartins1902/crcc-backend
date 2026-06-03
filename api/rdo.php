<?php
/**
 * GET  /construtora/api/rdo.php?data=2026-04-17&obra_id=UUID
 *   → lista RDOs do dia / obra
 *
 * POST /construtora/api/rdo.php
 *   Body: {
 *     "obra_id":           "uuid",
 *     "os_id":             "uuid" | null,
 *     "data":              "2026-04-17",
 *     "descricao":         "Atividades realizadas hoje...",
 *     "horas_trabalhadas": 8,
 *     "efetivo":           5,
 *     "observacoes":       "Chuva no período da tarde."
 *   }
 *   → { "sucesso": true, "rdo": { id, ... } }
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

$method = $_SERVER['REQUEST_METHOD'];

// ── GET ───────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $data   = $_GET['data']    ?? date('Y-m-d');
    $obraId = (int) ($_GET['obra_id'] ?? 0);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        jsonErr('Formato de data inválido. Use YYYY-MM-DD.');
    }

    $params = [
        'tenant_id'     => 'eq.' . $currentUser['tenant_id'],
        'data'          => 'eq.' . $data,
        'select'        => 'id,obra_id,os_id,data,descricao,horas_trabalhadas,efetivo,observacoes,responsavel_id,criado_em',
        'order'         => 'criado_em.desc',
    ];

    // Técnico vê apenas seus próprios RDOs
    if ($currentUser['nivel_acesso'] >= 3) {
        $params['responsavel_id'] = 'eq.' . $currentUser['id'];
    }

    if ($obraId > 0) {
        $params['obra_id'] = 'eq.' . $obraId;
    }

    $supabase = new Supabase();
    $rdos = $supabase->request('GET', '/rest/v1/rdo', null, $params) ?: [];

    jsonOk([
        'data'  => $data,
        'total' => count($rdos),
        'rdos'  => $rdos,
    ]);
}

// ── POST ──────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = bodyJson();

    $obraId          = (int) ($body['obra_id'] ?? 0);
    $osId            = ($body['os_id'] ?? null) ? (int) $body['os_id'] : null;
    $data            = trim($body['data']              ?? date('Y-m-d'));
    $descricao       = trim($body['descricao']         ?? '');
    $horasTrabalhadas = (float) ($body['horas_trabalhadas'] ?? 0);
    $efetivo         = (int)   ($body['efetivo']        ?? 0);
    $observacoes     = trim($body['observacoes']        ?? '');

    if (!$obraId || $obraId <= 0) {
        jsonErr('obra_id é obrigatório e deve ser um número válido.');
    }
    if (!$descricao) {
        jsonErr('descricao é obrigatório.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
        jsonErr('Formato de data inválido. Use YYYY-MM-DD.');
    }

    $supabase = new Supabase();

    // Verifica que a obra pertence ao tenant
    $obra = $supabase->request('GET', '/rest/v1/obras', null, [
        'id'        => 'eq.' . $obraId,
        'tenant_id' => 'eq.' . $currentUser['tenant_id'],
        'select'    => 'id',
        'limit'     => '1',
    ]);

    if (empty($obra)) {
        jsonErr('Obra não encontrada.', 404);
    }

    $payload = [
        'tenant_id'        => $currentUser['tenant_id'],
        'obra_id'          => $obraId,
        'os_id'            => $osId,
        'data'             => $data,
        'responsavel_id'   => $currentUser['id'],
        'descricao'        => $descricao,
        'horas_trabalhadas' => $horasTrabalhadas,
        'efetivo'          => $efetivo,
        'observacoes'      => $observacoes ?: null,
    ];

    $res = $supabase->request('POST', '/rest/v1/rdo', $payload);

    if (empty($res[0]['id'])) {
        jsonErr('Erro ao salvar RDO. Verifique se a tabela "rdo" existe no banco.', 500);
    }

    jsonOk(['sucesso' => true, 'rdo' => $res[0]], 201);
}

jsonErr('Método não permitido.', 405);
