<?php
/**
 * Controle de Acesso — CRCC
 *
 * Nível 1: Planejamento  — cria OS, define área e encarregado, acesso total
 * Nível 2: Gestor        — gerencia obras, aprova, vê tudo
 * Nível 3: Encarregado   — vê OS da própria área/atribuídas, atualiza status
 * Nível 4: Profissional  — vê OS atribuídas a ele, atualiza status
 */

require_once __DIR__ . '/src/Core/TenantManager.php';

$NIVEIS_CRCC = [
    1 => ['label' => 'Planejamento', 'cor' => 'primary'],
    2 => ['label' => 'Gestor',       'cor' => 'success'],
    3 => ['label' => 'Encarregado',  'cor' => 'warning'],
    4 => ['label' => 'Profissional', 'cor' => 'secondary'],
];

function getNivelAcesso(): int {
    return (int) ($_SESSION['nivel_acesso'] ?? 4);
}

function getNomeNivel(int $nivel): string {
    global $NIVEIS_CRCC;
    return $NIVEIS_CRCC[$nivel]['label'] ?? 'Nível ' . $nivel;
}

function getCorNivel(int $nivel): string {
    global $NIVEIS_CRCC;
    return $NIVEIS_CRCC[$nivel]['cor'] ?? 'secondary';
}

/**
 * Pode criar/editar/excluir OS, obras e planejamentos.
 * Níveis 1 (Planejamento) e 2 (Gestor).
 */
function podePlanejar(): bool {
    return getNivelAcesso() <= 2;
}

/**
 * Pode ver todos os dados do tenant (não filtra por responsável).
 * Níveis 1 e 2.
 */
function podeVerTudo(): bool {
    return getNivelAcesso() <= 2;
}

function requerLogin(): void {
    if (empty($_SESSION['logado'])) {
        header('Location: /web/login.php');
        exit;
    }
}
