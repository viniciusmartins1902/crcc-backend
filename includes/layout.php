<?php
/**
 * Layout compartilhado — CRCC
 * Uso: include no topo de cada página, após definir $titulo e $paginaAtiva
 */
if (!isset($titulo))      $titulo      = 'CRCC';
if (!isset($paginaAtiva)) $paginaAtiva = '';

$nivel = getNivelAcesso();
$nome  = nomeUsuario();
$ini   = strtoupper(substr(trim($nome), 0, 1));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($titulo) ?> — CRCC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  :root {
    --crcc-primary:   #1565C0;
    --crcc-dark:      #0D47A1;
    --crcc-light:     #E3F2FD;
    --sidebar-w:      240px;
    --topbar-h:       56px;
  }

  body { background: #f0f4f8; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

  /* ── Topbar ── */
  .crcc-topbar {
    position: fixed; top: 0; left: 0; right: 0; height: var(--topbar-h);
    background: var(--crcc-primary);
    display: flex; align-items: center; padding: 0 16px;
    z-index: 1000; box-shadow: 0 2px 8px rgba(0,0,0,.2);
    gap: 16px;
  }
  .topbar-toggle { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; padding:4px 8px; border-radius:6px; flex-shrink:0; }
  .topbar-toggle:hover { background:rgba(255,255,255,.15); }
  .topbar-brand { font-weight:800; font-size:18px; color:#fff; letter-spacing:-.5px; flex-shrink:0; }
  .topbar-brand span { opacity:.7; font-weight:400; font-size:13px; margin-left:6px; }
  .topbar-spacer { flex:1; }
  .topbar-user {
    display:flex; align-items:center; gap:10px; color:#fff;
    background:rgba(255,255,255,.12); border-radius:10px; padding:6px 12px;
  }
  .topbar-avatar {
    width:30px; height:30px; border-radius:50%;
    background:rgba(255,255,255,.25); color:#fff; font-size:13px; font-weight:700;
    display:flex; align-items:center; justify-content:center; flex-shrink:0;
  }
  .topbar-nome { font-size:13px; font-weight:600; }
  .topbar-nivel { font-size:11px; opacity:.7; }

  /* ── Sidebar ── */
  .crcc-sidebar {
    position: fixed; top: var(--topbar-h); left: 0; bottom: 0;
    width: var(--sidebar-w); background: #fff;
    border-right: 1px solid #e2e8f0;
    overflow-y: auto; z-index: 900;
    transition: transform .25s ease;
    box-shadow: 2px 0 8px rgba(0,0,0,.06);
  }
  .crcc-sidebar.collapsed { transform: translateX(calc(-1 * var(--sidebar-w))); }

  .sidebar-section { padding: 16px 12px 4px; font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.8px; }
  .sidebar-item {
    display:flex; align-items:center; gap:10px;
    padding:10px 16px; border-radius:10px; margin:2px 8px;
    font-size:14px; font-weight:500; color:#475569;
    text-decoration:none; transition:all .15s; cursor:pointer; border:none; background:none; width:calc(100% - 16px);
  }
  .sidebar-item:hover { background:#f1f5f9; color:#1e293b; }
  .sidebar-item.active { background:var(--crcc-light); color:var(--crcc-primary); font-weight:700; }
  .sidebar-item.active i { color:var(--crcc-primary); }
  .sidebar-item i { font-size:17px; width:20px; text-align:center; flex-shrink:0; }

  .sidebar-logout {
    margin:12px 8px 0; padding-top:12px; border-top:1px solid #e2e8f0;
  }
  .btn-logout { color:#ef4444 !important; }
  .btn-logout:hover { background:rgba(239,68,68,.08) !important; }

  /* ── Main content ── */
  .crcc-main {
    margin-top: var(--topbar-h);
    margin-left: var(--sidebar-w);
    padding: 28px 24px;
    min-height: calc(100vh - var(--topbar-h));
    transition: margin-left .25s ease;
  }
  .crcc-main.expanded { margin-left: 0; }

  .page-title { font-size:22px; font-weight:800; color:#1e293b; margin-bottom:4px; }
  .page-sub   { font-size:13px; color:#64748b; margin-bottom:24px; }

  /* ── Cards ── */
  .kpi-card { border-radius:14px; border:none; box-shadow:0 2px 10px rgba(0,0,0,.06); }
  .kpi-num  { font-size:36px; font-weight:800; }
  .kpi-lbl  { font-size:12px; color:#64748b; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }

  /* ── Tables ── */
  .crcc-table { border-radius:12px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,.06); }
  .crcc-table thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.4px; padding:12px 16px; }
  .crcc-table tbody td { padding:12px 16px; font-size:14px; vertical-align:middle; border-bottom:1px solid #f1f5f9; }
  .crcc-table tbody tr:last-child td { border-bottom:none; }
  .crcc-table tbody tr:hover td { background:#f8fafc; }

  .badge-planejamento { background:rgba(99,102,241,.12); color:#6366f1; }
  .badge-gestor       { background:rgba(16,185,129,.12); color:#10b981; }
  .badge-encarregado  { background:rgba(245,158,11,.12); color:#f59e0b; }
  .badge-profissional { background:rgba(100,116,139,.12); color:#64748b; }

  @media (max-width:768px) {
    .crcc-sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
    .crcc-sidebar.mobile-open { transform: translateX(0); }
    .crcc-main { margin-left:0; }
    .backdrop { position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:850; display:none; }
    .backdrop.show { display:block; }
  }
</style>
</head>
<body>

<!-- Topbar -->
<div class="crcc-topbar">
  <button class="topbar-toggle" id="sidebarToggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
  </button>
  <div class="topbar-brand">CRCC <span>Gestão de Obras</span></div>
  <div class="topbar-spacer"></div>
  <div class="topbar-user">
    <div class="topbar-avatar"><?= htmlspecialchars($ini) ?></div>
    <div>
      <div class="topbar-nome"><?= htmlspecialchars($nome) ?></div>
      <div class="topbar-nivel"><?= getNomeNivel($nivel) ?></div>
    </div>
  </div>
</div>

<!-- Backdrop mobile -->
<div class="backdrop" id="backdrop" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<nav class="crcc-sidebar" id="sidebar">
  <div class="sidebar-section">Diário</div>
  <a href="/web/dashboard.php" class="sidebar-item <?= $paginaAtiva === 'dashboard' ? 'active' : '' ?>">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>
  <?php if (podePlanejar()): ?>
  <a href="/web/planejamento.php" class="sidebar-item <?= $paginaAtiva === 'planejamento' ? 'active' : '' ?>">
    <i class="bi bi-calendar3"></i> Planejamento
  </a>
  <?php endif; ?>
  <a href="/web/atividades.php" class="sidebar-item <?= $paginaAtiva === 'atividades' ? 'active' : '' ?>">
    <i class="bi bi-clipboard-check"></i> Atividades / OS
  </a>
  <a href="/web/index.php" class="sidebar-item <?= $paginaAtiva === 'obras' ? 'active' : '' ?>">
    <i class="bi bi-building"></i> Obras
  </a>
  <a href="/web/usuarios.php" class="sidebar-item <?= $paginaAtiva === 'usuarios' ? 'active' : '' ?>">
    <i class="bi bi-people"></i> Usuários
  </a>

  <div class="sidebar-section">Financeiro</div>
  <a href="/web/pagamentos.php" class="sidebar-item <?= $paginaAtiva === 'pagamentos' ? 'active' : '' ?>">
    <i class="bi bi-receipt"></i> Reembolsos
  </a>

  <div class="sidebar-section">QSMS</div>
  <a href="/web/qualidade.php" class="sidebar-item <?= $paginaAtiva === 'qualidade' ? 'active' : '' ?>">
    <i class="bi bi-patch-check"></i> Qualidade
  </a>
  <a href="/web/seguranca.php" class="sidebar-item <?= $paginaAtiva === 'seguranca' ? 'active' : '' ?>">
    <i class="bi bi-shield-check"></i> Segurança
  </a>
  <?php if ($paginaAtiva === 'pesquisa' || true): ?>
  <a href="/web/pesquisa-resultados.php" class="sidebar-item <?= $paginaAtiva === 'pesquisa' ? 'active' : '' ?>">
    <i class="bi bi-bar-chart"></i> Pesquisa
  </a>
  <?php endif; ?>

  <div class="sidebar-logout">
    <a href="/web/logout.php" class="sidebar-item btn-logout">
      <i class="bi bi-box-arrow-left"></i> Sair
    </a>
  </div>
</nav>

<!-- Main content -->
<main class="crcc-main" id="mainContent">
