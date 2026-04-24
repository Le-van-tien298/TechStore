<?php
session_start();

// Nếu đã đăng nhập rồi thì redirect về trang chủ
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/Database.php';
$db = Database::getInstance();

$errors  = [];
$success = '';
$old     = []; // Giữ lại giá trị cũ khi có lỗi

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = trim($_POST['username']  ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';
    $name      = trim($_POST['name']      ?? '');
    $email     = trim($_POST['email']     ?? '');

    $old = compact('username', 'name', 'email');

    // Validate
    if ($username === '') {
        $errors['username'] = 'Vui lòng nhập tên đăng nhập.';
    } elseif (strlen($username) < 4) {
        $errors['username'] = 'Tên đăng nhập phải có ít nhất 4 ký tự.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors['username'] = 'Tên đăng nhập chỉ gồm chữ, số và dấu gạch dưới.';
    } elseif ($db->usernameExists($username)) {
        $errors['username'] = 'Tên đăng nhập này đã được sử dụng.';
    }

    if ($name === '') {
        $errors['name'] = 'Vui lòng nhập họ tên.';
    }

    if ($email === '') {
        $errors['email'] = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ.';
    } elseif ($db->emailExists($email)) {
        $errors['email'] = 'Email này đã được sử dụng.';
    }

    if ($password === '') {
        $errors['password'] = 'Vui lòng nhập mật khẩu.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    if ($password2 === '') {
        $errors['password2'] = 'Vui lòng xác nhận mật khẩu.';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Mật khẩu xác nhận không khớp.';
    }

    // Nếu không có lỗi thì đăng ký
    if (empty($errors)) {
        $ok = $db->register($username, $password, $name, $email);
        if ($ok) {
            // Tự động đăng nhập sau khi đăng ký
            $_SESSION['user'] = $username;
            $_SESSION['name'] = $name;
            $_SESSION['role'] = 'User';
            header('Location: index.php');
            exit;
        } else {
            $errors['general'] = 'Có lỗi xảy ra, vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Đăng ký – TechStore</title>
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
      --green:     #10b981;
      --text:      #111827;
      --muted:     #6b7280;
      --border:    #e5e7eb;
      --bg:        #f8fafc;
      --white:     #ffffff;
      --shadow-lg: 0 16px 48px rgba(13,27,75,0.18);
      --r-sm:      8px;
      --r:         14px;
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
      justify-content: space-between;
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

    .nav-login-link {
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--muted);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: color 0.2s;
    }

    .nav-login-link:hover { color: var(--blue); }

    /* MAIN */
    .page-wrap {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 24px;
    }

    .register-card {
      background: var(--white);
      border-radius: 20px;
      box-shadow: var(--shadow-lg);
      border: 1.5px solid var(--border);
      width: 100%;
      max-width: 500px;
      overflow: hidden;
      animation: cardIn 0.5s cubic-bezier(.4,0,.2,1) both;
    }

    @keyframes cardIn {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: none; }
    }

    /* Card header */
    .card-header {
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy) 60%, #1a237e 100%);
      padding: 30px 40px 26px;
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
      margin-bottom: 12px;
      position: relative;
    }

    .card-header .brand i { font-size: 1.4rem; color: var(--cyan); }
    .card-header .brand span { font-size: 1.4rem; font-weight: 800; color: white; letter-spacing: -0.5px; }

    .card-header h2 {
      font-size: 0.95rem;
      font-weight: 400;
      color: rgba(255,255,255,0.65);
      position: relative;
    }

    /* Card body */
    .card-body { padding: 32px 40px 36px; }

    /* Alert lỗi chung */
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
      margin-bottom: 20px;
      animation: shake 0.35s ease;
    }

    @keyframes shake {
      0%,100% { transform: translateX(0); }
      25%      { transform: translateX(-6px); }
      75%      { transform: translateX(6px); }
    }

    /* Form fields */
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
      margin-bottom: 16px;
    }

    @media(max-width: 480px) {
      .form-row { grid-template-columns: 1fr; }
      .card-body { padding: 24px 20px 28px; }
      .card-header { padding: 24px 20px 20px; }
    }

    .form-group { margin-bottom: 16px; }
    .form-group.no-mb { margin-bottom: 0; }

    .form-label {
      display: block;
      font-size: 0.82rem;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 7px;
    }

    .input-wrap { position: relative; }

    .input-icon {
      position: absolute;
      left: 13px; top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 0.88rem;
      pointer-events: none;
      transition: color 0.2s;
    }

    .form-input {
      width: 100%;
      padding: 10px 13px 10px 38px;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.93rem;
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

    .form-input.is-error {
      border-color: var(--red);
      box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
    }

    .form-input.is-ok {
      border-color: var(--green);
    }

    .input-wrap:focus-within .input-icon { color: var(--blue); }

    .field-error {
      font-size: 0.78rem;
      color: var(--red);
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .toggle-pass {
      position: absolute;
      right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      cursor: pointer; color: var(--muted);
      font-size: 0.88rem; padding: 0;
      transition: color 0.2s;
    }

    .toggle-pass:hover { color: var(--blue); }

    /* Password strength */
    .strength-bar {
      height: 4px;
      border-radius: 4px;
      background: var(--border);
      margin-top: 8px;
      overflow: hidden;
    }

    .strength-fill {
      height: 100%;
      border-radius: 4px;
      width: 0%;
      transition: width 0.3s, background 0.3s;
    }

    .strength-text {
      font-size: 0.75rem;
      margin-top: 4px;
      font-weight: 500;
      color: var(--muted);
    }

    /* Submit */
    .btn-register {
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
      margin-top: 8px;
    }

    .btn-register:hover {
      background: var(--blue-dark);
      box-shadow: 0 4px 16px rgba(37,99,235,0.35);
      transform: translateY(-1px);
    }

    .btn-register:active { transform: translateY(0); }

    /* Divider */
    .divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
      color: var(--muted);
      font-size: 0.8rem;
      font-weight: 500;
    }

    .divider::before, .divider::after {
      content: ''; flex: 1;
      height: 1px; background: var(--border);
    }

    .login-row {
      text-align: center;
      font-size: 0.88rem;
      color: var(--muted);
    }

    .login-row a {
      color: var(--blue);
      font-weight: 700;
      text-decoration: none;
      transition: color 0.2s;
    }

    .login-row a:hover { color: var(--blue-dark); text-decoration: underline; }

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

  <!-- NAVBAR -->
  <nav>
    <div class="nav-inner">
      <a class="logo" href="index.php">
        <i class="fa-solid fa-bolt"></i> TechStore
      </a>
      <a class="nav-login-link" href="login_form.php">
        <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
      </a>
    </div>
  </nav>

  <!-- MAIN -->
  <div class="page-wrap">
    <div class="register-card">

      <!-- Header -->
      <div class="card-header">
        <div class="brand">
          <i class="fa-solid fa-bolt"></i>
          <span>TechStore</span>
        </div>
        <h2>Tạo tài khoản mới</h2>
      </div>

      <!-- Body -->
      <div class="card-body">

        <?php if (!empty($errors['general'])): ?>
          <div class="alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= htmlspecialchars($errors['general']) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>

          <!-- Username + Họ tên -->
          <div class="form-row">
            <div class="form-group no-mb">
              <label class="form-label" for="username">Tên đăng nhập</label>
              <div class="input-wrap">
                <input class="form-input <?= isset($errors['username']) ? 'is-error' : (isset($old['username']) && !isset($errors['username']) && $old['username'] !== '' ? 'is-ok' : '') ?>"
                  type="text" id="username" name="username"
                  placeholder="vd: nguyenvan01"
                  value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                  autocomplete="username">
                <i class="fa-regular fa-user input-icon"></i>
              </div>
              <?php if (isset($errors['username'])): ?>
                <div class="field-error">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <?= htmlspecialchars($errors['username']) ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="form-group no-mb">
              <label class="form-label" for="name">Họ và tên</label>
              <div class="input-wrap">
                <input class="form-input <?= isset($errors['name']) ? 'is-error' : (isset($old['name']) && $old['name'] !== '' && !isset($errors['name']) ? 'is-ok' : '') ?>"
                  type="text" id="name" name="name"
                  placeholder="vd: Nguyễn Văn A"
                  value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                  autocomplete="name">
                <i class="fa-solid fa-id-card input-icon"></i>
              </div>
              <?php if (isset($errors['name'])): ?>
                <div class="field-error">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <?= htmlspecialchars($errors['name']) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Email -->
          <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <div class="input-wrap">
              <input class="form-input <?= isset($errors['email']) ? 'is-error' : (isset($old['email']) && $old['email'] !== '' && !isset($errors['email']) ? 'is-ok' : '') ?>"
                type="email" id="email" name="email"
                placeholder="vd: example@gmail.com"
                value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                autocomplete="email">
              <i class="fa-regular fa-envelope input-icon"></i>
            </div>
            <?php if (isset($errors['email'])): ?>
              <div class="field-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errors['email']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Mật khẩu -->
          <div class="form-group">
            <label class="form-label" for="password">Mật khẩu</label>
            <div class="input-wrap">
              <input class="form-input <?= isset($errors['password']) ? 'is-error' : '' ?>"
                type="password" id="password" name="password"
                placeholder="Ít nhất 6 ký tự"
                autocomplete="new-password"
                oninput="checkStrength(this.value)">
              <i class="fa-solid fa-lock input-icon"></i>
              <button type="button" class="toggle-pass" onclick="togglePass('password','eyeIcon1')">
                <i class="fa-regular fa-eye" id="eyeIcon1"></i>
              </button>
            </div>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
            <div class="strength-text" id="strengthText"></div>
            <?php if (isset($errors['password'])): ?>
              <div class="field-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errors['password']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Xác nhận mật khẩu -->
          <div class="form-group">
            <label class="form-label" for="password2">Xác nhận mật khẩu</label>
            <div class="input-wrap">
              <input class="form-input <?= isset($errors['password2']) ? 'is-error' : '' ?>"
                type="password" id="password2" name="password2"
                placeholder="Nhập lại mật khẩu"
                autocomplete="new-password">
              <i class="fa-solid fa-lock input-icon"></i>
              <button type="button" class="toggle-pass" onclick="togglePass('password2','eyeIcon2')">
                <i class="fa-regular fa-eye" id="eyeIcon2"></i>
              </button>
            </div>
            <?php if (isset($errors['password2'])): ?>
              <div class="field-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errors['password2']) ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Submit -->
          <button type="submit" class="btn-register">
            <i class="fa-solid fa-user-plus"></i> Đăng ký
          </button>

        </form>

        <div class="divider">đã có tài khoản?</div>

        <div class="login-row">
          <a href="login_form.php">
            <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập ngay
          </a>
        </div>

      </div>
    </div>
  </div>

  <footer>&copy; 2026 TechStore. All rights reserved.</footer>

  <script>
    function togglePass(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon  = document.getElementById(iconId);
      const show  = input.type === 'password';
      input.type  = show ? 'text' : 'password';
      icon.className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    }

    function checkStrength(val) {
      const fill = document.getElementById('strengthFill');
      const text = document.getElementById('strengthText');
      if (!val) { fill.style.width = '0%'; text.textContent = ''; return; }

      let score = 0;
      if (val.length >= 6)  score++;
      if (val.length >= 10) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^a-zA-Z0-9]/.test(val)) score++;

      const levels = [
        { pct: '20%', color: '#ef4444', label: 'Rất yếu' },
        { pct: '40%', color: '#f97316', label: 'Yếu' },
        { pct: '60%', color: '#eab308', label: 'Trung bình' },
        { pct: '80%', color: '#3b82f6', label: 'Mạnh' },
        { pct: '100%', color: '#10b981', label: 'Rất mạnh' },
      ];

      const lvl = levels[Math.min(score - 1, 4)] || levels[0];
      fill.style.width    = lvl.pct;
      fill.style.background = lvl.color;
      text.textContent    = lvl.label;
      text.style.color    = lvl.color;
    }
  </script>
</body>
</html>