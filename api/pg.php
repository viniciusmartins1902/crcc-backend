<?php
/**
 * GET/POST /construtora/api/pg.php
 * 
 * API para gerenciar pagamentos
 * 
 * GET - Buscar pagamentos com suporte a filtros
 * Campos retornados:
 *   - id, user_id, descricao, valor, foto_url, status, aprovacao, data_criacao, data_atualizacao
 * 
 * Params opcionais:
 *   ?id=UUID                — retorna um registro específico
 *   ?user_id=UUID           — filtra por user_id
 *   ?status=pendente        — filtra por status (pendente|sincronizado)
 *   ?aprovacao=Em análise   — filtra por aprovação (Em análise|Aprovado|Reprovado)
 *   ?limit=10               — limita resultados (padrão: 50)
 *   ?offset=0               — paginação
 * 
 * POST - Salvar novo pagamento
 * Body:
 *   - descricao (string, obrigatório)
 *   - valor (decimal, obrigatório)
 *   - foto_base64 (string, obrigatório)
 */

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_auth.php';

error_log("=== pg.php LOADED === " . $_SERVER['REQUEST_METHOD']);

// ────────────────────────────────────────────────────────────────────────────
// GET - LISTAR PAGAMENTOS
// ────────────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $id         = $_GET['id']         ?? null;
    $limit      = (int)($_GET['limit'] ?? 50);
    $offset     = (int)($_GET['offset'] ?? 0);

    $limit  = min(max($limit, 1), 500);
    $offset = max($offset, 0);

    $params = [
        'select' => 'id,user_id,descricao,valor,foto_url,status,aprovacao,data_criacao,data_atualizacao',
        'order'  => 'data_criacao.desc',
        'limit'  => $limit,
        'offset' => $offset,
    ];

    if ($id) {
        $params['id'] = 'eq.' . $id;
    }
    
    // Filtrar por usuário autenticado
    $params['user_id'] = 'eq.' . $currentUser['id'];

    try {
        $supabase = new Supabase();
        $pg = $supabase->request('GET', '/rest/v1/pagamentos', null, $params) ?: [];

        jsonOk([
            'total'  => count($pg),
            'limit'  => $limit,
            'offset' => $offset,
            'data'   => $pg,
        ]);
    } catch (Throwable $e) {
        error_log("GET /pg.php Erro: " . $e->getMessage());
        jsonErr($e->getMessage(), 500);
    }

// ────────────────────────────────────────────────────────────────────────────
// POST - SALVAR NOVO PAGAMENTO
// ────────────────────────────────────────────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    error_log("=== POST /pg.php START ===");
    error_log("currentUser: " . json_encode($currentUser ?? null));
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        error_log("Data recebida: " . json_encode($data));

        $descricao = $data['descricao'] ?? null;
        $valor = $data['valor'] ?? null;
        $fotoBase64 = $data['foto_base64'] ?? null;

        if (!$descricao || !$valor || !$fotoBase64) {
            error_log("Campos faltando! descricao=" . $descricao . " valor=" . $valor . " foto=" . strlen($fotoBase64 ?? ''));
            jsonErr('Campos obrigatórios: descricao, valor, foto_base64', 400);
        }

        $valor = floatval($valor);
        if ($valor <= 0) {
            error_log("Valor inválido: " . $valor);
            jsonErr('Valor deve ser maior que zero', 400);
        }

        // Limpar base64
        $fotoBase64 = preg_replace('/^data:image\/\w+;base64,/', '', $fotoBase64);
        error_log("Foto base64 limpa, tamanho: " . strlen($fotoBase64));
        error_log("CurrentUser ID: " . ($currentUser['id'] ?? 'NÃO DEFINIDO'));

        if (!isset($currentUser['id'])) {
            error_log("Erro: currentUser não definido");
            jsonErr('Usuário não autenticado', 401);
        }

        // Usar Supabase para INSERT
        $supabase = new Supabase();
        $payload = [
            'user_id'  => $currentUser['id'],
            'descricao' => $descricao,
            'valor'    => $valor,
            'foto_url' => $fotoBase64,
            'aprovacao' => 'Em análise',
            'status'   => 'pendente',
        ];

        error_log("Enviando para Supabase: " . json_encode($payload));
        $resultado = $supabase->request('POST', '/rest/v1/pagamentos', $payload, [
            'select' => 'id',
        ]);
        error_log("Resultado Supabase: " . json_encode($resultado));

        if (!$resultado || !is_array($resultado) || !isset($resultado[0]['id'])) {
            error_log("Erro: resposta inválida de Supabase");
            jsonErr('Erro ao salvar pagamento', 500);
        }

        $id = $resultado[0]['id'];

        jsonOk([
            'id' => $id,
            'mensagem' => 'Pagamento salvo com sucesso'
        ]);

    } catch (Throwable $e) {
        error_log("Exception: " . $e->getMessage() . " - Arquivo: " . $e->getFile() . " - Linha: " . $e->getLine());
        error_log("Stack trace: " . $e->getTraceAsString());
        jsonErr('Erro: ' . $e->getMessage(), 500);
    }

// ────────────────────────────────────────────────────────────────────────────
// PATCH - ATUALIZAR APROVAÇÃO
// ────────────────────────────────────────────────────────────────────────────
} elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $id = $data['id'] ?? null;
        $aprovacao = $data['aprovacao'] ?? null;

        if (!$id || !$aprovacao) {
            jsonErr('Campos obrigatórios: id, aprovacao', 400);
        }

        $aprovacaoValidos = ['Em análise', 'Aprovado', 'Reprovado'];
        if (!in_array($aprovacao, $aprovacaoValidos, true)) {
            jsonErr('Aprovação inválida. Use: ' . implode(', ', $aprovacaoValidos), 400);
        }

        $supabase = new Supabase();
        $supabase->request('PATCH', '/rest/v1/pagamentos', [
            'aprovacao' => $aprovacao
        ], [
            'id' => 'eq.' . $id
        ]);

        jsonOk(['mensagem' => 'Aprovação atualizada com sucesso']);

    } catch (Throwable $e) {
        error_log("PATCH /pg.php Exception: " . $e->getMessage());
        jsonErr('Erro: ' . $e->getMessage(), 500);
    }

} else {
    jsonErr('Método não permitido.', 405);
}
?>
 