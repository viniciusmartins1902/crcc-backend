<?php
require_once __DIR__ . '/../auth.php';

if (estaLogado()) { header('Location: /web/dashboard.php'); exit; }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    if (verificarLogin($email, $senha)) {
        session_write_close();
        header('Location: /web/dashboard.php');
        exit;
    }
    $erro = 'E-mail ou senha incorretos.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — CRCC</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #0D47A1 0%, #1565C0 60%, #1976D2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
  .login-card { width: 100%; max-width: 400px; border-radius: 20px; border: none; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
  .login-header { background: linear-gradient(135deg, #0D47A1, #1565C0); border-radius: 20px 20px 0 0; padding: 32px 32px 24px; text-align: center; }
  .login-body { padding: 32px; }
  .brand { font-size: 32px; font-weight: 900; color: #fff; letter-spacing: -1px; }
  .brand-sub { font-size: 13px; color: rgba(255,255,255,.7); margin-top: 4px; }
  .form-control { border-radius: 10px; padding: 12px 14px; border: 1.5px solid #e2e8f0; }
  .form-control:focus { border-color: #1565C0; box-shadow: 0 0 0 3px rgba(21,101,192,.15); }
  .btn-login { background: linear-gradient(135deg, #0D47A1, #1565C0); border: none; border-radius: 10px; padding: 13px; font-size: 15px; font-weight: 700; }
  .btn-login:hover { background: linear-gradient(135deg, #1565C0, #0D47A1); }
</style>
</head>
<body>
<div class="login-card card">
  <div class="login-header">
    <div class="brand">CRCC</div>
    <div class="brand-sub">Gestão de Obras · Zetta</div>
  </div>
  <div class="login-body">
    <?php if ($erro): ?>
      <div class="alert alert-danger py-2 mb-3" style="font-size:14px;border-radius:10px;">
        <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($erro) ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label fw-semibold text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">E-mail</label>
        <input type="email" name="email" class="form-control" placeholder="seu@email.com" required autofocus
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold text-muted" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;">Senha</label>
        <input type="password" name="senha" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary btn-login w-100 text-white">Entrar</button>
    </form>
  </div>
</div>
</body>
</html>
