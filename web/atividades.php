<?php
/**
 * Programação de Atividades — Ordens de Serviço (OS)
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();
ModuleManager::require('construtora');

$supabase   = new Supabase();
$tenantId   = TenantManager::id();
$nivel      = getNivelAcesso();
$podeEditar = $nivel <= 2;
$flash      = ['tipo' => '', 'msg' => ''];

// ─── Ações POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'criar' || $acao === 'editar') {
        if (!$podeEditar) {
            $flash = ['tipo' => 'danger', 'msg' => 'Sem permissão para esta operação.'];
        } else {
            $obra_id        = (int) ($_POST['obra_id'] ?? 0);
            $area           = trim($_POST['area'] ?? '');
            $titulo         = trim($_POST['titulo'] ?? '');
            $descricao      = trim($_POST['descricao'] ?? '');
            $responsavel_id = (int) ($_POST['responsavel_id'] ?? 0) ?: null;
            $data_prevista  = $_POST['data_prevista'] ?? '';
            $prioridade     = $_POST['prioridade'] ?? 'media';
            $status         = $_POST['status'] ?? 'pendente';

            if (!$obra_id || !$area || !$titulo) {
                $flash = ['tipo' => 'danger', 'msg' => 'Obra, área e título são obrigatórios.'];
            } else {
                $payload = [
                    'tenant_id'      => $tenantId,
                    'obra_id'        => $obra_id,
                    'area'           => $area,
                    'titulo'         => $titulo,
                    'descricao'      => $descricao ?: null,
                    'responsavel_id' => $responsavel_id,
                    'data_prevista'  => $data_prevista ?: null,
                    'prioridade'     => $prioridade,
                    'status'         => $status,
                ];

                if ($acao === 'criar') {
                    $res = $supabase->request('POST', '/rest/v1/ordens_servico', $payload);
                    $flash = isset($res[0]['id'])
                        ? ['tipo' => 'success', 'msg' => 'OS criada com sucesso.']
                        : ['tipo' => 'danger',  'msg' => 'Erro ao criar OS.'];
                } else {
                    $id  = (int) ($_POST['id'] ?? 0);
                    $supabase->request('PATCH',
                        '/rest/v1/ordens_servico?id=eq.' . $id . '&tenant_id=eq.' . $tenantId,
                        $payload
                    );
                    $flash = ['tipo' => 'success', 'msg' => 'OS atualizada.'];
                }
            }
        }
    }

    if ($acao === 'excluir' && $podeEditar) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $supabase->request('DELETE',
                '/rest/v1/ordens_servico?id=eq.' . $id . '&tenant_id=eq.' . $tenantId
            );
            $flash = ['tipo' => 'success', 'msg' => 'OS excluída.'];
        }
    }
}

// ─── Dados para selects ───────────────────────────────────────────────────────
$obras = $supabase->request('GET', '/rest/v1/obras', null, [
    'tenant_id' => 'eq.' . $tenantId,
    'status'    => 'neq.concluida',
    'select'    => 'id,nome',
    'order'     => 'nome.asc',
]) ?: [];

$usuarios = $supabase->request('GET', '/rest/v1/users', null, [
    'tenant_id' => 'eq.' . $tenantId,
    'select'    => 'id,nome,area',
    'order'     => 'nome.asc',
]) ?: [];

// ─── Filtros ──────────────────────────────────────────────────────────────────
$filtroObra   = $_GET['obra_id']  ?? '';
$filtroArea   = $_GET['area']     ?? '';
$filtroStatus = $_GET['status']   ?? '';

$osParams = [
    'tenant_id' => 'eq.' . $tenantId,
    'select'    => 'id,obra_id,area,titulo,descricao,responsavel_id,data_prevista,status,prioridade,criado_em',
    'order'     => 'criado_em.desc',
];
if ($filtroObra)   $osParams['obra_id'] = 'eq.' . (int) $filtroObra;
if ($filtroArea)   $osParams['area']    = 'eq.' . $filtroArea;
if ($filtroStatus) $osParams['status']  = 'eq.' . $filtroStatus;

$ordens = $supabase->request('GET', '/rest/v1/ordens_servico', null, $osParams) ?: [];

// Mapas auxiliares
$obraMap    = array_column($obras,    'nome', 'id');
$usuarioMap = array_column($usuarios, 'nome', 'id');

$statusLabels = [
    'pendente'     => ['label' => 'Pendente',     'cor' => 'secondary'],
    'em_andamento' => ['label' => 'Em andamento', 'cor' => 'primary'],
    'concluida'    => ['label' => 'Concluída',    'cor' => 'success'],
    'cancelada'    => ['label' => 'Cancelada',    'cor' => 'danger'],
];
$prioridadeLabels = [
    'baixa'  => ['label' => 'Baixa',  'cor' => 'secondary'],
    'media'  => ['label' => 'Média',  'cor' => 'warning'],
    'alta'   => ['label' => 'Alta',   'cor' => 'danger'],
];
$areas = ['Elétrica', 'Civil', 'Montagem'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividades — <?= htmlspecialchars(getConfigSistema('nome_sistema', 'Zetta')) ?></title>
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

            <!-- Cabeçalho -->
            <div class="d-flex justify-content-between flex-wrap align-items-center pb-3 mb-3">
                <div>
                    <h1 class="h3 mb-1" style="font-weight:700; color:#0F172A; letter-spacing:-.02em;">
                        <i class="bi bi-clipboard-plus me-2 text-primary"></i>Atividades
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Programação de Ordens de Serviço</p>
                </div>
                <?php if ($podeEditar): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalOS">
                    <i class="bi bi-plus-lg me-1"></i> Nova OS
                </button>
                <?php endif; ?>
            </div>

            <!-- Flash -->
            <?php if ($flash['msg']): ?>
            <div class="alert alert-<?= $flash['tipo'] ?> alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($flash['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Card de OS -->
            <div class="crcc-card">
                <div>

                <!-- Filtros dentro do card -->
                <form method="GET" class="crcc-filters">
                    <select name="obra_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas as obras</option>
                        <?php foreach ($obras as $o): ?>
                        <option value="<?= $o['id'] ?>" <?= $filtroObra == $o['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="area" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todas as áreas</option>
                        <?php foreach ($areas as $a): ?>
                        <option value="<?= $a ?>" <?= $filtroArea === $a ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos os status</option>
                        <?php foreach ($statusLabels as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $filtroStatus === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filtroObra || $filtroArea || $filtroStatus): ?>
                    <a href="atividades.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($ordens)): ?>
                <div class="crcc-empty">
                    <i class="bi bi-clipboard"></i>
                    <p>Nenhuma OS encontrada.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table crcc-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:24px;">OS / Título</th>
                                <th>Obra</th>
                                <th>Área</th>
                                <th>Responsável</th>
                                <th>Prazo</th>
                                <th>Prioridade</th>
                                <th>Status</th>
                                <?php if ($podeEditar): ?><th class="text-end" style="padding-right:24px;">Ações</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($ordens as $os):
                            $st  = $statusLabels[$os['status']]         ?? ['label' => $os['status'],     'cor' => 'secondary'];
                            $pr  = $prioridadeLabels[$os['prioridade']] ?? ['label' => $os['prioridade'], 'cor' => 'secondary'];
                            $prazoClass = '';
                            if ($os['data_prevista'] && $os['status'] === 'em_andamento') {
                                $dias = (strtotime($os['data_prevista']) - time()) / 86400;
                                if ($dias < 0)       $prazoClass = 'text-danger fw-semibold';
                                elseif ($dias <= 3)  $prazoClass = 'text-warning fw-semibold';
                            }
                        ?>
                            <tr>
                                <td style="padding-left:24px;">
                                    <div class="cell-primary"><?= htmlspecialchars($os['titulo']) ?></div>
                                    <?php if ($os['descricao']): ?>
                                    <div class="cell-muted"><?= htmlspecialchars(mb_strimwidth($os['descricao'], 0, 60, '…')) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($obraMap[$os['obra_id']] ?? '—') ?></td>
                                <td><?= htmlspecialchars($os['area']) ?></td>
                                <td><?= htmlspecialchars($usuarioMap[$os['responsavel_id']] ?? '—') ?></td>
                                <td class="<?= $prazoClass ?>">
                                    <?= $os['data_prevista'] ? date('d/m/Y', strtotime($os['data_prevista'])) : '—' ?>
                                </td>
                                <td><span class="badge crcc-badge bg-<?= $pr['cor'] ?>"><?= $pr['label'] ?></span></td>
                                <td><span class="badge crcc-badge bg-<?= $st['cor'] ?>"><?= $st['label'] ?></span></td>
                                <?php if ($podeEditar): ?>
                                <td class="text-end" style="padding-right:24px;">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-sm btn-outline-secondary btn-editar-os"
                                                data-id="<?= $os['id'] ?>"
                                                data-obra="<?= $os['obra_id'] ?>"
                                                data-area="<?= htmlspecialchars($os['area'], ENT_QUOTES) ?>"
                                                data-titulo="<?= htmlspecialchars($os['titulo'], ENT_QUOTES) ?>"
                                                data-descricao="<?= htmlspecialchars($os['descricao'] ?? '', ENT_QUOTES) ?>"
                                                data-responsavel="<?= (int)($os['responsavel_id'] ?? 0) ?>"
                                                data-prevista="<?= htmlspecialchars($os['data_prevista'] ?? '') ?>"
                                                data-prioridade="<?= htmlspecialchars($os['prioridade']) ?>"
                                                data-status="<?= htmlspecialchars($os['status']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline form-excluir-os">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= $os['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <?php endif; ?>
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

<!-- Modal Nova / Editar OS -->
<div class="modal fade" id="modalOS" tabindex="-1" aria-labelledby="modalOSLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="formOS">
                <input type="hidden" name="acao" id="osAcao" value="criar">
                <input type="hidden" name="id"   id="osId"   value="">

                <div class="modal-header text-white border-0"
                     style="background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: .375rem .375rem 0 0;">
                    <h5 class="modal-title fw-bold" id="modalOSLabel">
                        <i class="bi bi-clipboard-plus me-2"></i>
                        <span id="osTitulo">Nova Ordem de Serviço</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body bg-white px-4 py-3">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Obra <span class="text-danger">*</span></label>
                            <select name="obra_id" id="osObraId" class="form-select" required>
                                <option value="">— Selecione a obra —</option>
                                <?php foreach ($obras as $o): ?>
                                <option value="<?= $o['id'] ?>"><?= htmlspecialchars($o['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Área <span class="text-danger">*</span></label>
                            <select name="area" id="osArea" class="form-select" required>
                                <option value="">— Selecione a área —</option>
                                <?php foreach ($areas as $a): ?>
                                <option value="<?= $a ?>"><?= $a ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Título da OS <span class="text-danger">*</span></label>
                            <input type="text" name="titulo" id="osTituloInput" class="form-control"
                                   placeholder="Ex: Instalação de painéis elétricos — Bloco A" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Descrição</label>
                            <textarea name="descricao" id="osDescricao" class="form-control" rows="3"
                                      placeholder="Detalhes da atividade (opcional)"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Responsável</label>
                            <select name="responsavel_id" id="osResponsavel" class="form-select">
                                <option value="">— Sem responsável —</option>
                                <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>">
                                    <?= htmlspecialchars($u['nome']) ?>
                                    <?= $u['area'] ? '(' . htmlspecialchars($u['area']) . ')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Data prevista</label>
                            <input type="date" name="data_prevista" id="osDataPrevista" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Prioridade</label>
                            <select name="prioridade" id="osPrioridade" class="form-select">
                                <option value="baixa">Baixa</option>
                                <option value="media" selected>Média</option>
                                <option value="alta">Alta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Status</label>
                            <select name="status" id="osStatus" class="form-select">
                                <option value="pendente" selected>Pendente</option>
                                <option value="em_andamento">Em andamento</option>
                                <option value="concluida">Concluída</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>
                        <span id="osBtnTexto">Criar OS</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Preenche modal para edição
document.querySelectorAll('.btn-editar-os').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('osAcao').value       = 'editar';
        document.getElementById('osId').value         = btn.dataset.id;
        document.getElementById('osObraId').value     = btn.dataset.obra;
        document.getElementById('osArea').value       = btn.dataset.area;
        document.getElementById('osTituloInput').value= btn.dataset.titulo;
        document.getElementById('osDescricao').value  = btn.dataset.descricao;
        document.getElementById('osResponsavel').value= btn.dataset.responsavel;
        document.getElementById('osDataPrevista').value= btn.dataset.prevista;
        document.getElementById('osPrioridade').value = btn.dataset.prioridade;
        document.getElementById('osStatus').value     = btn.dataset.status;
        document.getElementById('osTitulo').textContent  = 'Editar OS';
        document.getElementById('osBtnTexto').textContent = 'Salvar Alterações';
        new bootstrap.Modal(document.getElementById('modalOS')).show();
    });
});

// Reset modal ao fechar
document.getElementById('modalOS').addEventListener('hidden.bs.modal', function() {
    document.getElementById('formOS').reset();
    document.getElementById('osAcao').value = 'criar';
    document.getElementById('osId').value   = '';
    document.getElementById('osTitulo').textContent   = 'Nova Ordem de Serviço';
    document.getElementById('osBtnTexto').textContent = 'Criar OS';
});

// Confirmação de exclusão
document.querySelectorAll('.form-excluir-os').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!confirm('Excluir esta OS? Esta ação não pode ser desfeita.')) e.preventDefault();
    });
});
</script>
</body>
</html>
