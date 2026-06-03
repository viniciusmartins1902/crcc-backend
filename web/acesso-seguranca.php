<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../controle-acesso.php';
require_once __DIR__ . '/../supabase.php';
;

requerLogin();


$nivel = getNivelAcesso();

$kpis = [
    'inspecoes_hoje' => 14,
    'ocorrencias_abertas' => 3,
    'epis_alerta_validade' => 7,
    'falhas_criticas' => 2,
];

$inspecoesVeiculares = [
    ['veiculo' => 'Caminhao Munck - CRCC 014', 'placa' => 'QWE-4A12', 'motorista' => 'Ronaldo Lima', 'status' => 'Aprovado', 'hora' => '06:45'],
    ['veiculo' => 'Van Apoio Eletrica', 'placa' => 'HTR-9D31', 'motorista' => 'Carla Torres', 'status' => 'Com ressalva', 'hora' => '07:12'],
    ['veiculo' => 'Picape Fiscalizacao', 'placa' => 'MNO-2K88', 'motorista' => 'Lucas Prado', 'status' => 'Bloqueado', 'hora' => '07:38'],
    ['veiculo' => 'Onibus Operacional 02', 'placa' => 'ABC-5J77', 'motorista' => 'Helio Nunes', 'status' => 'Aprovado', 'hora' => '08:05'],
];

$ocorrencias = [
    ['id' => 'OC-2026-188', 'tipo' => 'Desvio comportamental', 'local' => 'Frente Norte', 'gravidade' => 'Media', 'responsavel' => 'TST Eduardo', 'status' => 'Em tratativa'],
    ['id' => 'OC-2026-189', 'tipo' => 'Quase acidente', 'local' => 'Subestacao', 'gravidade' => 'Alta', 'responsavel' => 'TST Renata', 'status' => 'Aberta'],
    ['id' => 'OC-2026-190', 'tipo' => 'Nao conformidade EPI', 'local' => 'Bloco C', 'gravidade' => 'Baixa', 'responsavel' => 'TST Diego', 'status' => 'Concluida'],
];

$epis = [
    ['item' => 'Capacete Classe B', 'estoque' => 84, 'validade' => 'Em dia', 'proxima_compra' => '12/06/2026'],
    ['item' => 'Cinto paraquedista', 'estoque' => 19, 'validade' => 'Alerta', 'proxima_compra' => '04/06/2026'],
    ['item' => 'Luva anti-corte', 'estoque' => 126, 'validade' => 'Em dia', 'proxima_compra' => '18/06/2026'],
    ['item' => 'Respirador PFF2', 'estoque' => 41, 'validade' => 'Alerta', 'proxima_compra' => '06/06/2026'],
];

$colaboradores = [
    ['nome' => 'Ronaldo Lima', 'funcao' => 'Motorista', 'treinamento' => 'Direcao defensiva', 'status' => 'Regular'],
    ['nome' => 'Carla Torres', 'funcao' => 'Eletricista', 'treinamento' => 'NR-10 SEP', 'status' => 'Regular'],
    ['nome' => 'Lucas Prado', 'funcao' => 'Fiscal de campo', 'treinamento' => 'Trabalho em altura', 'status' => 'Reciclagem'],
    ['nome' => 'Helio Nunes', 'funcao' => 'Operador', 'treinamento' => 'Espaco confinado', 'status' => 'Regular'],
];

$naoConformidades = [
    ['codigo' => 'NC-4421', 'descricao' => 'Sinalizacao incompleta no acesso do Bloco C', 'prazo' => '03/06/2026', 'criticidade' => 'Media'],
    ['codigo' => 'NC-4422', 'descricao' => 'Extintor vencido em veiculo de apoio', 'prazo' => '02/06/2026', 'criticidade' => 'Alta'],
    ['codigo' => 'NC-4423', 'descricao' => 'Checklist diario sem assinatura', 'prazo' => '05/06/2026', 'criticidade' => 'Baixa'],
];

$falhasSeguranca = [
    ['falha' => 'Falha de bloqueio LOTO', 'area' => 'Subestacao', 'impacto' => 'Alto', 'acao' => 'Revisao imediata + bloqueio da frente'],
    ['falha' => 'Uso inadequado de EPI', 'area' => 'Frente Norte', 'impacto' => 'Medio', 'acao' => 'DDS direcionado + advertencia'],
    ['falha' => 'Atividade sem APR', 'area' => 'Oficina', 'impacto' => 'Medio', 'acao' => 'Parada da atividade ate regularizacao'],
];

$statusBadge = [
    'Aprovado' => 'success',
    'Com ressalva' => 'warning',
    'Bloqueado' => 'danger',
    'Alta' => 'danger',
    'Media' => 'warning',
    'Baixa' => 'info',
    'Em dia' => 'success',
    'Alerta' => 'warning',
    'Regular' => 'success',
    'Reciclagem' => 'warning',
    'Aberta' => 'danger',
    'Em tratativa' => 'warning',
    'Concluida' => 'success',
    'Alto' => 'danger',
    'Medio' => 'warning',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso Seguranca - <?= htmlspecialchars('CRCC') ?></title>
    
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
                    <h1 class="h3 mb-1" style="font-weight:700; letter-spacing:-.02em;">
                        <i class="bi bi-person-shield me-2 text-primary"></i>Acesso Seguranca
                    </h1>
                    <p class="mb-0" style="font-size:.875rem; color:#334155;">Modulo operacional para inspecoes, ocorrencias, EPIs e conformidade.</p>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge text-bg-primary">Perfil Seguranca</span>
                    <span class="badge text-bg-light">Nivel atual: <?= (int) $nivel ?></span>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3"><div class="crcc-metric"><div class="crcc-metric-value"><?= $kpis['inspecoes_hoje'] ?></div><p class="crcc-metric-label">Inspecoes hoje</p></div></div>
                <div class="col-6 col-md-3"><div class="crcc-metric"><div class="crcc-metric-value"><?= $kpis['ocorrencias_abertas'] ?></div><p class="crcc-metric-label">Ocorrencias abertas</p></div></div>
                <div class="col-6 col-md-3"><div class="crcc-metric"><div class="crcc-metric-value"><?= $kpis['epis_alerta_validade'] ?></div><p class="crcc-metric-label">EPIs em alerta</p></div></div>
                <div class="col-6 col-md-3"><div class="crcc-metric"><div class="crcc-metric-value"><?= $kpis['falhas_criticas'] ?></div><p class="crcc-metric-label">Falhas criticas</p></div></div>
            </div>

            <div class="crcc-card p-3 mb-4">
                <ul class="nav nav-pills gap-2" id="segTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-primary active" data-bs-toggle="tab" data-bs-target="#tab-inspecoes" type="button">Inspecoes Veiculares</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-ocorrencias" type="button">Registros de Ocorrencia</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-epis" type="button">Lista de EPIs</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-cadastro-epi" type="button">Cadastro de Novo EPI</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-colaboradores" type="button">Lista de Colaboradores</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-nao-conformidades" type="button">Nao Conformidades</button></li>
                    <li class="nav-item" role="presentation"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="tab" data-bs-target="#tab-falhas" type="button">Falhas de Seguranca</button></li>
                </ul>
            </div>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-inspecoes">
                    <div class="crcc-card p-4 mb-3" id="form-inspecao">
                        <h5 class="mb-3" style="font-weight:700;">Nova Inspecao Veicular</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Veiculo</label><input type="text" class="form-control" value="Caminhao Munck - CRCC 014" readonly></div>
                            <div class="col-md-2"><label class="form-label fw-semibold text-dark">Placa</label><input type="text" class="form-control" value="QWE-4A12" readonly></div>
                            <div class="col-md-3"><label class="form-label fw-semibold text-dark">Motorista</label><input type="text" class="form-control" value="Ronaldo Lima" readonly></div>
                            <div class="col-md-3"><label class="form-label fw-semibold text-dark">Status</label><select class="form-select"><option selected>Aprovado</option><option>Com ressalva</option><option>Bloqueado</option></select></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Salvar Inspecao (demo)</button></div>
                    </div>
                    <div class="crcc-card p-0">
                        <div class="table-responsive">
                            <table class="table crcc-table align-middle mb-0">
                                <thead><tr><th style="padding-left:24px;">Veiculo</th><th>Placa</th><th>Motorista</th><th>Hora</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($inspecoesVeiculares as $item): ?>
                                    <?php $badge = $statusBadge[$item['status']] ?? 'secondary'; ?>
                                    <tr>
                                        <td style="padding-left:24px;"><div class="cell-primary"><?= htmlspecialchars($item['veiculo']) ?></div></td>
                                        <td><?= htmlspecialchars($item['placa']) ?></td>
                                        <td><?= htmlspecialchars($item['motorista']) ?></td>
                                        <td><?= htmlspecialchars($item['hora']) ?></td>
                                        <td><span class="badge crcc-badge bg-<?= $badge ?>"><?= htmlspecialchars($item['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-ocorrencias">
                    <div class="crcc-card p-4 mb-3" id="form-ocorrencia">
                        <h5 class="mb-3" style="font-weight:700;">Abertura de Ocorrencia</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Tipo</label><select class="form-select"><option>Desvio comportamental</option><option selected>Quase acidente</option><option>Nao conformidade EPI</option></select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Local</label><input type="text" class="form-control" value="Frente Norte" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Gravidade</label><select class="form-select"><option>Baixa</option><option selected>Media</option><option>Alta</option></select></div>
                            <div class="col-12"><label class="form-label fw-semibold text-dark">Descricao</label><textarea class="form-control" rows="3" readonly>Colaborador sem talabarte durante deslocamento em plataforma.</textarea></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Registrar Ocorrencia (demo)</button></div>
                    </div>
                    <div class="crcc-card p-0">
                        <div class="table-responsive">
                            <table class="table crcc-table align-middle mb-0">
                                <thead><tr><th style="padding-left:24px;">ID</th><th>Tipo</th><th>Local</th><th>Gravidade</th><th>Responsavel</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($ocorrencias as $oc): ?>
                                    <tr>
                                        <td style="padding-left:24px;"><div class="cell-primary"><?= htmlspecialchars($oc['id']) ?></div></td>
                                        <td><?= htmlspecialchars($oc['tipo']) ?></td>
                                        <td><?= htmlspecialchars($oc['local']) ?></td>
                                        <td><span class="badge crcc-badge bg-<?= $statusBadge[$oc['gravidade']] ?? 'secondary' ?>"><?= htmlspecialchars($oc['gravidade']) ?></span></td>
                                        <td><?= htmlspecialchars($oc['responsavel']) ?></td>
                                        <td><span class="badge crcc-badge bg-<?= $statusBadge[$oc['status']] ?? 'secondary' ?>"><?= htmlspecialchars($oc['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-epis">
                    <div class="crcc-card p-4 mb-3" id="form-epi">
                        <h5 class="mb-3" style="font-weight:700;">Cadastro de Novo EPI</h5>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold text-dark">Descricao</label><input type="text" class="form-control" value="Protetor auricular tipo concha" readonly></div>
                            <div class="col-md-2"><label class="form-label fw-semibold text-dark">Codigo</label><input type="text" class="form-control" value="EPI-2026-044" readonly></div>
                            <div class="col-md-2"><label class="form-label fw-semibold text-dark">Estoque</label><input type="number" class="form-control" value="35" readonly></div>
                            <div class="col-md-2"><label class="form-label fw-semibold text-dark">Validade</label><input type="date" class="form-control" value="2028-12-31" readonly></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Cadastrar EPI (demo)</button></div>
                    </div>
                    <div class="crcc-card p-0">
                        <div class="table-responsive">
                            <table class="table crcc-table align-middle mb-0">
                                <thead><tr><th style="padding-left:24px;">Item</th><th>Estoque</th><th>Validade</th><th>Proxima compra</th></tr></thead>
                                <tbody>
                                    <?php foreach ($epis as $epi): ?>
                                    <tr>
                                        <td style="padding-left:24px;"><div class="cell-primary"><?= htmlspecialchars($epi['item']) ?></div></td>
                                        <td><?= (int) $epi['estoque'] ?> un.</td>
                                        <td><span class="badge crcc-badge bg-<?= $statusBadge[$epi['validade']] ?? 'secondary' ?>"><?= htmlspecialchars($epi['validade']) ?></span></td>
                                        <td><?= htmlspecialchars($epi['proxima_compra']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-colaboradores">
                    <div class="crcc-card p-4 mb-3" id="form-colaborador">
                        <h5 class="mb-3" style="font-weight:700;">Novo Colaborador</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Nome</label><input type="text" class="form-control" value="Novo colaborador" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Funcao</label><input type="text" class="form-control" value="Eletricista" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Treinamento</label><select class="form-select"><option>NR-10</option><option selected>NR-35</option><option>NR-18</option></select></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Salvar Colaborador (demo)</button></div>
                    </div>
                    <div class="crcc-card p-0">
                        <div class="table-responsive">
                            <table class="table crcc-table align-middle mb-0">
                                <thead><tr><th style="padding-left:24px;">Nome</th><th>Funcao</th><th>Treinamento</th><th>Status</th></tr></thead>
                                <tbody>
                                    <?php foreach ($colaboradores as $col): ?>
                                    <tr>
                                        <td style="padding-left:24px;"><div class="cell-primary"><?= htmlspecialchars($col['nome']) ?></div></td>
                                        <td><?= htmlspecialchars($col['funcao']) ?></td>
                                        <td><?= htmlspecialchars($col['treinamento']) ?></td>
                                        <td><span class="badge crcc-badge bg-<?= $statusBadge[$col['status']] ?? 'secondary' ?>"><?= htmlspecialchars($col['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-nao-conformidades">
                    <div class="crcc-card p-4 mb-3" id="form-nc">
                        <h5 class="mb-3" style="font-weight:700;">Nova Nao Conformidade</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Codigo</label><input type="text" class="form-control" value="NC-2026-200" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Criticidade</label><select class="form-select"><option>Baixa</option><option selected>Media</option><option>Alta</option></select></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Prazo</label><input type="date" class="form-control" value="2026-06-05" readonly></div>
                            <div class="col-12"><label class="form-label fw-semibold text-dark">Descricao</label><textarea class="form-control" rows="3" readonly>Sinalizacao incompleta no acesso do canteiro.</textarea></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Registrar NC (demo)</button></div>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($naoConformidades as $nc): ?>
                        <div class="col-md-4">
                            <div class="crcc-card p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge text-bg-light"><?= htmlspecialchars($nc['codigo']) ?></span>
                                    <span class="badge crcc-badge bg-<?= $statusBadge[$nc['criticidade']] ?? 'secondary' ?>"><?= htmlspecialchars($nc['criticidade']) ?></span>
                                </div>
                                <div class="cell-primary mb-2"><?= htmlspecialchars($nc['descricao']) ?></div>
                                <div style="font-size:.82rem;color:#475569;">Prazo: <?= htmlspecialchars($nc['prazo']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-falhas">
                    <div class="crcc-card p-4 mb-3" id="form-falha">
                        <h5 class="mb-3" style="font-weight:700;">Nova Falha de Seguranca</h5>
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Falha</label><input type="text" class="form-control" value="Falha de bloqueio LOTO" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Area</label><input type="text" class="form-control" value="Subestacao" readonly></div>
                            <div class="col-md-4"><label class="form-label fw-semibold text-dark">Impacto</label><select class="form-select"><option>Baixo</option><option selected>Medio</option><option>Alto</option></select></div>
                            <div class="col-12"><label class="form-label fw-semibold text-dark">Acao imediata</label><textarea class="form-control" rows="3" readonly>Revisao imediata e bloqueio da frente.</textarea></div>
                        </div>
                        <div class="mt-3"><button class="btn btn-primary" type="button">Salvar Falha (demo)</button></div>
                    </div>
                    <div class="crcc-card p-0">
                        <div class="table-responsive">
                            <table class="table crcc-table align-middle mb-0">
                                <thead><tr><th style="padding-left:24px;">Falha</th><th>Area</th><th>Impacto</th><th>Acao imediata</th></tr></thead>
                                <tbody>
                                    <?php foreach ($falhasSeguranca as $falha): ?>
                                    <tr>
                                        <td style="padding-left:24px;"><div class="cell-primary"><?= htmlspecialchars($falha['falha']) ?></div></td>
                                        <td><?= htmlspecialchars($falha['area']) ?></td>
                                        <td><span class="badge crcc-badge bg-<?= $statusBadge[$falha['impacto']] ?? 'secondary' ?>"><?= htmlspecialchars($falha['impacto']) ?></span></td>
                                        <td><?= htmlspecialchars($falha['acao']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const tabButtons = document.querySelectorAll('#segTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach((btn) => {
        btn.addEventListener('shown.bs.tab', () => {
            tabButtons.forEach((b) => {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-primary');
            });
            btn.classList.remove('btn-outline-primary');
            btn.classList.add('btn-primary');
        });
    });
})();
</script>
</body>
</html>
