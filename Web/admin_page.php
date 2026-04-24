<?php
session_start();
//require_once __DIR__ . '/auth_admin.php';
require_once __DIR__ . '/Database.php';

// Bảo vệ trang: chỉ Admin mới vào được
if (!isset($_SESSION['user']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: login_form.php');
    exit;
}

$db  = Database::getInstance();
$pdo = $db->getConnection();

// ── Xử lý action ──────────────────────────────────────────────────────────────

$action  = $_POST['action']  ?? $_GET['action']  ?? '';
$section = $_GET['section'] ?? 'products';
$success = '';
$error   = '';

// ── XỬ LÝ SẢN PHẨM ──
if ($action === 'add_product') {
    $name        = trim($_POST['name'] ?? '');
    $price       = (int)   ($_POST['price']       ?? 0);
    $description = trim($_POST['description'] ?? '');
    $id_type     = trim($_POST['id_type']     ?? '');
    $image       = trim($_POST['image']       ?? '');

    if ($name && $price && $id_type) {
        $db->insertProduct($name, $price, $description, $id_type, $image);
        $success = 'Thêm sản phẩm thành công!';
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    }
}

if ($action === 'edit_product') {
    $id          = (int)   ($_POST['id']          ?? 0);
    $name        = trim($_POST['name']        ?? '');
    $price       = (int)   ($_POST['price']       ?? 0);
    $description = trim($_POST['description'] ?? '');
    $id_type     = trim($_POST['id_type']     ?? '');
    $image       = trim($_POST['image']       ?? '');

    if ($id && $name && $price && $id_type) {
        $db->updateProduct($id, $name, $price, $description, $id_type, $image);
        $success = 'Cập nhật sản phẩm thành công!';
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc.';
    }
}

if ($action === 'delete_product') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $db->deleteProduct($id);
        $success = 'Đã xóa sản phẩm.';
    }
}

// ── XỬ LÝ NGƯỜI DÙNG ──
if ($action === 'add_user') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $role     = $_POST['role'] ?? 'User';

    if ($username && $password && $name && $email) {
        if ($db->usernameExists($username)) {
            $error = 'Username đã tồn tại.';
        } elseif ($db->emailExists($email)) {
            $error = 'Email đã được sử dụng.';
        } else {
            $db->register($username, $password, $name, $email, $role);
            $success = 'Thêm người dùng thành công!';
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    }
}

if ($action === 'edit_user') {
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');

    if ($username && $name && $email) {
        $db->updateUser($username, $name, $email);
        $success = 'Cập nhật người dùng thành công!';
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    }
}

if ($action === 'delete_user') {
    $username = trim($_POST['username'] ?? '');
    if ($username && $username !== $_SESSION['user']) {
        $stmt = $pdo->prepare("DELETE FROM p_users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $success = 'Đã xóa người dùng.';
    } else {
        $error = 'Không thể xóa tài khoản đang đăng nhập.';
    }
}

// ── Lấy dữ liệu ──
$products  = $db->getAllProducts();
$typeList  = $db->getAllTypes();
$userStmt  = $pdo->query("SELECT * FROM p_users ORDER BY role ASC, username ASC");
$users     = $userStmt->fetchAll();

function formatPrice(float $p): string {
    return number_format($p, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin – TechStore</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }

    :root {
      --navy:      #0d1b4b;
      --navy-deep: #080f2d;
      --blue:      #2563eb;
      --blue-lt:   #eff6ff;
      --cyan:      #06b6d4;
      --purple:    #7c3aed;
      --red:       #ef4444;
      --green:     #10b981;
      --text:      #111827;
      --muted:     #6b7280;
      --bg:        #f1f5f9;
      --white:     #ffffff;
      --border:    #e5e7eb;
      --sidebar-w: 240px;
      --r:         12px;
      --r-sm:      8px;
      --shadow:    0 2px 16px rgba(13,27,75,.09);
      --shadow-lg: 0 8px 32px rgba(13,27,75,.16);
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
    }

    /* ── SIDEBAR ──────────────────────────────────────── */
    .sidebar {
      width: var(--sidebar-w);
      background: var(--navy-deep);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 50;
      transition: transform .3s;
    }

    .sidebar-logo {
      padding: 22px 20px 18px;
      border-bottom: 1px solid rgba(255,255,255,.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .sidebar-logo a {
      font-size: 1.2rem;
      font-weight: 800;
      color: white;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .sidebar-logo a i { color: var(--cyan); }

    .sidebar-badge {
      font-size: 0.6rem;
      font-weight: 700;
      background: var(--purple);
      color: white;
      padding: 2px 7px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: .5px;
      margin-left: auto;
    }

    .sidebar-section-label {
      font-size: 0.65rem;
      font-weight: 700;
      color: rgba(255,255,255,.3);
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 20px 20px 8px;
    }

    .sidebar-nav { flex: 1; padding-bottom: 16px; }

    .sidebar-link {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 11px 20px;
      font-size: 0.9rem;
      font-weight: 500;
      color: rgba(255,255,255,.6);
      text-decoration: none;
      transition: background .2s, color .2s;
      border-left: 3px solid transparent;
      cursor: pointer;
    }

    .sidebar-link:hover {
      background: rgba(255,255,255,.06);
      color: white;
    }

    .sidebar-link.active {
      background: rgba(37,99,235,.25);
      color: white;
      border-left-color: var(--cyan);
      font-weight: 700;
    }

    .sidebar-link i { width: 18px; text-align: center; font-size: 0.9rem; }

    .sidebar-link .count {
      margin-left: auto;
      background: rgba(255,255,255,.12);
      color: rgba(255,255,255,.7);
      font-size: 0.7rem;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 20px;
    }

    .sidebar-link.active .count {
      background: var(--cyan);
      color: var(--navy-deep);
    }

    .sidebar-footer {
      padding: 16px 20px;
      border-top: 1px solid rgba(255,255,255,.08);
    }

    .sidebar-user {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 12px;
    }

    .sidebar-user-avatar {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--blue), var(--cyan));
      display: flex; align-items: center; justify-content: center;
      font-size: 0.85rem; color: white; font-weight: 700;
      flex-shrink: 0;
    }

    .sidebar-user-info .name {
      font-size: 0.85rem;
      font-weight: 700;
      color: white;
    }

    .sidebar-user-info .role {
      font-size: 0.7rem;
      color: var(--cyan);
      font-weight: 600;
    }

    .btn-logout {
      display: flex;
      align-items: center;
      gap: 8px;
      width: 100%;
      padding: 9px 14px;
      background: rgba(239,68,68,.15);
      color: #fca5a5;
      border: 1px solid rgba(239,68,68,.25);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.83rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: background .2s, color .2s;
      justify-content: center;
    }

    .btn-logout:hover { background: rgba(239,68,68,.3); color: white; }

    /* ── MAIN ─────────────────────────────────────────── */
    .main {
      margin-left: var(--sidebar-w);
      flex: 1;
      display: flex;
      flex-direction: column;
      min-height: 100vh;
    }

    /* ── TOPBAR ───────────────────────────────────────── */
    .topbar {
      background: white;
      border-bottom: 1.5px solid var(--border);
      padding: 14px 28px;
      display: flex;
      align-items: center;
      gap: 16px;
      position: sticky;
      top: 0; z-index: 40;
      box-shadow: var(--shadow);
    }

    .topbar-title {
      font-size: 1.1rem;
      font-weight: 800;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .topbar-title i { color: var(--blue); }

    .topbar-breadcrumb {
      font-size: 0.82rem;
      color: var(--muted);
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .topbar-breadcrumb a { color: var(--blue); text-decoration: none; }
    .topbar-breadcrumb a:hover { text-decoration: underline; }

    .topbar-right {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .btn-add {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 18px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s, transform .15s, box-shadow .2s;
      text-decoration: none;
    }

    .btn-add:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 4px 14px rgba(37,99,235,.35);
    }

    /* ── CONTENT ──────────────────────────────────────── */
    .content { padding: 28px; flex: 1; }

    /* ── STATS CARDS ──────────────────────────────────── */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 28px;
    }

    .stat-card {
      background: white;
      border-radius: var(--r);
      padding: 20px 22px;
      border: 1.5px solid var(--border);
      box-shadow: var(--shadow);
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .stat-icon {
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem;
      flex-shrink: 0;
    }

    .stat-info .label { font-size: 0.75rem; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .stat-info .value { font-size: 1.6rem; font-weight: 900; color: var(--text); line-height: 1.1; margin-top: 2px; }

    /* ── TABLE CARD ───────────────────────────────────── */
    .table-card {
      background: white;
      border-radius: var(--r);
      border: 1.5px solid var(--border);
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .table-card-header {
      padding: 18px 22px;
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .table-card-header h3 {
      font-size: 1rem;
      font-weight: 800;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .table-card-header h3 i { color: var(--blue); }

    .search-input-wrap {
      margin-left: auto;
      position: relative;
      display: flex;
      align-items: center;
    }

    .search-input-wrap i {
      position: absolute;
      left: 11px;
      color: var(--muted);
      font-size: 0.82rem;
    }

    .search-input-wrap input {
      padding: 8px 12px 8px 32px;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.85rem;
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      width: 200px;
    }

    .search-input-wrap input:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }

    .table-wrap { overflow-x: auto; }

    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
    }

    thead th {
      padding: 12px 16px;
      text-align: left;
      font-size: 0.72rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .6px;
      background: #f8fafc;
      border-bottom: 1.5px solid var(--border);
      white-space: nowrap;
    }

    tbody tr {
      border-bottom: 1px solid var(--border);
      transition: background .15s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #f8fafc; }

    tbody td { padding: 13px 16px; vertical-align: middle; }

    .td-img {
      width: 46px; height: 46px;
      border-radius: 8px;
      object-fit: contain;
      background: #f1f5f9;
      border: 1px solid var(--border);
      padding: 4px;
    }

    .td-img-placeholder {
      width: 46px; height: 46px;
      border-radius: 8px;
      background: #f1f5f9;
      border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      color: #cbd5e1; font-size: 1.1rem;
    }

    .badge-type {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      background: var(--blue-lt);
      color: var(--blue);
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-role {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 0.72rem;
      font-weight: 700;
      white-space: nowrap;
    }

    .badge-role.admin { background: #f5f3ff; color: var(--purple); }
    .badge-role.user  { background: #f0fdf4; color: var(--green);  }

    .price-cell { font-weight: 700; color: var(--blue); white-space: nowrap; }

    .action-btns { display: flex; gap: 6px; }

    .btn-edit, .btn-delete {
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      font-family: 'Inter', sans-serif;
      font-size: 0.78rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all .2s;
    }

    .btn-edit   { background: #eff6ff; color: var(--blue); }
    .btn-edit:hover   { background: var(--blue); color: white; }
    .btn-delete { background: #fef2f2; color: var(--red); }
    .btn-delete:hover { background: var(--red); color: white; }

    .product-name-cell { font-weight: 600; max-width: 220px; }
    .product-name-cell small { display: block; font-weight: 400; color: var(--muted); font-size: 0.78rem; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }

    /* ── ALERT ────────────────────────────────────────── */
    .alert {
      padding: 12px 16px;
      border-radius: var(--r-sm);
      font-size: 0.88rem;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    /* ── MODAL / DRAWER ───────────────────────────────── */
    .overlay {
      position: fixed; inset: 0; z-index: 200;
      background: rgba(8,15,45,.55);
      backdrop-filter: blur(4px);
      opacity: 0; visibility: hidden;
      transition: opacity .25s, visibility .25s;
    }

    .overlay.open { opacity: 1; visibility: visible; }

    .drawer {
      position: fixed;
      top: 0; right: 0; bottom: 0;
      width: 480px;
      background: white;
      box-shadow: -8px 0 40px rgba(13,27,75,.2);
      z-index: 210;
      display: flex;
      flex-direction: column;
      transform: translateX(100%);
      transition: transform .3s cubic-bezier(.4,0,.2,1);
    }

    .overlay.open .drawer { transform: translateX(0); }

    .drawer-header {
      padding: 20px 24px 18px;
      border-bottom: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .drawer-header h3 {
      font-size: 1rem;
      font-weight: 800;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .drawer-close {
      margin-left: auto;
      width: 32px; height: 32px;
      border-radius: 50%;
      border: none;
      background: #f1f5f9;
      color: var(--muted);
      font-size: 0.9rem;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      transition: background .2s, color .2s;
    }

    .drawer-close:hover { background: var(--red); color: white; }

    .drawer-body {
      flex: 1;
      overflow-y: auto;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .form-group { display: flex; flex-direction: column; gap: 6px; }

    .form-label {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--text);
      text-transform: uppercase;
      letter-spacing: .4px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .form-label .req { color: var(--red); }

    .form-control {
      padding: 10px 13px;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      color: var(--text);
      outline: none;
      transition: border-color .2s, box-shadow .2s;
      background: white;
    }

    .form-control:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }

    textarea.form-control { resize: vertical; min-height: 100px; }

    .drawer-footer {
      padding: 18px 24px;
      border-top: 1.5px solid var(--border);
      display: flex;
      gap: 10px;
    }

    .btn-save {
      flex: 1;
      padding: 11px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.93rem;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }

    .btn-save:hover { background: #1d4ed8; }

    .btn-cancel {
      padding: 11px 20px;
      background: var(--bg);
      color: var(--muted);
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-cancel:hover { background: var(--border); color: var(--text); }

    /* Confirm delete */
    .confirm-box {
      position: fixed; inset: 0; z-index: 300;
      background: rgba(8,15,45,.6);
      backdrop-filter: blur(4px);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; visibility: hidden;
      transition: opacity .2s, visibility .2s;
    }

    .confirm-box.open { opacity: 1; visibility: visible; }

    .confirm-card {
      background: white;
      border-radius: 16px;
      max-width: 380px; width: 100%;
      padding: 32px 28px 24px;
      box-shadow: 0 24px 80px rgba(13,27,75,.25);
      text-align: center;
      transform: scale(.95);
      transition: transform .25s;
    }

    .confirm-box.open .confirm-card { transform: scale(1); }

    .confirm-icon {
      font-size: 2.5rem;
      color: var(--red);
      margin-bottom: 12px;
    }

    .confirm-card h4 { font-size: 1.1rem; font-weight: 800; margin-bottom: 8px; }
    .confirm-card p  { font-size: 0.88rem; color: var(--muted); margin-bottom: 22px; line-height: 1.6; }

    .confirm-actions { display: flex; gap: 10px; }

    .btn-confirm-del {
      flex: 1; padding: 11px;
      background: var(--red); color: white;
      border: none; border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.92rem; font-weight: 700;
      cursor: pointer; transition: background .2s;
    }

    .btn-confirm-del:hover { background: #dc2626; }

    .btn-confirm-cancel {
      flex: 1; padding: 11px;
      background: var(--bg); color: var(--muted);
      border: 1.5px solid var(--border); border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.92rem; font-weight: 600;
      cursor: pointer; transition: all .2s;
    }

    .btn-confirm-cancel:hover { background: var(--border); color: var(--text); }

    /* Empty */
    .empty-row td { text-align: center; padding: 48px; color: var(--muted); font-size: 0.9rem; }

    /* Mobile sidebar toggle */
    .sidebar-toggle {
      display: none;
      background: none; border: none;
      font-size: 1.2rem; color: var(--text);
      cursor: pointer; padding: 4px;
    }

    @media(max-width: 900px) {
      :root { --sidebar-w: 0px; }
      .sidebar {
        transform: translateX(-240px);
        width: 240px;
      }
      .sidebar.open { transform: translateX(0); }
      .main { margin-left: 0; }
      .sidebar-toggle { display: block; }
      .stats-row { grid-template-columns: repeat(2, 1fr); }
    }

    @media(max-width: 560px) {
      .stats-row { grid-template-columns: 1fr 1fr; }
      .content { padding: 16px; }
      .topbar { padding: 12px 16px; }
      .drawer { width: 100%; }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <a href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
    <span class="sidebar-badge">Admin</span>
  </div>

  <nav class="sidebar-nav">
    <div class="sidebar-section-label">Quản lí</div>

    <a href="?section=products"
       class="sidebar-link <?= $section === 'products' ? 'active' : '' ?>">
      <i class="fa-solid fa-boxes-stacking"></i>
      Quản lí sản phẩm
      <span class="count"><?= count($products) ?></span>
    </a>

    <a href="?section=users"
       class="sidebar-link <?= $section === 'users' ? 'active' : '' ?>">
      <i class="fa-solid fa-users"></i>
      Quản lí người dùng
      <span class="count"><?= count($users) ?></span>
    </a>

    <div class="sidebar-section-label" style="margin-top:8px;">Cửa hàng</div>

    <a href="index.php" class="sidebar-link">
      <i class="fa-solid fa-store"></i>
      Xem cửa hàng
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-user-avatar">
        <?= strtoupper(substr($_SESSION['name'] ?? $_SESSION['user'], 0, 1)) ?>
      </div>
      <div class="sidebar-user-info">
        <div class="name"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['user']) ?></div>
        <div class="role"><i class="fa-solid fa-shield-halved"></i> Admin</div>
      </div>
    </div>
    <a href="logout.php" class="btn-logout">
      <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
    </a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <button class="sidebar-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
      <i class="fa-solid fa-bars"></i>
    </button>
    <div>
      <div class="topbar-title">
        <i class="fa-solid <?= $section === 'products' ? 'fa-boxes-stacking' : 'fa-users' ?>"></i>
        <?= $section === 'products' ? 'Quản lí sản phẩm' : 'Quản lí người dùng' ?>
      </div>
      <div class="topbar-breadcrumb">
        <a href="index.php">TechStore</a>
        <i class="fa-solid fa-chevron-right" style="font-size:.65rem"></i>
        Admin
        <i class="fa-solid fa-chevron-right" style="font-size:.65rem"></i>
        <?= $section === 'products' ? 'Sản phẩm' : 'Người dùng' ?>
      </div>
    </div>
    <div class="topbar-right">
      <?php if ($section === 'products'): ?>
        <button class="btn-add" onclick="openAddProduct()">
          <i class="fa-solid fa-plus"></i> Thêm sản phẩm
        </button>
      <?php else: ?>
        <button class="btn-add" onclick="openAddUser()">
          <i class="fa-solid fa-user-plus"></i> Thêm người dùng
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <?php if ($success): ?>
      <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error"><i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
      <?php if ($section === 'products'): ?>
        <div class="stat-card">
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-boxes-stacking"></i></div>
          <div class="stat-info">
            <div class="label">Tổng sản phẩm</div>
            <div class="value"><?= count($products) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-folder-open"></i></div>
          <div class="stat-info">
            <div class="label">Danh mục</div>
            <div class="value"><?= count($typeList) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fce7f3;color:#db2777;"><i class="fa-solid fa-circle-check"></i></div>
          <div class="stat-info">
            <div class="label">Còn hàng</div>
            <div class="value"><?= count($products) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-tags"></i></div>
          <div class="stat-info">
            <div class="label">Tổng giá trị</div>
            <div class="value" style="font-size:1rem;">
              <?= formatPrice(array_sum(array_column($products, 'price'))) ?>
            </div>
          </div>
        </div>
      <?php else: ?>
        <div class="stat-card">
          <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fa-solid fa-users"></i></div>
          <div class="stat-info">
            <div class="label">Tổng người dùng</div>
            <div class="value"><?= count($users) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fa-solid fa-user-shield"></i></div>
          <div class="stat-info">
            <div class="label">Admin</div>
            <div class="value"><?= count(array_filter($users, fn($u) => $u['role'] === 'Admin')) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#d1fae5;color:#059669;"><i class="fa-solid fa-user"></i></div>
          <div class="stat-info">
            <div class="label">User</div>
            <div class="value"><?= count(array_filter($users, fn($u) => $u['role'] === 'User')) ?></div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fa-solid fa-envelope"></i></div>
          <div class="stat-info">
            <div class="label">Email đã đăng ký</div>
            <div class="value"><?= count($users) ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- TABLE CARD -->
    <?php if ($section === 'products'): ?>
    <!-- ── BẢNG SẢN PHẨM ─────────────────────────────── -->
    <div class="table-card">
      <div class="table-card-header">
        <h3><i class="fa-solid fa-boxes-stacking"></i> Danh sách sản phẩm</h3>
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchProduct" placeholder="Tìm sản phẩm..." oninput="filterTable('productTable', this.value)">
        </div>
      </div>
      <div class="table-wrap">
        <table id="productTable">
          <thead>
            <tr>
              <th>#</th>
              <th>Ảnh</th>
              <th>Tên sản phẩm</th>
              <th>Danh mục</th>
              <th>Giá</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($products)): ?>
              <tr class="empty-row"><td colspan="6"><i class="fa-solid fa-box-open"></i> Chưa có sản phẩm</td></tr>
            <?php else: ?>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td style="color:var(--muted);font-size:.8rem;font-weight:600;"><?= $p['id'] ?></td>
                  <td>
                    <?php if (!empty($p['image'])): ?>
                      <img src="images/<?= htmlspecialchars($p['image']) ?>"
                           class="td-img"
                           onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                      <div class="td-img-placeholder" style="display:none"><i class="fa-solid fa-image"></i></div>
                    <?php else: ?>
                      <div class="td-img-placeholder"><i class="fa-solid fa-box"></i></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="product-name-cell">
                      <?= htmlspecialchars($p['name']) ?>
                      <small><?= htmlspecialchars($p['description']) ?></small>
                    </div>
                  </td>
                  <td><span class="badge-type"><?= htmlspecialchars($p['type_name'] ?? $p['id_type']) ?></span></td>
                  <td class="price-cell"><?= formatPrice((float)$p['price']) ?></td>
                  <td>
                    <div class="action-btns">
                      <button class="btn-edit" onclick="openEditProduct(<?= htmlspecialchars(json_encode($p), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen"></i> Sửa
                      </button>
                      <button class="btn-delete" onclick="confirmDelete('product', <?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')">
                        <i class="fa-solid fa-trash"></i> Xóa
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <?php else: ?>
    <!-- ── BẢNG NGƯỜI DÙNG ───────────────────────────── -->
    <div class="table-card">
      <div class="table-card-header">
        <h3><i class="fa-solid fa-users"></i> Danh sách người dùng</h3>
        <div class="search-input-wrap">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="searchUser" placeholder="Tìm người dùng..." oninput="filterTable('userTable', this.value)">
        </div>
      </div>
      <div class="table-wrap">
        <table id="userTable">
          <thead>
            <tr>
              <th>Username</th>
              <th>Họ tên</th>
              <th>Email</th>
              <th>Vai trò</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr class="empty-row"><td colspan="5"><i class="fa-solid fa-users"></i> Chưa có người dùng</td></tr>
            <?php else: ?>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td style="font-weight:700;font-family:monospace;font-size:.85rem;"><?= htmlspecialchars($u['username']) ?></td>
                  <td><?= htmlspecialchars($u['name']) ?></td>
                  <td style="color:var(--muted);font-size:.85rem;"><?= htmlspecialchars($u['email']) ?></td>
                  <td>
                    <span class="badge-role <?= strtolower($u['role']) ?>">
                      <i class="fa-solid fa-<?= $u['role'] === 'Admin' ? 'shield-halved' : 'user' ?>"></i>
                      <?= $u['role'] ?>
                    </span>
                  </td>
                  <td>
                    <div class="action-btns">
                      <button class="btn-edit" onclick="openEditUser(<?= htmlspecialchars(json_encode($u), ENT_QUOTES) ?>)">
                        <i class="fa-solid fa-pen"></i> Sửa
                      </button>
                      <?php if ($u['username'] !== $_SESSION['user']): ?>
                        <button class="btn-delete" onclick="confirmDelete('user', '<?= htmlspecialchars(addslashes($u['username'])) ?>', '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                          <i class="fa-solid fa-trash"></i> Xóa
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /content -->
</div><!-- /main -->


<!-- ═══════════════════════════════════════════════════════════════
     DRAWER – THÊM / SỬA SẢN PHẨM
═══════════════════════════════════════════════════════════════ -->
<div class="overlay" id="productDrawerOverlay" onclick="handleOverlayClick(event,'productDrawerOverlay')">
  <div class="drawer" id="productDrawer">
    <div class="drawer-header">
      <h3 id="productDrawerTitle"><i class="fa-solid fa-box"></i> Thêm sản phẩm</h3>
      <button class="drawer-close" onclick="closeDrawer('productDrawerOverlay')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" id="productForm">
      <input type="hidden" name="action" id="productAction" value="add_product">
      <input type="hidden" name="id"     id="productId"     value="">
      <div class="drawer-body">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-font"></i> Tên sản phẩm <span class="req">*</span></label>
          <input type="text" name="name" id="pName" class="form-control" placeholder="VD: iPhone 16 Pro Max 256GB" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-tag"></i> Giá (VNĐ) <span class="req">*</span></label>
          <input type="number" name="price" id="pPrice" class="form-control" placeholder="VD: 29990000" min="0" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-folder"></i> Danh mục <span class="req">*</span></label>
          <select name="id_type" id="pType" class="form-control" required>
            <option value="">-- Chọn danh mục --</option>
            <?php foreach ($typeList as $t): ?>
              <option value="<?= htmlspecialchars($t['type']) ?>"><?= htmlspecialchars($t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-image"></i> Tên file ảnh</label>
          <input type="text" name="image" id="pImage" class="form-control" placeholder="VD: ip16pro.png">
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-align-left"></i> Mô tả</label>
          <textarea name="description" id="pDesc" class="form-control" placeholder="Mô tả chi tiết sản phẩm..."></textarea>
        </div>
      </div>
      <div class="drawer-footer">
        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
        <button type="button" class="btn-cancel" onclick="closeDrawer('productDrawerOverlay')">Hủy</button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     DRAWER – THÊM / SỬA NGƯỜI DÙNG
═══════════════════════════════════════════════════════════════ -->
<div class="overlay" id="userDrawerOverlay" onclick="handleOverlayClick(event,'userDrawerOverlay')">
  <div class="drawer" id="userDrawer">
    <div class="drawer-header">
      <h3 id="userDrawerTitle"><i class="fa-solid fa-user-plus"></i> Thêm người dùng</h3>
      <button class="drawer-close" onclick="closeDrawer('userDrawerOverlay')"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <form method="POST" id="userForm">
      <input type="hidden" name="action" id="userAction" value="add_user">
      <div class="drawer-body">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-at"></i> Username <span class="req">*</span></label>
          <input type="text" name="username" id="uUsername" class="form-control" placeholder="VD: useraa02" required>
        </div>
        <div class="form-group" id="uPasswordGroup">
          <label class="form-label"><i class="fa-solid fa-lock"></i> Mật khẩu <span class="req">*</span></label>
          <input type="password" name="password" id="uPassword" class="form-control" placeholder="Nhập mật khẩu">
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-id-card"></i> Họ tên <span class="req">*</span></label>
          <input type="text" name="name" id="uName" class="form-control" placeholder="VD: Nguyen Van A" required>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-envelope"></i> Email <span class="req">*</span></label>
          <input type="email" name="email" id="uEmail" class="form-control" placeholder="VD: user@gmail.com" required>
        </div>
        <div class="form-group" id="uRoleGroup">
          <label class="form-label"><i class="fa-solid fa-shield-halved"></i> Vai trò</label>
          <select name="role" id="uRole" class="form-control">
            <option value="User">User</option>
            <option value="Admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="drawer-footer">
        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
        <button type="button" class="btn-cancel" onclick="closeDrawer('userDrawerOverlay')">Hủy</button>
      </div>
    </form>
  </div>
</div>

<!-- CONFIRM DELETE -->
<div class="confirm-box" id="confirmBox">
  <div class="confirm-card">
    <div class="confirm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <h4>Xác nhận xóa</h4>
    <p id="confirmMsg">Bạn có chắc muốn xóa mục này không? Hành động này không thể hoàn tác.</p>
    <div class="confirm-actions">
      <form method="POST" id="confirmForm">
        <input type="hidden" name="action" id="confirmAction">
        <input type="hidden" name="id"       id="confirmId">
        <input type="hidden" name="username" id="confirmUsername">
        <div class="confirm-actions" style="gap:10px;width:100%">
          <button type="submit" class="btn-confirm-del" style="flex:1"><i class="fa-solid fa-trash"></i> Xóa</button>
          <button type="button" class="btn-confirm-cancel" style="flex:1" onclick="closeConfirm()">Hủy</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ── Drawer helpers ──────────────────────────────────────────────
function openDrawer(id)  { document.getElementById(id).classList.add('open'); document.body.style.overflow='hidden'; }
function closeDrawer(id) { document.getElementById(id).classList.remove('open'); document.body.style.overflow=''; }
function handleOverlayClick(e, id) { if(e.target.id===id) closeDrawer(id); }

// ── Product drawer ──────────────────────────────────────────────
function openAddProduct() {
  document.getElementById('productDrawerTitle').innerHTML = '<i class="fa-solid fa-plus"></i> Thêm sản phẩm';
  document.getElementById('productAction').value = 'add_product';
  document.getElementById('productId').value = '';
  document.getElementById('pName').value  = '';
  document.getElementById('pPrice').value = '';
  document.getElementById('pDesc').value  = '';
  document.getElementById('pImage').value = '';
  document.getElementById('pType').value  = '';
  openDrawer('productDrawerOverlay');
}

function openEditProduct(p) {
  document.getElementById('productDrawerTitle').innerHTML = '<i class="fa-solid fa-pen"></i> Sửa sản phẩm';
  document.getElementById('productAction').value = 'edit_product';
  document.getElementById('productId').value  = p.id;
  document.getElementById('pName').value       = p.name;
  document.getElementById('pPrice').value      = p.price;
  document.getElementById('pDesc').value       = p.description;
  document.getElementById('pImage').value      = p.image;
  document.getElementById('pType').value       = p.id_type;
  openDrawer('productDrawerOverlay');
}

// ── User drawer ─────────────────────────────────────────────────
function openAddUser() {
  document.getElementById('userDrawerTitle').innerHTML = '<i class="fa-solid fa-user-plus"></i> Thêm người dùng';
  document.getElementById('userAction').value = 'add_user';
  document.getElementById('uUsername').value  = '';
  document.getElementById('uUsername').readOnly = false;
  document.getElementById('uPassword').value  = '';
  document.getElementById('uName').value      = '';
  document.getElementById('uEmail').value     = '';
  document.getElementById('uRole').value      = 'User';
  document.getElementById('uPasswordGroup').style.display = '';
  document.getElementById('uRoleGroup').style.display     = '';
  openDrawer('userDrawerOverlay');
}

function openEditUser(u) {
  document.getElementById('userDrawerTitle').innerHTML = '<i class="fa-solid fa-pen"></i> Sửa người dùng';
  document.getElementById('userAction').value  = 'edit_user';
  document.getElementById('uUsername').value   = u.username;
  document.getElementById('uUsername').readOnly = true;
  document.getElementById('uName').value       = u.name;
  document.getElementById('uEmail').value      = u.email;
  // Ẩn password & role khi edit (chỉ update name + email)
  document.getElementById('uPasswordGroup').style.display = 'none';
  document.getElementById('uRoleGroup').style.display     = 'none';
  openDrawer('userDrawerOverlay');
}

// ── Confirm delete ──────────────────────────────────────────────
function confirmDelete(type, id, name) {
  document.getElementById('confirmMsg').textContent =
    'Bạn có chắc muốn xóa "' + name + '" không? Hành động này không thể hoàn tác.';

  if (type === 'product') {
    document.getElementById('confirmAction').value   = 'delete_product';
    document.getElementById('confirmId').value       = id;
    document.getElementById('confirmUsername').value = '';
  } else {
    document.getElementById('confirmAction').value   = 'delete_user';
    document.getElementById('confirmId').value       = '';
    document.getElementById('confirmUsername').value = id;
  }
  document.getElementById('confirmBox').classList.add('open');
}

function closeConfirm() { document.getElementById('confirmBox').classList.remove('open'); }

// ── Search/filter table ─────────────────────────────────────────
function filterTable(tableId, query) {
  const q   = query.toLowerCase();
  const rows = document.querySelectorAll('#' + tableId + ' tbody tr:not(.empty-row)');
  rows.forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── ESC key ─────────────────────────────────────────────────────
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeDrawer('productDrawerOverlay');
    closeDrawer('userDrawerOverlay');
    closeConfirm();
  }
});

// Auto-hide alert
setTimeout(function() {
  document.querySelectorAll('.alert').forEach(function(a) {
    a.style.transition = 'opacity .5s';
    a.style.opacity = '0';
    setTimeout(function() { a.remove(); }, 500);
  });
}, 3500);
</script>

</body>
</html>