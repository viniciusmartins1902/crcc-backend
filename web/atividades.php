<?php
require_once __DIR__ . '/../auth.php';
requerLogin();

$supabase   = new Supabase();
$tenantId   = DEFAULT_TENANT_ID;
$podeEditar = podePlanejar();
$flash      = ['tipo'=>'','msg'=>''];

if ($_SERVER['REQUEST_METHOD']==='POST' && $podeEditar) {
    $acao = $_POST['acao'] ?? '';
    if ($acao==='criar'||$acao==='editar') {
        $obra_id   = (int)($_POST['obra_id']??0);
        $area      = trim($_POST['area']??'');
        $titulo_os = trim($_POST['titulo']??'');
        $resp_id   = (int)($_POST['responsavel_id']??0)?:null;
        $dt_prev   = $_POST['data_prevista']??'';
        $prioridade= $_POST['prioridade']??'media';
        $status    = $_POST['status']??'pendente';
        if (!$obra_id||!$area||!$titulo_os) { $flash=['tipo'=>'danger','msg'=>'Obra, área e título são obrigatórios.']; }
        else {
            $payload=['tenant_id'=>$tenantId,'obra_id'=>$obra_id,'area'=>$area,'titulo'=>$titulo_os,'responsavel_id'=>$resp_id,'data_prevista'=>$dt_prev?:null,'prioridade'=>$prioridade,'status'=>$status];
            if ($acao==='criar') { $supabase->request('POST','/rest/v1/ordens_servico',$payload); $flash=['tipo'=>'success','msg'=>'OS criada.']; }
            else { $id=(int)($_POST['id']??0); $supabase->request('PATCH','/rest/v1/ordens_servico?id=eq.'.$id.'&tenant_id=eq.'.$tenantId,$payload); $flash=['tipo'=>'success','msg'=>'OS atualizada.']; }
        }
    } elseif ($acao==='excluir') {
        $id=(int)($_POST['id']??0);
        if ($id) { $supabase->request('DELETE','/rest/v1/ordens_servico?id=eq.'.$id.'&tenant_id=eq.'.$tenantId,null); $flash=['tipo'=>'success','msg'=>'OS excluída.']; }
    }
}

$filtroObra=$_GET['obra_id']??''; $filtroArea=$_GET['area']??''; $filtroStatus=$_GET['status']??'';
$osParams=['tenant_id'=>'eq.'.$tenantId,'select'=>'id,obra_id,area,titulo,responsavel_id,data_prevista,status,prioridade,criado_em','order'=>'criado_em.desc'];
if ($filtroObra) $osParams['obra_id']='eq.'.(int)$filtroObra;
if ($filtroArea) $osParams['area']='eq.'.$filtroArea;
if ($filtroStatus) $osParams['status']='eq.'.$filtroStatus;
$ordens=$supabase->request('GET','/rest/v1/ordens_servico',null,$osParams)?:[];

$obras=$supabase->request('GET','/rest/v1/obras',null,['tenant_id'=>'eq.'.$tenantId,'select'=>'id,nome','status'=>'neq.concluida','order'=>'nome.asc'])?:[];
$usuarios=$supabase->request('GET','/rest/v1/users',null,['tenant_id'=>'eq.'.$tenantId,'select'=>'id,nome,area,nivel_acesso','order'=>'nome.asc'])?:[];
$encarregados=array_filter($usuarios,fn($u)=>$u['nivel_acesso']>=3);

$obraMap=array_column($obras,'nome','id');
$userMap=array_column($usuarios,'nome','id');
$areas=['Elétrica','Civil','Montagem','O&M'];
$statusLabels=['pendente'=>['Pendente','secondary'],'em_andamento'=>['Em andamento','primary'],'concluida'=>['Concluída','success'],'cancelada'=>['Cancelada','danger']];
$priorLabels=['alta'=>['Alta','danger'],'media'=>['Média','warning'],'baixa'=>['Baixa','secondary']];
$hoje=date('Y-m-d');
$titulo='Atividades / OS'; $paginaAtiva='atividades';
require __DIR__ . '/../includes/layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <div><div class="page-title">Atividades / OS</div><div class="page-sub"><?=count($ordens)?> ordem(ns) encontrada(s)</div></div>
  <?php if($podeEditar): ?><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalOS"><i class="bi bi-plus-lg me-2"></i>Nova OS</button><?php endif; ?>
</div>

<?php if($flash['msg']): ?><div class="alert alert-<?=$flash['tipo']?> alert-dismissible fade show mb-3"><i class="bi bi-<?=$flash['tipo']==='success'?'check-circle':'exclamation-circle'?> me-2"></i><?=htmlspecialchars($flash['msg'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

<!-- Filtros -->
<form method="GET" class="card p-3 mb-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3"><label class="form-label mb-1 small fw-semibold text-muted">Obra</label>
      <select name="obra_id" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todas as obras</option>
        <?php foreach($obras as $o): ?><option value="<?=$o['id']?>" <?=$filtroObra==$o['id']?'selected':''?>><?=htmlspecialchars($o['nome'])?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><label class="form-label mb-1 small fw-semibold text-muted">Área</label>
      <select name="area" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todas</option>
        <?php foreach($areas as $a): ?><option value="<?=$a?>" <?=$filtroArea===$a?'selected':''?>><?=$a?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2"><label class="form-label mb-1 small fw-semibold text-muted">Status</label>
      <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
        <option value="">Todos</option>
        <?php foreach($statusLabels as $k=>[$l,$c]): ?><option value="<?=$k?>" <?=$filtroStatus===$k?'selected':''?>><?=$l?></option><?php endforeach; ?>
      </select>
    </div>
    <?php if($filtroObra||$filtroArea||$filtroStatus): ?><div class="col-auto"><a href="atividades.php" class="btn btn-sm btn-outline-secondary">Limpar</a></div><?php endif; ?>
  </div>
</form>

<div class="card crcc-table">
  <div class="table-responsive">
    <table class="table crcc-table mb-0">
      <thead><tr><th>#</th><th>Título</th><th>Obra</th><th>Área</th><th>Responsável</th><th>Prioridade</th><th>Status</th><th>Prazo</th><?php if($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach($ordens as $os):
        [$sl,$sc]=$statusLabels[$os['status']]??['—','secondary'];
        [$pl,$pc]=$priorLabels[$os['prioridade']]??['—','secondary'];
        $atrasada=!empty($os['data_prevista'])&&$os['data_prevista']<$hoje&&$os['status']!=='concluida';
      ?>
      <tr>
        <td class="text-muted fw-bold">#<?=$os['id']?></td>
        <td><?=htmlspecialchars($os['titulo'])?></td>
        <td class="text-muted"><?=htmlspecialchars($obraMap[$os['obra_id']]??'—')?></td>
        <td><span class="badge bg-light text-dark"><?=htmlspecialchars($os['area'])?></span></td>
        <td class="text-muted"><?=htmlspecialchars($userMap[$os['responsavel_id']]??'—')?></td>
        <td><span class="badge bg-<?=$pc?>"><?=$pl?></span></td>
        <td><span class="badge bg-<?=$sc?>"><?=$sl?></span></td>
        <td class="<?=$atrasada?'text-danger fw-bold':'text-muted'?>"><?=$os['data_prevista']?date('d/m/Y',strtotime($os['data_prevista'])):'—'?><?=$atrasada?' <i class="bi bi-exclamation-triangle"></i>':''?></td>
        <?php if($podeEditar): ?>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary btn-editar"
            data-id="<?=$os['id']?>" data-obra="<?=$os['obra_id']?>" data-area="<?=htmlspecialchars($os['area'],ENT_QUOTES)?>"
            data-titulo="<?=htmlspecialchars($os['titulo'],ENT_QUOTES)?>" data-resp="<?=(int)($os['responsavel_id']??0)?>"
            data-prev="<?=htmlspecialchars($os['data_prevista']??'')?>" data-prior="<?=$os['prioridade']?>" data-status="<?=$os['status']?>">
            <i class="bi bi-pencil"></i>
          </button>
          <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta OS?')">
            <input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?=$os['id']?>">
            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($ordens)): ?><tr><td colspan="9" class="text-center text-muted py-5">Nenhuma OS encontrada.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if($podeEditar): ?>
<div class="modal fade" id="modalOS" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content border-0 shadow">
  <form method="POST"><input type="hidden" name="acao" id="osAcao" value="criar"><input type="hidden" name="id" id="osId" value="">
  <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;border:none;">
    <h5 class="modal-title fw-bold"><i class="bi bi-clipboard-plus me-2"></i><span id="osTitulo">Nova Ordem de Serviço</span></h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body p-4">
    <div class="row g-3">
      <div class="col-md-6"><label class="form-label fw-semibold">Obra *</label>
        <select name="obra_id" id="osObra" class="form-select" required>
          <option value="">— Selecione —</option>
          <?php foreach($obras as $o): ?><option value="<?=$o['id']?>"><?=htmlspecialchars($o['nome'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label fw-semibold">Área *</label>
        <select name="area" id="osArea" class="form-select" required onchange="filtrarEncarregados()">
          <option value="">— Selecione —</option>
          <?php foreach($areas as $a): ?><option value="<?=$a?>"><?=$a?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-12"><label class="form-label fw-semibold">Título *</label><input type="text" name="titulo" id="osTitulo2" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Encarregado / Responsável</label>
        <select name="responsavel_id" id="osResp" class="form-select">
          <option value="">— Selecione a área primeiro —</option>
          <?php foreach($encarregados as $u): ?>
          <option value="<?=$u['id']?>" data-area="<?=htmlspecialchars($u['area']??'')?>"><?=htmlspecialchars($u['nome'])?> <?=$u['area']?'('.$u['area'].')':''?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6"><label class="form-label fw-semibold">Data prevista</label><input type="date" name="data_prevista" id="osPrev" class="form-control"></div>
      <div class="col-md-6"><label class="form-label fw-semibold">Prioridade</label>
        <select name="prioridade" id="osPrior" class="form-select"><option value="baixa">Baixa</option><option value="media" selected>Média</option><option value="alta">Alta</option></select>
      </div>
      <div class="col-md-6"><label class="form-label fw-semibold">Status</label>
        <select name="status" id="osStatus" class="form-select"><option value="pendente" selected>Pendente</option><option value="em_andamento">Em andamento</option><option value="concluida">Concluída</option><option value="cancelada">Cancelada</option></select>
      </div>
    </div>
  </div>
  <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Salvar OS</button></div>
  </form>
</div></div></div>

<script>
// Encarregados com suas áreas
const encData = <?= json_encode(array_values(array_map(fn($u)=>['id'=>$u['id'],'nome'=>$u['nome'],'area'=>$u['area']??''],array_filter($usuarios,fn($u)=>$u['nivel_acesso']>=3)))) ?>;

function filtrarEncarregados() {
  const area = document.getElementById('osArea').value;
  const sel  = document.getElementById('osResp');
  const atual = sel.value;
  sel.innerHTML = '<option value="">— Sem responsável —</option>';
  const filtrados = area ? encData.filter(e => e.area === area) : encData;
  filtrados.forEach(e => {
    const opt = document.createElement('option');
    opt.value = e.id;
    opt.textContent = e.nome + (e.area ? ' (' + e.area + ')' : '');
    if (e.id == atual) opt.selected = true;
    sel.appendChild(opt);
  });
  if (!filtrados.length) sel.innerHTML = '<option value="">Nenhum encarregado nesta área</option>';
}

document.querySelectorAll('.btn-editar').forEach(b => {
  b.addEventListener('click', () => {
    document.getElementById('osAcao').value  = 'editar';
    document.getElementById('osId').value    = b.dataset.id;
    document.getElementById('osObra').value  = b.dataset.obra;
    document.getElementById('osArea').value  = b.dataset.area;
    document.getElementById('osTitulo2').value = b.dataset.titulo;
    document.getElementById('osPrev').value  = b.dataset.prev;
    document.getElementById('osPrior').value = b.dataset.prior;
    document.getElementById('osStatus').value= b.dataset.status;
    document.getElementById('osTitulo').textContent = 'Editar OS';
    filtrarEncarregados();
    document.getElementById('osResp').value  = b.dataset.resp;
    new bootstrap.Modal(document.getElementById('modalOS')).show();
  });
});
document.getElementById('modalOS').addEventListener('hidden.bs.modal', () => {
  document.getElementById('osAcao').value='criar';
  document.getElementById('osId').value='';
  document.getElementById('osObra').value='';
  document.getElementById('osArea').value='';
  document.getElementById('osTitulo2').value='';
  document.getElementById('osPrev').value='';
  document.getElementById('osPrior').value='media';
  document.getElementById('osStatus').value='pendente';
  document.getElementById('osTitulo').textContent='Nova Ordem de Serviço';
  filtrarEncarregados();
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/layout-footer.php'; ?>
