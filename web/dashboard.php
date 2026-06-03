<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();


$supabase = new Supabase();
$tenantId = TenantManager::id();
$nivel    = getNivelAcesso();

$kpi = [
    'obras_ativas' => 4,
    'os_andamento' => 19,
    'os_atraso' => 5,
    'os_concluidas' => 47,
    'incidentes_mes' => 2,
    'qualidade_aprovacao' => 96,
    'desembolso_previsto' => 284500.00,
    'desembolso_real' => 271240.00,
];

$timeline = [
    ['hora' => '07:40', 'evento' => 'DDS concluído no Canteiro Norte', 'tipo' => 'seguranca'],
    ['hora' => '09:10', 'evento' => 'Inspeção de qualidade - Bloco B finalizada', 'tipo' => 'qualidade'],
    ['hora' => '11:25', 'evento' => 'OS #1482 iniciada (Montagem eletromecânica)', 'tipo' => 'operacao'],
    ['hora' => '14:50', 'evento' => 'Medição de avanço físico atualizada para 68%', 'tipo' => 'planejamento'],
    ['hora' => '16:20', 'evento' => 'Fechamento parcial de custos do dia publicado', 'tipo' => 'financeiro'],
];

$frentes = [
    ['nome' => 'Residencial Aurora', 'progresso' => 78, 'status' => 'No prazo'],
    ['nome' => 'Centro Logístico Norte', 'progresso' => 63, 'status' => 'Atenção'],
    ['nome' => 'UFV Setor B', 'progresso' => 71, 'status' => 'No prazo'],
    ['nome' => 'Pátio Operacional Leste', 'progresso' => 100, 'status' => 'Concluída'],
];

$statusCores = [
    'No prazo' => 'success',
    'Atenção' => 'warning',
    'Concluída' => 'secondary',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?= htmlspecialchars('CRCC') ?></title>
    
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
                        <i class="bi bi-speedometer2 me-2 text-primary"></i>Dashboard
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Visão geral das operações CRCC</p>
                </div>
            </div>

            <!-- Métricas -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-icon" style="background:rgba(79,195,247,0.15);">
                            <i class="bi bi-building" style="color:#4fc3f7;"></i>
                        </div>
                        <div class="crcc-metric-value"><?= $kpi['obras_ativas'] ?></div>
                        <p class="crcc-metric-label">Obras ativas</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-icon" style="background:rgba(251,191,36,0.15);">
                            <i class="bi bi-clipboard-check" style="color:#fbbf24;"></i>
                        </div>
                        <div class="crcc-metric-value"><?= $kpi['os_andamento'] ?></div>
                        <p class="crcc-metric-label">OS em andamento</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-icon" style="background:rgba(248,113,113,0.15);">
                            <i class="bi bi-exclamation-triangle" style="color:#f87171;"></i>
                        </div>
                        <div class="crcc-metric-value"><?= $kpi['os_atraso'] ?></div>
                        <p class="crcc-metric-label">OS em atraso</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-icon" style="background:rgba(52,211,153,0.15);">
                            <i class="bi bi-check-circle" style="color:#34d399;"></i>
                        </div>
                        <div class="crcc-metric-value"><?= $kpi['os_concluidas'] ?></div>
                        <p class="crcc-metric-label">OS concluídas</p>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="crcc-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0" style="color:#0F172A;font-weight:700;">Segurança</h6>
                            <i class="bi bi-shield-check" style="color:#22c55e;"></i>
                        </div>
                        <div style="font-size:2rem;font-weight:800;color:#0F172A;"><?= $kpi['incidentes_mes'] ?></div>
                        <div style="font-size:.8rem;color:#64748b;">Incidentes no mês</div>
                        <div class="mt-3" style="font-size:.8rem;color:#334155;">28 dias sem afastamento em frente crítica.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="crcc-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0" style="color:#0F172A;font-weight:700;">Qualidade</h6>
                            <i class="bi bi-patch-check" style="color:#0ea5e9;"></i>
                        </div>
                        <div style="font-size:2rem;font-weight:800;color:#0F172A;"><?= $kpi['qualidade_aprovacao'] ?>%</div>
                        <div style="font-size:.8rem;color:#64748b;">Taxa de aprovação em inspeções</div>
                        <div class="mt-3" style="font-size:.8rem;color:#334155;">3 pendências abertas com tratativa em andamento.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="crcc-card p-4 h-100">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="mb-0" style="color:#0F172A;font-weight:700;">Financeiro</h6>
                            <i class="bi bi-graph-up-arrow" style="color:#f59e0b;"></i>
                        </div>
                        <div style="font-size:1.35rem;font-weight:800;color:#0F172A;">R$ <?= number_format($kpi['desembolso_real'], 2, ',', '.') ?></div>
                        <div style="font-size:.8rem;color:#64748b;">Realizado no ciclo atual</div>
                        <div class="mt-2" style="font-size:.78rem;color:#334155;">Previsto: R$ <?= number_format($kpi['desembolso_previsto'], 2, ',', '.') ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Frentes de obra</h6>
                        <?php foreach ($frentes as $frente): ?>
                        <?php $cor = $statusCores[$frente['status']] ?? 'secondary'; ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div style="font-size:.88rem;color:#0f172a;font-weight:600;"><?= htmlspecialchars($frente['nome']) ?></div>
                                <span class="badge bg-<?= $cor ?>"><?= htmlspecialchars($frente['status']) ?></span>
                            </div>
                            <div class="progress" style="height:8px;background:#e2e8f0;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?= (int) $frente['progresso'] ?>%"></div>
                            </div>
                            <div style="font-size:.75rem;color:#64748b;margin-top:4px;"><?= (int) $frente['progresso'] ?>% concluído</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Timeline operacional</h6>
                        <?php foreach ($timeline as $item): ?>
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="badge text-bg-light" style="min-width:54px;"><?= htmlspecialchars($item['hora']) ?></span>
                            <div style="font-size:.84rem;color:#334155;"><?= htmlspecialchars($item['evento']) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
