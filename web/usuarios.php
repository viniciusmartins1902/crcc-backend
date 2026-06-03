<?php
require_once __DIR__ . '/../auth.php';
requerLogin();

$supabase = new Supabase();
$usuarios = $supabase->request('GET','/rest/v1/users',null,['tenant_id'=>'eq.'.DEFAULT_TENANT_ID,'select'=>'id,nome,email,nivel_acesso,funcao,area','order'=>'nome.asc']) ?: [];

$niveis = [1=>['Planejamento','primary'],2=>['Gestor','success'],3=>['Encarregado','warning'],4=>['Profissional','secondary']];
$titulo='Usuários'; $paginaAtiva='usuarios';
require __DIR__ . '/../includes/layout.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div><div class="page-title">Usuários</div><div class="page-sub"><?=count($usuarios)?> usuário(s) cadastrado(s) · Gerenciamento via painel Zetta</div></div>
</div>
<div class="card crcc-table">
  <div class="table-responsive">
    <table class="table crcc-table mb-0">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Área</th><th>Função</th><th>Nível</th></tr></thead>
      <tbody>
      <?php foreach($usuarios as $u): [$nl,$nc]=$niveis[$u['nivel_acesso']]??['—','secondary']; ?>
      <tr>
        <td class="fw-semibold"><?=htmlspecialchars($u['nome'])?></td>
        <td class="text-muted"><?=htmlspecialchars($u['email'])?></td>
        <td><?=htmlspecialchars($u['area']??'—')?></td>
        <td><?=htmlspecialchars($u['funcao']??'—')?></td>
        <td><span class="badge bg-<?=$nc?>"><?=$nl?></span></td>
      </tr>
      <?php endforeach; ?>
      <?php if(empty($usuarios)): ?><tr><td colspan="5" class="text-center text-muted py-5">Nenhum usuário.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require __DIR__ . '/../includes/layout-footer.php'; ?>
