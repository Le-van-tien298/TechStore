<?php
session_start();
require_once __DIR__ . '/Database.php';

// Redirect nếu chưa đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login_form.php?redirect=wishlist.php');
    exit;
}

$db       = Database::getInstance();
$pdo      = $db->getConnection();
$username = $_SESSION['user'];

// Số lượng giỏ hàng
$cartCount = $db->getCartCount($username);

// Lấy danh sách sản phẩm yêu thích
$wishlistItems = $db->getWishlist($username);
$wishlistProductIds = array_column($wishlistItems, 'product_id');

$products = [];
if (!empty($wishlistProductIds)) {
    $placeholders = implode(',', array_fill(0, count($wishlistProductIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT p.*, t.name AS type_name
         FROM p_product p
         LEFT JOIN p_type t ON p.id_type = t.type
         WHERE p.id IN ($placeholders)
         ORDER BY p.id DESC"
    );
    $stmt->execute($wishlistProductIds);
    $products = $stmt->fetchAll();
}

function formatPrice(float $price): string {
    return number_format($price, 0, ',', '.') . '&#8363;';
}

function typeIcon(string $typeCode): string {
    $map = [
        'apple'   => 'fa-brands fa-apple',
        'samsung' => 'fa-solid fa-mobile-screen',
        'laptop'  => 'fa-solid fa-laptop',
        'gaming'  => 'fa-solid fa-gamepad',
        'audio'   => 'fa-solid fa-headphones',
        'smart'   => 'fa-solid fa-clock',
        'phone'   => 'fa-solid fa-mobile-screen',
        'tablet'  => 'fa-solid fa-tablet-screen-button',
    ];
    foreach ($map as $k => $v) {
        if (stripos($typeCode, $k) !== false) return $v;
    }
    return 'fa-solid fa-box';
}

$productsJson = json_encode(array_values($products), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechStore – Danh sách yêu thích</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

    :root {
      --navy: #0d1b4b;
      --navy-deep: #080f2d;
      --blue: #2563eb;
      --cyan: #06b6d4;
      --purple: #7c3aed;
      --red: #ef4444;
      --text: #111827;
      --muted: #6b7280;
      --bg: #f8fafc;
      --white: #ffffff;
      --border: #e5e7eb;
      --shadow: 0 4px 24px rgba(13,27,75,0.10);
      --shadow-lg: 0 8px 40px rgba(13,27,75,0.18);
      --r: 14px;
      --r-sm: 8px;
    }

    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* ── NAVBAR ── */
    nav {
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      position: sticky; top: 0; z-index: 100;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }

    .nav-inner {
      max-width: 1100px; margin: 0 auto;
      display: flex; align-items: center; gap: 24px; padding: 14px 24px;
    }

    .logo {
      font-weight: 800; font-size: 1.35rem; color: var(--blue);
      letter-spacing: -0.5px; text-decoration: none;
      flex-shrink: 0; display: flex; align-items: center; gap: 8px;
    }
    .logo i { color: var(--cyan); }

    .search-form {
      flex: 1; display: flex; align-items: center;
      background: var(--bg); border: 1.5px solid var(--border);
      border-radius: 50px; padding: 8px 18px; gap: 10px;
      transition: border-color .2s, box-shadow .2s;
    }
    .search-form:focus-within { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
    .search-form i { color: var(--muted); font-size: .9rem; }
    .search-form input {
      border: none; background: transparent; outline: none;
      font-family: 'Inter', sans-serif; font-size: .95rem; color: var(--text); width: 100%;
    }
    .search-form button { background: none; border: none; cursor: pointer; color: var(--muted); font-size: .9rem; transition: color .2s; }
    .search-form button:hover { color: var(--blue); }

    .nav-icons { display: flex; align-items: center; gap: 22px; }
    .nav-icon { position: relative; cursor: pointer; color: var(--text); transition: color .2s; font-size: 1.15rem; }
    .nav-icon:hover { color: var(--blue); }
    .nav-icon .badge {
      position: absolute; top: -7px; right: -9px;
      background: var(--red); color: white; font-size: .58rem; font-weight: 800;
      width: 16px; height: 16px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
    }

    .user-dropdown-wrap { position: relative; }
    .user-dropdown {
      position: absolute; top: calc(100% + 12px); right: 0;
      background: white; border: 1.5px solid var(--border);
      border-radius: var(--r); box-shadow: var(--shadow-lg);
      min-width: 200px; overflow: hidden;
      opacity: 0; visibility: hidden; transform: translateY(-8px);
      transition: opacity .2s, visibility .2s, transform .2s; z-index: 200;
    }
    .user-dropdown-wrap:hover .user-dropdown,
    .user-dropdown-wrap.open .user-dropdown { opacity: 1; visibility: visible; transform: translateY(0); }
    .user-dropdown-header { padding: 14px 16px 10px; border-bottom: 1px solid var(--border); }
    .user-dropdown-header .greeting { font-size: .78rem; color: var(--muted); margin-bottom: 2px; }
    .user-dropdown-header .uname { font-size: .95rem; font-weight: 700; color: var(--text); }
    .user-dropdown-item {
      display: flex; align-items: center; gap: 10px;
      padding: 11px 16px; font-size: .88rem; font-weight: 500;
      color: var(--text); text-decoration: none; transition: background .15s, color .15s;
      cursor: pointer; border: none; background: none; width: 100%;
      text-align: left; font-family: 'Inter', sans-serif;
    }
    .user-dropdown-item:hover { background: var(--bg); color: var(--blue); }
    .user-dropdown-item.logout { color: var(--red); }
    .user-dropdown-item.logout:hover { background: #fef2f2; }
    .user-dropdown-item.admin { color: var(--purple); }
    .user-dropdown-item.admin:hover { background: #f5f3ff; }
    .user-dropdown-item i { width: 16px; text-align: center; font-size: .9rem; }

    /* ── PAGE HEADER ── */
    .page-header {
      max-width: 1100px; margin: 36px auto 0; padding: 0 24px;
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
    }

    .page-header-left { display: flex; align-items: center; gap: 14px; }

    .page-header-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.4rem; color: var(--red);
      box-shadow: 0 4px 16px rgba(239,68,68,0.15);
    }

    .page-header-text h1 {
      font-size: 1.5rem; font-weight: 800; letter-spacing: -.5px; color: var(--text);
    }
    .page-header-text p { font-size: .88rem; color: var(--muted); margin-top: 2px; }

    .btn-clear-all {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 9px 18px; border-radius: 50px;
      border: 1.5px solid #fecaca; background: #fff5f5; color: var(--red);
      font-family: 'Inter', sans-serif; font-size: .85rem; font-weight: 700;
      cursor: pointer; transition: all .2s;
    }
    .btn-clear-all:hover { background: var(--red); color: white; border-color: var(--red); }

    /* ── PRODUCT GRID ── */
    .featured { max-width: 1100px; margin: 28px auto 0; padding: 0 24px; }

    .product-grid {
      display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;
    }
    @media(max-width:900px) { .product-grid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width:520px)  { .product-grid { grid-template-columns: 1fr; } }

    .product-card {
      background: white; border: 1.5px solid var(--border);
      border-radius: var(--r); overflow: hidden;
      transition: all .22s; box-shadow: var(--shadow);
      display: flex; flex-direction: column;
      animation: cardIn .4s both;
    }
    @keyframes cardIn { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
    .product-card:hover { transform: translateY(-6px); box-shadow: var(--shadow-lg); border-color: var(--blue); }

    .product-img-wrap {
      width: 100%; height: 200px;
      background: #f1f5f9;
      display: flex; align-items: center; justify-content: center;
      padding: 10px; overflow: hidden; position: relative; cursor: pointer;
    }
    .product-img-wrap img {
      max-width: 100%; max-height: 180px; width: auto; height: auto;
      object-fit: contain; display: block;
    }
    .icon-fallback { font-size: 4rem; color: #cbd5e1; }

    /* Nút remove wishlist trên card */
    .btn-remove-wish {
      position: absolute; top: 10px; right: 10px;
      width: 32px; height: 32px;
      background: rgba(255,255,255,0.9); border: 1.5px solid #fecaca;
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      font-size: .85rem; cursor: pointer; color: var(--red);
      transition: all .2s; backdrop-filter: blur(4px);
    }
    .btn-remove-wish:hover { background: var(--red); color: white; border-color: var(--red); transform: scale(1.1); }

    .product-info { padding: 16px; flex: 1; display: flex; flex-direction: column; gap: 8px; }

    .product-type {
      display: inline-flex; align-items: center; gap: 5px;
      background: #eff6ff; color: var(--blue);
      font-size: .72rem; font-weight: 700;
      padding: 3px 10px; border-radius: 20px;
      text-transform: uppercase; letter-spacing: .5px; width: fit-content;
    }

    .product-name {
      font-size: .97rem; font-weight: 700; line-height: 1.4;
      cursor: pointer; transition: color .2s;
    }
    .product-name:hover { color: var(--blue); text-decoration: underline; }

    .product-desc {
      font-size: .82rem; color: var(--muted); line-height: 1.5;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    .price { font-size: 1.15rem; font-weight: 800; color: var(--blue); margin-top: 4px; }

    .btn-action-wrap { display: flex; gap: 8px; margin-top: auto; }

    .btn-buynow {
      flex: 1; padding: 10px 14px;
      background: var(--blue); color: white;
      border: none; border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif; font-size: .9rem; font-weight: 700;
      cursor: pointer; transition: background .2s, transform .15s;
      display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .btn-buynow:hover { background: #1d4ed8; transform: scale(1.02); }

    .btn-cart-sm {
      flex-shrink: 0; width: 38px; height: 38px;
      background: #eff6ff; color: var(--blue);
      border: 1.5px solid #bfdbfe; border-radius: var(--r-sm);
      display: flex; align-items: center; justify-content: center;
      font-size: .95rem; cursor: pointer; transition: all .2s; padding: 0;
    }
    .btn-cart-sm:hover { background: var(--blue); color: white; border-color: var(--blue); }

    /* ── EMPTY STATE ── */
    .empty-state { text-align: center; padding: 100px 24px; }
    .empty-heart {
      font-size: 5rem; color: #fecaca; margin-bottom: 20px;
      animation: heartbeat 2.5s ease-in-out infinite;
    }
    @keyframes heartbeat {
      0%, 100% { transform: scale(1); }
      15% { transform: scale(1.12); }
      30% { transform: scale(1); }
      45% { transform: scale(1.07); }
      60% { transform: scale(1); }
    }
    .empty-state h3 { font-size: 1.5rem; font-weight: 800; margin-bottom: 10px; }
    .empty-state p { color: var(--muted); margin-bottom: 28px; font-size: .95rem; }
    .btn-blue {
      background: var(--blue); color: white;
      display: inline-flex; align-items: center; gap: 8px;
      padding: 12px 28px; border-radius: 50px;
      font-family: 'Inter', sans-serif; font-size: .93rem; font-weight: 700;
      text-decoration: none; transition: background .2s;
    }
    .btn-blue:hover { background: #1d4ed8; }

    /* ── MODAL ── */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 999;
      background: rgba(8,15,45,0.6); backdrop-filter: blur(5px);
      display: flex; align-items: center; justify-content: center; padding: 20px;
      opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s;
    }
    .modal-overlay.open { opacity: 1; visibility: visible; }
    .modal {
      background: white; border-radius: 20px;
      max-width: 980px; width: 100%; max-height: 90vh; overflow-y: auto;
      box-shadow: 0 24px 80px rgba(13,27,75,0.28);
      transform: translateY(32px) scale(.97);
      transition: transform .28s cubic-bezier(.4,0,.2,1); position: relative;
    }
    .modal-overlay.open .modal { transform: translateY(0) scale(1); }
    .modal-close {
      position: absolute; top: 14px; right: 14px;
      width: 34px; height: 34px; border-radius: 50%;
      border: none; background: #f1f5f9; color: var(--muted);
      font-size: .95rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background .2s, color .2s; z-index: 2;
    }
    .modal-close:hover { background: var(--red); color: white; }
    .modal-body { display: grid; grid-template-columns: 1fr 1fr; }
    @media(max-width:600px) { .modal-body { grid-template-columns: 1fr; } }
    .modal-img-side {
      background: #f1f5f9; border-radius: 20px 0 0 20px;
      display: flex; align-items: center; justify-content: center; padding: 32px 24px; min-height: 0;
    }
    @media(max-width:600px) { .modal-img-side { border-radius: 20px 20px 0 0; min-height: 400px; } }
    .modal-img-side img { max-width: 100%; max-height: 360px; width: auto; height: auto; object-fit: contain; }
    .modal-info { padding: 32px 28px; display: flex; flex-direction: column; gap: 14px; }
    .modal-type {
      display: inline-flex; align-items: center; gap: 6px;
      background: #eff6ff; color: var(--blue); font-size: .72rem; font-weight: 700;
      padding: 4px 12px; border-radius: 20px; text-transform: uppercase; letter-spacing: .5px; width: fit-content;
    }
    .modal-name { font-size: 1.25rem; font-weight: 800; line-height: 1.35; color: var(--text); }
    .modal-price { font-size: 1.7rem; font-weight: 900; color: var(--blue); }
    .modal-divider { border: none; border-top: 1.5px solid var(--border); }
    .modal-section-label {
      font-size: .75rem; font-weight: 700; color: var(--muted);
      text-transform: uppercase; letter-spacing: .8px;
      display: flex; align-items: center; gap: 6px;
    }
    .modal-desc {
      font-size: .93rem; color: #374151; line-height: 1.75; white-space: pre-line;
      max-height: 160px; overflow-y: auto;
      border: 1.5px solid var(--border); border-radius: var(--r-sm);
      padding: 12px; background: #f8fafc;
    }
    .modal-desc::-webkit-scrollbar { width: 5px; }
    .modal-desc::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
    .modal-desc::-webkit-scrollbar-thumb:hover { background: var(--blue); }
    .modal-meta { background: #f8fafc; border-radius: 10px; padding: 14px 16px; display: flex; flex-direction: column; gap: 9px; }
    .modal-meta-row { display: flex; align-items: center; gap: 10px; font-size: .87rem; color: var(--muted); }
    .modal-meta-row i { color: var(--blue); width: 16px; text-align: center; flex-shrink: 0; }
    .modal-meta-row span { color: var(--text); font-weight: 600; }
    .modal-actions { display: flex; gap: 10px; margin-top: auto; }
    .modal-btn-cart {
      flex: 1; padding: 12px; background: var(--blue); color: white;
      border: none; border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif; font-size: .93rem; font-weight: 700;
      cursor: pointer; transition: background .2s, transform .15s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .modal-btn-cart:hover { background: #1d4ed8; transform: scale(1.02); }
    .modal-btn-remove {
      padding: 12px 16px; background: #fff5f5; color: var(--red);
      border: 1.5px solid #fecaca; border-radius: var(--r-sm);
      font-size: 1rem; cursor: pointer; transition: all .2s;
    }
    .modal-btn-remove:hover { background: var(--red); color: white; }

    /* ── FOOTER ── */
    footer {
      max-width: 1100px; margin: 50px auto 0;
      padding: 32px 24px; border-top: 1.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
    }
    footer p { font-size: .85rem; color: var(--muted); }
    .foot-links { display: flex; gap: 20px; }
    .foot-links a { font-size: .85rem; color: var(--muted); text-decoration: none; display: flex; align-items: center; gap: 5px; }
    .foot-links a:hover { color: var(--blue); }

    /* ── RESPONSIVE ── */
    @media(max-width:768px) {
      .nav-inner { padding: 10px 16px; gap: 12px; }
      .search-form { display: none; }
      .page-header { padding: 0 12px; margin-top: 24px; }
      .page-header-text h1 { font-size: 1.2rem; }
      .featured { padding: 0 12px; margin-top: 16px; }
      .product-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 12px; }
      footer { flex-direction: column; align-items: flex-start; padding: 24px 16px; }
      .modal { max-width: 100%; max-height: 95vh; border-radius: 16px 16px 0 0; position: fixed; bottom: 0; left: 0; right: 0; margin: 0; }
      .modal-overlay { align-items: flex-end; padding: 0; }
      .modal-body { grid-template-columns: 1fr; }
      .modal-img-side { border-radius: 16px 16px 0 0; min-height: 220px; padding: 20px; }
      .modal-img-side img { max-height: 180px; }
      .modal-info { padding: 20px; }
      .product-img-wrap { height: 160px; }
      .product-img-wrap img { max-height: 140px; }
    }
    @media(max-width:400px) {
      .product-grid { grid-template-columns: repeat(2, 1fr) !important; }
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
      <form class="search-form" method="GET" action="index.php">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Tìm kiếm sản phẩm...">
        <button type="submit"><i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <div class="nav-icons">
        <a class="nav-icon" href="wishlist.php" style="text-decoration:none;color:var(--red)" title="Yêu thích">
          <i class="fa-solid fa-heart"></i>
          <?php if (count($products) > 0): ?>
            <div class="badge"><?= count($products) ?></div>
          <?php endif; ?>
        </a>
        <a class="nav-icon" href="cart.php" style="text-decoration:none;color:inherit">
          <i class="fa-solid fa-bag-shopping"></i>
          <div class="badge" id="cartBadge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount ?></div>
        </a>
        <div class="nav-icon user-dropdown-wrap" onclick="this.classList.toggle('open')">
          <i class="fa-regular fa-user"></i>
          <div class="user-dropdown">
            <div class="user-dropdown-header">
              <div class="greeting">Xin chào,</div>
              <div class="uname"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['user']) ?></div>
            </div>
            <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
              <a href="admin_page.php" class="user-dropdown-item admin">
                <i class="fa-solid fa-screwdriver-wrench"></i> Quản lí
              </a>
            <?php endif; ?>
            <a href="orders.php" class="user-dropdown-item">
              <i class="fa-solid fa-shopping-bag"></i> Đơn hàng của tôi
            </a>
            <a href="logout.php" class="user-dropdown-item logout">
              <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
          </div>
        </div>
      </div>
    </div>
  </nav>

  <!-- PAGE HEADER -->
  <div class="page-header">
    <div class="page-header-left">
      <div class="page-header-icon">
        <i class="fa-solid fa-heart"></i>
      </div>
      <div class="page-header-text">
        <h1>Sản phẩm yêu thích</h1>
        <p><?= count($products) ?> sản phẩm đã lưu</p>
      </div>
    </div>
    <?php if (!empty($products)): ?>
      <button class="btn-clear-all" onclick="clearAll()">
        <i class="fa-solid fa-trash"></i> Xóa tất cả
      </button>
    <?php endif; ?>
  </div>

  <!-- PRODUCTS -->
  <section class="featured">
    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="empty-heart"><i class="fa-regular fa-heart"></i></div>
        <h3>Chưa có sản phẩm yêu thích</h3>
        <p>Hãy thêm những sản phẩm bạn thích bằng cách nhấn vào icon <i class="fa-regular fa-heart" style="color:var(--red)"></i></p>
        <a href="index.php" class="btn-blue"><i class="fa-solid fa-arrow-left"></i> Khám phá sản phẩm</a>
      </div>
    <?php else: ?>
      <div class="product-grid" id="productGrid">
        <?php foreach ($products as $i => $p):
          $delay = ($i % 4) * 0.07;
        ?>
          <div class="product-card" id="card-<?= $p['id'] ?>" style="animation-delay:<?= $delay ?>s">
            <div class="product-img-wrap" onclick="openModal(<?= (int)$p['id'] ?>)">
              <?php if (!empty($p['image'])): ?>
                <img src="images/<?= htmlspecialchars($p['image']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <i class="fa-solid fa-image icon-fallback" style="display:none"></i>
              <?php else: ?>
                <i class="fa-solid fa-box icon-fallback"></i>
              <?php endif; ?>
              <button class="btn-remove-wish"
                      onclick="event.stopPropagation(); removeWishlist(<?= $p['id'] ?>, this)"
                      title="Xóa khỏi yêu thích">
                <i class="fa-solid fa-heart-crack"></i>
              </button>
            </div>
            <div class="product-info">
              <span class="product-type"><?= htmlspecialchars($p['type_name'] ?? $p['id_type']) ?></span>
              <div class="product-name" onclick="openModal(<?= (int)$p['id'] ?>)">
                <?= htmlspecialchars($p['name']) ?>
              </div>
              <div class="product-desc"><?= htmlspecialchars($p['description']) ?></div>
              <div class="price"><?= formatPrice((float)$p['price']) ?></div>
              <div class="btn-action-wrap">
                <button class="btn-cart-sm" onclick="addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p["name"]), ENT_QUOTES) ?>')" title="Thêm vào giỏ">
                  <i class="fa-solid fa-cart-plus"></i>
                </button>
                <button class="btn-buynow" onclick="buyNow(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p["name"]), ENT_QUOTES) ?>')">
                  <i class="fa-solid fa-bolt"></i> Mua ngay
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <footer>
    <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
    <p>&copy; 2026 TechStore. All rights reserved.</p>
    <div class="foot-links">
      <a href="#"><i class="fa-solid fa-shield-halved"></i> Bảo mật</a>
      <a href="#"><i class="fa-solid fa-file-lines"></i> Điều khoản</a>
      <a href="#"><i class="fa-solid fa-headset"></i> Hỗ trợ</a>
    </div>
  </footer>

  <!-- MODAL CHI TIẾT SẢN PHẨM -->
  <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="modal">
      <button class="modal-close" onclick="closeModal()"><i class="fa-solid fa-xmark"></i></button>
      <div class="modal-body">
        <div class="modal-img-side" id="modalImgSide"></div>
        <div class="modal-info">
          <span class="modal-type" id="modalType"></span>
          <div class="modal-name" id="modalName"></div>
          <div class="modal-price" id="modalPrice"></div>
          <hr class="modal-divider">
          <div class="modal-section-label"><i class="fa-solid fa-align-left"></i> Mô tả sản phẩm</div>
          <div class="modal-desc" id="modalDesc"></div>
          <div class="modal-meta" id="modalMeta"></div>
          <div class="modal-actions">
            <button class="modal-btn-cart" id="modalBtnCart" onclick="handleModalCart()">
              <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
            <button class="modal-btn-remove" id="modalBtnRemove" onclick="handleModalRemove()" title="Xóa khỏi yêu thích">
              <i class="fa-solid fa-heart-crack"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    const products = <?= $productsJson ?>;
    const productMap = {};
    products.forEach(p => { productMap[p.id] = p; });

    const typeIconMap = {
      apple: 'fa-brands fa-apple', samsung: 'fa-solid fa-mobile-screen',
      laptop: 'fa-solid fa-laptop', gaming: 'fa-solid fa-gamepad',
      audio: 'fa-solid fa-headphones', smart: 'fa-solid fa-clock',
      phone: 'fa-solid fa-mobile-screen', tablet: 'fa-solid fa-tablet-screen-button',
    };
    function getTypeIcon(typeCode) {
      const t = (typeCode || '').toLowerCase();
      for (const [k, v] of Object.entries(typeIconMap)) { if (t.includes(k)) return v; }
      return 'fa-solid fa-box';
    }
    function formatPrice(price) { return Number(price).toLocaleString('vi-VN') + '₫'; }
    function escHtml(str) {
      return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    let currentModalId = null;

    function openModal(id) {
      const p = productMap[id];
      if (!p) return;
      currentModalId = id;
      const imgSide = document.getElementById('modalImgSide');
      imgSide.innerHTML = p.image
        ? `<img src="images/${escHtml(p.image)}" alt="${escHtml(p.name)}"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
           <i class="fa-solid fa-image icon-fallback" style="display:none;font-size:6rem;"></i>`
        : `<i class="fa-solid fa-box icon-fallback" style="font-size:6rem;"></i>`;

      const typeName = p.type_name || p.id_type;
      document.getElementById('modalType').innerHTML = `<i class="${getTypeIcon(p.id_type)}"></i> ${escHtml(typeName)}`;
      document.getElementById('modalName').textContent  = p.name;
      document.getElementById('modalPrice').innerHTML   = formatPrice(p.price);
      document.getElementById('modalDesc').textContent  = p.description;
      document.getElementById('modalMeta').innerHTML = `
        <div class="modal-meta-row"><i class="fa-solid fa-tag"></i> Danh mục: <span>${escHtml(typeName)}</span></div>
        <div class="modal-meta-row"><i class="fa-solid fa-hashtag"></i> Mã sản phẩm: <span>#${escHtml(String(p.id))}</span></div>
        <div class="modal-meta-row"><i class="fa-solid fa-circle-check" style="color:#10b981"></i> Tình trạng: <span style="color:#10b981">Còn hàng</span></div>
      `;
      document.getElementById('modalBtnCart').dataset.name = p.name;
      document.getElementById('modalOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      document.getElementById('modalOverlay').classList.remove('open');
      document.body.style.overflow = '';
      currentModalId = null;
    }
    function handleOverlayClick(e) {
      if (e.target === document.getElementById('modalOverlay')) closeModal();
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

    function handleModalCart() {
      const name = document.getElementById('modalBtnCart').dataset.name || '';
      addToCart(currentModalId, name);
    }

    function handleModalRemove() {
      if (!currentModalId) return;
      const card = document.getElementById('card-' + currentModalId);
      const btn = card ? card.querySelector('.btn-remove-wish') : null;
      closeModal();
      removeWishlist(currentModalId, btn);
    }

    // ── Toast ──
    function showToast(msg, type = 'success') {
      let t = document.getElementById('toast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#111827;color:white;padding:12px 24px;border-radius:50px;font-size:.9rem;font-weight:600;z-index:9999;transition:transform .3s;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.2);';
        document.body.appendChild(t);
      }
      t.innerHTML = (type === 'success'
        ? '<i class="fa-solid fa-check" style="color:#10b981"></i> '
        : '<i class="fa-solid fa-xmark" style="color:#ef4444"></i> ') + msg;
      t.style.transform = 'translateX(-50%) translateY(0)';
      clearTimeout(t._timer);
      t._timer = setTimeout(() => { t.style.transform = 'translateX(-50%) translateY(80px)'; }, 2500);
    }

    // ── Cart badge ──
    function updateCartBadge(count) {
      const badge = document.getElementById('cartBadge');
      if (!badge) return;
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }

    // ── Wishlist count in header ──
    function updateHeaderCount(count) {
      const p = document.querySelector('.page-header-text p');
      if (p) p.textContent = count + ' sản phẩm đã lưu';
    }

    // ── Add to cart ──
    function addToCart(productId, productName) {
      fetch('cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&product_id=${productId}&qty=1`
      })
      .then(r => r.json())
      .then(d => {
        if (d.ok) { updateCartBadge(d.count); showToast('Đã thêm vào giỏ hàng!'); }
      });
    }

    function buyNow(productId, productName) {
      fetch('cart_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=add&product_id=${productId}&qty=1`
      })
      .then(r => r.json())
      .then(d => { if (d.ok) window.location.href = 'cart.php'; });
    }

    // ── Remove from wishlist ──
    function removeWishlist(productId, btn) {
      fetch('wishlist_action.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=toggle&product_id=${productId}`
      })
      .then(r => r.json())
      .then(d => {
        if (d.ok) {
          const card = document.getElementById('card-' + productId);
          if (card) {
            card.style.transition = 'opacity .3s, transform .3s';
            card.style.opacity = '0';
            card.style.transform = 'scale(.9)';
            setTimeout(() => {
              card.remove();
              delete productMap[productId];
              updateHeaderCount(d.count);
              // Nếu không còn sản phẩm, reload để show empty state
              if (d.count === 0) location.reload();
            }, 320);
          }
          showToast('Đã xóa khỏi yêu thích');
        }
      });
    }

    // ── Clear all ──
    function clearAll() {
      if (!confirm('Bạn có chắc muốn xóa tất cả sản phẩm yêu thích?')) return;
      const ids = Object.keys(productMap);
      if (!ids.length) return;

      Promise.all(ids.map(id =>
        fetch('wishlist_action.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: `action=toggle&product_id=${id}`
        }).then(r => r.json())
      )).then(() => {
        showToast('Đã xóa tất cả yêu thích');
        setTimeout(() => location.reload(), 600);
      });
    }
  </script>
</body>
</html>