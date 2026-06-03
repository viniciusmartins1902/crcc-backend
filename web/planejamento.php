<?php
require_once __DIR__ . '/../auth.php';
requerLogin();

if (!podePlanejar()) {
    header('Location: /web/dashboard.php');
    exit;
}

$supabase = new Supabase();
$tenantId = DEFAULT_TENANT_ID;
$flash    = ['tipo'=>'','msg'=>''];
$hoje     = date('Y-m-d');
$mesAtual = $_GET['mes'] ?? date('Y-m');

// ── POST ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao      = $_POST['acao'] ?? '';
    if ($acao === 'criar' || $acao === 'editar') {
        $titulo    = trim($_POST['titulo'] ?? '');
        $obra_id   = (int)($_POST['obra_id'] ?? 0) ?: null;
        $area      = trim($_POST['area'] ?? '');
        $resp_id   = (int)($_POST['responsavel_id'] ?? 0) ?: null;
        $mes       = trim($_POST['mes_execucao'] ?? '');
        $prioridade= $_POST['prioridade'] ?? 'media';
        $descricao = trim($_POST['descricao'] ?? '');

        if (!$titulo || !$area || !$mes) {
            $flash = ['tipo'=>'danger','msg'=>'Título, área e mês são obrigatórios.'];
        } else {
            $data_prev = $mes . '-01';
            $payload   = [
                'tenant_id'    => $tenantId,
                'titulo'       => $titulo,
                'obra_id'      => $obra_id,
                'area'         => $area,
                'responsavel_id' => $resp_id,
                'data_prevista'=> $data_prev,
                'prioridade'   => $prioridade,
                'status'       => 'pendente',
                'descricao'    => $descricao ?: null,
            ];
            if ($acao === 'criar') {
                $supabase->request('POST', '/rest/v1/ordens_servico', $payload);
                $flash = ['tipo'=>'success','msg'=>'Atividade planejada com sucesso.'];
            } else {
                $id = (int)($_POST['id'] ?? 0);
                $supabase->request('PATCH', '/rest/v1/ordens_servico?id=eq.'.$id.'&tenant_id=eq.'.$tenantId, $payload);
                $flash = ['tipo'=>'success','msg'=>'Atividade atualizada.'];
            }
            header('Location: /web/planejamento.php?mes='.$mes.($flash['tipo']==='danger'?'':''));
            exit;
        }
    } elseif ($acao === 'excluir') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $supabase->request('DELETE', '/rest/v1/ordens_servico?id=eq.'.$id.'&tenant_id=eq.'.$tenantId, null);
        }
        header('Location: /web/planejamento.php?mes='.$mesAtual);
        exit;
    }
}

// ── Dados ─────────────────────────────────────────────────────
// Obras ativas
$obras = $supabase->request('GET', '/rest/v1/obras', null, [
    'tenant_id' => 'eq.'.$tenantId,
    'select'    => 'id,nome',
    'status'    => 'neq.concluida',
    'order'     => 'nome.asc',
]) ?: [];

// Usuários (encarregados nivel >= 3)
$usuarios = $supabase->request('GET', '/rest/v1/users', null, [
    'tenant_id'    => 'eq.'.$tenantId,
    'select'       => 'id,nome,area,nivel_acesso',
    'order'        => 'nome.asc',
]) ?: [];
$encarregados = array_filter($usuarios, fn($u) => $u['nivel_acesso'] >= 3);
$userMap      = array_column($usuarios, 'nome', 'id');

// Atividades do mês selecionado + próximos 5 meses para a visão geral
$mesIni = $mesAtual . '-01';
$mesFim = date('Y-m-t', strtotime($mesIni));

$atividades = $supabase->request('GET', '/rest/v1/ordens_servico', null, [
    'tenant_id'    => 'eq.'.$tenantId,
    'select'       => 'id,titulo,area,responsavel_id,data_prevista,prioridade,status,obra_id,descricao',
    'data_prevista'=> 'gte.'.$mesIni,
    'order'        => 'data_prevista.asc,prioridade.desc',
    'limit'        => '200',
]) ?: [];

// Filtrar apenas atividades do mês visualizado (pode ter além)
$ativsDoMes = array_filter($atividades, fn($a) =>
    substr($a['data_prevista'], 0, 7) === $mesAtual
);

// Agrupar por área
$porArea = [];
foreach ($ativsDoMes as $a) {
    $porArea[$a['area']][] = $a;
}
ksort($porArea);

// Resumo próximos 6 meses
$resumoMeses = [];
foreach ($atividades as $a) {
    $m = substr($a['data_prevista'], 0, 7);
    $resumoMeses[$m] = ($resumoMeses[$m] ?? 0) + 1;
}
ksort($resumoMeses);

$obraMap  = array_column($obras, 'nome', 'id');
$areas    = ['Elétrica', 'Civil', 'Montagem', 'O&M'];
$priorCor = ['alta'=>'danger','media'=>'warning','baixa'=>'secondary'];
$priorLabel = ['alta'=>'Alta','media'=>'Média','baixa'=>'Baixa'];
$statusLabel = ['pendente'=>'Pendente','em_andamento'=>'Em andamento','concluida'=>'Concluída','cancelada'=>'Cancelada'];
$statusCor   = ['pendente'=>'secondary','em_andamento'=>'primary','concluida'=>'success','cancelada'=>'danger'];

// Meses para navegação
$meses = [];
for ($i = -1; $i <= 5; $i++) {
    $ts = strtotime($mesAtual.'-01 '.$i.' month');
    $meses[] = ['val'=>date('Y-m',$ts), 'label'=>ucfirst(strftime('%B %Y', $ts) ?: date('M/Y', $ts))];
}

$titulo = 'Planejamento'; $paginaAtiva = 'planejamento';
require __DIR__ . '/../includes/layout.php';
?>

<!-- Cabeçalho -->
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
  <div>
    <div class="page-title"><i class="bi bi-calendar3 me-2 text-primary"></i>Planejamento</div>
    <div class="page-sub">Programação de atividades por área e encarregado</div>
  </div>
  <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalPlan">
    <i class="bi bi-plus-lg me-2"></i>Nova Atividade
  </button>
</div>

<?php if ($flash['msg']): ?>
<div class="alert alert-<?=$flash['tipo']?> alert-dismissible fade show mb-4">
  <i class="bi bi-<?=$flash['tipo']==='success'?'check-circle':'exclamation-circle'?> me-2"></i>
  <?=htmlspecialchars($flash['msg'])?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Linha de resumo dos meses -->
<div class="d-flex gap-2 mb-4 overflow-auto pb-1">
<?php foreach ($meses as $m): $isCurrent = $m['val'] === $mesAtual; $cnt = $resumoMeses[$m['val']] ?? 0; ?>
  <a href="?mes=<?=$m['val']?>"
     class="text-decoration-none flex-shrink-0"
     style="min-width:100px;">
    <div class="card p-3 text-center <?=$isCurrent?'border-primary bg-primary text-white shadow':''?>" style="border-radius:12px;transition:.15s;">
      <div style="font-size:18px;font-weight:800;"><?=$cnt?></div>
      <div style="font-size:11px;font-weight:600;opacity:<?=$isCurrent?'1':'.7'?>;margin-top:2px;"><?=$m['label']?></div>
    </div>
  </a>
<?php endforeach; ?>
</div>

<!-- Navegação de mês -->
<?php
$mesPrevTs = strtotime($mesAtual.'-01 -1 month');
$mesNextTs = strtotime($mesAtual.'-01 +1 month');
$mesPrev   = date('Y-m', $mesPrevTs);
$mesNext   = date('Y-m', $mesNextTs);
$mesLabel  = date('F Y', strtotime($mesAtual.'-01'));
?>
<div class="d-flex align-items-center gap-3 mb-4">
  <a href="?mes=<?=$mesPrev?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-chevron-left"></i></a>
  <h5 class="mb-0 fw-bold" style="color:#1e293b;"><?=htmlspecialchars($mesLabel)?></h5>
  <a href="?mes=<?=$mesNext?>" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-chevron-right"></i></a>
  <span class="badge bg-primary rounded-pill ms-1"><?=count($ativsDoMes)?> atividade(s)</span>
</div>

<!-- Atividades por área -->
<?php if (empty($ativsDoMes)): ?>
<div class="card p-5 text-center" style="border-radius:16px;border:2px dashed #e2e8f0;">
  <i class="bi bi-calendar-x" style="font-size:48px;color:#cbd5e1;"></i>
  <p class="text-muted mt-3 mb-3">Nenhuma atividade planejada para <?=htmlspecialchars($mesLabel)?></p>
  <button class="btn btn-primary rounded-pill px-4 mx-auto" style="width:fit-content;" data-bs-toggle="modal" data-bs-target="#modalPlan">
    <i class="bi bi-plus-lg me-2"></i>Planejar atividade
  </button>
</div>

<?php else: ?>
<div class="row g-3">
<?php foreach ($porArea as $area => $itens): ?>
<div class="col-md-6 col-xl-4">
  <div class="card h-100" style="border-radius:14px;border:none;box-shadow:0 2px 10px rgba(0,0,0,.06);">
    <div class="card-header border-0 pt-3 pb-2 px-4" style="background:transparent;">
      <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <div style="width:10px;height:10px;border-radius:50%;background:var(--crcc-primary);"></div>
          <h6 class="mb-0 fw-bold" style="color:#1e293b;"><?=htmlspecialchars($area)?></h6>
        </div>
        <span class="badge bg-light text-dark rounded-pill"><?=count($itens)?></span>
      </div>
    </div>
    <div class="card-body pt-1 px-3 pb-3">
    <?php foreach ($itens as $item):
      $atrasada = $item['data_prevista'] < $hoje && !in_array($item['status'], ['concluida','cancelada']);
    ?>
    <div class="p-3 mb-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;position:relative;">
      <!-- Prioridade badge -->
      <div class="d-flex justify-content-between align-items-start mb-2">
        <span class="badge bg-<?=$priorCor[$item['prioridade']]?> rounded-pill"><?=$priorLabel[$item['prioridade']]?></span>
        <div class="d-flex gap-1">
          <span class="badge bg-<?=$statusCor[$item['status']]?> rounded-pill" style="font-size:10px;"><?=$statusLabel[$item['status']]?></span>
          <?php if ($atrasada): ?><span class="badge bg-danger rounded-pill" style="font-size:10px;"><i class="bi bi-exclamation-triangle"></i></span><?php endif; ?>
        </div>
      </div>
      <!-- Título -->
      <div class="fw-semibold mb-1" style="font-size:14px;color:#1e293b;"><?=htmlspecialchars($item['titulo'])?></div>
      <!-- Meta -->
      <?php if ($item['obra_id']): ?><div class="text-muted mb-1" style="font-size:12px;"><i class="bi bi-building me-1"></i><?=htmlspecialchars($obraMap[$item['obra_id']]??'—')?></div><?php endif; ?>
      <?php if ($item['responsavel_id']): ?><div class="text-muted mb-2" style="font-size:12px;"><i class="bi bi-person me-1"></i><?=htmlspecialchars($userMap[$item['responsavel_id']]??'—')?></div><?php endif; ?>
      <?php if ($item['descricao']): ?><div class="text-muted" style="font-size:12px;font-style:italic;"><?=htmlspecialchars(mb_strimwidth($item['descricao'],0,80,'…'))?></div><?php endif; ?>
      <!-- Ações -->
      <div class="d-flex gap-1 mt-2 pt-2 border-top">
        <button class="btn btn-sm btn-outline-secondary flex-fill btn-editar"
          data-id="<?=$item['id']?>"
          data-titulo="<?=htmlspecialchars($item['titulo'],ENT_QUOTES)?>"
          data-obra="<?=(int)($item['obra_id']??0)?>"
          data-area="<?=htmlspecialchars($item['area'],ENT_QUOTES)?>"
          data-resp="<?=(int)($item['responsavel_id']??0)?>"
          data-mes="<?=substr($item['data_prevista'],0,7)?>"
          data-prior="<?=$item['prioridade']?>"
          data-desc="<?=htmlspecialchars($item['descricao']??'',ENT_QUOTES)?>">
          <i class="bi bi-pencil me-1"></i>Editar
        </button>
        <form method="POST" class="flex-fill" onsubmit="return confirm('Excluir esta atividade?')">
          <input type="hidden" name="acao" value="excluir">
          <input type="hidden" name="id" value="<?=$item['id']?>">
          <button class="btn btn-sm btn-outline-danger w-100"><i class="bi bi-trash me-1"></i>Excluir</button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Modal Nova / Editar Atividade -->
<div class="modal fade" id="modalPlan" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius:20px;overflow:hidden;">
      <form method="POST">
        <input type="hidden" name="acao" id="planAcao" value="criar">
        <input type="hidden" name="id"   id="planId"   value="">

        <div class="modal-header border-0 px-4 pt-4 pb-3" style="background:linear-gradient(135deg,#0D47A1,#1565C0);">
          <div>
            <h5 class="modal-title fw-bold text-white mb-1">
              <i class="bi bi-calendar-plus me-2"></i><span id="planTitulo">Nova Atividade Planejada</span>
            </h5>
            <p class="text-white mb-0" style="opacity:.7;font-size:13px;">Programe atividades para encarregados por área</p>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body px-4 py-4">
          <div class="row g-3">

            <!-- Título -->
            <div class="col-12">
              <label class="form-label fw-semibold">Título da atividade *</label>
              <input type="text" name="titulo" id="planTituloInput" class="form-control form-control-lg"
                     placeholder="Ex: Manutenção preventiva dos inversores — Bloco A" required>
            </div>

            <!-- Área + Encarregado -->
            <div class="col-md-5">
              <label class="form-label fw-semibold">Área *</label>
              <select name="area" id="planArea" class="form-select" required onchange="filtrarEncs()">
                <option value="">— Selecione a área —</option>
                <?php foreach ($areas as $a): ?>
                <option value="<?=$a?>"><?=$a?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-7">
              <label class="form-label fw-semibold">Encarregado responsável</label>
              <select name="responsavel_id" id="planResp" class="form-select">
                <option value="">— Selecione a área primeiro —</option>
              </select>
            </div>

            <!-- Obra -->
            <div class="col-md-6">
              <label class="form-label fw-semibold">Obra <span class="text-muted fw-normal">(opcional)</span></label>
              <select name="obra_id" id="planObra" class="form-select">
                <option value="">— Nenhuma —</option>
                <?php foreach ($obras as $o): ?>
                <option value="<?=$o['id']?>"><?=htmlspecialchars($o['nome'])?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Mês de execução -->
            <div class="col-md-3">
              <label class="form-label fw-semibold">Mês de execução *</label>
              <input type="month" name="mes_execucao" id="planMes" class="form-control"
                     value="<?=$mesAtual?>" required>
            </div>

            <!-- Prioridade -->
            <div class="col-md-3">
              <label class="form-label fw-semibold">Prioridade</label>
              <select name="prioridade" id="planPrior" class="form-select">
                <option value="baixa">🟢 Baixa</option>
                <option value="media" selected>🟡 Média</option>
                <option value="alta">🔴 Alta</option>
              </select>
            </div>

            <!-- Descrição -->
            <div class="col-12">
              <label class="form-label fw-semibold">Descrição <span class="text-muted fw-normal">(opcional)</span></label>
              <textarea name="descricao" id="planDesc" class="form-control" rows="3"
                        placeholder="Detalhes, escopo, materiais necessários..."></textarea>
            </div>

          </div>
        </div>

        <div class="modal-footer border-0 px-4 pb-4 pt-0 gap-2">
          <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-check-lg me-2"></i>Salvar Atividade
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Dados dos encarregados com área
const encs = <?= json_encode(array_values(array_map(fn($u) => [
    'id'   => $u['id'],
    'nome' => $u['nome'],
    'area' => $u['area'] ?? '',
], array_filter($usuarios, fn($u) => $u['nivel_acesso'] >= 3)))) ?>;

function filtrarEncs(areaForce, respForce) {
  const area = areaForce ?? document.getElementById('planArea').value;
  const sel  = document.getElementById('planResp');
  const prev = respForce ?? sel.value;
  sel.innerHTML = '<option value="">— Sem responsável —</option>';
  const lista = area ? encs.filter(e => e.area === area) : encs;
  if (!lista.length && area) {
    sel.innerHTML = '<option value="">Nenhum encarregado nesta área</option>';
    return;
  }
  lista.forEach(e => {
    const o = document.createElement('option');
    o.value = e.id;
    o.textContent = e.nome + (e.area ? ' (' + e.area + ')' : '');
    if (String(e.id) === String(prev)) o.selected = true;
    sel.appendChild(o);
  });
}

// Editar
document.querySelectorAll('.btn-editar').forEach(b => {
  b.addEventListener('click', () => {
    document.getElementById('planAcao').value       = 'editar';
    document.getElementById('planId').value         = b.dataset.id;
    document.getElementById('planTituloInput').value= b.dataset.titulo;
    document.getElementById('planObra').value       = b.dataset.obra;
    document.getElementById('planMes').value        = b.dataset.mes;
    document.getElementById('planPrior').value      = b.dataset.prior;
    document.getElementById('planDesc').value       = b.dataset.desc;
    document.getElementById('planArea').value       = b.dataset.area;
    document.getElementById('planTitulo').textContent = 'Editar Atividade';
    filtrarEncs(b.dataset.area, b.dataset.resp);
    new bootstrap.Modal(document.getElementById('modalPlan')).show();
  });
});

// Reset
document.getElementById('modalPlan').addEventListener('hidden.bs.modal', () => {
  document.getElementById('planAcao').value       = 'criar';
  document.getElementById('planId').value         = '';
  document.getElementById('planTituloInput').value= '';
  document.getElementById('planObra').value       = '';
  document.getElementById('planArea').value       = '';
  document.getElementById('planMes').value        = '<?=$mesAtual?>';
  document.getElementById('planPrior').value      = 'media';
  document.getElementById('planDesc').value       = '';
  document.getElementById('planTitulo').textContent = 'Nova Atividade Planejada';
  filtrarEncs('', '');
});
</script>

<?php require __DIR__ . '/../includes/layout-footer.php'; ?>
