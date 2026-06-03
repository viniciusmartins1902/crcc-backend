<?php
/**
 * Painel Administrativo — Módulo Construtora
 * Gestão de Obras: listagem, criação, edição e exclusão.
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();
ModuleManager::require('construtora');

$supabase   = new Supabase();
$tenantId   = TenantManager::id();
$flash      = ['tipo' => '', 'msg' => ''];
$podeEditar = getNivelAcesso() <= 2; // Admin e Gestor podem criar/editar/excluir

// ─── Ações POST ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$podeEditar) {
        $flash = ['tipo' => 'danger', 'msg' => 'Sem permissão para esta operação.'];
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'criar' || $acao === 'editar') {
            $nome              = trim($_POST['nome'] ?? '');
            $descricao         = trim($_POST['descricao'] ?? '');
            $data_inicio       = $_POST['data_inicio'] ?? '';
            $data_fim_prevista = $_POST['data_fim_prevista'] ?? '';
            $status            = $_POST['status'] ?? 'em_andamento';

            if ($nome === '') {
                $flash = ['tipo' => 'danger', 'msg' => 'O nome da obra é obrigatório.'];
            } else {
                $payload = [
                    'tenant_id'         => $tenantId,
                    'nome'              => $nome,
                    'descricao'         => $descricao ?: null,
                    'data_inicio'       => $data_inicio ?: null,
                    'data_fim_prevista' => $data_fim_prevista ?: null,
                    'status'            => $status,
                ];

                if ($acao === 'criar') {
                    $res = $supabase->request('POST', '/rest/v1/obras', $payload);
                    $flash = isset($res[0]['id'])
                        ? ['tipo' => 'success', 'msg' => 'Obra criada com sucesso.']
                        : ['tipo' => 'danger',  'msg' => 'Erro ao criar obra.'];
                } else {
                    $id  = (int) ($_POST['id'] ?? 0);
                    $res = $supabase->request('PATCH', '/rest/v1/obras?id=eq.' . $id . '&tenant_id=eq.' . $tenantId, $payload);
                    $flash = ['tipo' => 'success', 'msg' => 'Obra atualizada.'];
                }
            }
        }

        if ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $supabase->request('DELETE', '/rest/v1/obras?id=eq.' . $id . '&tenant_id=eq.' . $tenantId);
                $flash = ['tipo' => 'success', 'msg' => 'Obra excluída.'];
            }
        }
    }
}

// ─── Buscar obras do tenant ───────────────────────────────────────────────────
$obras = $supabase->request('GET', '/rest/v1/obras', null, [
    'tenant_id' => 'eq.' . $tenantId,
    'order'     => 'criado_em.desc',
    'select'    => 'id,nome,descricao,data_inicio,data_fim_prevista,status,criado_em',
]);
if (!is_array($obras)) $obras = [];

// ─── Labels de status ─────────────────────────────────────────────────────────
$statusLabels = [
    'em_andamento' => ['label' => 'Em andamento', 'cor' => 'success'],
    'paralisada'   => ['label' => 'Paralisada',   'cor' => 'warning'],
    'concluida'    => ['label' => 'Concluída',     'cor' => 'secondary'],
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obras — <?= htmlspecialchars(getConfigSistema('nome_sistema', 'Zetta')) ?></title>
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
                        <i class="bi bi-building me-2 text-primary"></i>Obras
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Cadastro e gestão de obras da CRCC</p>
                </div>
                <?php if ($podeEditar): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalObra">
                    <i class="bi bi-plus-lg me-1"></i> Nova Obra
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

            <!-- Card de Obras -->
            <div class="crcc-card">
                <div>

                <?php if (empty($obras)): ?>
                <div class="crcc-empty">
                    <i class="bi bi-building"></i>
                    <p>Nenhuma obra cadastrada ainda.</p>
                    <small>Clique em <strong>Nova Obra</strong> para começar.</small>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table crcc-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:24px;">Obra</th>
                                <th>Início</th>
                                <th>Prazo</th>
                                <th>Status</th>
                                <th class="text-end" style="padding-right:24px;">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($obras as $obra): ?>
                            <?php
                                $st  = $statusLabels[$obra['status']] ?? ['label' => $obra['status'], 'cor' => 'secondary'];
                                $ini = $obra['data_inicio']       ? date('d/m/Y', strtotime($obra['data_inicio']))       : '—';
                                $fim = $obra['data_fim_prevista'] ? date('d/m/Y', strtotime($obra['data_fim_prevista'])) : '—';
                                $prazoClass = '';
                                if ($obra['data_fim_prevista'] && $obra['status'] === 'em_andamento') {
                                    $dias = (strtotime($obra['data_fim_prevista']) - time()) / 86400;
                                    if ($dias < 0)        $prazoClass = 'text-danger fw-semibold';
                                    elseif ($dias <= 30)  $prazoClass = 'text-warning fw-semibold';
                                }
                            ?>
                            <tr>
                                <td style="padding-left:24px;">
                                    <div class="cell-primary"><?= htmlspecialchars($obra['nome']) ?></div>
                                    <?php if ($obra['descricao']): ?>
                                    <div class="cell-muted"><?= htmlspecialchars($obra['descricao']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= $ini ?></td>
                                <td class="<?= $prazoClass ?>"><?= $fim ?></td>
                                <td>
                                    <span class="badge crcc-badge bg-<?= $st['cor'] ?>"><?= $st['label'] ?></span>
                                </td>
                                <td class="text-end" style="padding-right:24px;">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="atividades.php?obra_id=<?= $obra['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-clipboard-check me-1"></i>Atividades
                                        </a>
                                        <?php if ($podeEditar): ?>
                                        <button class="btn btn-sm btn-outline-secondary btn-editar"
                                                data-id="<?= $obra['id'] ?>"
                                                data-nome="<?= htmlspecialchars($obra['nome'], ENT_QUOTES) ?>"
                                                data-descricao="<?= htmlspecialchars($obra['descricao'] ?? '', ENT_QUOTES) ?>"
                                                data-inicio="<?= htmlspecialchars($obra['data_inicio'] ?? '') ?>"
                                                data-fim="<?= htmlspecialchars($obra['data_fim_prevista'] ?? '') ?>"
                                                data-status="<?= htmlspecialchars($obra['status']) ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline form-excluir">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= $obra['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
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

<!-- Modal Nova / Editar Obra -->
<div class="modal fade" id="modalObra" tabindex="-1" aria-labelledby="modalObraLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" id="formObra">
                <input type="hidden" name="acao" id="inputAcao" value="criar">
                <input type="hidden" name="id"   id="inputId"   value="">

                <div class="modal-header text-white border-0"
                     style="background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: .375rem .375rem 0 0;">
                    <h5 class="modal-title fw-bold" id="modalObraLabel">
                        <i class="bi bi-building-add me-2"></i>
                        <span id="modalTitulo">Nova Obra</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body bg-white px-4 py-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Nome da Obra <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nome" id="inputNome"
                               placeholder="Ex: Usina Solar Mauriti — Infraestrutura Civil" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Descrição</label>
                        <textarea class="form-control" name="descricao" id="inputDescricao" rows="2"
                                  placeholder="Detalhes adicionais (opcional)" style="resize:none;"></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Data de Início</label>
                            <input type="date" class="form-control" name="data_inicio" id="inputInicio">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold text-dark">Prazo de Conclusão</label>
                            <input type="date" class="form-control" name="data_fim_prevista" id="inputFim">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label fw-semibold text-dark">Status</label>
                        <select class="form-select" name="status" id="inputStatus">
                            <option value="em_andamento">Em andamento</option>
                            <option value="paralisada">Paralisada</option>
                            <option value="concluida">Concluída</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> <span id="btnSubmitTexto">Salvar Obra</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Modo edição: preenche o modal com os dados da linha
document.querySelectorAll('.btn-editar').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('inputAcao').value    = 'editar';
        document.getElementById('inputId').value      = btn.dataset.id;
        document.getElementById('inputNome').value    = btn.dataset.nome;
        document.getElementById('inputDescricao').value = btn.dataset.descricao;
        document.getElementById('inputInicio').value  = btn.dataset.inicio;
        document.getElementById('inputFim').value     = btn.dataset.fim;
        document.getElementById('inputStatus').value  = btn.dataset.status;
        document.getElementById('modalTitulo').textContent  = 'Editar Obra';
        document.getElementById('btnSubmitTexto').textContent = 'Salvar Alterações';
        new bootstrap.Modal(document.getElementById('modalObra')).show();
    });
});

// Resetar modal ao fechar
document.getElementById('modalObra').addEventListener('hidden.bs.modal', function() {
    document.getElementById('inputAcao').value   = 'criar';
    document.getElementById('inputId').value     = '';
    document.getElementById('formObra').reset();
    document.getElementById('modalTitulo').textContent  = 'Nova Obra';
    document.getElementById('btnSubmitTexto').textContent = 'Salvar Obra';
});

// Confirmar exclusão
document.querySelectorAll('.form-excluir').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        if (!confirm('Excluir esta obra? Todas as áreas e atividades vinculadas também serão removidas.')) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
