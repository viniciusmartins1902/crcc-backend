<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
;

requerLogin();


$kpis = [
    'aprovacao_inspecoes' => 96,
    'ncs_abertas' => 3,
    'retrabalho' => 1.8,
    'checklists_concluidos' => 42,
];

$inspecoes = [
    ['item' => 'Concretagem Bloco B', 'status' => 'Aprovado', 'responsavel' => 'Eng. Mariana', 'nota' => 9.4],
    ['item' => 'Alinhamento estruturas metálicas', 'status' => 'Aprovado', 'responsavel' => 'Eng. Rafael', 'nota' => 9.1],
    ['item' => 'Acabamento área técnica', 'status' => 'Com ressalvas', 'responsavel' => 'Eng. Carla', 'nota' => 8.2],
    ['item' => 'Instalação de eletrocalhas', 'status' => 'Aprovado', 'responsavel' => 'Eng. Diego', 'nota' => 9.0],
];

$planos = [
    ['acao' => 'Tratar não conformidade de junta de dilatação no Bloco A', 'prazo' => '03/06/2026', 'owner' => 'Coord. Civil'],
    ['acao' => 'Reforçar inspeção de torque em trackers fileira 12-16', 'prazo' => '02/06/2026', 'owner' => 'Coord. Montagem'],
    ['acao' => 'Padronizar checklist de entrega de frente', 'prazo' => '05/06/2026', 'owner' => 'QSMS'],
];

$statusCor = [
    'Aprovado' => 'success',
    'Com ressalvas' => 'warning',
    'Reprovado' => 'danger',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qualidade - <?= htmlspecialchars('CRCC') ?></title>
    
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
            <div class="d-flex justify-content-between flex-wrap align-items-center pb-3 mb-3">
                <div>
                    <h1 class="h3 mb-1" style="font-weight:700; color:#0F172A; letter-spacing:-.02em;">
                        <i class="bi bi-patch-check me-2 text-info"></i>Qualidade
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Modelo visual para apresentação de controle de qualidade</p>
                </div>
                <span class="badge text-bg-info">Módulo demonstrativo</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['aprovacao_inspecoes'] ?>%</div>
                        <p class="crcc-metric-label">Aprovação de inspeções</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['ncs_abertas'] ?></div>
                        <p class="crcc-metric-label">Não conformidades abertas</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= number_format($kpis['retrabalho'], 1, ',', '.') ?>%</div>
                        <p class="crcc-metric-label">Índice de retrabalho</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['checklists_concluidos'] ?></div>
                        <p class="crcc-metric-label">Checklists concluídos</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Inspeções recentes</h6>
                        <?php foreach ($inspecoes as $insp): ?>
                        <?php $badge = $statusCor[$insp['status']] ?? 'secondary'; ?>
                        <div class="mb-3" style="border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong style="font-size:.88rem;color:#0f172a;"><?= htmlspecialchars($insp['item']) ?></strong>
                                <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($insp['status']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between" style="font-size:.78rem;color:#64748b;">
                                <span><?= htmlspecialchars($insp['responsavel']) ?></span>
                                <span>Nota <?= number_format((float) $insp['nota'], 1, ',', '.') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Plano de ação</h6>
                        <?php foreach ($planos as $plano): ?>
                        <div class="mb-3 pb-2" style="border-bottom:1px solid #e2e8f0;">
                            <div style="font-size:.84rem;color:#334155;"><?= htmlspecialchars($plano['acao']) ?></div>
                            <div class="d-flex justify-content-between mt-1" style="font-size:.75rem;color:#64748b;">
                                <span><?= htmlspecialchars($plano['owner']) ?></span>
                                <span>Prazo <?= htmlspecialchars($plano['prazo']) ?></span>
                            </div>
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
