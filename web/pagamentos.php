<?php
/**
 * Gestão de Pagamentos
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();


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
            $descricao = trim($_POST['descricao'] ?? '');
            $valor     = (float) ($_POST['valor'] ?? 0);
            $aprovacao = $_POST['aprovacao'] ?? 'Em análise';

            if (!$descricao || $valor <= 0) {
                $flash = ['tipo' => 'danger', 'msg' => 'Descrição e valor são obrigatórios.'];
            } else {
                $payload = [
                    'user_id'   => $_SESSION['user_id'],
                    'descricao' => $descricao,
                    'valor'     => $valor,
                    'aprovacao' => $aprovacao,
                    'foto_url'  => $_POST['foto_url'] ?? null,
                ];

                if ($acao === 'criar') {
                    $res = $supabase->request('POST', '/rest/v1/pagamentos', $payload);
                    $flash = isset($res[0]['id'])
                        ? ['tipo' => 'success', 'msg' => 'Pagamento criado com sucesso.']
                        : ['tipo' => 'danger',  'msg' => 'Erro ao criar pagamento.'];
                } else {
                    $id = (int) ($_POST['id'] ?? 0);
                    $supabase->request('PATCH',
                        '/rest/v1/pagamentos?id=eq.' . $id,
                        $payload
                    );
                    $flash = ['tipo' => 'success', 'msg' => 'Pagamento atualizado.'];
                }
            }
        }
    }

    if ($acao === 'excluir' && $podeEditar) {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $supabase->request('DELETE', '/rest/v1/pagamentos?id=eq.' . $id);
            $flash = ['tipo' => 'success', 'msg' => 'Pagamento excluído.'];
        }
    }
}

// ─── Filtros ──────────────────────────────────────────────────────────────────
$filtroAprovacao = $_GET['aprovacao'] ?? '';

$pagParams = [
    'user_id' => 'eq.' . $_SESSION['user_id'],
    'select'  => 'id,descricao,valor,foto_url,aprovacao,data_criacao,data_atualizacao',
    'order'   => 'data_criacao.desc',
];
if ($filtroAprovacao) $pagParams['aprovacao'] = 'eq.' . $filtroAprovacao;

$pagamentos = $supabase->request('GET', '/rest/v1/pagamentos', null, $pagParams) ?: [];

$aprovacaoLabels = [
    'pendente'    => ['label' => 'Pendente',    'cor' => 'secondary'],
    'Em análise'  => ['label' => 'Em análise',  'cor' => 'info'],
    'aprovado'    => ['label' => 'Aprovado',    'cor' => 'success'],
    'rejeitado'   => ['label' => 'Rejeitado',   'cor' => 'danger'],
];

// Estatísticas
$totalPagamentos = count($pagamentos);
$valorTotal      = array_reduce($pagamentos, fn($sum, $p) => $sum + (float)$p['valor'], 0);
$aprovados       = count(array_filter($pagamentos, fn($p) => $p['aprovacao'] === 'aprovado'));
$pendentes       = $totalPagamentos - $aprovados;

$pagamentosDemo = [
    [
        'fornecedor' => 'Locadora Horizonte',
        'categoria' => 'Equipamentos',
        'descricao' => 'Locação de plataforma elevatória - Frente Norte',
        'valor' => 6850.00,
        'status' => 'Em análise',
        'vencimento' => date('d/m/Y', strtotime('+4 days')),
    ],
    [
        'fornecedor' => 'EletroFase Materiais',
        'categoria' => 'Materiais elétricos',
        'descricao' => 'Cabos e conectores para bloco técnico',
        'valor' => 12340.90,
        'status' => 'aprovado',
        'vencimento' => date('d/m/Y', strtotime('+1 days')),
    ],
    [
        'fornecedor' => 'TransLog BR',
        'categoria' => 'Logística',
        'descricao' => 'Transporte de estruturas metálicas - lote 07',
        'valor' => 4750.30,
        'status' => 'pendente',
        'vencimento' => date('d/m/Y', strtotime('+6 days')),
    ],
    [
        'fornecedor' => 'Concreto Premium',
        'categoria' => 'Civil',
        'descricao' => 'Fornecimento de concreto usinado FCK30',
        'valor' => 15880.00,
        'status' => 'rejeitado',
        'vencimento' => date('d/m/Y', strtotime('-2 days')),
    ],
];

$valorPipelineDemo = array_reduce($pagamentosDemo, fn($acc, $i) => $acc + (float) $i['valor'], 0.0);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos — <?= htmlspecialchars('CRCC') ?></title>
    
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
                        <i class="bi bi-cash-coin me-2 text-warning"></i>Pagamentos
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Gestão e acompanhamento de pagamentos</p>
                </div>
                <?php if ($podeEditar): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPagamento">
                    <i class="bi bi-plus-lg me-1"></i> Novo Pagamento
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

            <!-- Estatísticas -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="crcc-stat-card">
                        <div class="crcc-stat-value"><?= $totalPagamentos ?></div>
                        <p class="crcc-stat-label">Total</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-stat-card">
                        <div class="crcc-stat-value" style="font-size:1.3rem;">R$ <?= number_format($valorTotal, 2, ',', '.') ?></div>
                        <p class="crcc-stat-label">Valor Total</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-stat-card">
                        <div class="crcc-stat-value" style="color:#34d399;"><?= $aprovados ?></div>
                        <p class="crcc-stat-label">Aprovados</p>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="crcc-stat-card">
                        <div class="crcc-stat-value" style="color:#fbbf24;"><?= $pendentes ?></div>
                        <p class="crcc-stat-label">Pendentes</p>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-8">
                    <div class="crcc-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0" style="color:#0F172A;font-weight:700;">Pipeline de pagamentos (demo visual)</h6>
                            <span class="badge text-bg-info">Apresentação</span>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($pagamentosDemo as $demo): ?>
                            <?php $st = $aprovacaoLabels[$demo['status']] ?? ['label' => $demo['status'], 'cor' => 'secondary']; ?>
                            <div class="col-md-6">
                                <div style="border:1px solid #e2e8f0;border-radius:10px;padding:12px;background:#f8fafc;">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <div style="font-size:.82rem;color:#64748b;"><?= htmlspecialchars($demo['categoria']) ?></div>
                                        <span class="badge bg-<?= $st['cor'] ?>"><?= htmlspecialchars($st['label']) ?></span>
                                    </div>
                                    <div style="font-size:.9rem;font-weight:700;color:#0f172a;"><?= htmlspecialchars($demo['fornecedor']) ?></div>
                                    <div style="font-size:.8rem;color:#334155;margin-top:4px;"><?= htmlspecialchars($demo['descricao']) ?></div>
                                    <div class="d-flex justify-content-between mt-2" style="font-size:.8rem;color:#334155;">
                                        <span>Vencimento: <?= htmlspecialchars($demo['vencimento']) ?></span>
                                        <strong>R$ <?= number_format($demo['valor'], 2, ',', '.') ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="crcc-card p-4 h-100">
                        <h6 class="mb-3" style="color:#0F172A;font-weight:700;">Resumo executivo (demo)</h6>
                        <div style="font-size:1.5rem;font-weight:800;color:#0F172A;">R$ <?= number_format($valorPipelineDemo, 2, ',', '.') ?></div>
                        <div style="font-size:.78rem;color:#64748b;">Valor total em análise/aprovação</div>
                        <hr>
                        <div class="d-flex justify-content-between" style="font-size:.84rem;color:#334155;">
                            <span>Itens no fluxo</span>
                            <strong><?= count($pagamentosDemo) ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size:.84rem;color:#334155;">
                            <span>Maior lançamento</span>
                            <strong>R$ 15.880,00</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2" style="font-size:.84rem;color:#334155;">
                            <span>Previsão de caixa (7 dias)</span>
                            <strong style="color:#059669;">Controlado</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Pagamentos -->
            <div class="crcc-card">
                <div>

                <form method="GET" class="crcc-filters">
                    <select name="aprovacao" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Todos os status</option>
                        <?php foreach ($aprovacaoLabels as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $filtroAprovacao === $k ? 'selected' : '' ?>><?= $v['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($filtroAprovacao): ?>
                    <a href="pagamentos.php" class="btn btn-sm btn-outline-secondary">Limpar</a>
                    <?php endif; ?>
                </form>

                <?php if (empty($pagamentos)): ?>
                <div class="crcc-empty">
                    <i class="bi bi-receipt"></i>
                    <p>Nenhum pagamento encontrado.</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table crcc-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="padding-left:24px;">Foto</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Status</th>
                                <th>Data</th>
                                <?php if ($podeEditar): ?><th class="text-end" style="padding-right:24px;">Ações</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pagamentos as $pag):
                            $st = $aprovacaoLabels[$pag['aprovacao']] ?? ['label' => $pag['aprovacao'], 'cor' => 'secondary'];
                        ?>
                            <tr>
                                <td style="padding-left:24px;">
                                    <?php if ($pag['foto_url']): ?>
                                    <img src="<?= htmlspecialchars($pag['foto_url']) ?>" alt="Foto"
                                         style="width:36px;height:36px;border-radius:6px;object-fit:cover;cursor:pointer;"
                                         data-bs-toggle="modal" data-bs-target="#fotoModal"
                                         onclick="document.getElementById('fotoPreview').src=this.src">
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><div class="cell-primary"><?= htmlspecialchars(mb_strimwidth($pag['descricao'], 0, 50, '…')) ?></div></td>
                                <td><strong class="text-success">R$ <?= number_format($pag['valor'], 2, ',', '.') ?></strong></td>
                                <td><span class="badge crcc-badge bg-<?= $st['cor'] ?>"><?= $st['label'] ?></span></td>
                                <td><?= date('d/m/Y', strtotime($pag['data_criacao'])) ?></td>
                                <?php if ($podeEditar): ?>
                                <td class="text-end" style="padding-right:24px;">
                                    <button class="btn btn-sm btn-outline-secondary me-1" onclick="editarPagamento(<?= $pag['id'] ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="excluirPagamento(<?= $pag['id'] ?>)">
                                        <i class="bi bi-trash"></i>
                                    </button>
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

<!-- Modal Novo/Editar Pagamento -->
<div class="modal fade" id="modalPagamento" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white border-0"
                 style="background: linear-gradient(135deg, #0d6efd, #0a58ca); border-radius: .375rem .375rem 0 0;">
                <h5 class="modal-title fw-bold">Novo Pagamento</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body bg-white px-4 py-3">
                    <input type="hidden" name="acao" value="criar">
                    <input type="hidden" name="id" id="pagId" value="">

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Descrição</label>
                        <textarea class="form-control" name="descricao" id="pagDescricao" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Valor (R$)</label>
                        <input type="number" class="form-control" name="valor" id="pagValor" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">URL da Foto</label>
                        <input type="text" class="form-control" name="foto_url" id="pagFoto" placeholder="https://...">
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold text-dark">Status</label>
                        <select class="form-select" name="aprovacao" id="pagAprovacao">
                            <option value="Em análise">Em análise</option>
                            <option value="pendente">Pendente</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="rejeitado">Rejeitado</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Foto -->
<div class="modal fade" id="fotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <img id="fotoPreview" src="" alt="Foto" class="img-fluid" style="border-radius:8px;">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editarPagamento(id) {
    alert('Função de edição em desenvolvimento.');
}

function excluirPagamento(id) {
    if (confirm('Tem certeza que deseja excluir este pagamento?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
