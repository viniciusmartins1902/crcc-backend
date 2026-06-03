<?php
function getConfigSistema(string $chave, string $default = ''): string {
    $config = [
        'nome_sistema' => 'CRCC',
        'favicon_path' => 'assets/images/favicon.ico',
    ];
    return $config[$chave] ?? $default;
}
