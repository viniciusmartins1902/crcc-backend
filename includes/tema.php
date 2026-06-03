<?php
/**
 * Tema dinâmico por tenant
 * Gera CSS variables a partir das cores salvas em tenants.config.
 * Inclua APÓS sistema-config.php estar carregado.
 */

if (!function_exists('getConfigSistema')) {
    require_once __DIR__ . '/sistema-config.php';
}

/**
 * Converte hex (#rrggbb ou #rgb) para array [r, g, b]
 */
function hexParaRgb(string $hex): array {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (strlen($hex) !== 6) return [13, 71, 161]; // fallback azul
    return [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
}

/**
 * Escurece uma cor hex em $pct% (0-100)
 */
function escurecerCor(string $hex, int $pct): string {
    [$r,$g,$b] = hexParaRgb($hex);
    $fator = 1 - ($pct / 100);
    return sprintf('#%02x%02x%02x',
        max(0, (int)round($r * $fator)),
        max(0, (int)round($g * $fator)),
        max(0, (int)round($b * $fator))
    );
}

/**
 * Clareia uma cor hex em $pct%
 */
function clarearCor(string $hex, int $pct): string {
    [$r,$g,$b] = hexParaRgb($hex);
    $fator = $pct / 100;
    return sprintf('#%02x%02x%02x',
        min(255, (int)round($r + (255 - $r) * $fator)),
        min(255, (int)round($g + (255 - $g) * $fator)),
        min(255, (int)round($b + (255 - $b) * $fator))
    );
}

$cor1  = getConfigSistema('cor_primaria',   '#0D47A1');
$cor2  = getConfigSistema('cor_secundaria', '#1565c0');

// Valida que são hex válidos
if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $cor1)) $cor1 = '#0D47A1';
if (!preg_match('/^#[a-fA-F0-9]{3,6}$/', $cor2)) $cor2 = '#1565c0';

$cor1Dark  = escurecerCor($cor1, 12);
$cor1Light = clarearCor($cor1, 85);
[$r1,$g1,$b1] = hexParaRgb($cor1);
[$r2,$g2,$b2] = hexParaRgb($cor2);
?>
<style id="tema-tenant">
:root {
    --cor-primaria:     <?= $cor1 ?>;
    --cor-secundaria:   <?= $cor2 ?>;
    --cor-primaria-dark:<?= $cor1Dark ?>;
    --cor-primaria-rgb: <?= "$r1,$g1,$b1" ?>;
    --cor-secundaria-rgb:<?= "$r2,$g2,$b2" ?>;
    --cor-primaria-light:<?= $cor1Light ?>;
    /* Override Bootstrap */
    --bs-primary:       <?= $cor1 ?>;
    --bs-link-color:    <?= $cor2 ?>;
    --zt-text-on-dark:  #F8FAFC;
    --zt-text-on-light: #0F172A;
}

/* Botões */
.btn-primary,
.btn-primary:focus {
    background: linear-gradient(135deg, <?= $cor1 ?>, <?= $cor2 ?>) !important;
    border-color: <?= $cor1 ?> !important;
    box-shadow: 0 4px 12px rgba(<?= "$r1,$g1,$b1" ?>, 0.4) !important;
}
.btn-primary:hover {
    background: linear-gradient(135deg, <?= $cor2 ?>, <?= $cor1 ?>) !important;
    border-color: <?= $cor2 ?> !important;
}
.btn-outline-primary {
    color: <?= $cor1 ?> !important;
    border-color: <?= $cor1 ?> !important;
}
.btn-outline-primary:hover {
    background-color: <?= $cor1 ?> !important;
    color: #fff !important;
}

/* Tabs */
.nav-tabs .nav-link.active,
.nav-pills .nav-link.active {
    background-color: <?= $cor1 ?> !important;
    border-color: <?= $cor1 ?> !important;
}
.nav-tabs .nav-link:hover { color: <?= $cor1 ?> !important; }

/* Texto / borda / fundo */
.text-primary   { color: <?= $cor1 ?> !important; }
.bg-primary     { background-color: <?= $cor1 ?> !important; }
.border-primary { border-color: <?= $cor1 ?> !important; }

/* Links */
a       { color: <?= $cor2 ?> !important; }
a:hover { color: <?= $cor1 ?> !important; }

/* Focus */
.form-control:focus,
.form-select:focus {
    border-color: <?= $cor1 ?> !important;
    box-shadow: 0 0 0 3px rgba(<?= "$r1,$g1,$b1" ?>, 0.2) !important;
}

/* Sidebar */
.sidebar .nav-link.active {
    background: <?= $cor1 ?> !important;
    box-shadow: 0 4px 12px rgba(<?= "$r1,$g1,$b1" ?>, 0.5) !important;
}

/* Dashboard — cabeçalhos de cards */
.card-header[style*="linear-gradient"],
.card[style*="linear-gradient"] > .card-header {
    /* mantém o linear-gradient mas usa as vars dinamicamente */
}

/* Gradientes de cards (Dashboard) */
.card-gradient-primary {
    background: linear-gradient(135deg, <?= $cor1 ?>, <?= $cor2 ?>) !important;
}

/* Barras de topo em cards (accent bar) */
[style*="background: linear-gradient(90deg, #0D47A1"],
[style*="background: linear-gradient(90deg, var(--cor-primaria"] {
    background: linear-gradient(90deg, <?= $cor1 ?>, <?= $cor2 ?>) !important;
}

/* Overrides inline de gradientes usados em cabeçalhos de cards do dashboard */
.card .card-header {
    --card-grad-start: <?= $cor1 ?>;
    --card-grad-end:   <?= $cor2 ?>;
}

/* Dashboard tabs ativas */
.nav-link.active {
    background: linear-gradient(135deg, <?= $cor1 ?>, <?= $cor2 ?>) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(<?= "$r1,$g1,$b1" ?>, 0.3) !important;
}
.nav-link:hover {
    color: <?= $cor1 ?> !important;
    background-color: rgba(<?= "$r1,$g1,$b1" ?>, 0.06) !important;
}
.nav-link:hover i { color: <?= $cor1 ?> !important; }
.nav-link.active i { color: white !important; }

/* Spinner / progress */
.spinner-border { color: <?= $cor1 ?> !important; }

/* Badge primary */
.badge.bg-primary { background-color: <?= $cor1 ?> !important; }

/* Checkbox accent */
input[type="checkbox"], input[type="radio"] {
    accent-color: <?= $cor1 ?>;
}

/* Gradiente do body mantém escuro mas adiciona leve toque da cor do tenant */
body {
    background: linear-gradient(135deg,
        color-mix(in srgb, #0f2027 85%, <?= $cor1 ?> 15%),
        color-mix(in srgb, #203a43 80%, <?= $cor1 ?> 20%),
        color-mix(in srgb, #1a0a0a 90%, <?= $cor1 ?> 10%)
    ) !important;
}

/* Navbar brand gradient */
.navbar-brand-sgm .brand-name {
    background: linear-gradient(90deg, <?= $cor2 ?>, #ffffff) !important;
    -webkit-background-clip: text !important;
    -webkit-text-fill-color: transparent !important;
    background-clip: text !important;
}

/* Correção global de contraste em superfícies detectadas via JS */
.zt-contrast-dark,
.zt-contrast-dark h1,
.zt-contrast-dark h2,
.zt-contrast-dark h3,
.zt-contrast-dark h4,
.zt-contrast-dark h5,
.zt-contrast-dark h6,
.zt-contrast-dark p,
.zt-contrast-dark small,
.zt-contrast-dark span,
.zt-contrast-dark label,
.zt-contrast-dark strong,
.zt-contrast-dark i,
.zt-contrast-dark .card-title,
.zt-contrast-dark .form-label {
    color: var(--zt-text-on-dark) !important;
}

.zt-contrast-light,
.zt-contrast-light h1,
.zt-contrast-light h2,
.zt-contrast-light h3,
.zt-contrast-light h4,
.zt-contrast-light h5,
.zt-contrast-light h6,
.zt-contrast-light p,
.zt-contrast-light small,
.zt-contrast-light span,
.zt-contrast-light label,
.zt-contrast-light strong,
.zt-contrast-light i,
.zt-contrast-light .card-title,
.zt-contrast-light .form-label {
    color: var(--zt-text-on-light) !important;
}

/* Não sobrescreve componentes interativos */
.zt-contrast-dark input,
.zt-contrast-light input,
.zt-contrast-dark select,
.zt-contrast-light select,
.zt-contrast-dark textarea,
.zt-contrast-light textarea,
.zt-contrast-dark .dropdown-menu,
.zt-contrast-light .dropdown-menu {
    color: initial !important;
}

/* Botão Finalizar em Documentação: garantir contraste mesmo com overrides globais */
.btn-finalizar-doc,
.doc-actions .btn-finalizar-doc,
.doc-actions form .btn-finalizar-doc {
    background: #334155 !important;
    border-color: #334155 !important;
    color: #ffffff !important;
    font-weight: 700 !important;
}
.btn-finalizar-doc:hover,
.doc-actions .btn-finalizar-doc:hover,
.doc-actions form .btn-finalizar-doc:hover {
    background: #1f2937 !important;
    border-color: #1f2937 !important;
    color: #ffffff !important;
}
</style>
<script>
// Aplica tema dinâmico sobre gradientes inline hardcoded
(function() {
    var c1 = '<?= addslashes($cor1) ?>';
    var c2 = '<?= addslashes($cor2) ?>';
    var r1 = <?= $r1 ?>, g1 = <?= $g1 ?>, b1 = <?= $b1 ?>;

    function hexToRgb(hex) {
        if (!hex) return null;
        var h = hex.replace('#', '').trim();
        if (h.length === 3) h = h.split('').map(function(ch){ return ch + ch; }).join('');
        if (h.length !== 6) return null;
        var n = parseInt(h, 16);
        if (isNaN(n)) return null;
        return { r: (n >> 16) & 255, g: (n >> 8) & 255, b: n & 255 };
    }

    function luminance(rgb) {
        if (!rgb) return 255;
        return (0.2126 * rgb.r) + (0.7152 * rgb.g) + (0.0722 * rgb.b);
    }

    function extractColors(styleText) {
        if (!styleText) return [];
        var colors = [];
        var hexMatches = styleText.match(/#[0-9a-fA-F]{3,6}/g) || [];
        hexMatches.forEach(function(h) {
            var rgb = hexToRgb(h);
            if (rgb) colors.push(rgb);
        });
        var rgbMatches = styleText.match(/rgba?\(([^)]+)\)/g) || [];
        rgbMatches.forEach(function(v) {
            var nums = (v.match(/\d+(?:\.\d+)?/g) || []).map(Number);
            if (nums.length >= 3) {
                colors.push({ r: nums[0], g: nums[1], b: nums[2] });
            }
        });
        return colors;
    }

    function applyContrastClass(el) {
        if (!el || !el.getAttribute) return;
        var s = el.getAttribute('style') || '';
        if (!s) return;
        if (!/background|linear-gradient/i.test(s)) return;

        var colors = extractColors(s);
        if (!colors.length) return;

        var avg = colors.reduce(function(acc, c) {
            acc.r += c.r; acc.g += c.g; acc.b += c.b;
            return acc;
        }, { r: 0, g: 0, b: 0 });
        avg.r /= colors.length;
        avg.g /= colors.length;
        avg.b /= colors.length;

        var isDark = luminance(avg) < 150;
        el.classList.remove('zt-contrast-dark', 'zt-contrast-light');
        el.classList.add(isDark ? 'zt-contrast-dark' : 'zt-contrast-light');
    }

    function applyTheme() {
        // Substitui todos os elementos com gradiente azul hardcoded
        document.querySelectorAll('[style]').forEach(function(el) {
            var s = el.getAttribute('style');
            if (!s) return;
            // linear-gradient com as cores padrão PowerChina
            s = s.replace(/linear-gradient\(135deg,\s*#0D47A1,\s*#1565c0\)/gi,
                          'linear-gradient(135deg,' + c1 + ',' + c2 + ')');
            s = s.replace(/linear-gradient\(135deg,\s*#1565c0,\s*#0D47A1\)/gi,
                          'linear-gradient(135deg,' + c2 + ',' + c1 + ')');
            s = s.replace(/linear-gradient\(90deg,\s*#0D47A1,\s*#1565c0\)/gi,
                          'linear-gradient(90deg,' + c1 + ',' + c2 + ')');
            s = s.replace(/linear-gradient\(90deg,\s*#F5576C,\s*#F093FB\)/gi,
                          'linear-gradient(90deg,' + c1 + ',' + c2 + ')');
            s = s.replace(/#0D47A1/gi, c1);
            s = s.replace(/#1565c0/gi, c2);
            el.setAttribute('style', s);
            applyContrastClass(el);
        });
        // Títulos com cor inline
        document.querySelectorAll('[style*="#0D47A1"], [style*="#1565c0"]').forEach(function(el) {
            var s = el.getAttribute('style');
            if (!s) return;
            s = s.replace(/#0D47A1/gi, c1).replace(/#1565c0/gi, c2);
            el.setAttribute('style', s);
            applyContrastClass(el);
        });

        // Garante contraste em elementos dinâmicos já renderizados
        document.querySelectorAll('[style*="background"], [style*="linear-gradient"], [style*="background-color"]').forEach(function(el) {
            applyContrastClass(el);
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyTheme);
    } else {
        applyTheme();
    }
    // Observa mudanças dinâmicas (gráficos, AJAX)
    var mo = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            m.addedNodes.forEach(function(node) {
                if (node.nodeType === 1) {
                    if (node.getAttribute && node.getAttribute('style')) applyTheme();
                    node.querySelectorAll && node.querySelectorAll('[style]').length && applyTheme();
                }
            });
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        mo.observe(document.body, { childList: true, subtree: true });
    });
})();
</script>
