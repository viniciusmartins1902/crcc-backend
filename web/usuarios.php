<?php
/**
 * Listagem de Usuários — Módulo Construtora (somente leitura)
 * Criação e edição de usuários é feita exclusivamente pelo painel admin Zetta.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();


$supabase = new Supabase();
$tenantId = TenantManager::id();

$usuarios = $supabase->request('GET', '/rest/v1/users', null, [
    'tenant_id' => 'eq.' . $tenantId,
    'order'     => 'nome.asc',
    'select'    => 'id,nome,email,nivel_acesso,funcao,area',
]);
if (!is_array($usuarios) || isset($usuarios['code'])) $usuarios = [];

$niveis = [
    1 => ['label' => 'Administrador', 'cor' => 'danger'],
    2 => ['label' => 'Gestor',        'cor' => 'warning'],
    3 => ['label' => 'Encarregado',   'cor' => 'info'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários — <?= htmlspecialchars('CRCC') ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/admin.min.css">
    <link rel="stylesheet" href="/assets/css/admin-contrast.min.css">
    <link rel="stylesheet" href="/assets/css/construtora.css?v=20260601-2">
</head>
<body style="background: linear-gradient(135deg, #0f2027, #203a43, #2c3e50); min-height: 100vh;">

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4" style="padding-top:1.5rem;">

            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between flex-wrap align-items-center pb-3 mb-3">
                <div>
                    <h1 class="h3 mb-1" style="font-weight:700; color:#0F172A; letter-spacing:-.02em;">
                        <i class="bi bi-people me-2 text-primary"></i>Usuários
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Para adicionar ou remover usuários, entre em contato com a Zetta.</p>
                </div>
            </div>

            <div class="crcc-card">
                <div>

                <?php if (empty($usuarios)): ?>
                <div class="crcc-empty">
                    <i class="bi bi-people"></i>
                    <p>Nenhum usuário cadastrado.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table crcc-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:24px;">Nome</th>
                                <th>E-mail</th>
                                <th>Área</th>
                                <th>Função</th>
                                <th>Perfil</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($usuarios as $u):
                            if (!isset($u['nome'])) continue;
                            $n = $niveis[$u['nivel_acesso']] ?? ['label' => 'N/A', 'cor' => 'secondary'];
                        ?>
                            <tr>
                                <td style="padding-left:24px; color:#0f172a; font-weight:600;"><?= htmlspecialchars($u['nome']) ?></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['area'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($u['funcao'] ?? '—') ?></td>
                                <td><span class="badge crcc-badge bg-<?= $n['cor'] ?>"><?= $n['label'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
