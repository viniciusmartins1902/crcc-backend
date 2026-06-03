<?php

/**
 * Gerenciador de Tenants (clientes/empresas)
 *
 * Responsabilidades:
 *  - Detectar o tenant ativo a partir do subdomínio HTTP ou fallback do .env
 *  - Carregar dados do tenant e seus módulos contratados do Supabase
 *  - Persistir no $_SESSION para não repetir chamadas a cada request
 *
 * Fluxo:
 *  1. Login: TenantManager::initForSession() é chamado após autenticação
 *  2. Requests seguintes: TenantManager::current() lê da sessão (rápido)
 *  3. Logout: dados apagados junto com a sessão
 */
class TenantManager
{
    /** Chave de sessão onde o tenant fica armazenado */
    private const SESSION_KEY = '__tenant';

    // -------------------------------------------------------
    // API pública
    // -------------------------------------------------------

    /**
     * Inicializa o tenant a partir do tenant_id do usuário logado.
     * Usado após login — qualquer cliente loga em zetta.net.br/login.php
     * e o tenant é resolvido pelo vínculo do usuário, não pelo subdomínio.
     *
     * @param string|null $tenantId  tenant_id vindo de users.tenant_id
     */
    public static function initFromUserId(?string $tenantId): void
    {
        if ($tenantId) {
            $tenant = self::loadById($tenantId);
            if ($tenant) {
                $_SESSION[self::SESSION_KEY] = $tenant;
                return;
            }
        }
        // Fallback: sem tenant_id (usuário pré-migration) → usa subdomínio / .env
        self::initForSession();
    }

    /**
     * Inicializa o tenant na sessão atual.
     * Deve ser chamado após o login bem-sucedido.
     */
    public static function initForSession(): void
    {
        $slug   = self::detectSlug();
        $tenant = self::loadBySlug($slug);

        if (!$tenant) {
            // Tenant não encontrado no banco (migration não rodou ainda ou slug inválido)
            // db_confirmed = false indica que não devemos usar tenant_id como filtro de login
            $tenant = [
                'id'             => DEFAULT_TENANT_ID,
                'nome'           => DEFAULT_TENANT_SLUG,
                'slug'           => DEFAULT_TENANT_SLUG,
                'ativa'          => true,
                'db_confirmed'   => false,
                'modules'        => [],
                'module_configs' => [],
            ];
        } else {
            $tenant['db_confirmed'] = true;
        }

        if (!isset($tenant['modules'])) {
            $loaded                  = self::loadModules($tenant['id']);
            $tenant['modules']       = $loaded['keys'];
            $tenant['module_configs'] = $loaded['configs'];
        }

        $_SESSION[self::SESSION_KEY] = $tenant;
    }

    /**
     * Retorna o tenant ativo.
     * Se não estiver na sessão, tenta inicializar (migração de sessões antigas).
     */
    public static function current(): array
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            // Fallback: usa o tenant_id do usuário logado, não o subdomínio
            // Isso evita que usuários de tenants secundários recebam o DEFAULT_TENANT_ID
            $tenantId = $_SESSION['user_tenant_id'] ?? null;
            if ($tenantId) {
                self::initFromUserId($tenantId);
            } else {
                self::initForSession();
            }
        }

        // Sessão sem módulos E db_confirmed=true = sessão stale (criada antes das migrations).
        // Recarrega os módulos do banco sem forçar logout.
        $tenant = $_SESSION[self::SESSION_KEY] ?? [];
        if (($tenant['db_confirmed'] ?? false) && empty($tenant['modules'])) {
            $loaded = self::loadModules($tenant['id']);
            $_SESSION[self::SESSION_KEY]['modules']        = $loaded['keys'];
            $_SESSION[self::SESSION_KEY]['module_configs'] = $loaded['configs'];
        }

        return $_SESSION[self::SESSION_KEY] ?? [];
    }

    /**
     * Retorna o UUID do tenant ativo.
     */
    public static function id(): string
    {
        return self::current()['id'] ?? DEFAULT_TENANT_ID;
    }

    /**
     * Indica se o tenant foi carregado do banco de dados (true) ou é apenas
     * o fallback do .env (false — migration não rodou ou slug não encontrado).
     * Quando false, o login não deve filtrar por tenant_id.
     */
    public static function isDbConfirmed(): bool
    {
        return (bool) (self::current()['db_confirmed'] ?? false);
    }

    /**
     * Retorna o array de chaves de módulos habilitados para o tenant.
     * Ex: ['rdo', 'inspecoes', 'indicadores']
     */
    public static function modules(): array
    {
        return self::current()['modules'] ?? [];
    }

    /**
     * Retorna a configuração específica de um módulo para o tenant atual.
     *
     * Exemplo de uso:
     *   $cfg = TenantManager::getModuleConfig('rdo');
     *   $campos = $cfg['campos'] ?? [];
     *
     * A configuração é definida no campo `config` JSONB de `tenant_modules`
     * e pode conter qualquer estrutura: campos customizados, labels, regras, etc.
     *
     * @return array  Array associativo; vazio se não houver configuração.
     */
    public static function getModuleConfig(string $chave): array
    {
        $configs = self::current()['module_configs'] ?? [];
        $config  = $configs[$chave] ?? null;

        if (empty($config)) {
            return [];
        }

        // Supabase pode retornar JSONB como string depend. do driver
        if (is_string($config)) {
            $config = json_decode($config, true) ?? [];
        }

        return is_array($config) ? $config : [];
    }

    /**
     * Verifica se o tenant possui um módulo específico habilitado.
     */
    public static function hasModule(string $moduleKey): bool
    {
        $modules = self::modules();
        // Compatibilidade com período de migração: somente concede acesso total
        // ao tenant padrão se os dados NÃO vieram do banco (db_confirmed = false).
        // Se db_confirmed = true, o tenant está carregado e devemos respeitar seus módulos.
        $confirmed = self::current()['db_confirmed'] ?? false;
        if (empty($modules) && self::id() === DEFAULT_TENANT_ID && !$confirmed) {
            return true;
        }
        return in_array($moduleKey, $modules, true);
    }

    /**
     * Remove os dados do tenant da sessão (usado no logout).
     */
    public static function clearSession(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    // -------------------------------------------------------
    // Métodos privados
    // -------------------------------------------------------

    /**
     * Detecta o slug do tenant a partir do subdomínio da URL.
     * Exposto como público para uso externo (ex: verificarLogin).
     *
     * Exemplos:
     *   powerchina.sgm.com.br  → "powerchina"
     *   cliente-b.sgm.com.br   → "cliente-b"
     *   localhost               → DEFAULT_TENANT_SLUG (do .env)
     */
    public static function detectSlug(): string
    {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');

        // Remove porta, se houver (ex: localhost:8080)
        $host = explode(':', $host)[0];

        $parts = explode('.', $host);

        // Subdomínio válido: mínimo 2 partes e não é "www", "localhost" nem vazio
        // Aceita pwc.localhost (dev local) e pwc.zetta.net.br (produção)
        if (count($parts) >= 2 && !in_array($parts[0], ['www', 'localhost', ''], true)) {
            return $parts[0];
        }

        return DEFAULT_TENANT_SLUG;
    }

    /**
     * Carrega dados do tenant do Supabase pelo UUID.
     */
    private static function loadById(string $id): ?array
    {
        require_once __DIR__ . '/../../supabase.php';
        $supabase = new Supabase();

        $result = $supabase->request('GET', '/rest/v1/tenants', null, [
            'id'     => 'eq.' . $id,
            'ativa'  => 'eq.true',
            'select' => 'id,nome,slug,ativa,config',
        ]);

        if (empty($result) || !isset($result[0])) {
            return null;
        }

        $tenant  = $result[0];
        $loaded  = self::loadModules($tenant['id']);
        $tenant['modules']        = $loaded['keys'];
        $tenant['module_configs'] = $loaded['configs'];
        $tenant['db_confirmed']   = true;

        return $tenant;
    }

    /**
     * Carrega dados do tenant do Supabase pelo slug.
     */
    private static function loadBySlug(string $slug): ?array
    {
        require_once __DIR__ . '/../../supabase.php';
        $supabase = new Supabase();

        $result = $supabase->request('GET', '/rest/v1/tenants', null, [
            'slug'   => 'eq.' . $slug,
            'ativa'  => 'eq.true',
            'select' => 'id,nome,slug,ativa,config',
        ]);

        if (empty($result) || !isset($result[0])) {
            return null;
        }

        $tenant = $result[0];
        $loaded  = self::loadModules($tenant['id']);
        $tenant['modules']        = $loaded['keys'];
        $tenant['module_configs'] = $loaded['configs'];

        return $tenant;
    }

    /**
     * Carrega as chaves e configurações dos módulos habilitados para um tenant.
     *
     * @return array{keys: string[], configs: array<string, mixed>}
     */
    private static function loadModules(string $tenantId): array
    {
        require_once __DIR__ . '/../../supabase.php';
        $supabase = new Supabase();

        $result = $supabase->request('GET', '/rest/v1/tenant_modules', null, [
            'tenant_id' => 'eq.' . $tenantId,
            'ativo'     => 'eq.true',
            'select'    => 'module_chave',
        ]);

        if (!is_array($result)) {
            return ['keys' => [], 'configs' => []];
        }

        $keys = array_column($result, 'module_chave');

        return ['keys' => $keys, 'configs' => []];
    }
}
