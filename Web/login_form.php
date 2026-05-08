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
  <link rel="stylesheet" href="public/css/login.css">
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