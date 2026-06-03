<?php
/**
 * Wrapper para API REST do Supabase
 */

require_once 'config.php';

class Supabase {
        /**
         * Estatísticas de solicitações de intervenção
         * - Por mês (criadas)
         * - Finalizadas
         * - Em andamento (status pendente ou aprovado_seguranca)
         */
        public function getSolicitacoesStats() {
            $tenantId = $this->getTenantId();
            $endpoint = '/rest/v1/intervention_requests';
            $params = [
                'select' => 'id,criado_em,status',
                'limit' => 5000
            ];
            if ($tenantId) {
                $params['tenant_id'] = 'eq.' . $tenantId;
            }
            $solicitacoes = $this->request('GET', $endpoint, null, $params);
            $porMes = [];
            $finalizadas = 0;
            $emAndamento = 0;
            $pendentes = 0;
            if (is_array($solicitacoes)) {
                foreach ($solicitacoes as $sol) {
                    $mes = isset($sol['criado_em']) ? substr($sol['criado_em'], 0, 7) : 'Desconhecido';
                    $porMes[$mes] = ($porMes[$mes] ?? 0) + 1;
                    if (($sol['status'] ?? '') === 'finalizado') {
                        $finalizadas++;
                    }
                    if (($sol['status'] ?? '') === 'pendente') {
                        $pendentes++;
                    }
                    if (in_array(($sol['status'] ?? ''), ['pendente', 'aprovado_seguranca', 'aprovado_om'], true)) {
                        $emAndamento++;
                    }
                }
            }
            ksort($porMes);
            return [
                'por_mes' => $porMes,
                'finalizadas' => $finalizadas,
                'em_andamento' => $emAndamento,
                'pendentes' => $pendentes
            ];
        }
    private $url;
    private $key;
    
    public function __construct() {
        $this->url = SUPABASE_URL;
        $this->key = SUPABASE_ANON_KEY;
    }

    /**
     * Retorna o tenant_id da sessão atual.
     * Em contexto CLI (cron/script), usa DEFAULT_TENANT_ID como fallback.
     * NUNCA retornar dados sem este filtro em tabelas multi-tenant.
     */
    public function getTenantId(): ?string {
        // Sessão ativa (contexto web)
        if (isset($_SESSION['__tenant']['id'])) {
            return $_SESSION['__tenant']['id'];
        }
        // Contexto CLI (cron job, script manual)
        if (php_sapi_name() === 'cli' && defined('DEFAULT_TENANT_ID')) {
            return DEFAULT_TENANT_ID;
        }
        return null;
    }
    
    /**
     * Busca todas as inspeções
     */
    public function getInspections($filters = []) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return [];

        $endpoint = '/rest/v1/inspections';
        $params = ['select' => '*', 'order' => 'data_criacao.desc', 'limit' => 1000,
                   'tenant_id' => 'eq.' . $tenantId];
        
        // Aplica filtros
        if (!empty($filters['data_inicio'])) {
            $params['data_inicio'] = 'gte.' . $filters['data_inicio'];
        }
        if (!empty($filters['data_final'])) {
            $params['data_final'] = 'lte.' . $filters['data_final'];
        }
        if (!empty($filters['campo'])) {
            $params['campo'] = 'eq.' . $filters['campo'];
        }
        if (!empty($filters['tecnico'])) {
            $params['or'] = "(tecnico1.eq.{$filters['tecnico']},tecnico2.eq.{$filters['tecnico']})";
        }
        
        return $this->request('GET', $endpoint, null, $params);
    }
    
    /**
     * Busca uma inspeção específica
     */
    public function getInspection($id) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return null;
        $endpoint = "/rest/v1/inspections?id=eq.{$id}&tenant_id=eq.{$tenantId}&select=*";
        $result = $this->request('GET', $endpoint);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Busca fotos de uma inspeção
     */
    public function getPhotos($inspection_id) {
        $endpoint = "/rest/v1/inspection_photos?inspection_id=eq.{$inspection_id}&select=*&order=created_at.desc";
        return $this->request('GET', $endpoint);
    }
    
    /**
     * Estatísticas gerais
     */
    public function getStats() {
        $inspections = $this->getInspections();
        
        // Verifica se retornou dados válidos
        if ($inspections === null || !is_array($inspections)) {
            return [
                'total' => 0,
                'hoje' => 0,
                'por_campo' => [],
                'por_tecnico' => [],
                'por_dia' => [],
                'erro' => 'Não foi possível conectar ao banco de dados'
            ];
        }
        
        $stats = [
            'total' => count($inspections),
            'hoje' => 0,
            'por_campo' => [],
            'por_tecnico' => [],
            'por_dia' => []
        ];
        
        $hoje = date('Y-m-d');
        
        foreach ($inspections as $insp) {
            // Conta hoje
            if (strpos($insp['data_criacao'], $hoje) === 0) {
                $stats['hoje']++;
            }
            
            // Agrupa por campo
            $campo = $insp['campo'] ?? 'Não informado';
            $stats['por_campo'][$campo] = ($stats['por_campo'][$campo] ?? 0) + 1;
            
            // Agrupa por técnico (apenas técnico1) - normalizado
            if (!empty($insp['tecnico1'])) {
                // Normaliza o nome: trim, remove espaços duplos e padroniza capitalização
                $tecnico = trim($insp['tecnico1']);
                $tecnico = preg_replace('/\s+/', ' ', $tecnico); // Remove espaços múltiplos
                $stats['por_tecnico'][$tecnico] = ($stats['por_tecnico'][$tecnico] ?? 0) + 1;
            }
            
            // Agrupa por dia (últimos 7 dias)
            $dia = substr($insp['data_criacao'], 0, 10);
            $stats['por_dia'][$dia] = ($stats['por_dia'][$dia] ?? 0) + 1;
        }
        
        return $stats;
    }
    
    /**
     * Atualizar uma inspeção
     */
    public function updateInspection($id, $data) {
        $endpoint = "/rest/v1/inspections?id=eq.{$id}";
        $result = $this->request('PATCH', $endpoint, $data);
        return !empty($result);
    }
    
    /**
     * Excluir uma inspeção (e suas fotos via CASCADE)
     */
    public function deleteInspection($id) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return false;
        // Inclui tenant_id no filtro do DELETE para impedir exclusão cross-tenant (IDOR)
        $endpoint = "/rest/v1/inspections?id=eq.{$id}&tenant_id=eq.{$tenantId}";
        $this->request('DELETE', $endpoint);
        return true;
    }
    
    /**
     * Requisição HTTP para API do Supabase (exposta como public para debug)
     */
    public function request($method, $endpoint, $data = null, $params = [], $extraHeaders = []) {
        // Verificar se cURL está disponível
        if (!function_exists('curl_init')) {
            error_log('ERRO CRÍTICO: cURL não está instalado ou habilitado!');
            return [];
        }
        
        $url = $this->url . $endpoint;
        
        if (!empty($params)) {
            // Constrói a query string manualmente para suportar múltiplos valores no mesmo parâmetro
            $queryParts = [];
            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    // Se é array, adiciona cada item como um parâmetro separado com o mesmo nome
                    foreach ($value as $v) {
                        $queryParts[] = urlencode($key) . '=' . urlencode($v);
                    }
                } else {
                    $queryParts[] = urlencode($key) . '=' . urlencode($value);
                }
            }
            $queryString = implode('&', $queryParts);
            $url .= (strpos($url, '?') === false ? '?' : '&') . $queryString;
        }
        
        // Log em arquivo ao invés de stdout
        error_log("Supabase Request: $method $url");
        
        // Range e Prefer variam por método:
        // GET  → Range: 0-9999 para paginar até 10k; return=representation p/ receber os dados
        // POST → return=representation p/ receber os registros inseridos
        // PATCH/DELETE → return=minimal (sem body de retorno) e SEM Range (PostgREST rejeita)
        $isWrite  = in_array($method, ['PATCH', 'PUT', 'DELETE'], true);
        $prefer   = $isWrite ? 'return=minimal' : 'return=representation';

        $headers = [
            'apikey: '       . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json',
            'Prefer: '       . $prefer,
        ];

        // Range apenas para leituras — em escritas PostgREST retorna 400
        if (!$isWrite) {
            $headers[] = 'Range: 0-9999';
        }

        if (!empty($extraHeaders)) {
            foreach ($extraHeaders as $extra) {
                $name = strtolower(explode(':', $extra)[0]);
                $headers = array_filter($headers, fn($h) => strtolower(explode(':', $h)[0]) !== $name);
                $headers[] = $extra;
            }
            $headers = array_values($headers);
        }
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log de debug
        if ($httpCode < 200 || $httpCode >= 300) {
            error_log("Supabase API Error - Code: $httpCode, URL: $url, Error: $error, Response: $response");
            
            // Tenta retornar resultado decodificado mesmo em erro para debug
            $decoded = json_decode($response, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $result = json_decode($response, true);
            if (is_array($result)) {
                error_log("Supabase Success: Retrieved " . count($result) . " items");
                return $result;
            } else {
                error_log("Supabase Warning: Response is not an array");
                return [];
            }
        }

        // Se falhou, retorna array vazio
        error_log("Supabase Failed: HTTP $httpCode, Response: $response");
        return [];
    }
    
    /**
     * Usuários
     */
    public function getUsuarios(bool $todosTenants = false) {
        $tenantId = $this->getTenantId();
        $filter = (!$todosTenants && $tenantId) ? "&tenant_id=eq.{$tenantId}" : '';
        $endpoint = "/rest/v1/users?select=*&order=created_at.desc&limit=1000{$filter}";
        return $this->request('GET', $endpoint);
    }
    
    /**
     * Busca usuário por e-mail.
     *
     * @param string      $email    E-mail do usuário
     * @param string|null $tenantId UUID do tenant — quando fornecido, garante isolamento
     *                              entre clientes (mesmo e-mail em tenants diferentes).
     *                              Omitir apenas para autenticação do painel admin.
     */
    public function getUsuarioPorEmail(string $email, ?string $tenantId = null): ?array
    {
        $params = [
            'email'  => 'eq.' . $email,
            'select' => '*',
            'limit'  => '1',
        ];
        if ($tenantId !== null) {
            $params['tenant_id'] = 'eq.' . $tenantId;
        }
        $result = $this->request('GET', '/rest/v1/users', null, $params);
        return $result ? $result[0] : null;
    }
    
    public function cadastrarUsuario($nome, $email, $senha, $funcao = 'Usuário', $nivel_acesso = 4, $expira_em = null) {
        $endpoint = "/rest/v1/users";
        $data = [
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT),
            'funcao' => $funcao,
            'nivel_acesso' => intval($nivel_acesso)
        ];
        if ($expira_em !== null) {
            $data['expira_em'] = $expira_em; // formato: 'YYYY-MM-DD'
        }
        return $this->request('POST', $endpoint, $data);
    }
    
    public function atualizarUsuario($id, $nome, $email, $funcao = null) {
        $endpoint = "/rest/v1/users?id=eq." . intval($id);
        $data = [
            'nome' => $nome,
            'email' => $email
        ];
        if ($funcao !== null) {
            $data['funcao'] = $funcao;
        }
        return $this->request('PATCH', $endpoint, $data);
    }
    
    public function atualizarFotoUsuario($id, $foto_path) {
        $endpoint = "/rest/v1/users?id=eq." . intval($id);
        $data = ['foto' => $foto_path];
        return $this->request('PATCH', $endpoint, $data);
    }
    
    public function alterarSenha($id, $senha_atual, $senha_nova) {
        $usuario = $this->request('GET', "/rest/v1/users?id=eq." . intval($id) . "&select=*");
        if ($usuario && password_verify($senha_atual, $usuario[0]['senha'])) {
            $endpoint = "/rest/v1/users?id=eq." . intval($id);
            $data = ['senha' => password_hash($senha_nova, PASSWORD_DEFAULT)];
            return $this->request('PATCH', $endpoint, $data);
        }
        return false;
    }
    
    public function excluirUsuario($id) {
        $endpoint = "/rest/v1/users?id=eq." . intval($id);
        return $this->request('DELETE', $endpoint);
    }
    
    /**
     * === MÉTODOS PARA DADOS DE MEDIÇÃO WAY2 ===
     */
    
    /**
     * Salva um registro de medição no banco
     */
    public function salvarDadoMedicao($dados) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return false;
        $endpoint = '/rest/v1/dados_medicao';
        $registro = [
            'tenant_id' => $tenantId,
            'ponto_id' => $dados['ponto_id'] ?? '',
            'ponto_nome' => $dados['ponto_nome'] ?? null,
            'grandeza' => $dados['grandeza'] ?? null,
            'valor' => $dados['valor'] ?? null,
            'unidade' => $dados['unidade'] ?? null,
            'timestamp_medicao' => $dados['timestamp_medicao'] ?? date('Y-m-d H:i:s')
        ];
        return $this->request('POST', $endpoint, $registro);
    }
    
    /**
     * Salva múltiplos dados de medição em lote (OTIMIZADO)
     * Muito mais rápido que salvar um por um
     * Divide em lotes de 500 para evitar limites da API
     */
    public function salvarDadosMedicaoLote($arrayDados, ?string $tenantIdOverride = null) {
        $tenantId = $tenantIdOverride ?? $this->getTenantId();
        if (!$tenantId) return ['sucesso' => false, 'mensagem' => 'tenant_id não definido'];

        if (empty($arrayDados)) {
            return ['sucesso' => false, 'mensagem' => 'Array vazio'];
        }
        
        $endpoint = '/rest/v1/dados_medicao';
        
        // Prepara os dados para inserção em lote
        $registros = [];
        foreach ($arrayDados as $dados) {
            $registros[] = [
                'tenant_id' => $tenantId,
                'ponto_id' => $dados['ponto_id'] ?? '',
                'ponto_nome' => $dados['ponto_nome'] ?? null,
                'grandeza' => $dados['grandeza'] ?? null,
                'valor' => $dados['valor'] ?? null,
                'unidade' => $dados['unidade'] ?? null,
                'timestamp_medicao' => $dados['timestamp_medicao'] ?? date('Y-m-d H:i:s')
            ];
        }
        
        // DIVIDE em lotes de 500 registros para evitar limite de 1000 do Supabase
        $tamanhoLote = 500;
        $totalRegistros = count($registros);
        $totalInserido = 0;
        $lotes = ceil($totalRegistros / $tamanhoLote);
        
        for ($i = 0; $i < $lotes; $i++) {
            $offset = $i * $tamanhoLote;
            $lote = array_slice($registros, $offset, $tamanhoLote);
            
            // Insere o lote
            $resultado = $this->request('POST', $endpoint, $lote);
            
            if ($resultado !== false) {
                $totalInserido += count($lote);
                error_log("Lote " . ($i + 1) . "/$lotes inserido: " . count($lote) . " registros");
            } else {
                error_log("ERRO ao inserir lote " . ($i + 1) . "/$lotes");
                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao salvar lote ' . ($i + 1) . ' de ' . $lotes,
                    'total_inserido' => $totalInserido
                ];
            }
        }
        
        return [
            'sucesso' => true,
            'total_inserido' => $totalInserido,
            'mensagem' => $totalInserido . ' registros salvos com sucesso em ' . $lotes . ' lote(s)'
        ];
    }
    
    /**
     * Upsert de dados de medição em lote — evita duplicatas via ON CONFLICT.
     * Requer unique constraint em (tenant_id, ponto_id, timestamp_medicao) no banco.
     */
    public function upsertDadosMedicaoLote(array $arrayDados, ?string $tenantIdOverride = null): array {
        $tenantId = $tenantIdOverride ?? $this->getTenantId();
        if (!$tenantId) return ['sucesso' => false, 'mensagem' => 'tenant_id não definido'];
        if (empty($arrayDados)) return ['sucesso' => false, 'mensagem' => 'Array vazio'];

        $endpoint = '/rest/v1/dados_medicao';

        $registros = [];
        foreach ($arrayDados as $dados) {
            $registros[] = [
                'tenant_id'          => $tenantId,
                'ponto_id'           => $dados['ponto_id'] ?? '',
                'ponto_nome'         => $dados['ponto_nome'] ?? null,
                'grandeza'           => $dados['grandeza'] ?? null,
                'valor'              => $dados['valor'] ?? null,
                'unidade'            => $dados['unidade'] ?? null,
                'timestamp_medicao'  => $dados['timestamp_medicao'] ?? date('Y-m-d H:i:s'),
            ];
        }

        $tamanhoLote   = 500;
        $totalRegistros = count($registros);
        $totalInserido  = 0;
        $lotes          = ceil($totalRegistros / $tamanhoLote);

        for ($i = 0; $i < $lotes; $i++) {
            $lote      = array_slice($registros, $i * $tamanhoLote, $tamanhoLote);
            $resultado = $this->request('POST', $endpoint, $lote, [], [
                'Prefer: resolution=merge-duplicates,return=representation',
            ]);

            if ($resultado !== false) {
                $totalInserido += count($lote);
            } else {
                return [
                    'sucesso'        => false,
                    'mensagem'       => 'Erro ao upsert lote ' . ($i + 1) . ' de ' . $lotes,
                    'total_inserido' => $totalInserido,
                ];
            }
        }

        return [
            'sucesso'        => true,
            'total_inserido' => $totalInserido,
            'mensagem'       => $totalInserido . ' registros processados em ' . $lotes . ' lote(s)',
        ];
    }

    /**
     * Busca dados de medição com filtros
     */
    public function buscarDadosMedicao($filtros = []) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return [];
        $endpoint = '/rest/v1/dados_medicao';
        $params = ['select' => '*', 'order' => 'timestamp_medicao.desc',
                   'tenant_id' => 'eq.' . $tenantId];
        
        if (isset($filtros['ponto_id'])) {
            $params['ponto_id'] = 'eq.' . $filtros['ponto_id'];
        }
        if (isset($filtros['grandeza'])) {
            $params['grandeza'] = 'eq.' . $filtros['grandeza'];
        }
        if (isset($filtros['data_inicio'])) {
            $params['timestamp_medicao'] = 'gte.' . $filtros['data_inicio'];
        }
        if (isset($filtros['data_fim'])) {
            $params['timestamp_medicao'] = 'lte.' . $filtros['data_fim'];
        }
        if (isset($filtros['limit'])) {
            $params['limit'] = intval($filtros['limit']);
        }
        
        return $this->request('GET', $endpoint, null, $params);
    }
    
    /**
     * Estatísticas de dados de medição
     */
    public function getEstatisticasMedicao() {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return ['total_registros' => 0, 'pontos' => [], 'ultima_atualizacao' => null, 'media_valores' => 0];
        $endpoint = '/rest/v1/dados_medicao';
        $params = ['select' => 'ponto_id,ponto_nome,grandeza,valor,timestamp_medicao',
                   'limit' => 5000, 'order' => 'timestamp_medicao.desc',
                   'tenant_id' => 'eq.' . $tenantId];
        $dados = $this->request('GET', $endpoint, null, $params);
        
        if (!is_array($dados)) {
            return [
                'total_registros' => 0,
                'pontos' => [],
                'ultima_atualizacao' => null,
                'media_valores' => 0
            ];
        }
        
        $pontos = [];
        $somaValores = 0;
        $ultimaAtualizacao = null;
        
        foreach ($dados as $item) {
            $pontoId = $item['ponto_id'] ?? 'desconhecido';
            if (!isset($pontos[$pontoId])) {
                $pontos[$pontoId] = [
                    'nome' => $item['ponto_nome'] ?? $pontoId,
                    'total' => 0,
                    'soma' => 0
                ];
            }
            $pontos[$pontoId]['total']++;
            $pontos[$pontoId]['soma'] += floatval($item['valor'] ?? 0);
            $somaValores += floatval($item['valor'] ?? 0);
            
            $timestamp = $item['timestamp_medicao'] ?? null;
            if ($timestamp && (!$ultimaAtualizacao || $timestamp > $ultimaAtualizacao)) {
                $ultimaAtualizacao = $timestamp;
            }
        }
        
        // Calcula médias por ponto
        foreach ($pontos as $id => &$ponto) {
            $ponto['media'] = $ponto['total'] > 0 ? $ponto['soma'] / $ponto['total'] : 0;
        }
        
        return [
            'total_registros' => count($dados),
            'pontos' => $pontos,
            'ultima_atualizacao' => $ultimaAtualizacao,
            'media_valores' => count($dados) > 0 ? $somaValores / count($dados) : 0
        ];
    }

    /**
     * Busca dados agregados a cada 30 minutos (média dos 6 registros de 5 minutos)
     */
    public function buscarDadosAgregados30min($pontoId = null, $dataInicio = null, $dataFim = null) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return [];

        $endpoint = '/rest/v1/dados_medicao';
        
        // Busca TODOS os dados paginando se necessário
        $todosDados = [];
        $offset = 0;
        $limite = 1000;
        
        while (true) {
            $params = [
                'select' => 'ponto_id,ponto_nome,valor,timestamp_medicao',
                'order' => 'timestamp_medicao.asc',
                'limit' => $limite,
                'offset' => $offset,
                'tenant_id' => 'eq.' . $tenantId
            ];
            
            if ($pontoId) {
                $params['ponto_id'] = 'eq.' . $pontoId;
            }
            
            // Filtro de período
            // PostgREST utiliza operadores: gte (greater than or equal), lte (less than or equal)
            // Para múltiplos filtros no mesmo campo: campo1=op1.valor1&campo1=op2.valor2
            if ($dataInicio && $dataFim) {
                // Cria duas chaves para o mesmo parâmetro (será codificado como array)
                $params['timestamp_medicao'] = [
                    'gte.' . $dataInicio,
                    'lte.' . $dataFim
                ];
            } elseif ($dataInicio) {
                $params['timestamp_medicao'] = 'gte.' . $dataInicio;
            } elseif ($dataFim) {
                $params['timestamp_medicao'] = 'lte.' . $dataFim;
            }
            
            $dadosPagina = $this->request('GET', $endpoint, null, $params);
            
            if (!is_array($dadosPagina) || empty($dadosPagina)) {
                break;
            }
            
            $todosDados = array_merge($todosDados, $dadosPagina);
            
            // Se retornou menos que o limite, acabou
            if (count($dadosPagina) < $limite) {
                break;
            }
            
            $offset += $limite;
            
            // Segurança: máximo 5 páginas (5000 registros)
            if ($offset >= 5000) {
                break;
            }
        }
        
        $dados = $todosDados;
        
        if (!is_array($dados) || empty($dados)) {
            return [];
        }

        // Filtra manualmente por horário (06:00-18:00) se não conseguir via API
        $dadosFiltrados = [];
        foreach ($dados as $registro) {
            $timestamp = $registro['timestamp_medicao'];
            // Garante que o timestamp está no formato correto
            $timestamp_unix = strtotime($timestamp);
            if ($timestamp_unix === false) {
                continue; // Pula timestamps inválidos
            }
            $hora = intval(date('H', $timestamp_unix));
            $minutos = intval(date('i', $timestamp_unix));
            
            // Apenas entre 6h e 18h (período solar)
            // 06:00:00 até 17:59:59
            if ($hora >= 6 && $hora < 18) {
                $dadosFiltrados[] = $registro;
            }
        }

        // Agrupa por ponto e depois por período de 30 minutos
        $agregados = [];
        
        foreach ($dadosFiltrados as $registro) {
            $pontoId = $registro['ponto_id'];
            $valor = floatval($registro['valor'] ?? 0);
            $timestamp = $registro['timestamp_medicao'];

            // Cria chave para grupo de 30 minutos
            // Pega a hora e os minutos, arredonda para 0 ou 30
            $dt = new DateTime($timestamp);
            $minutos = intval($dt->format('i'));
            $grupoMinutos = ($minutos < 30) ? 0 : 30;
            $dt->setTime($dt->format('H'), $grupoMinutos, 0);
            $chaveTempo = $dt->format('Y-m-d H:i');

            if (!isset($agregados[$pontoId])) {
                $agregados[$pontoId] = [];
            }

            if (!isset($agregados[$pontoId][$chaveTempo])) {
                $agregados[$pontoId][$chaveTempo] = [
                    'soma' => 0,
                    'quantidade' => 0,
                    'timestamp_inicio' => $chaveTempo,
                    'ponto_id' => $pontoId,
                    'ponto_nome' => $registro['ponto_nome']
                ];
            }

            $agregados[$pontoId][$chaveTempo]['soma'] += $valor;
            $agregados[$pontoId][$chaveTempo]['quantidade']++;
        }

        // Calcula as médias
        $resultado = [];
        foreach ($agregados as $pontoId => $periodos) {
            foreach ($periodos as $chaveTempo => $grupo) {
                $resultado[] = [
                    'ponto_id' => $grupo['ponto_id'],
                    'ponto_nome' => $grupo['ponto_nome'],
                    'timestamp_30min' => $grupo['timestamp_inicio'],
                    'media_valor' => round($grupo['soma'] / $grupo['quantidade'], 4),
                    'soma_valores' => round($grupo['soma'], 4),
                    'quantidade_amostras' => $grupo['quantidade']
                ];
            }
        }

        // Ordena por timestamp
        usort($resultado, function($a, $b) {
            return strcmp($a['timestamp_30min'], $b['timestamp_30min']);
        });

        return $resultado;
    }

    /**
     * Busca dados para gráfico por ponto (período solar 06:00-18:00)
     * @param int $pontoId ID do ponto
     * @param string $dataInicio Data/hora inicial (ex: '2026-02-28 06:00:00')
     * @param string $dataFim Data/hora final (ex: '2026-02-28 18:00:00')
     * @return array Labels e dados agregados a cada 30 minutos
     */
    public function buscarGraficoPonto($pontoId, $dataInicio = null, $dataFim = null) {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return ['labels' => [], 'dados' => [], 'pontoId' => $pontoId];

        // Fallback: Se não fornecido, usa últimas 48 horas (raramente usado)
        if (!$dataInicio || !$dataFim) {
            $dataFim = date('Y-m-d H:i:s');
            $dataInicio = date('Y-m-d H:i:s', strtotime('-48 hours'));
        }

        $endpoint = '/rest/v1/dados_medicao';
        $params = [
            'select' => 'valor,timestamp_medicao',
            'ponto_id' => 'eq.' . $pontoId,
            'timestamp_medicao' => 'gte.' . $dataInicio,
            'order' => 'timestamp_medicao.asc',
            'limit' => '500',
            'tenant_id' => 'eq.' . $tenantId
        ];

        $dados = $this->request('GET', $endpoint, null, $params);

        if (!is_array($dados) || empty($dados)) {
            return [
                'labels' => [],
                'dados' => [],
                'pontoId' => $pontoId
            ];
        }

        // Filtra manualmente por período (06:00-18:00) e data fim
        $dadosFiltrados = [];
        $dataFimUnix = $dataFim ? strtotime($dataFim) : null;
        
        foreach ($dados as $item) {
            $timestamp = $item['timestamp_medicao'];
            // Garante que o timestamp está no formato correto
            $timestamp_unix = strtotime($timestamp);
            if ($timestamp_unix === false) {
                continue; // Pula timestamps inválidos
            }
            $hora = intval(date('H', $timestamp_unix));
            
            // Período solar: 6h às 18h (06:00:00 até 17:59:59)
            if ($hora >= 6 && $hora < 18) {
                // Se tem data fim, verifica usando Unix timestamp
                if ($dataFimUnix && $timestamp_unix <= $dataFimUnix) {
                    $dadosFiltrados[] = $item;
                } elseif (!$dataFimUnix) {
                    $dadosFiltrados[] = $item;
                }
            }
        }

        if (empty($dadosFiltrados)) {
            return [
                'labels' => [],
                'dados' => [],
                'pontoId' => $pontoId
            ];
        }

        // Agrupa por 30 minutos manualmente
        $grupos = [];
        foreach ($dadosFiltrados as $item) {
            $valor = floatval($item['valor'] ?? 0);
            $timestamp = $item['timestamp_medicao'];

            $dt = new DateTime($timestamp);
            $minutos = intval($dt->format('i'));
            $grupoMinutos = ($minutos < 30) ? 0 : 30;
            $dt->setTime($dt->format('H'), $grupoMinutos, 0);
            $chave = $dt->format('Y-m-d H:i');

            if (!isset($grupos[$chave])) {
                $grupos[$chave] = ['soma' => 0, 'qtd' => 0];
            }
            $grupos[$chave]['soma'] += $valor;
            $grupos[$chave]['qtd']++;
        }

        // Gera TODOS os labels de 30 em 30 minutos das 06:00 às 17:30
        $dataBase = substr($dataInicio, 0, 10);
        $todosLabels = [];
        for ($hora = 6; $hora < 18; $hora++) {
            $todosLabels[] = $dataBase . ' ' . sprintf('%02d:00', $hora);
            $todosLabels[] = $dataBase . ' ' . sprintf('%02d:30', $hora);
        }
        array_pop($todosLabels); // Remove 18:00

        // Preenche valores para todos os labels (null se não houver dados)
        $labels = [];
        $valores = [];
        foreach ($todosLabels as $label) {
            $labels[] = $label;
            if (isset($grupos[$label])) {
                $valores[] = round($grupos[$label]['soma'] / $grupos[$label]['qtd'], 2);
            } else {
                $valores[] = null; // Sem dados para este período
            }
        }

        return [
            'labels' => $labels,
            'dados' => $valores,
            'pontoId' => $pontoId
        ];
    }

    /**
     * Salvar restrições ONS no banco de dados
     */
    public function salvarRestricoesONS(array $restricoes): array
    {
        $resultado = [
            'sucesso' => 0,
            'falhas' => 0,
            'erros' => []
        ];

        foreach ($restricoes as $restricao) {
            try {
                // Preparar dados
                $dados = [
                    'data' => $restricao['data'] ?? null,
                    'hora_inicial' => $restricao['hora_inicial'] ?? '00:00',
                    'hora_final' => $restricao['hora_final'] ?? '00:00',
                    'valor_restricao_mwmed' => floatval($restricao['valor_restricao_mwmed'] ?? 0),
                    'duracao_minutos' => intval($restricao['duracao_minutos'] ?? $restricao['duracao_min'] ?? 0),
                    'energia_limitada_mwh' => floatval($restricao['energia_limitada_mwh'] ?? 0),
                    'razao' => $restricao['razao'] ?? null,
                    'origem' => $restricao['origem'] ?? null,
                    'motivo' => $restricao['motivo'] ?? null,
                    'usina' => 'MAURITI'
                ];

                // Tentar inserir ou atualizar
                $resultado_req = $this->request('POST', '/rest/v1/ons_restricoes', $dados);
                
                if ($resultado_req && !isset($resultado_req['code'])) {
                    $resultado['sucesso']++;
                } else {
                    $resultado['falhas']++;
                    if (isset($resultado_req['message'])) {
                        $resultado['erros'][] = $resultado_req['message'];
                    }
                }
            } catch (Exception $e) {
                $resultado['falhas']++;
                $resultado['erros'][] = $e->getMessage();
            }
        }

        return $resultado;
    }

    /**
     * Buscar restrições ONS do banco de dados
     */
    public function obterRestricoesONS(string $dataInicio = null, string $dataFim = null, int $limite = 1000): array
    {
        $tenantId = $this->getTenantId();
        if (!$tenantId) return [];

        // Construir a URL com parâmetros para evitar problemas de encoding
        $url = '/rest/v1/ons_restricoes?select=*&limit=' . intval($limite) . '&order=data.desc,hora_inicial.desc&tenant_id=eq.' . urlencode($tenantId);
        
        if ($dataInicio && $dataFim) {
            // Filtro com AND implícito (ambas as condições devem ser verdadeiras)
            $url .= '&data=gte.' . urlencode($dataInicio) . '&data=lte.' . urlencode($dataFim);
        } elseif ($dataInicio) {
            $url .= '&data=gte.' . urlencode($dataInicio);
        } elseif ($dataFim) {
            $url .= '&data=lte.' . urlencode($dataFim);
        }

        // Fazer requisição direta sem usar params (que codifica demais)
        if (!function_exists('curl_init')) {
            error_log('ERRO: cURL não disponível');
            return [];
        }

        $fullUrl = $this->url . $url;
        error_log("Supabase Request: GET $fullUrl");

        $headers = [
            'apikey: ' . $this->key,
            'Authorization: Bearer ' . $this->key,
            'Content-Type: application/json'
        ];

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Supabase Error: HTTP $httpCode - $response");
            return [];
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Obter resumo de restrições por período
     */
    public function obterResumoRestricoesONS(string $dataInicio, string $dataFim): array
    {
        $restricoes = $this->obterRestricoesONS($dataInicio, $dataFim, 10000);

        $resumo = [
            'total_registros' => count($restricoes),
            'total_restricoes' => 0,
            'total_energia_mwh' => 0.0,
            'restricoes_por_dia' => [],
            'motivos' => [],
            'valor_medio_mwmed' => 0.0,
            'energia_media_mwh' => 0.0
        ];

        if (empty($restricoes)) {
            return $resumo;
        }

        $soma_valor = 0;
        $soma_energia = 0;

        foreach ($restricoes as $r) {
            $resumo['total_restricoes']++;
            
            $valor = floatval($r['valor_restricao_mwmed'] ?? 0);
            $energia = floatval($r['energia_limitada_mwh'] ?? 0);
            
            $resumo['total_energia_mwh'] += $energia;
            $soma_valor += $valor;
            $soma_energia += $energia;

            // Por dia
            $dia = $r['data'] ?? '';
            if ($dia) {
                $resumo['restricoes_por_dia'][$dia] = ($resumo['restricoes_por_dia'][$dia] ?? 0) + 1;
            }

            // Motivos
            $motivo = $r['motivo'] ?? 'Sem detalhes';
            if ($motivo) {
                $resumo['motivos'][$motivo] = ($resumo['motivos'][$motivo] ?? 0) + 1;
            }
        }

        // Médias
        if ($resumo['total_restricoes'] > 0) {
            $resumo['valor_medio_mwmed'] = $soma_valor / $resumo['total_restricoes'];
            $resumo['energia_media_mwh'] = $soma_energia / $resumo['total_restricoes'];
        }

        // Ordenar motivos
        arsort($resumo['motivos']);

        return $resumo;
    }

    /**
     * Limpar restrições antigas (mais de N dias)
     */
    public function limparRestricoesAntiBias(int $diasRetencao = 90): array
    {
        $dataLimite = date('Y-m-d', strtotime('-' . $diasRetencao . ' days'));
        
        $params = [
            'data' => 'lt.' . $dataLimite
        ];

        $resultado = $this->request('DELETE', '/rest/v1/ons_restricoes', null, $params);
        
        return [
            'deletadas' => is_array($resultado) ? count($resultado) : 0,
            'mensagem' => 'Restrições anteriores a ' . $dataLimite . ' foram removidas'
        ];
    }
}
