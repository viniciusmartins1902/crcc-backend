<?php
/**
 * Resultados da Pesquisa de Satisfação — Zetta
 * Requer login de admin/gestor
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../supabase.php';

requerLogin();

$supabase  = new Supabase();
$respostas = $supabase->request('GET', '/rest/v1/pesquisa_satisfacao', null, [
    'select' => '*',
    'order'  => 'criado_em.desc',
    'limit'  => '500',
]) ?: [];

$total = count($respostas);
$media = $total > 0 ? round(array_sum(array_column($respostas, 'nota')) / $total, 1) : 0;
$comContato = count(array_filter($respostas, fn($r) => !empty($r['whatsapp']) || !empty($r['nome'])));

// Interesse
$interesses = ['sim' => 0, 'talvez' => 0, 'nao' => 0, 'nao_fit' => 0];
foreach ($respostas as $r) {
    $k = $r['interesse'] ?? '';
    if (isset($interesses[$k])) $interesses[$k]++;
}

// Perfil
$perfis = [];
foreach ($respostas as $r) {
    $p = $r['perfil'] ?? 'outro';
    if ($p) $perfis[$p] = ($perfis[$p] ?? 0) + 1;
}
arsort($perfis);

// Tamanho
$tamanhos = [];
foreach ($respostas as $r) {
    $t = $r['tamanho'] ?? '';
    if ($t) $tamanhos[$t] = ($tamanhos[$t] ?? 0) + 1;
}

// Desafios
$desafios = [];
foreach ($respostas as $r) {
    foreach (explode(', ', $r['desafios'] ?? '') as $d) {
        $d = trim($d);
        if ($d) $desafios[$d] = ($desafios[$d] ?? 0) + 1;
    }
}
arsort($desafios);

// Destaques
$destaques = [];
foreach ($respostas as $r) {
    foreach (explode(', ', $r['destaques'] ?? '') as $d) {
        $d = trim($d);
        if ($d) $destaques[$d] = ($destaques[$d] ?? 0) + 1;
    }
}
arsort($destaques);

// Prazo
$prazos = [];
foreach ($respostas as $r) {
    $p = $r['prazo'] ?? '';
    if ($p) $prazos[$p] = ($prazos[$p] ?? 0) + 1;
}

$labels = [
    'interesse' => ['sim' => '🚀 Quer contratar', 'talvez' => '🤔 Vai avaliar', 'nao' => '⏳ No futuro', 'nao_fit' => '❌ Não é fit'],
    'perfil'    => ['proprietario' => '🏢 Proprietário', 'gestor' => '📋 Gestor', 'engenheiro' => '⚙️ Engenheiro', 'encarregado' => '🦺 Encarregado', 'financeiro' => '💰 Financeiro', 'outro' => '👤 Outro'],
    'tamanho'   => ['1-10' => 'Até 10', '11-50' => '11–50', '51-200' => '51–200', '200+' => '200+'],
    'desafios'  => ['controle_os' => '🔧 Controle de OS', 'relatorios' => '📄 Relatórios', 'comunicacao' => '📡 Comunicação', 'gestao_custos' => '💸 Custos', 'visibilidade' => '👁️ Visibilidade', 'reembolsos' => '🧾 Reembolsos', 'produtividade' => '⏱️ Produtividade', 'documentacao' => '📁 Documentação'],
    'destaques' => ['app_mobile' => '📱 App Mobile', 'dashboard_web' => '💻 Dashboard', 'rdo_digital' => '📝 RDO', 'gestao_os' => '🔧 Gestão OS', 'reembolsos' => '💳 Reembolsos', 'relatorios' => '📊 Relatórios', 'offline' => '📶 Offline', 'integracao' => '🔗 Integrações'],
    'prazo'     => ['imediato' => '⚡ Imediato', '3_meses' => '📅 Até 3m', '6_meses' => '🗓️ 3–6m', 'sem_previsao' => '🔮 Sem previsão'],
    'interesse_cores' => ['sim' => '#16a34a', 'talvez' => '#d97706', 'nao' => '#2563eb', 'nao_fit' => '#dc2626'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="refresh" content="30">
<title>Resultados da Pesquisa — Zetta</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="/assets/css/admin.min.css">
<link rel="stylesheet" href="/assets/css/admin-contrast.min.css">
<style>
  body { background: #f0f4f8; }
  .kpi-card { background:#fff; border-radius:16px; padding:20px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.06); }
  .kpi-num  { font-size:44px; font-weight:800; line-height:1; margin-bottom:4px; }
  .kpi-lbl  { font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
  .kpi-sub  { font-size:12px; color:#94a3b8; margin-top:2px; }
  .chart-card { background:#fff; border-radius:16px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,0.06); height:100%; }
  .chart-title { font-size:13px; font-weight:700; color:#0f172a; margin-bottom:14px; display:flex; align-items:center; gap:6px; }
  .bar-row { margin-bottom:10px; }
  .bar-label { display:flex; justify-content:space-between; margin-bottom:4px; }
  .bar-label span:first-child { font-size:12px; font-weight:600; color:#334155; }
  .bar-label span:last-child  { font-size:12px; font-weight:700; }
  .bar-bg   { background:#f1f5f9; border-radius:6px; height:10px; overflow:hidden; }
  .bar-fill { height:100%; border-radius:6px; transition:width .8s ease; }
  .stars-txt { color:#f59e0b; font-size:20px; letter-spacing:2px; }
  .badge-sim     { background:rgba(22,163,74,.12);  color:#16a34a; }
  .badge-talvez  { background:rgba(217,119,6,.12);  color:#d97706; }
  .badge-nao     { background:rgba(37,99,235,.12);  color:#2563eb; }
  .badge-nao_fit { background:rgba(220,38,38,.12);  color:#dc2626; }
  .resp-card { background:#fff; border-radius:12px; padding:16px; margin-bottom:10px; border:1px solid #e2e8f0; transition:box-shadow .15s; }
  .resp-card:hover { box-shadow:0 4px 16px rgba(0,0,0,0.08); }
  .nota-stars { color:#f59e0b; letter-spacing:1px; }
  .chip { display:inline-flex; align-items:center; background:#f1f5f9; border-radius:20px; font-size:11px; padding:3px 8px; margin:2px; color:#475569; font-weight:600; }
  .live-dot { width:8px; height:8px; border-radius:50%; background:#16a34a; animation:pulse 1.5s infinite; display:inline-block; margin-right:6px; }
  @keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
</style>
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div style="max-width:1080px; margin:0 auto; padding:20px 16px 60px;">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-start mb-4">
    <div>
      <h1 class="h3 fw-bold mb-1" style="color:#0f172a;">
        <i class="bi bi-bar-chart-fill text-primary me-2"></i>Resultados da Pesquisa
      </h1>
      <p class="mb-0" style="font-size:13px;color:#64748b;">
        <span class="live-dot"></span>
        Atualiza a cada 30s ·
        <?= $total ?> resposta<?= $total !== 1 ? 's' : '' ?> ·
        <?= date('d/m/Y H:i') ?>
      </p>
    </div>
    <div class="d-flex gap-2">
      <a href="pesquisa.php" target="_blank" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-box-arrow-up-right me-1"></i> Formulário
      </a>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-num text-primary"><?= $total ?></div>
        <div class="kpi-lbl">Respostas</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-num" style="color:#f59e0b;"><?= number_format($media, 1) ?></div>
        <div class="stars-txt" style="font-size:16px;"><?= str_repeat('★', (int)round($media)) ?><?= str_repeat('☆', 5-(int)round($media)) ?></div>
        <div class="kpi-lbl mt-1">Nota Média</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-num" style="color:#16a34a;"><?= $interesses['sim'] ?></div>
        <div class="kpi-lbl">Querem Contratar</div>
        <div class="kpi-sub"><?= $total > 0 ? round($interesses['sim']/$total*100) : 0 ?>% do total</div>
      </div>
    </div>
    <div class="col-3">
      <div class="kpi-card">
        <div class="kpi-num" style="color:#1976d2;"><?= $comContato ?></div>
        <div class="kpi-lbl">Com Contato</div>
        <div class="kpi-sub">leads identificados</div>
      </div>
    </div>
  </div>

  <!-- Gráficos linha 1 -->
  <div class="row g-3 mb-3">

    <!-- Interesse -->
    <div class="col-md-5">
      <div class="chart-card">
        <div class="chart-title"><i class="bi bi-pie-chart text-primary"></i> Interesse em Contratar</div>
        <?php foreach ($interesses as $k => $v):
          $pct = $total > 0 ? round($v/$total*100) : 0;
          $cor = $labels['interesse_cores'][$k] ?? '#64748b';
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span><?= $labels['interesse'][$k] ?? $k ?></span>
            <span style="color:<?= $cor ?>;"><?= $v ?> (<?= $pct ?>%)</span>
          </div>
          <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>;"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Perfil + Tamanho -->
    <div class="col-md-7">
      <div class="chart-card">
        <div class="row g-3">
          <div class="col-6">
            <div class="chart-title"><i class="bi bi-person text-primary"></i> Perfil</div>
            <?php foreach ($perfis as $k => $v):
              $pct = $total > 0 ? round($v/$total*100) : 0;
            ?>
            <div class="bar-row">
              <div class="bar-label">
                <span style="font-size:12px;"><?= $labels['perfil'][$k] ?? $k ?></span>
                <span style="color:#1976d2;font-size:12px;"><?= $v ?></span>
              </div>
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#1976d2;"></div></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($perfis)): ?><p class="text-muted" style="font-size:12px;">—</p><?php endif; ?>
          </div>
          <div class="col-6">
            <div class="chart-title"><i class="bi bi-building text-primary"></i> Tamanho</div>
            <?php
            $ordemTamanho = ['1-10','11-50','51-200','200+'];
            foreach ($ordemTamanho as $k):
              $v = $tamanhos[$k] ?? 0;
              if (!$v) continue;
              $pct = $total > 0 ? round($v/$total*100) : 0;
            ?>
            <div class="bar-row">
              <div class="bar-label">
                <span style="font-size:12px;"><?= $labels['tamanho'][$k] ?></span>
                <span style="color:#7c3aed;font-size:12px;"><?= $v ?></span>
              </div>
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#7c3aed;"></div></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($tamanhos)): ?><p class="text-muted" style="font-size:12px;">—</p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Gráficos linha 2 -->
  <div class="row g-3 mb-4">

    <!-- Desafios -->
    <div class="col-md-6">
      <div class="chart-card">
        <div class="chart-title"><i class="bi bi-exclamation-triangle text-warning"></i> Principais Desafios</div>
        <?php if (empty($desafios)): ?>
          <p class="text-muted" style="font-size:13px;">Nenhum dado ainda.</p>
        <?php else:
          $maxD = max($desafios);
          foreach ($desafios as $d => $cnt):
            $pct = round($cnt/$maxD*100);
        ?>
        <div class="bar-row">
          <div class="bar-label">
            <span><?= $labels['desafios'][$d] ?? $d ?></span>
            <span style="color:#d97706;"><?= $cnt ?></span>
          </div>
          <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#d97706;"></div></div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

    <!-- Destaques + Prazo -->
    <div class="col-md-6">
      <div class="chart-card">
        <div class="row">
          <div class="col-7">
            <div class="chart-title"><i class="bi bi-star text-primary"></i> Funcionalidades</div>
            <?php if (empty($destaques)): ?>
              <p class="text-muted" style="font-size:12px;">—</p>
            <?php else:
              $maxF = max($destaques);
              foreach ($destaques as $d => $cnt):
                $pct = round($cnt/$maxF*100);
            ?>
            <div class="bar-row">
              <div class="bar-label">
                <span style="font-size:12px;"><?= $labels['destaques'][$d] ?? $d ?></span>
                <span style="color:#1976d2;font-size:12px;"><?= $cnt ?></span>
              </div>
              <div class="bar-bg"><div class="bar-fill" style="width:<?= $pct ?>%;background:#1976d2;"></div></div>
            </div>
            <?php endforeach; endif; ?>
          </div>
          <div class="col-5">
            <div class="chart-title"><i class="bi bi-calendar text-success"></i> Prazo</div>
            <?php
            $ordemPrazo = ['imediato','3_meses','6_meses','sem_previsao'];
            foreach ($ordemPrazo as $k):
              $v = $prazos[$k] ?? 0;
              if (!$v) continue;
              $pct = $total > 0 ? round($v/$total*100) : 0;
            ?>
            <div class="bar-row">
              <div class="bar-label">
                <span style="font-size:11px;"><?= $labels['prazo'][$k] ?></span>
                <span style="color:#16a34a;font-size:11px;"><?= $v ?></span>
              </div>
              <div class="bar-bg"><div class="bar-fill" style="width:<?= max($pct,4) ?>%;background:#16a34a;"></div></div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($prazos)): ?><p class="text-muted" style="font-size:12px;">—</p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Respostas individuais -->
  <div class="chart-card">
    <div class="chart-title" style="font-size:14px;">
      <i class="bi bi-list-ul text-primary"></i> Respostas Individuais
      <span class="ms-auto text-muted fw-normal" style="font-size:11px;">mais recentes primeiro</span>
    </div>

    <?php if ($total === 0): ?>
      <div class="text-center py-5 text-muted">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <p>Nenhuma resposta ainda. Compartilhe o link!</p>
      </div>

    <?php else:
      foreach ($respostas as $r):
        $nota   = (int)($r['nota'] ?? 0);
        $inter  = $r['interesse'] ?? 'nao';
        $data   = $r['criado_em'] ? date('d/m H:i', strtotime($r['criado_em'])) : '—';
    ?>
    <div class="resp-card">
      <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
        <div>
          <strong style="font-size:14px;"><?= htmlspecialchars($r['nome'] ?: 'Anônimo') ?></strong>
          <?php if ($r['empresa']): ?>
            <span class="text-muted" style="font-size:13px;"> · <?= htmlspecialchars($r['empresa']) ?></span>
          <?php endif; ?>
          <?php if ($r['whatsapp']): ?>
            <a href="https://wa.me/55<?= preg_replace('/\D/','',$r['whatsapp']) ?>" target="_blank"
               class="ms-2" style="font-size:12px;color:#16a34a;text-decoration:none;">
              <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($r['whatsapp']) ?>
            </a>
          <?php endif; ?>
        </div>
        <div class="d-flex align-items-center gap-2 flex-shrink-0">
          <span class="nota-stars"><?= str_repeat('★',$nota).str_repeat('☆',5-$nota) ?></span>
          <span class="badge rounded-pill px-2 badge-<?= $inter ?>" style="font-size:11px;">
            <?= $labels['interesse'][$inter] ?? $inter ?>
          </span>
          <span class="text-muted" style="font-size:11px;"><?= $data ?></span>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-1 mb-2">
        <?php if ($r['perfil']): ?>
          <span class="chip"><?= $labels['perfil'][$r['perfil']] ?? $r['perfil'] ?></span>
        <?php endif; ?>
        <?php if ($r['tamanho']): ?>
          <span class="chip">🏢 <?= $labels['tamanho'][$r['tamanho']] ?? $r['tamanho'] ?></span>
        <?php endif; ?>
        <?php if ($r['prazo']): ?>
          <span class="chip"><?= $labels['prazo'][$r['prazo']] ?? $r['prazo'] ?></span>
        <?php endif; ?>
        <?php foreach (explode(', ', $r['destaques'] ?? '') as $d):
          $d = trim($d); if (!$d) continue; ?>
          <span class="chip" style="background:#e3f0ff;color:#1976d2;"><?= $labels['destaques'][$d] ?? $d ?></span>
        <?php endforeach; ?>
      </div>

      <?php if ($r['desafios']): ?>
        <p class="mb-1" style="font-size:12px;color:#64748b;">
          <i class="bi bi-exclamation-triangle text-warning me-1"></i>
          <?php foreach (explode(', ', $r['desafios']) as $d):
            $d = trim($d); if (!$d) continue;
            echo '<span class="chip" style="background:#fff7ed;color:#d97706;">' . ($labels['desafios'][$d] ?? $d) . '</span> ';
          endforeach; ?>
        </p>
      <?php endif; ?>

      <?php if ($r['comentario']): ?>
        <p class="mb-0 mt-1" style="font-size:13px;color:#334155;font-style:italic; border-top:1px solid #f1f5f9;padding-top:8px;">
          "<?= htmlspecialchars($r['comentario']) ?>"
        </p>
      <?php endif; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
