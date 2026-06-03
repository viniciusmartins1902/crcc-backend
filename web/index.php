<?php
require_once __DIR__ . '/../auth.php';
requerLogin();

$supabase = new Supabase();
$tenantId = DEFAULT_TENANT_ID;
$podeEditar = podePlanejar();
$flash = ['tipo'=>'','msg'=>''];

if ($_SERVER['REQUEST_METHOD']==='POST' && $podeEditar) {
    $acao = $_POST['acao'] ?? '';
    if ($acao==='criar'||$acao==='editar') {
        $nome       = trim($_POST['nome'] ?? '');
        $status     = $_POST['status'] ?? 'em_andamento';
        $dt_inicio  = $_POST['data_inicio'] ?? null;
        $dt_fim     = $_POST['data_fim'] ?? null;
        if (!$nome) { $flash=['tipo'=>'danger','msg'=>'Nome é obrigatório.']; }
        else {
            $payload = ['tenant_id'=>$tenantId,'nome'=>$nome,'status'=>$status,'data_inicio'=>$dt_inicio?:null,'data_fim'=>$dt_fim?:null];
            if ($acao==='criar') { $supabase->request('POST','/rest/v1/obras',$payload); $flash=['tipo'=>'success','msg'=>'Obra criada.']; }
            else { $id=(int)($_POST['id']??0); $supabase->request('PATCH','/rest/v1/obras?id=eq.'.$id.'&tenant_id=eq.'.$tenantId,$payload); $flash=['tipo'=>'success','msg'=>'Obra atualizada.']; }
        }
    } elseif ($acao==='excluir') {
        $id=(int)($_POST['id']??0);
        if ($id) { $supabase->request('DELETE','/rest/v1/obras?id=eq.'.$id.'&tenant_id=eq.'.$tenantId,null); $flash=['tipo'=>'success','msg'=>'Obra excluída.']; }
    }
}

$obras = $supabase->request('GET','/rest/v1/obras',null,['tenant_id'=>'eq.'.$tenantId,'select'=>'id,nome,status,data_inicio,data_fim','order'=>'nome.asc']) ?: [];
$statusLabels = ['em_andamento'=>['Andamento','primary'],'paralisada'=>['Paralisada','warning'],'concluida'=>['Concluída','success'],'cancelada'=>['Cancelada','danger']];
$titulo='Obras'; $paginaAtiva='obras';
require __DIR__ . '/../includes/layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><div class="page-title">Obras</div><div class="page-sub"><?=count($obras)?> obra(s) cadastrada(s)</div></div>
  <?php if($podeEditar): ?><button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalObra"><i class="bi bi-plus-lg me-2"></i>Nova Obra</button><?php endif; ?>
</div>
<?php if($flash['msg']): ?><div class="alert alert-<?=$flash['tipo']?> alert-dismissible fade show"><i class="bi bi-<?=$flash['tipo']==='success'?'check-circle':'exclamation-circle'?> me-2"></i><?=htmlspecialchars($flash['msg'])?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<div class="card crcc-table">
  <div class="table-responsive">
    <table class="table crcc-table mb-0">
      <thead><tr><th>Nome</th><th>Status</th><th>Início</th><th>Previsão de término</th><?php if($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?></tr></thead>
      <tbody>
      <?php foreach($obras as $o): [$sl,$sc]=$statusLabels[$o['status']]??['—','secondary']; ?>
      <tr>
        <td class="fw-semibold"><?=htmlspecialchars($o['nome'])?></td>
        <td><span class="badge bg-<?=$sc?>"><?=$sl?></span></td>
        <td class="text-muted"><?=$o['data_inicio']?date('d/m/Y',strtotime($o['data_inicio'])):'—'?></td>
        <td class="text-muted"><?=$o['data_fim']?date('d/m/Y',strtotime($o['data_fim'])):'—'?></td>
        <?php if($podeEditar): ?>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary btn-editar" data-id="<?=$o['id']?>" data-nome="<?=htmlspecialchars($o['nome'],ENT_QUOTES)?>" data-status="<?=$o['status']?>" data-inicio="<?=$o['data_inicio']??''?>" data-fim="<?=$o['data_fim']??''?>"><i class="bi bi-pencil"></i></button>
          <form method="POST" class="d-inline" onsubmit="return confirm('Excluir esta obra?')"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?=$o['id']?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($obras)): ?><tr><td colspan="5" class="text-center text-muted py-5">Nenhuma obra cadastrada.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php if($podeEditar): ?>
<div class="modal fade" id="modalObra" tabindex="-1"><div class="modal-dialog"><div class="modal-content border-0 shadow">
  <form method="POST"><input type="hidden" name="acao" id="obraAcao" value="criar"><input type="hidden" name="id" id="obraId" value="">
  <div class="modal-header" style="background:linear-gradient(135deg,#1565C0,#0D47A1);color:#fff;border:none;">
    <h5 class="modal-title fw-bold"><i class="bi bi-building me-2"></i><span id="obraTitulo">Nova Obra</span></h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body p-4">
    <div class="mb-3"><label class="form-label fw-semibold">Nome *</label><input type="text" name="nome" id="obraNome" class="form-control" required></div>
    <div class="row g-3">
      <div class="col-md-4"><label class="form-label fw-semibold">Status</label>
        <select name="status" id="obraStatus" class="form-select">
          <option value="em_andamento">Em andamento</option><option value="paralisada">Paralisada</option><option value="concluida">Concluída</option><option value="cancelada">Cancelada</option>
        </select>
      </div>
      <div class="col-md-4"><label class="form-label fw-semibold">Início</label><input type="date" name="data_inicio" id="obraInicio" class="form-control"></div>
      <div class="col-md-4"><label class="form-label fw-semibold">Término</label><input type="date" name="data_fim" id="obraFim" class="form-control"></div>
    </div>
  </div>
  <div class="modal-footer bg-light"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary px-4">Salvar</button></div>
  </form>
</div></div></div>
<script>
document.querySelectorAll('.btn-editar').forEach(b=>{
  b.addEventListener('click',()=>{
    document.getElementById('obraAcao').value='editar';
    document.getElementById('obraId').value=b.dataset.id;
    document.getElementById('obraNome').value=b.dataset.nome;
    document.getElementById('obraStatus').value=b.dataset.status;
    document.getElementById('obraInicio').value=b.dataset.inicio;
    document.getElementById('obraFim').value=b.dataset.fim;
    document.getElementById('obraTitulo').textContent='Editar Obra';
    new bootstrap.Modal(document.getElementById('modalObra')).show();
  });
});
document.getElementById('modalObra').addEventListener('hidden.bs.modal',()=>{
  document.getElementById('obraAcao').value='criar';
  document.getElementById('obraId').value='';
  document.getElementById('obraNome').value='';
  document.getElementById('obraStatus').value='em_andamento';
  document.getElementById('obraInicio').value='';
  document.getElementById('obraFim').value='';
  document.getElementById('obraTitulo').textContent='Nova Obra';
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/layout-footer.php'; ?>
