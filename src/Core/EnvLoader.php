<?php

/**
 * Carregador de variáveis de ambiente a partir de um arquivo .env
 * Sem dependências externas — funciona em LAMPP e Docker.
 */
class EnvLoader
{
    private static bool $loaded = false;

    /**
     * Carrega o arquivo .env e popula $_ENV / getenv().
     * Seguro para chamar múltiplas vezes (idempotente).
     */
    public static function load(string $filePath): void
    {
        if (self::$loaded) {
            return;
        }

        if (!file_exists($filePath)) {
            // Em produção (Docker), as variáveis já chegam via --env-file.
            // Sem .env local, apenas marca como carregado e segue.
            self::$loaded = true;
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Ignorar comentários e linhas em branco
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Suporte a: KEY=VALUE, KEY="VALUE", KEY='VALUE'
            $eqPos = strpos($line, '=');
            if ($eqPos === false) {
                continue;
            }

            $key   = trim(substr($line, 0, $eqPos));
            $value = trim(substr($line, $eqPos + 1));

            // Remove aspas ao redor do valor
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[-1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Não sobrescrever variáveis já definidas no ambiente (ex: Docker)
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key]  = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Retorna o valor de uma variável de ambiente.
     */
    public static function get(string $key, string $default = ''): string
    {
        return $_ENV[$key] ?? (getenv($key) ?: $default);
    }

    /**
     * Verifica se o ambiente atual é produção.
     */
    public static function isProduction(): bool
    {
        return strtolower(self::get('APP_ENV', 'production')) === 'production';
    }
}
