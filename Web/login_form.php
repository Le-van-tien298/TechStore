<?php
session_start();

// Nếu đã đăng nhập rồi thì redirect về trang chủ
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/Database.php';
$db = Database::getInstance();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.';
    } else {
        $user = $db->login($username, $password);
        if ($user) {
            $_SESSION['user']     = $user['username'];
            $_SESSION['name']     = $user['name'];
            $_SESSION['role']     = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng nhập – TechStore</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy-deep: #080f2d;
      --navy:      #0d1b4b;
      --blue:      #2563eb;
      --blue-dark: #1d4ed8;
      --cyan:      #06b6d4;
      --red:       #ef4444;
      --text:      #111827;
      --muted:     #6b7280;
      --border:    #e5e7eb;
      --bg:        #f8fafc;
      --white:     #ffffff;
      --shadow-lg: 0 16px 48px rgba(13,27,75,0.18);
      --r-sm:      8px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* NAVBAR */
    nav {
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .nav-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      padding: 14px 24px;
    }

    .logo {
      font-weight: 800;
      font-size: 1.35rem;
      color: var(--blue);
      letter-spacing: -0.5px;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo i { color: var(--cyan); }

    /* MAIN */
    .page-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 24px;
    }

    .login-card {
      background: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      border: 1.5px solid var(--border);
      width: 100%;
      max-width: 440px;
      overflow: hidden;
      animation: cardIn 0.5s cubic-bezier(.4,0,.2,1) both;
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: none; }
    }

    .card-header {
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy) 60%, #1a237e 100%);
      padding: 36px 40px 32px;
      position: relative;
      overflow: hidden;
      text-align: center;
    }

    .card-header::before {
      content: '';
      position: absolute;
      top: -40px; right: -40px;
      width: 200px; height: 200px;
      background: radial-gradient(circle, rgba(6,182,212,0.2) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .card-header .brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      margin-bottom: 16px;
      position: relative;
    }

    .card-header .brand i { font-size: 1.6rem; color: var(--cyan); }
    .card-header .brand span { font-size: 1.5rem; font-weight: 800; color: white; letter-spacing: -0.5px; }

    .card-header h2 {
      font-size: 1rem;
      font-weight: 400;
      color: rgba(255,255,255,0.65);
      position: relative;
    }

    .card-body { padding: 36px 40px 40px; }

    .alert-error {
      background: #fef2f2;
      border: 1.5px solid #fecaca;
      color: #dc2626;
      border-radius: var(--r-sm);
      padding: 12px 16px;
      font-size: 0.88rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 24px;
      animation: shake 0.35s ease;
    }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25%      { transform: translateX(-6px); }
      75%      { transform: translateX(6px); }
    }

    .form-group { margin-bottom: 20px; }

    .form-label {
      display: block;
      font-size: 0.83rem;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 8px;
    }

    .input-wrap { position: relative; }

    .input-icon {
      position: absolute;
      left: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 0.9rem;
      pointer-events: none;
      transition: color 0.2s;
    }

    .form-input {
      width: 100%;
      padding: 11px 14px 11px 40px;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      color: var(--text);
      background: var(--bg);
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
      background: var(--white);
    }

    .input-wrap:focus-within .input-icon { color: var(--blue); }

    .toggle-pass {
      position: absolute;
      right: 13px; top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      font-size: 0.9rem;
      padding: 0;
      transition: color 0.2s;
    }

    .toggle-pass:hover { color: var(--blue); }

    .form-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 28px;
      font-size: 0.85rem;
    }

    .remember {
      display: flex;
      align-items: center;
      gap: 7px;
      cursor: pointer;
      color: var(--muted);
      font-weight: 500;
      user-select: none;
    }

    .remember input[type="checkbox"] {
      width: 15px; height: 15px;
      accent-color: var(--blue);
      cursor: pointer;
    }

    .forgot {
      color: var(--blue);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .forgot:hover { color: var(--blue-dark); text-decoration: underline; }

    .btn-login {
      width: 100%;
      padding: 13px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.97rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-login:hover {
      background: var(--blue-dark);
      box-shadow: 0 4px 16px rgba(37,99,235,0.35);
      transform: translateY(-1px);
    }

    .btn-login:active { transform: translateY(0); }

    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 24px 0;
      color: var(--muted);
      font-size: 0.8rem;
      font-weight: 500;
    }

    .divider::before, .divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }

    .register-row {
      text-align: center;
      font-size: 0.88rem;
      color: var(--muted);
    }

    .register-row a {
      color: var(--blue);
      font-weight: 700;
      text-decoration: none;
      transition: color 0.2s;
    }

    .register-row a:hover { color: var(--blue-dark); text-decoration: underline; }

    footer {
      text-align: center;
      padding: 20px;
      font-size: 0.82rem;
      color: var(--muted);
      border-top: 1px solid var(--border);
    }
  </style>
</head>
<body>

  <nav>
    <div class="nav-inner">
      <a class="logo" href="index.php">
        <i class="fa-solid fa-bolt"></i> TechStore
      </a>
    </div>
  </nav>

  <div class="page-wrap">
    <div class="login-card">

      <div class="card-header">
        <div class="brand">
          <i class="fa-solid fa-bolt"></i>
          <span>TechStore</span>
        </div>
        <h2>Đăng nhập vào tài khoản của bạn</h2>
      </div>

      <div class="card-body">

        <?php if ($error): ?>
          <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="login_form.php">

          <div class="form-group">
            <label class="form-label" for="username">Tên đăng nhập</label>
            <div class="input-wrap">
              <input class="form-input" type="text" id="username" name="username"
                placeholder="Nhập tên đăng nhập..."
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                autocomplete="username" required>
              <i class="fa-regular fa-user input-icon"></i>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="password">Mật khẩu</label>
            <div class="input-wrap">
              <input class="form-input" type="password" id="password" name="password"
                placeholder="Nhập mật khẩu..."
                autocomplete="current-password" required>
              <i class="fa-solid fa-lock input-icon"></i>
              <button type="button" class="toggle-pass" onclick="togglePassword()">
                <i class="fa-regular fa-eye" id="eyeIcon"></i>
              </button>
            </div>
          </div>

          <div class="form-meta">
            <label class="remember">
              <input type="checkbox" name="remember"> Ghi nhớ đăng nhập
            </label>
            <a href="#" class="forgot">Quên mật khẩu?</a>
          </div>

          <button type="submit" class="btn-login">
            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
          </button>

        </form>

        <div class="divider">hoặc</div>

        <div class="register-row">
          Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
        </div>

      </div>
    </div>
  </div>

  <footer>&copy; 2026 TechStore. All rights reserved.</footer>

  <script>
    function togglePassword() {
      const input = document.getElementById('password');
      const icon  = document.getElementById('eyeIcon');
      const show  = input.type === 'password';
      input.type  = show ? 'text' : 'password';
      icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }
  </script>
</body>
</html>