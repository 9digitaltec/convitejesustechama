<?php
/**
 * Login do Painel Administrativo — 24º Jesus te Chama
 */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';

startSecureAdminSession();

if (isAdminAuthenticated()) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $username  = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
    $password  = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if (!hash_equals(getCsrfToken(), (string) $csrfToken)) {
        $errorMessage = 'Sessão expirada. Atualize a página e tente novamente.';
    } elseif ($username === '' || $password === '') {
        $errorMessage = 'Preencha usuário e senha.';
    } elseif (verifyAdminCredentials($username, $password)) {
        // Regenera o ID da sessão após login bem-sucedido (prevenção de Session Fixation)
        session_regenerate_id(true);
        $_SESSION['jtc_admin_logged_in'] = true;
        $_SESSION['jtc_admin_username']  = $username;
        $_SESSION['jtc_admin_login_at']  = time();

        header('Location: index.php');
        exit;
    } else {
        $errorMessage = 'Usuário ou senha inválidos.';
    }
}

$csrf = getCsrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Login Admin | 24º Jesus te Chama</title>
  <style>
    :root {
      --bg-deep: #061224;
      --bg-card: #0c1f3a;
      --gold: #f4c36d;
      --gold-soft: #f7d99c;
      --text: #f8f5f0;
      --muted: #9fb0c7;
      --danger: #f87171;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      background: radial-gradient(circle at 50% 0%, #122b52 0%, var(--bg-deep) 70%);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-card {
      width: 100%;
      max-width: 400px;
      background: var(--bg-card);
      border: 1px solid rgba(244, 195, 109, 0.28);
      border-radius: 16px;
      padding: 32px 24px;
      box-shadow: 0 16px 40px rgba(0, 0, 0, 0.5);
    }
    .brand {
      text-align: center;
      margin-bottom: 24px;
    }
    .brand-eyebrow {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.1em;
      color: var(--gold);
      font-weight: 700;
    }
    .brand h1 {
      font-size: 1.45rem;
      margin-top: 6px;
      color: var(--text);
    }
    .brand p {
      font-size: 0.88rem;
      color: var(--muted);
      margin-top: 4px;
    }
    .alert-error {
      background: rgba(248, 113, 113, 0.15);
      border: 1px solid var(--danger);
      color: #fecaca;
      padding: 10px 14px;
      border-radius: 10px;
      font-size: 0.88rem;
      margin-bottom: 18px;
      text-align: center;
    }
    .form-group {
      margin-bottom: 16px;
    }
    label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--gold-soft);
      margin-bottom: 6px;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px 14px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.16);
      background: rgba(5, 13, 26, 0.75);
      color: var(--text);
      font-size: 1rem;
    }
    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: 2px solid var(--gold);
      border-color: transparent;
    }
    .btn-submit {
      width: 100%;
      padding: 13px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(135deg, var(--gold) 0%, #e5a948 100%);
      color: #061224;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      margin-top: 6px;
    }
    .btn-submit:hover {
      filter: brightness(1.06);
    }
    .back-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      font-size: 0.85rem;
      color: var(--muted);
      text-decoration: none;
    }
    .back-link:hover {
      color: var(--gold);
    }
  </style>
</head>
<body>
  <main class="login-card">
    <div class="brand">
      <span class="brand-eyebrow">24º Jesus te Chama</span>
      <h1>Painel de Métricas</h1>
      <p>Acesso restrito à coordenação</p>
    </div>

    <?php if ($errorMessage !== ''): ?>
      <div class="alert-error" role="alert">
        <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

      <div class="form-group">
        <label for="username">Usuário</label>
        <input type="text" id="username" name="username" required autofocus autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password">Senha</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn-submit">Entrar no Painel</button>
    </form>

    <a href="../index.html" class="back-link">← Voltar para a Landing Page</a>
  </main>
</body>
</html>
