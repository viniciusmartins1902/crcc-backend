<?php
require_once __DIR__ . '/../auth.php';

if (!empty($_SESSION['logado'])) {
    header('Location: /web/dashboard.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');
    if (verificarLogin($email, $senha)) {
        header('Location: /web/dashboard.php');
        exit;
    }
    $erro = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — CRCC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg,#0d47a1,#1976d2); min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .card { border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,.2); border:none; }
  .logo { font-size:28px; font-weight:800; color:#1976d2; letter-spacing:-1px; }
</style>
</head>
<body>
<div class="card p-4" style="width:100%;max-width:380px;">
  <div class="text-center mb-4">
    <div class="logo">CRCC</div>
    <small class="text-muted">Gestão de Obras — Zetta</small>
  </div>
  <?php if ($erro): ?>
    <div class="alert alert-danger py-2" style="font-size:13px;"><?= htmlspecialchars($erro) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="mb-3">
      <label class="form-label fw-semibold">E-mail</label>
      <input type="email" name="email" class="form-control" required autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label fw-semibold">Senha</label>
      <input type="password" name="senha" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary w-100 fw-bold">Entrar</button>
  </form>
</div>
</body>
</html>
