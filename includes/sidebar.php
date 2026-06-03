<?php
require_once __DIR__ . '/../controle-acesso.php';
if (!isset($GLOBALS['tradutor'])) {
    require_once __DIR__ . '/../tradutor.php';
}
if (!function_exists('getConfigSistema')) {
    require_once __DIR__ . '/sistema-config.php';
}
$nivel = getNivelAcesso();
$modoOperador = !empty($_SESSION['modo_operador']);

$nomeUsuarioSB = htmlspecialchars(nomeUsuario());
$inicialSB     = mb_strtoupper(mb_substr(nomeUsuario(), 0, 1));
$logoSidebar   = 'assets/images/icone.png';
$nomeClienteSB = htmlspecialchars(TenantManager::current()['nome'] ?? getConfigSistema('nome_sistema', 'Zetta'));
$idiomaAtualSB = function_exists('idioma_atual') ? idioma_atual() : ($_SESSION['idioma'] ?? 'pt');
$idiomasSB     = ['pt' => 'PT', 'en' => 'EN', 'zh' => '中文'];
$querySB       = $_GET;
unset($querySB['lang']);
$pathSB        = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: ($_SERVER['PHP_SELF'] ?? '/dashboard.php');
?>
<!-- ═══ SIDEBAR ═══ -->
<style>
#sidebarMenu {
    position: fixed !important;
    top: 0 !important; left: 0 !important; bottom: 0 !important;
    width: 240px !important;
    background: #ffffff !important;
    border-right: 1px solid #E2E8F0 !important;
    z-index: 200 !important;
    display: flex !important;
    flex-direction: column !important;
    overflow: hidden !important;
    box-shadow: none !important;
    backdrop-filter: none !important;
    transition: transform .3s cubic-bezier(.4,0,.2,1) !important;
}
@media (max-width: 767px) {
    #sidebarMenu { transform: translateX(-100%) !important; }
    #sidebarMenu.sidebar-open { transform: translateX(0) !important; box-shadow: 4px 0 24px rgba(15,23,42,.12) !important; }
}
/* Brand */
.sb-brand {
    display: flex; align-items: center; gap: 10px;
    padding: 20px 16px 16px;
    border-bottom: 1px solid #F1F5F9;
    flex-shrink: 0;
    text-decoration: none;
}
.sb-brand-icon {
    width: 34px; height: 34px;
    object-fit: contain; border-radius: 8px; flex-shrink: 0;
}
.sb-brand-name {
    font-family: 'Space Grotesk', sans-serif;
    font-size: .9375rem; font-weight: 700; letter-spacing: .01em;
    background: linear-gradient(90deg, #0BB8D4, #8B5CF6);
    -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    background-clip: text; line-height: 1.2;
}
/* Mobile toggle inside brand */
.sb-toggle {
    display: none;
    margin-left: auto;
    width: 32px; height: 32px;
    background: #F8FAFC; border: 1px solid #E2E8F0;
    border-radius: 8px; cursor: pointer;
    align-items: center; justify-content: center;
    font-size: 1.1rem; color: #64748B;
    flex-shrink: 0;
}
@media (max-width: 767px) { .sb-toggle { display: flex; } }

/* Scrollable nav area */
.sb-nav {
    flex: 1; overflow-y: auto; overflow-x: hidden;
    padding: .75rem .75rem;
    scrollbar-width: thin; scrollbar-color: #E2E8F0 transparent;
}
.sb-nav::-webkit-scrollbar { width: 3px; }
.sb-nav::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 4px; }

/* Section label */
.sb-label {
    font-size: 10px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #94A3B8;
    padding: .75rem .5rem .3rem; display: block;
}

/* Nav item */
.sb-nav ul { list-style: none; padding: 0; margin: 0; }
.sb-nav li { margin: 1px 0; }
.sb-nav .nav-link {
    display: flex !important; align-items: center !important; gap: 10px !important;
    padding: 9px 10px !important;
    border-radius: 8px !important;
    font-size: .8625rem !important; font-weight: 500 !important;
    color: #475569 !important;
    text-decoration: none; transition: all .18s !important;
    white-space: nowrap; overflow: hidden;
    background: transparent !important;
    box-shadow: none !important;
}
.sb-nav .nav-link i {
    font-size: .95rem; width: 18px; flex-shrink: 0;
    color: #94A3B8 !important; transition: color .18s;
}
.sb-nav .nav-link:hover {
    background: #F0FDFE !important;
    color: #0BB8D4 !important;
}
.sb-nav .nav-link:hover i { color: #0BB8D4 !important; }
.sb-nav .nav-link.active {
    background: linear-gradient(135deg, #EFF9FC, #F3EFFE) !important;
    color: #0BB8D4 !important; font-weight: 600 !important;
    box-shadow: inset 3px 0 0 #0BB8D4 !important;
}
.sb-nav .nav-link.active i { color: #0BB8D4 !important; }

/* User section */
.sb-user {
    padding: 12px 12px;
    border-top: 1px solid #F1F5F9;
    flex-shrink: 0;
}
.sb-user-card {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 10px;
    text-decoration: none; transition: background .18s;
}
.sb-user-card:hover { background: #F8FAFC; }
.sb-avatar {
    width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
    background: linear-gradient(135deg, #0BB8D4, #8B5CF6);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: white;
}
.sb-user-name {
    font-size: .8125rem; font-weight: 600; color: #0F172A;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 120px;
}
.sb-user-role {
    font-size: .7rem; color: #94A3B8; line-height: 1.2;
}
.sb-logout {
    margin-left: auto; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px;
    border-radius: 8px; border: 1px solid #E2E8F0;
    background: transparent; color: #94A3B8;
    text-decoration: none; transition: all .18s;
    font-size: .9rem;
}
.sb-logout:hover { background: #FEF2F2; border-color: #FCA5A5; color: #DC2626; }

/* Language switcher */
.sb-lang {
    display: flex; gap: 6px; align-items: center;
    margin-bottom: 8px;
}
.sb-lang a {
    text-decoration: none;
    border: 1px solid #E2E8F0;
    color: #64748B;
    background: #fff;
    padding: 3px 8px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1.2;
}
.sb-lang a.active {
    border-color: #0BB8D4;
    color: #0BB8D4;
    background: #F0FDFE;
}

/* ── Overrides: garante cores corretas contra admin-contrast.css ── */
#sidebarMenu { background: #ffffff !important; }
#sidebarMenu .sb-label { color: #94A3B8 !important; font-size: 10px !important; }
#sidebarMenu .sb-nav .nav-link { color: #475569 !important; background: transparent !important; }
#sidebarMenu .sb-nav .nav-link i { color: #94A3B8 !important; }
#sidebarMenu .sb-nav .nav-link:hover { background: #F0FDFE !important; color: #0BB8D4 !important; }
#sidebarMenu .sb-nav .nav-link:hover i { color: #0BB8D4 !important; }
#sidebarMenu .sb-nav .nav-link.active { background: linear-gradient(135deg,#EFF9FC,#F3EFFE) !important; color: #0BB8D4 !important; }
#sidebarMenu .sb-nav .nav-link.active i { color: #0BB8D4 !important; }
#sidebarMenu .sb-brand-name { color: transparent !important; }
#sidebarMenu .sb-user-name { color: #0F172A !important; }
#sidebarMenu .sb-user-role { color: #94A3B8 !important; }
#sidebarMenu .sb-lang a { color: #64748B !important; background: #fff !important; }
#sidebarMenu .sb-lang a.active { color: #0BB8D4 !important; background: #F0FDFE !important; }
#sidebarMenu .sb-logout { color: #94A3B8 !important; background: transparent !important; }
#sidebarMenu .sb-logout:hover { color: #DC2626 !important; background: #FEF2F2 !important; }
</style>

<div id="sidebarBackdrop" class="pl-sidebar-backdrop"></div>

<nav id="sidebarMenu">
    <!-- Brand + mobile toggle -->
    <a class="sb-brand" href="/dashboard.php">
        <img src="/<?= $logoSidebar ?>?v=2" alt="Zetta" class="sb-brand-icon">
        <span class="sb-brand-name"><?= $nomeClienteSB ?></span>
        <button class="sb-toggle" id="sidebarClose" aria-label="Fechar menu">
            <i class="bi bi-x-lg" style="font-size:.9rem;"></i>
        </button>
    </a>

    <!-- Nav links -->
    <div class="sb-nav">
        <?php $isConstrutora = (TenantManager::current()['slug'] === 'crcc'); ?>
        <ul>
        <?php if ($modoOperador): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : '' ?>" href="perfil.php"><i class="bi bi-person"></i><?= t('meu_perfil') ?></a></li>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorios-rdo.php' ? 'active' : '' ?>" href="relatorios-rdo.php"><i class="bi bi-bar-chart"></i><?= t('relatorios_rdo') ?></a></li>

        <?php elseif ($isConstrutora):
            $paginaAtual = basename($_SERVER['PHP_SELF']);
            $emConstrutora = strpos($_SERVER['PHP_SELF'], 'construtora') !== false;
        ?>
            <li><span class="sb-label">Construtora</span></li>
            <?php if ($emConstrutora && $paginaAtual === 'acesso-seguranca.php'): ?>
            <li><a class="nav-link active" href="../construtora/acesso-seguranca.php"><i class="bi bi-person-shield"></i>Painel Segurança</a></li>
            <li><span class="sb-label">Formulários</span></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-inspecao"><i class="bi bi-truck"></i>Nova Inspeção</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-ocorrencia"><i class="bi bi-journal-text"></i>Nova Ocorrência</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-epi"><i class="bi bi-shield-check"></i>Novo EPI</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-colaborador"><i class="bi bi-people"></i>Novo Colaborador</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-nc"><i class="bi bi-exclamation-triangle"></i>Nova NC</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#form-falha"><i class="bi bi-cone-striped"></i>Nova Falha</a></li>
            <li><span class="sb-label">Listas</span></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-inspecoes"><i class="bi bi-clipboard2-data"></i>Inspeções Veiculares</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-ocorrencias"><i class="bi bi-journal-check"></i>Registros de Ocorrência</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-epis"><i class="bi bi-list-check"></i>Lista de EPIs</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-colaboradores"><i class="bi bi-person-lines-fill"></i>Lista de Colaboradores</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-nao-conformidades"><i class="bi bi-card-list"></i>Não Conformidades</a></li>
            <li><a class="nav-link" href="../construtora/acesso-seguranca.php#tab-falhas"><i class="bi bi-exclamation-octagon"></i>Falhas de Segurança</a></li>
            <li><a class="nav-link" href="../construtora/dashboard.php"><i class="bi bi-arrow-left-circle"></i>Voltar ao Dashboard</a></li>
            <?php else: ?>
            <li><a class="nav-link <?= ($paginaAtual == 'dashboard.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
            <li><a class="nav-link <?= ($paginaAtual == 'index.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/index.php"><i class="bi bi-building"></i>Obras</a></li>
            <li><a class="nav-link <?= ($paginaAtual == 'atividades.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/atividades.php"><i class="bi bi-clipboard-plus"></i>Atividades</a></li>
            <li><a class="nav-link <?= ($paginaAtual == 'pagamentos.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/pagamentos.php"><i class="bi bi-cash-coin"></i>Pagamentos</a></li>
            <li><a class="nav-link <?= ($paginaAtual == 'seguranca.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/seguranca.php"><i class="bi bi-shield-check"></i>Segurança</a></li>
            <li><a class="nav-link <?= ($paginaAtual == 'qualidade.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/qualidade.php"><i class="bi bi-patch-check"></i>Qualidade</a></li>
            <?php if ($nivel <= 2): ?>
            <li><a class="nav-link <?= ($paginaAtual == 'usuarios.php' && $emConstrutora) ? 'active' : '' ?>" href="../construtora/usuarios.php"><i class="bi bi-people"></i>Usuários</a></li>
            <?php endif; ?>
            <?php endif; ?>
            <li><span class="sb-label">Conta</span></li>
            <li><a class="nav-link <?= $paginaAtual == 'perfil.php' ? 'active' : '' ?>" href="../perfil.php"><i class="bi bi-person"></i><?= t('meu_perfil') ?></a></li>

        <?php else: ?>
            <?php if (temPermissao('dashboard') && temModulo('indicadores')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php"><i class="bi bi-speedometer2"></i><?= t('dashboard') ?></a></li>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorio-interativo.php' ? 'active' : '' ?>" href="relatorio-interativo.php"><i class="bi bi-graph-up-arrow"></i>Relatório Interativo</a></li>
            <?php endif; ?>
            <?php if (temModulo('documentos')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'documentacao.php' ? 'active' : '' ?>" href="documentacao.php"><i class="bi bi-journal-bookmark-fill"></i><?= t('documentacao') ?></a></li>
            <?php endif; ?>
            <?php if (temPermissao('documentos-internos') && temModulo('documentos_internos')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'documentos-internos.php' ? 'active' : '' ?>" href="documentos-internos.php"><i class="bi bi-file-earmark-text"></i><?= t('documentos_internos') ?></a></li>
            <?php endif; ?>
            <?php if ((temPermissao('inspecoes') || $nivel == 3) && temModulo('inspecoes')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'inspecoes.php' ? 'active' : '' ?>" href="inspecoes.php"><i class="bi bi-clipboard-check"></i><?= t('inspecoes') ?></a></li>
            <?php endif; ?>
            <?php if (temPermissao('padronizar-tecnicos')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'padronizar-tecnicos.php' ? 'active' : '' ?>" href="padronizar-tecnicos.php"><i class="bi bi-people"></i><?= t('padronizar_tecnicos') ?></a></li>
            <?php endif; ?>
            <?php if (temPermissao('relatorios') && temModulo('rdo')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'relatorios-rdo.php' ? 'active' : '' ?>" href="relatorios-rdo.php"><i class="bi bi-bar-chart"></i><?= t('relatorios_rdo') ?></a></li>
            <?php if (basename($_SERVER['PHP_SELF']) === 'rdo-formulario.php'): ?>
            <li><a class="nav-link active" href="rdo-formulario.php"><i class="bi bi-file-earmark-plus"></i>Novo RDO</a></li>
            <?php endif; ?>
            <?php endif; ?>
            <?php if (temModulo('programacao_semanal')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'programacao-semanal.php' ? 'active' : '' ?>" href="programacao-semanal.php"><i class="bi bi-calendar-week"></i><?= t('programacao_semanal') ?></a></li>
            <?php if ($nivel <= 2 || $nivel == 5): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'atividades-em-execucao.php' ? 'active' : '' ?>" href="atividades-em-execucao.php"><i class="bi bi-activity"></i>Em Execução</a></li>
            <?php endif; ?>
            <?php endif; ?>

            <?php if ($nivel <= 2): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'sharepoint.php' ? 'active' : '' ?>" href="sharepoint.php"><i class="bi bi-microsoft"></i>SharePoint</a></li>
            <?php endif; ?>

            <?php if (temPermissao('usuarios') || temPermissao('empresas-externas') || $nivel <= 1): ?>
            <li><span class="sb-label">Administração</span></li>
            <?php endif; ?>
            <?php if (temPermissao('usuarios')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : '' ?>" href="usuarios.php"><i class="bi bi-person-gear"></i><?= t('usuarios') ?></a></li>
            <?php endif; ?>
            <?php if ($nivel <= 2): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'empresas-externas.php' ? 'active' : '' ?>" href="empresas-externas.php"><i class="bi bi-building"></i><?= t('empresas_externas') ?></a></li>
            <?php endif; ?>
            <?php if ($nivel <= 1): ?>
            <li><a class="nav-link" href="admin/login.php" target="_blank" rel="noopener"><i class="bi bi-buildings"></i>Gerenciar Clientes <i class="bi bi-box-arrow-up-right" style="font-size:10px;margin-left:2px;"></i></a></li>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'configuracoes.php' ? 'active' : '' ?>" href="configuracoes.php"><i class="bi bi-gear-fill"></i>Configurações</a></li>
            <?php endif; ?>

            <li><span class="sb-label">Conta</span></li>
            <?php if (temPermissao('perfil')): ?>
            <li><a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'perfil.php' ? 'active' : '' ?>" href="perfil.php"><i class="bi bi-person"></i><?= t('meu_perfil') ?></a></li>
            <?php endif; ?>
        <?php endif; ?>
        </ul>
    </div>

    <!-- Usuário + logout na base -->
    <div class="sb-user">
        <div class="sb-lang">
            <?php foreach ($idiomasSB as $codSB => $labelSB):
                $urlSB = $pathSB . '?' . http_build_query(array_merge($querySB, ['lang' => $codSB]));
            ?>
            <a href="<?= htmlspecialchars($urlSB) ?>" class="<?= $idiomaAtualSB === $codSB ? 'active' : '' ?>"><?= $labelSB ?></a>
            <?php endforeach; ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="/perfil.php" class="sb-user-card flex-grow-1">
                <div class="sb-avatar"><?= $inicialSB ?></div>
                <div>
                    <div class="sb-user-name"><?= $nomeUsuarioSB ?></div>
                    <div class="sb-user-role">Nível <?= getNivelAcesso() ?></div>
                </div>
            </a>
            <a href="/logout.php" class="sb-logout" title="<?= t('sair') ?>">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>

<!-- Mobile toggle flutuante (fora do sidebar) -->
<button class="sb-float-toggle" id="sidebarToggle" aria-label="Menu" style="
    display:none; position:fixed; top:14px; left:14px; z-index:300;
    width:40px; height:40px; border-radius:10px;
    background:white; border:1px solid #E2E8F0;
    align-items:center; justify-content:center;
    cursor:pointer; font-size:1.2rem; color:#475569;
    box-shadow:0 2px 8px rgba(15,23,42,.08);
">
    <i class="bi bi-list"></i>
</button>
<style>
@media (max-width: 767px) {
    .sb-float-toggle { display: flex !important; }
}
</style>

<script>
(function() {
    var toggle   = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarClose');
    var backdrop = document.getElementById('sidebarBackdrop');

    function getSidebar() { return document.getElementById('sidebarMenu'); }
    function open()  {
        var s = getSidebar(); if (!s) return;
        s.classList.add('sidebar-open');
        if (backdrop) backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function close() {
        var s = getSidebar(); if (!s) return;
        s.classList.remove('sidebar-open');
        if (backdrop) backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }
    if (toggle)   toggle.addEventListener('click', function(e){ e.stopPropagation(); getSidebar().classList.contains('sidebar-open') ? close() : open(); });
    if (closeBtn) closeBtn.addEventListener('click', close);
    if (backdrop) backdrop.addEventListener('click', close);

    document.querySelectorAll('#sidebarMenu a.nav-link').forEach(function(l){
        l.addEventListener('click', function(){ if(window.innerWidth < 768) close(); });
    });
})();
</script>
