<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
;

requerLogin();


$kpis = [
    'dias_sem_afastamento' => 28,
    'dds_realizados' => 17,
    'pts_abertas' => 9,
    'incidentes_leves' => 2,
];

$frentes = [
    ['frente' => 'Residencial Aurora', 'risco' => 'Médio', 'epc' => 94, 'epi' => 97],
    ['frente' => 'Centro Logístico Norte', 'risco' => 'Alto', 'epc' => 88, 'epi' => 92],
    ['frente' => 'UFV Setor B', 'risco' => 'Baixo', 'epc' => 98, 'epi' => 99],
];

$acoes = [
    ['hora' => '07:00', 'acao' => 'DDS de abertura realizado com 43 colaboradores.'],
    ['hora' => '09:30', 'acao' => 'Inspeção de linha de vida concluída no bloco técnico.'],
    ['hora' => '11:10', 'acao' => 'Checklist de APR finalizado para içamento de carga.'],
    ['hora' => '15:20', 'acao' => 'Treinamento rápido de bloqueio e etiquetagem (LOTO).'],
];

$riscoCor = [
    'Alto' => 'danger',
    'Médio' => 'warning',
    'Baixo' => 'success',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança - <?= htmlspecialchars(getConfigSistema('nome_sistema', 'Zetta')) ?></title>
    <link rel="icon" type="image/svg+xml" href="../<?= htmlspecialchars(getConfigSistema('favicon_path', 'assets/images/sgm-logo.svg')) ?>">
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
                        <i class="bi bi-shield-check me-2 text-success"></i>Segurança
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Modelo visual para apresentação operacional QSMS</p>
                </div>
                <span class="badge text-bg-success">Módulo demonstrativo</span>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['dias_sem_afastamento'] ?></div>
                        <p class="crcc-metric-label">Dias sem afastamento</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['dds_realizados'] ?></div>
                        <p class="crcc-metric-label">DDS no mês</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['pts_abertas'] ?></div>
                        <p class="crcc-metric-label">PTs abertas</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-metric">
                        <div class="crcc-metric-value"><?= $kpis['incidentes_leves'] ?></div>
                        <p class="crcc-metric-label">Incidentes leves</p>
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-7">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Risco por frente</h6>
                        <?php foreach ($frentes as $item): ?>
                        <?php $badge = $riscoCor[$item['risco']] ?? 'secondary'; ?>
                        <div class="mb-3" style="border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong style="font-size:.9rem;color:#0f172a;"><?= htmlspecialchars($item['frente']) ?></strong>
                                <span class="badge bg-<?= $badge ?>">Risco <?= htmlspecialchars($item['risco']) ?></span>
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div style="font-size:.75rem;color:#64748b;">Conformidade EPC</div>
                                    <div class="progress" style="height:7px;background:#dbeafe;">
                                        <div class="progress-bar bg-info" style="width: <?= (int)$item['epc'] ?>%"></div>
                                    </div>
                                    <div style="font-size:.75rem;color:#334155;margin-top:2px;"><?= (int)$item['epc'] ?>%</div>
                                </div>
                                <div class="col-6">
                                    <div style="font-size:.75rem;color:#64748b;">Conformidade EPI</div>
                                    <div class="progress" style="height:7px;background:#dcfce7;">
                                        <div class="progress-bar bg-success" style="width: <?= (int)$item['epi'] ?>%"></div>
                                    </div>
                                    <div style="font-size:.75rem;color:#334155;margin-top:2px;"><?= (int)$item['epi'] ?>%</div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Timeline de ações preventivas</h6>
                        <?php foreach ($acoes as $acao): ?>
                        <div class="d-flex align-items-start gap-2 mb-3">
                            <span class="badge text-bg-light" style="min-width:54px;"><?= htmlspecialchars($acao['hora']) ?></span>
                            <div style="font-size:.84rem;color:#334155;"><?= htmlspecialchars($acao['acao']) ?></div>
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
