<?php
/**
 * Pesquisa de Satisfação — Apresentação Zetta
 * Página pública (sem login) — salva em pesquisa_satisfacao no Supabase
 */

require_once __DIR__ . '/../supabase.php';

$enviado = false;
$erro    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nota        = (int)   ($_POST['nota']        ?? 0);
    $interesse   = trim(   $_POST['interesse']     ?? '');
    $perfil      = trim(   $_POST['perfil']        ?? '');
    $tamanho     = trim(   $_POST['tamanho']       ?? '');
    $desafios    = (array) ($_POST['desafios']     ?? []);
    $destaques   = (array) ($_POST['destaques']    ?? []);
    $prazo       = trim(   $_POST['prazo']         ?? '');
    $nome        = trim(   $_POST['nome']          ?? '');
    $empresa     = trim(   $_POST['empresa']       ?? '');
    $whatsapp    = trim(   $_POST['whatsapp']      ?? '');
    $comentario  = trim(   $_POST['comentario']    ?? '');

    if ($nota < 1 || $nota > 5) {
        $erro = 'Por favor, selecione uma nota de 1 a 5.';
    } elseif (!$interesse) {
        $erro = 'Por favor, informe se sua empresa usaria a plataforma.';
    } else {
        $supabase = new Supabase();
        $res = $supabase->request('POST', '/rest/v1/pesquisa_satisfacao', [
            'nota'       => $nota,
            'interesse'  => $interesse,
            'perfil'     => $perfil     ?: null,
            'tamanho'    => $tamanho    ?: null,
            'desafios'   => $desafios   ? implode(', ', array_map('trim', $desafios))  : null,
            'destaques'  => $destaques  ? implode(', ', array_map('trim', $destaques)) : null,
            'prazo'      => $prazo      ?: null,
            'nome'       => $nome       ?: null,
            'empresa'    => $empresa    ?: null,
            'whatsapp'   => $whatsapp   ?: null,
            'comentario' => $comentario ?: null,
            'criado_em'  => date('c'),
        ]);

        if ($res === false || (is_array($res) && isset($res['code']))) {
            $erro = 'Erro ao salvar. Tente novamente.';
        } else {
            $enviado = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesquisa de Satisfação — Zetta</title>
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --primary:      #1976d2;
    --primary-dark: #1565c0;
    --primary-light:#e3f0ff;
    --success:      #16a34a;
    --danger:       #dc2626;
    --warning:      #d97706;
    --bg:           #f0f4f8;
    --surface:      #ffffff;
    --text:         #1e293b;
    --muted:        #64748b;
    --border:       #e2e8f0;
    --radius:       16px;
  }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    padding: 0 0 80px;
  }

  /* ── Header ── */
  .header {
    background: linear-gradient(135deg, #0a2463, #1976d2 60%, #1e88e5);
    padding: 40px 24px 56px;
    text-align: center;
    position: relative;
    overflow: hidden;
  }
  .header::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,0.06);
  }
  .header::after {
    content: '';
    position: absolute;
    bottom: -24px; left: 0; right: 0;
    height: 48px;
    background: var(--bg);
    border-radius: 50% 50% 0 0 / 100% 100% 0 0;
  }
  .header-logo {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,255,255,0.12);
    border: 1px solid rgba(255,255,255,0.2);
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    padding: 6px 16px;
    border-radius: 20px;
    letter-spacing: .5px;
    margin-bottom: 18px;
  }
  .header h1 {
    font-size: 26px;
    font-weight: 800;
    color: #fff;
    margin-bottom: 8px;
    line-height: 1.2;
  }
  .header p {
    font-size: 14px;
    color: rgba(255,255,255,0.72);
    line-height: 1.6;
  }

  /* ── Progresso ── */
  .progress-bar {
    height: 4px;
    background: var(--border);
    border-radius: 2px;
    margin: 0 0 24px;
    overflow: hidden;
  }
  .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--primary), #42a5f5);
    border-radius: 2px;
    transition: width .4s ease;
  }

  /* ── Container ── */
  .container {
    max-width: 580px;
    margin: 0 auto;
    padding: 28px 16px 0;
  }

  /* ── Seção numerada ── */
  .secao-num {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
  }
  .num-badge {
    width: 26px; height: 26px;
    border-radius: 50%;
    background: var(--primary);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
  }
  .num-titulo {
    font-size: 14px;
    font-weight: 700;
    color: var(--text);
  }
  .num-subtitulo {
    font-size: 12px;
    color: var(--muted);
    margin-top: 1px;
  }

  /* ── Card ── */
  .card {
    background: var(--surface);
    border-radius: var(--radius);
    padding: 20px;
    margin-bottom: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    border: 1px solid rgba(0,0,0,0.04);
  }

  /* ── Estrelas ── */
  .star-group {
    display: flex;
    gap: 6px;
    justify-content: center;
    padding: 8px 0 4px;
  }
  .star-btn {
    background: none;
    border: none;
    font-size: 42px;
    cursor: pointer;
    color: #cbd5e1;
    transition: color .1s, transform .12s;
    padding: 2px 4px;
    line-height: 1;
    -webkit-tap-highlight-color: transparent;
  }
  .star-btn.active { color: #f59e0b; }
  .star-btn:active  { transform: scale(1.25); }
  .star-label {
    text-align: center;
    font-size: 14px;
    color: var(--muted);
    min-height: 22px;
    font-weight: 600;
    transition: color .15s;
  }

  /* ── Grid de chips (2 colunas) ── */
  .chips-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
  .chips-grid.one-col { grid-template-columns: 1fr; }
  @media (max-width: 400px) { .chips-grid { grid-template-columns: 1fr; } }

  .chip-label {
    display: flex;
    align-items: center;
    gap: 9px;
    padding: 11px 14px;
    border: 2px solid var(--border);
    border-radius: 12px;
    cursor: pointer;
    font-size: 13.5px;
    font-weight: 500;
    color: var(--text);
    transition: all .15s;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
  }
  .chip-label input { display: none; }
  .chip-label .chip-icon { font-size: 16px; flex-shrink: 0; }
  .chip-label:hover  { border-color: var(--primary); background: var(--primary-light); }
  .chip-label.selected {
    border-color: var(--primary);
    background: var(--primary-light);
    color: var(--primary);
    font-weight: 700;
  }
  .chip-label.selected::after {
    content: '✓';
    margin-left: auto;
    font-size: 13px;
    color: var(--primary);
    font-weight: 800;
  }

  /* Opção de radio grande (interesse) */
  .opcao-grande {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border: 2px solid var(--border);
    border-radius: 14px;
    cursor: pointer;
    transition: all .15s;
    margin-bottom: 10px;
    user-select: none;
  }
  .opcao-grande:last-child { margin-bottom: 0; }
  .opcao-grande input { display: none; }
  .opcao-grande .opcao-emoji { font-size: 22px; flex-shrink: 0; }
  .opcao-grande .opcao-texto strong { font-size: 14px; font-weight: 700; display: block; }
  .opcao-grande .opcao-texto span  { font-size: 12px; color: var(--muted); }
  .opcao-grande:hover { border-color: var(--primary); background: var(--primary-light); }
  .opcao-grande.selected { border-color: var(--primary); background: var(--primary-light); }
  .opcao-grande.selected .opcao-texto strong { color: var(--primary); }
  .radio-check {
    width: 22px; height: 22px;
    border-radius: 50%;
    border: 2px solid var(--border);
    margin-left: auto;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
  }
  .opcao-grande.selected .radio-check {
    background: var(--primary);
    border-color: var(--primary);
  }
  .opcao-grande.selected .radio-check::after {
    content: '';
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #fff;
  }

  /* ── Campos de texto ── */
  .field { margin-bottom: 12px; }
  .field:last-child { margin-bottom: 0; }
  .field label {
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    display: block;
    margin-bottom: 6px;
  }
  .field input, .field textarea, .field select {
    width: 100%;
    background: #f8fafc;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 12px 14px;
    font-size: 15px;
    color: var(--text);
    outline: none;
    transition: border-color .15s, background .15s;
    font-family: inherit;
    appearance: none;
  }
  .field input:focus, .field textarea:focus, .field select:focus {
    border-color: var(--primary);
    background: #fff;
  }
  .field textarea { resize: none; min-height: 90px; }
  .fields-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
  @media (max-width: 480px) { .fields-row { grid-template-columns: 1fr; } }

  /* ── Botão enviar ── */
  .btn-enviar {
    width: 100%;
    background: linear-gradient(135deg, var(--primary), #1e88e5);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    padding: 17px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
    margin-top: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 4px 16px rgba(25,118,210,0.35);
    letter-spacing: .2px;
  }
  .btn-enviar:hover  { opacity: .92; }
  .btn-enviar:active { transform: scale(.98); opacity: .85; }

  /* ── Erro ── */
  .erro-box {
    background: rgba(220,38,38,0.08);
    border: 1.5px solid rgba(220,38,38,0.25);
    border-radius: 12px;
    color: var(--danger);
    font-size: 14px;
    padding: 14px 16px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  /* ── Sucesso ── */
  .sucesso-card {
    text-align: center;
    padding: 32px 20px;
  }
  .sucesso-icon {
    width: 88px; height: 88px;
    background: rgba(22,163,74,0.1);
    border-radius: 50%;
    color: var(--success);
    font-size: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
  }
  .sucesso-card h2 { font-size: 22px; font-weight: 800; color: var(--text); margin-bottom: 10px; }
  .sucesso-card p  { font-size: 15px; color: var(--muted); line-height: 1.7; }

  /* ── Rodapé ── */
  .footer {
    text-align: center;
    padding: 24px 16px 0;
    font-size: 12px;
    color: var(--muted);
  }
  .footer strong { color: var(--primary); }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="header-logo">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
      <polyline points="9 22 9 12 15 12 15 22"/>
    </svg>
    Zetta · Gestão de Obras
  </div>
  <h1>Pesquisa de Satisfação</h1>
  <p>Sua opinião nos ajuda a construir<br>a melhor plataforma para o setor.</p>
</div>

<div class="container">

<?php if ($enviado): ?>

  <div class="card sucesso-card">
    <div class="sucesso-icon">✓</div>
    <h2>Obrigado pelo seu feedback!</h2>
    <p>Sua resposta foi registrada com sucesso.<br>Em breve entraremos em contato.</p>
  </div>

<?php else: ?>

  <?php if ($erro): ?>
    <div class="erro-box">⚠️ <?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>

  <form method="POST" id="form-pesquisa">

    <!-- 1. Avaliação geral -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">1</div>
        <div>
          <div class="num-titulo">Como você avalia a apresentação?</div>
          <div class="num-subtitulo">Sua impressão geral sobre o que foi mostrado</div>
        </div>
      </div>
      <div class="star-group" id="stars">
        <button type="button" class="star-btn" data-val="1">★</button>
        <button type="button" class="star-btn" data-val="2">★</button>
        <button type="button" class="star-btn" data-val="3">★</button>
        <button type="button" class="star-btn" data-val="4">★</button>
        <button type="button" class="star-btn" data-val="5">★</button>
      </div>
      <div class="star-label" id="star-label">Toque para avaliar</div>
      <input type="hidden" name="nota" id="nota-hidden" value="<?= (int)($_POST['nota'] ?? 0) ?>">
    </div>

    <!-- 2. Perfil -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">2</div>
        <div>
          <div class="num-titulo">Qual é o seu perfil?</div>
          <div class="num-subtitulo">Selecione o que melhor descreve seu cargo</div>
        </div>
      </div>
      <div class="chips-grid">
        <?php
        $perfis = [
          'proprietario'  => ['🏢', 'Proprietário / Sócio'],
          'gestor'        => ['📋', 'Gestor / Coordenador'],
          'engenheiro'    => ['⚙️', 'Engenheiro / Técnico'],
          'encarregado'   => ['🦺', 'Encarregado de Campo'],
          'financeiro'    => ['💰', 'Financeiro / Adm.'],
          'outro'         => ['👤', 'Outro'],
        ];
        $postPerfil = $_POST['perfil'] ?? '';
        foreach ($perfis as $val => [$emoji, $label]):
        ?>
        <label class="chip-label <?= $postPerfil === $val ? 'selected' : '' ?>">
          <input type="radio" name="perfil" value="<?= $val ?>" <?= $postPerfil === $val ? 'checked' : '' ?>>
          <span class="chip-icon"><?= $emoji ?></span> <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 3. Tamanho da empresa -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">3</div>
        <div>
          <div class="num-titulo">Tamanho da sua empresa</div>
          <div class="num-subtitulo">Quantos funcionários / colaboradores no total?</div>
        </div>
      </div>
      <div class="chips-grid">
        <?php
        $tamanhos = [
          '1-10'   => ['🏠', 'Até 10 pessoas'],
          '11-50'  => ['🏗️', '11 a 50 pessoas'],
          '51-200' => ['🏢', '51 a 200 pessoas'],
          '200+'   => ['🏙️', 'Mais de 200'],
        ];
        $postTamanho = $_POST['tamanho'] ?? '';
        foreach ($tamanhos as $val => [$emoji, $label]):
        ?>
        <label class="chip-label <?= $postTamanho === $val ? 'selected' : '' ?>">
          <input type="radio" name="tamanho" value="<?= $val ?>" <?= $postTamanho === $val ? 'checked' : '' ?>>
          <span class="chip-icon"><?= $emoji ?></span> <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 4. Desafios atuais -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">4</div>
        <div>
          <div class="num-titulo">Quais são seus maiores desafios hoje?</div>
          <div class="num-subtitulo">Selecione todos que se aplicam</div>
        </div>
      </div>
      <div class="chips-grid">
        <?php
        $desafios_opcoes = [
          'controle_os'       => ['🔧', 'Controle de OS'],
          'relatorios'        => ['📄', 'Relatórios manuais'],
          'comunicacao'       => ['📡', 'Comunicação em campo'],
          'gestao_custos'     => ['💸', 'Gestão de custos'],
          'visibilidade'      => ['👁️', 'Visibilidade em tempo real'],
          'reembolsos'        => ['🧾', 'Controle de reembolsos'],
          'produtividade'     => ['⏱️', 'Produtividade da equipe'],
          'documentacao'      => ['📁', 'Documentação e histórico'],
        ];
        $postDesafios = (array)($_POST['desafios'] ?? []);
        foreach ($desafios_opcoes as $val => [$emoji, $label]):
        ?>
        <label class="chip-label <?= in_array($val, $postDesafios) ? 'selected' : '' ?>">
          <input type="checkbox" name="desafios[]" value="<?= $val ?>" <?= in_array($val, $postDesafios) ? 'checked' : '' ?>>
          <span class="chip-icon"><?= $emoji ?></span> <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 5. Destaques da plataforma -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">5</div>
        <div>
          <div class="num-titulo">O que mais chamou sua atenção?</div>
          <div class="num-subtitulo">Funcionalidades que mais te interessaram</div>
        </div>
      </div>
      <div class="chips-grid">
        <?php
        $destaques_opcoes = [
          'app_mobile'    => ['📱', 'App Mobile'],
          'dashboard_web' => ['💻', 'Dashboard Web'],
          'rdo_digital'   => ['📝', 'RDO Digital'],
          'gestao_os'     => ['🔧', 'Gestão de OS'],
          'reembolsos'    => ['💳', 'Reembolsos'],
          'relatorios'    => ['📊', 'Relatórios'],
          'offline'       => ['📶', 'Funciona Offline'],
          'integracao'    => ['🔗', 'Integrações'],
        ];
        $postDestaques = (array)($_POST['destaques'] ?? []);
        foreach ($destaques_opcoes as $val => [$emoji, $label]):
        ?>
        <label class="chip-label <?= in_array($val, $postDestaques) ? 'selected' : '' ?>">
          <input type="checkbox" name="destaques[]" value="<?= $val ?>" <?= in_array($val, $postDestaques) ? 'checked' : '' ?>>
          <span class="chip-icon"><?= $emoji ?></span> <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 6. Interesse -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">6</div>
        <div>
          <div class="num-titulo">Sua empresa usaria essa plataforma?</div>
          <div class="num-subtitulo">Sendo honesto, qual é o nível de interesse?</div>
        </div>
      </div>
      <?php
      $opcoes = [
        'sim'    => ['🚀', 'Sim, quero contratar!',          'Tenho interesse imediato'],
        'talvez' => ['🤔', 'Talvez, preciso avaliar',        'Vou analisar com a equipe'],
        'nao'    => ['⏳', 'Não agora, mas no futuro',       'Interessante para mais adiante'],
        'nao_fit'=> ['❌', 'Não é o que preciso',            'Não se encaixa no momento'],
      ];
      $postInteresse = $_POST['interesse'] ?? '';
      foreach ($opcoes as $val => [$emoji, $forte, $fraco]):
      ?>
      <label class="opcao-grande <?= $postInteresse === $val ? 'selected' : '' ?>">
        <input type="radio" name="interesse" value="<?= $val ?>" <?= $postInteresse === $val ? 'checked' : '' ?> required>
        <span class="opcao-emoji"><?= $emoji ?></span>
        <div class="opcao-texto">
          <strong><?= $forte ?></strong>
          <span><?= $fraco ?></span>
        </div>
        <div class="radio-check"></div>
      </label>
      <?php endforeach; ?>
    </div>

    <!-- 7. Prazo de implementação -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">7</div>
        <div>
          <div class="num-titulo">Em quanto tempo pensaria em implementar?</div>
          <div class="num-subtitulo">Estimativa do prazo para adoção</div>
        </div>
      </div>
      <div class="chips-grid">
        <?php
        $prazos = [
          'imediato'    => ['⚡', 'Imediato'],
          '3_meses'     => ['📅', 'Até 3 meses'],
          '6_meses'     => ['🗓️', '3 a 6 meses'],
          'sem_previsao'=> ['🔮', 'Sem previsão'],
        ];
        $postPrazo = $_POST['prazo'] ?? '';
        foreach ($prazos as $val => [$emoji, $label]):
        ?>
        <label class="chip-label <?= $postPrazo === $val ? 'selected' : '' ?>">
          <input type="radio" name="prazo" value="<?= $val ?>" <?= $postPrazo === $val ? 'checked' : '' ?>>
          <span class="chip-icon"><?= $emoji ?></span> <?= $label ?>
        </label>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- 8. Dados de contato -->
    <div class="card">
      <div class="secao-num">
        <div class="num-badge">8</div>
        <div>
          <div class="num-titulo">Seus dados para contato</div>
          <div class="num-subtitulo">Todos os campos são opcionais</div>
        </div>
      </div>
      <div class="fields-row">
        <div class="field">
          <label>Nome</label>
          <input type="text" name="nome" placeholder="Seu nome" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
        </div>
        <div class="field">
          <label>Empresa</label>
          <input type="text" name="empresa" placeholder="Nome da empresa" value="<?= htmlspecialchars($_POST['empresa'] ?? '') ?>">
        </div>
      </div>
      <div class="field">
        <label>WhatsApp</label>
        <input type="tel" name="whatsapp" placeholder="(00) 00000-0000" value="<?= htmlspecialchars($_POST['whatsapp'] ?? '') ?>">
      </div>
      <div class="field">
        <label>Comentários e sugestões</label>
        <textarea name="comentario" placeholder="O que sentiu falta? O que mais gostou? Alguma dúvida?"><?= htmlspecialchars($_POST['comentario'] ?? '') ?></textarea>
      </div>
    </div>

    <button type="submit" class="btn-enviar">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20">
        <line x1="22" y1="2" x2="11" y2="13"/>
        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
      Enviar Pesquisa
    </button>

  </form>

  <div class="footer">
    <p>Desenvolvido por <strong>Zetta</strong> · crcc.zetta.net.br</p>
  </div>

<?php endif; ?>

</div>

<script>
// ── Estrelas ──
const starLabels = {1:'Ruim 😕', 2:'Regular 😐', 3:'Bom 🙂', 4:'Muito bom 😊', 5:'Excelente! 🤩'};
const starBtns   = document.querySelectorAll('.star-btn');
const notaHidden = document.getElementById('nota-hidden');
const starLabel  = document.getElementById('star-label');
let currentStar  = parseInt(notaHidden.value) || 0;

function renderStars(val) {
  starBtns.forEach(b => b.classList.toggle('active', parseInt(b.dataset.val) <= val));
  starLabel.textContent  = val ? starLabels[val] : 'Toque para avaliar';
  starLabel.style.color  = val ? '#f59e0b' : '';
}

starBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    currentStar = parseInt(btn.dataset.val);
    notaHidden.value = currentStar;
    renderStars(currentStar);
  });
  btn.addEventListener('mouseenter', () => renderStars(parseInt(btn.dataset.val)));
  btn.addEventListener('mouseleave',  () => renderStars(currentStar));
});

renderStars(currentStar);

// ── Chips (radio e checkbox) ──
document.querySelectorAll('.chip-label').forEach(label => {
  label.addEventListener('click', () => {
    const input = label.querySelector('input');
    if (!input) return;

    if (input.type === 'radio') {
      const name = input.name;
      document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
        r.closest('.chip-label')?.classList.remove('selected');
      });
      label.classList.add('selected');
    } else {
      setTimeout(() => {
        label.classList.toggle('selected', input.checked);
      }, 0);
    }
  });
});

// ── Opções grandes (interesse) ──
document.querySelectorAll('.opcao-grande').forEach(op => {
  op.addEventListener('click', () => {
    document.querySelectorAll('.opcao-grande').forEach(o => o.classList.remove('selected'));
    op.classList.add('selected');
  });
});

// ── Validação de nota ──
document.getElementById('form-pesquisa')?.addEventListener('submit', e => {
  if (!currentStar) {
    e.preventDefault();
    starLabel.textContent = '⚠️ Selecione uma nota antes de enviar';
    starLabel.style.color = '#dc2626';
    document.querySelector('.star-group').scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
});
</script>
</body>
</html>
