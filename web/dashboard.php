<?php
require_once __DIR__ . '/../auth.php';
requerLogin();

$supabase = new Supabase();
$tenantId = DEFAULT_TENANT_ID;

$obras = $supabase->request('GET', '/rest/v1/obras', null, ['tenant_id'=>'eq.'.$tenantId,'select'=>'id,status']) ?: [];
$os    = $supabase->request('GET', '/rest/v1/ordens_servico', null, ['tenant_id'=>'eq.'.$tenantId,'select'=>'id,status,data_prevista']) ?: [];

$hoje         = date('Y-m-d');
$obrasAtivas  = count(array_filter($obras, fn($o) => $o['status']==='em_andamento'));
$osAndamento  = count(array_filter($os, fn($o) => $o['status']==='em_andamento'));
$osConcluidas = count(array_filter($os, fn($o) => $o['status']==='concluida'));
$osAtrasadas  = count(array_filter($os, fn($o) => in_array($o['status'],['pendente','em_andamento']) && !empty($o['data_prevista']) && $o['data_prevista']<$hoje));

$ultimasOS = $supabase->request('GET', '/rest/v1/ordens_servico', null, [
    'tenant_id'=>'eq.'.$tenantId, 'status'=>'not.in.(concluida,cancelada)',
    'select'=>'id,titulo,area,status,prioridade,data_prevista', 'order'=>'criado_em.desc', 'limit'=>'8',
]) ?: [];

$statusLabel = ['pendente'=>'Pendente','em_andamento'=>'Em andamento','concluida'=>'Concluída','cancelada'=>'Cancelada'];
$statusBadge = ['pendente'=>'secondary','em_andamento'=>'primary','concluida'=>'success','cancelada'=>'danger'];
$priorBadge  = ['alta'=>'danger','media'=>'warning','baixa'=>'secondary'];

$titulo = 'Dashboard'; $paginaAtiva = 'dashboard';
require __DIR__ . '/../includes/layout.php';
?>
<div class="page-title">Dashboard</div>
<div class="page-sub">Visão geral das obras e operações · <?= date('d/m/Y') ?></div>
<div class="row g-3 mb-4">
<?php foreach ([
    ['Obras ativas',$obrasAtivas,'building','#1565C0','#E3F2FD'],
    ['OS em andamento',$osAndamento,'clipboard-check','#7B1FA2','#F3E5F5'],
    ['OS concluídas',$osConcluidas,'check-circle','#2E7D32','#E8F5E9'],
    ['OS atrasadas',$osAtrasadas,'exclamation-circle','#C62828','#FFEBEE'],
] as [$lbl,$val,$ico,$cor,$bg]): ?>
<div class="col-6 col-lg-3">
  <div class="card kpi-card p-4">
    <div class="d-flex align-items-center gap-3">
      <div style="width:48px;height:48px;border-radius:12px;background:<?=$bg?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-<?=$ico?>" style="font-size:22px;color:<?=$cor?>;"></i>
      </div>
      <div><div class="kpi-num" style="color:<?=$cor?>;"><?=$val?></div><div class="kpi-lbl"><?=$lbl?></div></div>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<div class="card crcc-table">
  <div class="card-header bg-white border-bottom py-3 px-4">
    <h6 class="mb-0 fw-bold"><i class="bi bi-clipboard-list me-2 text-primary"></i>OS em aberto</h6>
  </div>
  <?php if(empty($ultimasOS)): ?><div class="p-5 text-center text-muted">Nenhuma OS em aberto.</div>
  <?php else: ?><div class="table-responsive"><table class="table crcc-table mb-0">
  <thead><tr><th>#</th><th>Título</th><th>Área</th><th>Prioridade</th><th>Status</th><th>Prazo</th></tr></thead>
  <tbody>
  <?php foreach($ultimasOS as $o):
    $atrasada = !empty($o['data_prevista'])&&$o['data_prevista']<$hoje&&$o['status']!=='concluida'; ?>
  <tr>
    <td class="text-muted fw-bold">#<?=$o['id']?></td>
    <td><?=htmlspecialchars($o['titulo'])?></td>
    <td><span class="badge bg-light text-dark"><?=htmlspecialchars($o['area'])?></span></td>
    <td><span class="badge bg-<?=$priorBadge[$o['prioridade']]??'secondary'?>"><?=ucfirst($o['prioridade'])?></span></td>
    <td><span class="badge bg-<?=$statusBadge[$o['status']]??'secondary'?>"><?=$statusLabel[$o['status']]??$o['status']?></span></td>
    <td class="<?=$atrasada?'text-danger fw-bold':'text-muted'?>">
      <?=$o['data_prevista']?date('d/m/Y',strtotime($o['data_prevista'])):'—'?>
      <?=$atrasada?'<i class="bi bi-exclamation-triangle ms-1"></i>':''?>
    </td>
  </tr>
  <?php endforeach; ?></tbody></table></div>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/layout-footer.php'; ?>
