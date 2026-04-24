<?php
session_start();
require_once __DIR__ . '/Database.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Số lượng giỏ hàng & wishlist
$cartCount     = isset($_SESSION['user']) ? $db->getCartCount($_SESSION['user'])     : 0;
$wishlistIds   = isset($_SESSION['user']) ? array_column($db->getWishlist($_SESSION['user']), 'product_id') : [];

$keyword  = trim($_GET['q']    ?? '');
$type     = trim($_GET['type']  ?? '');
$sort     = trim($_GET['sort']  ?? '');        // price_asc | price_desc
$page     = max(1, (int)($_GET['page']  ?? 1));
$perPage  = 8;

// Lấy danh sách loại từ bảng p_type
$typeList = $pdo->query("SELECT * FROM p_type ORDER BY name ASC")->fetchAll();

// ── Xây dựng ORDER BY ──
$orderBy = match ($sort) {
  'price_asc'  => 'p.price ASC',
  'price_desc' => 'p.price DESC',
  default      => 'p.id ASC',
};

// ── Xây dựng WHERE ──
$baseSelect = "SELECT p.*, t.name AS type_name
               FROM p_product p
               LEFT JOIN p_type t ON p.id_type = t.type";

$conditions = [];
$params     = [];

if ($keyword !== '') {
  $conditions[] = "(p.name LIKE :kw OR p.description LIKE :kw2)";
  $params[':kw']  = "%$keyword%";
  $params[':kw2'] = "%$keyword%";
}
if ($type !== '') {
  $conditions[] = "p.id_type = :type";
  $params[':type'] = $type;
}

$whereClause = $conditions ? " WHERE " . implode(' AND ', $conditions) : "";

// ── Đếm tổng để phân trang ──
$countSql  = "SELECT COUNT(*) FROM p_product p" . $whereClause;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalItems = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Lấy sản phẩm trang hiện tại ──
$sql  = $baseSelect . $whereClause . " ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// ── Helper: build URL giữ nguyên params hiện tại ──
function buildUrl(array $override = []): string
{
  $base = array_filter([
    'q'    => trim($_GET['q']    ?? ''),
    'type' => trim($_GET['type'] ?? ''),
    'sort' => trim($_GET['sort'] ?? ''),
    'page' => (int)($_GET['page'] ?? 1) > 1 ? (int)$_GET['page'] : null,
  ], fn($v) => $v !== '' && $v !== null && $v !== 0);
  $merged = array_merge($base, $override);
  $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null && $v !== 0 && $v !== 1 || ($v === 1 && ($merged['page'] ?? null) == 1));
  // Làm sạch page=1
  if (($merged['page'] ?? null) == 1) unset($merged['page']);
  return 'index.php' . ($merged ? '?' . http_build_query($merged) : '');
}

function formatPrice(float $price): string
{
  return number_format($price, 0, ',', '.') . '&#8363;';
}

// Map type code -> FA icon (dùng prefix của mã, vd apple_01 => apple)
function typeIcon(string $typeCode): string
{
  $map = [
    'apple'   => 'fa-solid fa-mobile-screen-button',
    'samsung' => 'fa-solid fa-mobile-screen',
    'laptop'  => 'fa-solid fa-laptop',
    'gaming'  => 'fa-solid fa-gamepad',
    'audio'   => 'fa-solid fa-headphones',
    'smart'   => 'fa-solid fa-clock',
    'phone'   => 'fa-solid fa-mobile-screen',
    'tablet'  => 'fa-solid fa-tablet-screen-button',
    'xiaomi'  => 'fa-solid fa-mobile-screen',
    'redmi'   => 'fa-solid fa-mobile-screen',
  ];
  foreach ($map as $k => $v) {
    if (stripos($typeCode, $k) !== false) return $v;
  }
  return 'fa-solid fa-box';
}

// Encode products sang JSON cho JS modal
$productsJson = json_encode(array_values($products), JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TechStore<?= $keyword ? ' - ' . htmlspecialchars($keyword) : '' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

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
      --shadow: 0 4px 24px rgba(13, 27, 75, 0.10);
      --shadow-lg: 0 8px 40px rgba(13, 27, 75, 0.18);
      --r: 14px;
      --r-sm: 8px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* NAVBAR */
    nav {
      background: var(--white);
      border-bottom: 1.5px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
    }

    .nav-inner {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      gap: 24px;
      padding: 14px 24px;
    }

    .logo {
      font-weight: 800;
      font-size: 1.35rem;
      color: var(--blue);
      letter-spacing: -0.5px;
      text-decoration: none;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .logo i {
      color: var(--cyan);
    }

    .search-form {
      flex: 1;
      display: flex;
      align-items: center;
      background: var(--bg);
      border: 1.5px solid var(--border);
      border-radius: 50px;
      padding: 8px 18px;
      gap: 10px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .search-form:focus-within {
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .search-form i {
      color: var(--muted);
      font-size: 0.9rem;
    }

    .search-form input {
      border: none;
      background: transparent;
      outline: none;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      color: var(--text);
      width: 100%;
    }

    .search-form button {
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      font-size: 0.9rem;
      padding: 0;
      transition: color 0.2s;
    }

    .search-form button:hover {
      color: var(--blue);
    }

    .nav-icons {
      display: flex;
      align-items: center;
      gap: 22px;
    }

    .nav-icon {
      position: relative;
      cursor: pointer;
      color: var(--text);
      transition: color 0.2s;
      font-size: 1.15rem;
    }

    .nav-icon:hover {
      color: var(--blue);
    }

    .nav-icon .badge {
      position: absolute;
      top: -7px;
      right: -9px;
      background: var(--red);
      color: white;
      font-size: 0.58rem;
      font-weight: 800;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .nav-menu {
      max-width: 1100px;
      margin: 0 auto;
      display: flex;
      gap: 28px;
      padding: 0 24px 12px;
      flex-wrap: wrap;
    }

    .nav-menu a {
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text);
      text-decoration: none;
      transition: color 0.2s;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .nav-menu a:hover,
    .nav-menu a.active {
      color: var(--blue);
      font-weight: 700;
    }

    .nav-menu a.deal {
      color: var(--red);
      font-weight: 700;
    }

    /* BANNER SLIDESHOW */
    .banner-slider {
      max-width: 1100px;
      margin: 18px auto 0;
      border-radius: 16px;
      overflow: hidden;
      position: relative;
      box-shadow: var(--shadow-lg);
      height: 520px;
      background: #000;
    }

    .banner-track {
      display: flex;
      height: 100%;
      transition: transform 0.7s cubic-bezier(.4, 0, .2, 1);
    }

    .banner-slide {
      min-width: 100%;
      height: 520px;
      flex-shrink: 0;
    }

    .banner-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .banner-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      z-index: 10;
      background: rgba(0, 0, 0, 0.35);
      backdrop-filter: blur(4px);
      border: 1.5px solid rgba(255, 255, 255, 0.25);
      color: white;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      font-size: 1rem;
      transition: background 0.2s, transform 0.15s;
    }

    .banner-arrow:hover {
      background: rgba(0, 0, 0, 0.6);
      transform: translateY(-50%) scale(1.1);
    }

    .banner-arrow.prev {
      left: 16px;
    }

    .banner-arrow.next {
      right: 16px;
    }

    .banner-dots {
      position: absolute;
      bottom: 14px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 8px;
      z-index: 10;
    }

    .banner-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.45);
      cursor: pointer;
      transition: background 0.2s, transform 0.2s;
      border: none;
      padding: 0;
    }

    .banner-dot.active {
      background: white;
      transform: scale(1.35);
    }

    .banner-progress {
      position: absolute;
      bottom: 0;
      left: 0;
      height: 3px;
      background: var(--cyan);
      width: 0%;
      z-index: 10;
    }

    /* SECTION TITLE */
    .section-title {
      text-align: center;
      font-size: 1.55rem;
      font-weight: 800;
      color: var(--text);
      margin-bottom: 30px;
      letter-spacing: -0.5px;
    }

    /* CATEGORIES */
    .categories {
      max-width: 1100px;
      margin: 44px auto 0;
      padding: 0 24px;
    }

    .cat-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 14px;
    }

    @media(max-width:900px) {
      .cat-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media(max-width:560px) {
      .cat-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .cat-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      padding: 20px 10px;
      border-radius: var(--r);
      background: white;
      border: 1.5px solid var(--border);
      cursor: pointer;
      transition: all 0.22s;
      text-decoration: none;
      color: var(--text);
      box-shadow: var(--shadow);
    }

    .cat-card:hover {
      transform: translateY(-5px);
      box-shadow: var(--shadow-lg);
      border-color: var(--blue);
    }

    .cat-icon {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
    }

    .cat-label {
      font-size: 0.85rem;
      font-weight: 600;
      text-align: center;
    }

    /* FILTER BAR */
    .filter-bar {
      max-width: 1100px;
      margin: 36px auto 0;
      padding: 0 24px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .result-info {
      font-size: 0.93rem;
      color: var(--muted);
      margin-right: auto;
    }

    .result-info strong {
      color: var(--text);
    }

    .filter-tag {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 0.83rem;
      font-weight: 600;
      background: white;
      border: 1.5px solid var(--border);
      cursor: pointer;
      text-decoration: none;
      color: var(--text);
      transition: all 0.2s;
    }

    .filter-tag.active {
      background: var(--blue);
      color: white;
      border-color: var(--blue);
    }

    /* PRODUCTS */
    .featured {
      max-width: 1100px;
      margin: 28px auto 0;
      padding: 0 24px;
    }

    .product-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    @media(max-width:900px) {
      .product-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media(max-width:520px) {
      .product-grid {
        grid-template-columns: 1fr;
      }
    }

    .product-card {
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--r);
      overflow: hidden;
      transition: all 0.22s;
      box-shadow: var(--shadow);
      display: flex;
      flex-direction: column;
      animation: cardIn 0.4s both;
    }

    @keyframes cardIn {
      from {
        opacity: 0;
        transform: translateY(16px);
      }

      to {
        opacity: 1;
        transform: none;
      }
    }

    .product-card:hover {
      transform: translateY(-6px);
      box-shadow: var(--shadow-lg);
      border-color: var(--blue);
    }

    .product-img-wrap {
      width: 100%;
      height: 200px;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 10px;
      overflow: hidden;
    }

    .product-img-wrap img {
      max-width: 100%;
      max-height: 180px;
      width: auto;
      height: auto;
      object-fit: contain;
      display: block;
    }

    .icon-fallback {
      font-size: 4rem;
      color: #cbd5e1;
    }

    .product-info {
      padding: 16px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .product-type {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #eff6ff;
      color: var(--blue);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 3px 10px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      width: fit-content;
    }

    .product-name {
      font-size: 0.97rem;
      font-weight: 700;
      line-height: 1.4;
      cursor: pointer;
      transition: color 0.2s;
    }

    .product-name:hover {
      color: var(--blue);
      text-decoration: underline;
    }

    .product-desc {
      font-size: 0.82rem;
      color: var(--muted);
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .price {
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--blue);
      margin-top: 4px;
    }

    /* Wrapper nút hành động */
    .btn-action-wrap {
      display: flex;
      gap: 8px;
      margin-top: auto;
    }

    /* Nút Mua ngay - chiếm phần lớn */
    .btn-buynow {
      flex: 1;
      padding: 10px 14px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
    }

    .btn-buynow:hover {
      background: #1d4ed8;
      transform: scale(1.02);
    }

    /* Nút giỏ hàng - hình vuông nhỏ bên trái */
    .btn-cart-sm {
      flex-shrink: 0;
      width: 38px;
      height: 38px;
      background: #eff6ff;
      color: var(--blue);
      border: 1.5px solid #bfdbfe;
      border-radius: var(--r-sm);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      cursor: pointer;
      transition: background 0.2s, color 0.2s, border-color 0.2s;
      padding: 0;
    }

    .btn-cart-sm:hover {
      background: var(--blue);
      color: white;
      border-color: var(--blue);
    }

    /* Nút wishlist trên card */
    .btn-wish-card {
      position: absolute;
      top: 10px;
      right: 10px;
      width: 32px;
      height: 32px;
      background: rgba(255, 255, 255, 0.9);
      border: 1.5px solid var(--border);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.85rem;
      cursor: pointer;
      color: var(--muted);
      transition: all 0.2s;
      backdrop-filter: blur(4px);
    }

    .btn-wish-card:hover,
    .btn-wish-card.active {
      color: var(--red);
      border-color: #fecaca;
      background: #fff0f0;
    }

    .btn-wish-card.active i {
      font-weight: 900;
    }

    .product-img-wrap {
      position: relative;
    }

    /* EMPTY */
    .empty-state {
      text-align: center;
      padding: 80px 24px;
    }

    .empty-state .empty-icon {
      font-size: 4rem;
      color: #cbd5e1;
      margin-bottom: 16px;
    }

    .empty-state h3 {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .empty-state p {
      color: var(--muted);
      margin-bottom: 24px;
    }

    .btn-blue {
      background: var(--blue);
      color: white;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 26px;
      border-radius: 50px;
      font-family: 'Inter', sans-serif;
      font-size: 0.93rem;
      font-weight: 700;
      text-decoration: none;
      transition: background 0.2s;
    }

    .btn-blue:hover {
      background: #1d4ed8;
    }

    /* PROMO */
    .promo-banner {
      max-width: 1100px;
      margin: 50px auto 0;
      padding: 0 24px;
    }

    .promo-inner {
      background: linear-gradient(135deg, #7c3aed 0%, #2563eb 50%, #06b6d4 100%);
      border-radius: var(--r);
      padding: 52px 56px;
      display: flex;
      flex-direction: column;
      align-items: center;
      text-align: center;
      gap: 16px;
    }

    .promo-inner h2 {
      font-size: 2.1rem;
      font-weight: 900;
      color: white;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 12px;
      justify-content: center;
    }

    .promo-inner p {
      color: rgba(255, 255, 255, 0.82);
      font-size: 1rem;
    }

    .btn-promo {
      background: rgba(255, 255, 255, 0.18);
      border: 2px solid rgba(255, 255, 255, 0.6);
      color: white;
      padding: 11px 30px;
      border-radius: 50px;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .btn-promo:hover {
      background: white;
      color: var(--purple);
    }

    /* FOOTER */
    footer {
      max-width: 1100px;
      margin: 50px auto 0;
      padding: 32px 24px;
      border-top: 1.5px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 16px;
    }

    footer p {
      font-size: 0.85rem;
      color: var(--muted);
    }

    .foot-links {
      display: flex;
      gap: 20px;
    }

    .foot-links a {
      font-size: 0.85rem;
      color: var(--muted);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .foot-links a:hover {
      color: var(--blue);
    }

    mark {
      background: #fef3c7;
      border-radius: 3px;
      padding: 0 2px;
    }

    /* MODAL */
    .modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 999;
      background: rgba(8, 15, 45, 0.6);
      backdrop-filter: blur(5px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.25s, visibility 0.25s;
    }

    .modal-overlay.open {
      opacity: 1;
      visibility: visible;
    }

    .modal {
      background: white;
      border-radius: 20px;
      max-width: 980px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
      box-shadow: 0 24px 80px rgba(13, 27, 75, 0.28);
      transform: translateY(32px) scale(0.97);
      transition: transform 0.28s cubic-bezier(.4, 0, .2, 1);
      position: relative;
    }

    .modal-overlay.open .modal {
      transform: translateY(0) scale(1);
    }

    .modal-close {
      position: absolute;
      top: 14px;
      right: 14px;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: none;
      background: #f1f5f9;
      color: var(--muted);
      font-size: 0.95rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s, color 0.2s;
      z-index: 2;
    }

    .modal-close:hover {
      background: var(--red);
      color: white;
    }

    .modal-body {
      display: grid;
      grid-template-columns: 1fr 1fr;
    }

    @media(max-width:600px) {
      .modal-body {
        grid-template-columns: 1fr;
      }
    }

    .modal-img-side {
      background: #f1f5f9;
      border-radius: 20px 0 0 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 32px 24px;
      min-height: 0;
    }

    @media(max-width:600px) {
      .modal-img-side {
        border-radius: 20px 20px 0 0;
        min-height: 400px;
      }
    }

    .modal-img-side img {
      max-width: 100%;
      max-height: 360px;
      width: auto;
      height: auto;
      object-fit: contain;
    }

    .modal-img-side .icon-fallback {
      font-size: 6rem;
    }

    .modal-info {
      padding: 32px 28px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }

    .modal-type {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #eff6ff;
      color: var(--blue);
      font-size: 0.72rem;
      font-weight: 700;
      padding: 4px 12px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      width: fit-content;
    }

    .modal-name {
      font-size: 1.25rem;
      font-weight: 800;
      line-height: 1.35;
      color: var(--text);
    }

    .modal-price {
      font-size: 1.7rem;
      font-weight: 900;
      color: var(--blue);
    }

    .modal-divider {
      border: none;
      border-top: 1.5px solid var(--border);
    }

    .modal-section-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .modal-desc {
      font-size: 0.93rem;
      color: #374151;
      line-height: 1.75;
      white-space: pre-line;
      max-height: 160px;
      overflow-y: auto;
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      padding: 12px;
      background: #f8fafc;
    }

    .modal-desc::-webkit-scrollbar {
      width: 5px;
    }

    .modal-desc::-webkit-scrollbar-track {
      background: transparent;
    }

    .modal-desc::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
    }

    .modal-desc::-webkit-scrollbar-thumb:hover {
      background: var(--blue);
    }

    .modal-meta {
      background: #f8fafc;
      border-radius: 10px;
      padding: 14px 16px;
      display: flex;
      flex-direction: column;
      gap: 9px;
    }

    .modal-meta-row {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.87rem;
      color: var(--muted);
    }

    .modal-meta-row i {
      color: var(--blue);
      width: 16px;
      text-align: center;
      flex-shrink: 0;
    }

    .modal-meta-row span {
      color: var(--text);
      font-weight: 600;
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      margin-top: auto;
    }

    .modal-btn-cart {
      flex: 1;
      padding: 12px;
      background: var(--blue);
      color: white;
      border: none;
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.93rem;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .modal-btn-cart:hover {
      background: #1d4ed8;
      transform: scale(1.02);
    }

    .modal-btn-wish {
      padding: 12px 16px;
      background: #f1f5f9;
      color: var(--text);
      border: none;
      border-radius: var(--r-sm);
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s;
    }

    .modal-btn-wish:hover {
      background: #fee2e2;
      color: var(--red);
    }

    /* USER DROPDOWN */
    .user-dropdown-wrap {
      position: relative;
    }

    .user-dropdown {
      position: absolute;
      top: calc(100% + 12px);
      right: 0;
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--r);
      box-shadow: var(--shadow-lg);
      min-width: 200px;
      overflow: hidden;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-8px);
      transition: opacity 0.2s, visibility 0.2s, transform 0.2s;
      z-index: 200;
    }

    .user-dropdown-wrap:hover .user-dropdown,
    .user-dropdown-wrap.open .user-dropdown {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .user-dropdown-header {
      padding: 14px 16px 10px;
      border-bottom: 1px solid var(--border);
    }

    .user-dropdown-header .greeting {
      font-size: 0.78rem;
      color: var(--muted);
      margin-bottom: 2px;
    }

    .user-dropdown-header .uname {
      font-size: 0.95rem;
      font-weight: 700;
      color: var(--text);
    }

    .user-dropdown-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 16px;
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text);
      text-decoration: none;
      transition: background 0.15s, color 0.15s;
      cursor: pointer;
      border: none;
      background: none;
      width: 100%;
      text-align: left;
      font-family: 'Inter', sans-serif;
    }

    .user-dropdown-item:hover {
      background: var(--bg);
      color: var(--blue);
    }

    .user-dropdown-item.logout {
      color: var(--red);
    }

    .user-dropdown-item.logout:hover {
      background: #fef2f2;
      color: var(--red);
    }

    .user-dropdown-item.admin {
      color: var(--purple);
    }

    .user-dropdown-item.admin:hover {
      background: #f5f3ff;
      color: var(--purple);
    }

    .user-dropdown-item i {
      width: 16px;
      text-align: center;
      font-size: 0.9rem;
    }

    /* LOGIN REQUIRED POPUP */
    .login-popup-overlay {
      position: fixed;
      inset: 0;
      z-index: 1000;
      background: rgba(8, 15, 45, 0.6);
      backdrop-filter: blur(5px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.25s, visibility 0.25s;
    }

    .login-popup-overlay.open {
      opacity: 1;
      visibility: visible;
    }

    .login-popup {
      background: white;
      border-radius: 20px;
      max-width: 400px;
      width: 100%;
      box-shadow: 0 24px 80px rgba(13, 27, 75, 0.28);
      transform: translateY(24px) scale(0.97);
      transition: transform 0.28s cubic-bezier(.4, 0, .2, 1);
      overflow: hidden;
    }

    .login-popup-overlay.open .login-popup {
      transform: translateY(0) scale(1);
    }

    .login-popup-header {
      background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy) 60%, #1a237e 100%);
      padding: 28px 32px 24px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .login-popup-header::before {
      content: '';
      position: absolute;
      top: -30px;
      right: -30px;
      width: 140px;
      height: 140px;
      background: radial-gradient(circle, rgba(6, 182, 212, 0.2) 0%, transparent 70%);
      border-radius: 50%;
    }

    .login-popup-header .popup-icon {
      font-size: 2.2rem;
      color: var(--cyan);
      margin-bottom: 10px;
      position: relative;
    }

    .login-popup-header h3 {
      font-size: 1.15rem;
      font-weight: 800;
      color: white;
      position: relative;
    }

    .login-popup-header p {
      font-size: 0.85rem;
      color: rgba(255, 255, 255, 0.65);
      margin-top: 6px;
      position: relative;
    }

    .login-popup-close {
      position: absolute;
      top: 12px;
      right: 12px;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      border: none;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      font-size: 0.85rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.2s;
      z-index: 2;
    }

    .login-popup-close:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .login-popup-body {
      padding: 28px 32px 32px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .popup-product-name {
      background: var(--bg);
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      padding: 10px 14px;
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--text);
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .popup-product-name i {
      color: var(--blue);
    }

    .btn-popup-login {
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
      text-decoration: none;
    }

    .btn-popup-login:hover {
      background: #1d4ed8;
      box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
      transform: translateY(-1px);
    }

    .btn-popup-cancel {
      width: 100%;
      padding: 11px;
      background: var(--bg);
      color: var(--muted);
      border: 1.5px solid var(--border);
      border-radius: var(--r-sm);
      font-family: 'Inter', sans-serif;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-popup-cancel:hover {
      background: var(--border);
      color: var(--text);
    }

    /* ── RESPONSIVE MOBILE ── */
    @media(max-width: 768px) {
      .nav-inner {
        padding: 10px 16px;
        gap: 12px;
      }

      .search-form {
        display: none;
      }

      .nav-menu {
        padding: 0 16px 10px;
        gap: 0;
        overflow-x: auto;
        flex-wrap: nowrap;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
      }

      .nav-menu::-webkit-scrollbar {
        display: none;
      }

      .nav-menu a {
        white-space: nowrap;
        padding: 6px 14px;
        font-size: 0.85rem;
        flex-shrink: 0;
      }

      .banner-slider {
        max-width: 100%;
        margin: 0;
        border-radius: 0;
        height: 240px;
      }

      .banner-slide {
        height: 240px;
      }

      .banner-arrow {
        width: 34px;
        height: 34px;
        font-size: 0.85rem;
      }

      .cat-grid {
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 10px;
      }

      .categories {
        padding: 0 12px;
        margin-top: 24px;
      }

      .product-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 12px;
      }

      .featured {
        padding: 0 12px;
        margin-top: 20px;
      }

      .filter-bar {
        padding: 0 12px;
        margin-top: 20px;
      }

      .promo-inner {
        padding: 32px 24px;
      }

      .promo-inner h2 {
        font-size: 1.4rem;
      }

      .promo-banner {
        padding: 0 12px;
      }

      footer {
        flex-direction: column;
        align-items: flex-start;
        padding: 24px 16px;
        gap: 12px;
      }

      .modal {
        max-width: 100%;
        max-height: 95vh;
        border-radius: 16px 16px 0 0;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        margin: 0;
      }

      .modal-overlay {
        align-items: flex-end;
        padding: 0;
      }

      .modal-body {
        grid-template-columns: 1fr;
      }

      .modal-img-side {
        border-radius: 16px 16px 0 0;
        min-height: 220px;
        padding: 20px;
      }

      .modal-img-side img {
        max-height: 180px;
      }

      .modal-info {
        padding: 20px;
      }

      .login-popup {
        max-width: 100%;
        margin: 0 8px;
      }

      .product-img-wrap {
        height: 160px;
      }

      .product-img-wrap img {
        max-height: 140px;
      }
    }

    @media(max-width: 400px) {
      .product-grid {
        grid-template-columns: repeat(2, 1fr) !important;
      }

      .banner-slider {
        height: 200px;
      }

      .banner-slide {
        height: 200px;
      }
    }

    /* SORT SELECT */
    .sort-select {
      padding: 7px 32px 7px 12px;
      border: 1.5px solid var(--border);
      border-radius: 50px;
      font-family: 'Inter', sans-serif;
      font-size: 0.83rem;
      font-weight: 600;
      color: var(--text);
      background: white;
      outline: none;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 10px center;
      transition: border-color 0.2s;
    }

    .sort-select:focus {
      border-color: var(--blue);
    }

    /* PAGINATION */
    .pagination {
      max-width: 1100px;
      margin: 36px auto 0;
      padding: 0 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .page-btn {
      min-width: 38px;
      height: 38px;
      padding: 0 10px;
      border-radius: var(--r-sm);
      border: 1.5px solid var(--border);
      background: white;
      color: var(--text);
      font-family: 'Inter', sans-serif;
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all 0.18s;
    }

    .page-btn:hover {
      border-color: var(--blue);
      color: var(--blue);
    }

    .page-btn.active {
      background: var(--blue);
      color: white;
      border-color: var(--blue);
    }

    .page-btn.disabled {
      opacity: 0.4;
      pointer-events: none;
    }

    .page-dots {
      color: var(--muted);
      font-size: 0.88rem;
      padding: 0 4px;
    }

    /* NAV DROPDOWN */
    .nav-dropdown {
      position: relative;
    }

    .nav-dropdown-toggle {
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 5px;
      cursor: pointer;
      transition: color 0.2s;
      white-space: nowrap;
    }

    .nav-dropdown-toggle:hover,
    .nav-dropdown-toggle.active {
      color: var(--blue);
      font-weight: 700;
    }

    .nav-chevron {
      font-size: 0.65rem;
      transition: transform 0.2s;
    }

    .nav-dropdown:hover .nav-chevron {
      transform: rotate(180deg);
    }

    .nav-dropdown-menu {
      position: absolute;
      top: calc(100% + 10px);
      left: 0;
      background: white;
      border: 1.5px solid var(--border);
      border-radius: var(--r);
      box-shadow: var(--shadow-lg);
      min-width: 180px;
      overflow: hidden;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-6px);
      transition: opacity 0.2s, visibility 0.2s, transform 0.2s;
      z-index: 200;
    }

    .nav-dropdown:hover .nav-dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .nav-dropdown-menu a {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      font-size: 0.88rem;
      font-weight: 500;
      color: var(--text);
      text-decoration: none;
      transition: background 0.15s, color 0.15s;
      white-space: nowrap;
    }

    .nav-dropdown-menu a:hover,
    .nav-dropdown-menu a.active {
      background: #eff6ff;
      color: var(--blue);
      font-weight: 700;
    }

    @media(max-width: 768px) {
      .nav-dropdown-menu {
        position: static;
        box-shadow: none;
        border: none;
        background: #f8fafc;
        border-radius: 0;
        opacity: 1;
        visibility: visible;
        transform: none;
        display: none;
      }

      .nav-dropdown.open .nav-dropdown-menu {
        display: block;
      }

      .nav-dropdown.open .nav-chevron {
        transform: rotate(180deg);
      }

      .nav-dropdown-menu a {
        padding: 6px 20px;
        font-size: 0.83rem;
      }
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
        <?php if ($type): ?>
          <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
        <?php endif; ?>
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" name="q" placeholder="Tìm kiếm sản phẩm..." value="<?= htmlspecialchars($keyword) ?>">
        <button type="submit"><i class="fa-solid fa-arrow-right"></i></button>
      </form>
      <div class="nav-icons">
        <a class="nav-icon" id="wishlistIcon" href="wishlist.php" title="Yêu thích" style="text-decoration:none;color:inherit">
          <i class="fa-regular fa-heart"></i>
          <?php if (!empty($wishlistIds)): ?>
            <div class="badge" id="wishlistBadge"><?= count($wishlistIds) ?></div>
          <?php else: ?>
            <div class="badge" id="wishlistBadge" style="display:none"><?= count($wishlistIds) ?></div>
          <?php endif; ?>
        </a>
        <a class="nav-icon" href="cart.php" style="text-decoration:none;color:inherit">
          <i class="fa-solid fa-bag-shopping"></i>
          <div class="badge" id="cartBadge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>"><?= $cartCount ?></div>
        </a>
        <div class="nav-icon user-dropdown-wrap" onclick="this.classList.toggle('open')">
          <i class="fa-regular fa-user"></i>
          <div class="user-dropdown">
            <?php if (isset($_SESSION['user'])): ?>
              <div class="user-dropdown-header">
                <div class="greeting">Xin chào,</div>
                <div class="uname"><?= htmlspecialchars($_SESSION['name'] ?? $_SESSION['user']) ?></div>
              </div>
              <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                <a href="admin_page.php" class="user-dropdown-item admin">
                  <i class="fa-solid fa-screwdriver-wrench"></i> Quản lí
                </a>
              <?php endif; ?>
              <a href="logout.php" class="user-dropdown-item logout">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
              </a>
            <?php else: ?>
              <a href="login_form.php" class="user-dropdown-item">
                <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
    <div class="nav-menu">
      <a href="index.php" <?= (!$keyword && !$type) ? 'class="active"' : '' ?>>
        <i class="fa-solid fa-house"></i> Tất cả
      </a>

      <!-- Dropdown Điện thoại -->
      <?php
      $phoneTypes = array_filter(
        $typeList,
        fn($t) =>
        stripos($t['type'], 'phone') !== false ||
          stripos($t['type'], 'apple') !== false ||
          stripos($t['type'], 'samsung') !== false
      );
      $phoneTypes = array_filter($typeList, fn($t) => ($t['category'] ?? '') === 'phone');
      $isPhoneActive = in_array($type, array_column($phoneTypes, 'type'));
      // $laptopTypes = array_filter($typeList, fn($t) => ($t['category'] ?? '') === 'laptop');
      // $isLaptopActive = in_array($type, array_column($laptopTypes, 'type'));
      ?>
      <div class="nav-dropdown" id="ddPhone">
        <a class="nav-dropdown-toggle <?= $isPhoneActive ? 'active' : '' ?>"
          onclick="toggleNavDropdown('ddPhone')">
          <i class="fa-solid fa-mobile-screen"></i> Điện thoại
          <i class="fa-solid fa-chevron-down nav-chevron"></i>
        </a>
        <div class="nav-dropdown-menu">
          <?php foreach ($phoneTypes as $t): ?>
            <a href="?type=<?= urlencode($t['type']) ?>"
              <?= $type === $t['type'] ? 'class="active"' : '' ?>>
              <i class="<?= typeIcon($t['type']) ?>"></i>
              <?= htmlspecialchars($t['name']) ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <a href="?deal=1" class="deal"><i class="fa-solid fa-tag"></i> Sale</a>
    </div>
  </nav>

  <?php if (!$keyword && !$type): ?>
    <!-- BANNER SLIDESHOW -->
    <div class="banner-slider" id="bannerSlider">
      <div class="banner-track" id="bannerTrack">
        <div class="banner-slide">
          <img src="banners/ip17_banner.jpg" alt="iPhone 17 Pro Max">
        </div>
        <div class="banner-slide">
          <img src="banners/sss26ul_banner.jpg" alt="Samsung Galaxy S26 Ultra">
        </div>
        <div class="banner-slide">
          <img src="banners/macbookairm4_banner(1).jpg" alt="Macbook Air M4">
        </div>
      </div>
      <button class="banner-arrow prev" onclick="bannerPrev()">
        <i class="fa-solid fa-chevron-left"></i>
      </button>
      <button class="banner-arrow next" onclick="bannerNext()">
        <i class="fa-solid fa-chevron-right"></i>
      </button>
      <div class="banner-dots" id="bannerDots">
        <button class="banner-dot active" onclick="bannerGoTo(0)"></button>
        <button class="banner-dot" onclick="bannerGoTo(1)"></button>
        <button class="banner-dot" onclick="bannerGoTo(2)"></button>
      </div>
      <div class="banner-progress" id="bannerProgress"></div>
    </div>
  <?php endif; ?>

  <!-- FILTER BAR -->
  <div class="filter-bar" id="products">
    <div class="result-info">
      <?php
      $typeName = $type;
      foreach ($typeList as $tl) {
        if ($tl['type'] === $type) {
          $typeName = $tl['name'];
          break;
        }
      }
      ?>
      <?php if ($keyword || $type): ?>
        Tìm thấy <strong><?= $totalItems ?></strong> sản phẩm
        <?= $keyword ? " cho <strong>\"" . htmlspecialchars($keyword) . "\"</strong>" : '' ?>
        <?= $type ? " trong <strong>" . htmlspecialchars($typeName) . "</strong>" : '' ?>
      <?php else: ?>
        <strong><?= $totalItems ?></strong> sản phẩm
      <?php endif; ?>
    </div>

    <?php if ($keyword): ?>
      <a href="<?= htmlspecialchars(buildUrl(['q' => '', 'page' => null])) ?>" class="filter-tag active">
        <i class="fa-solid fa-magnifying-glass"></i> <?= htmlspecialchars($keyword) ?>
        <i class="fa-solid fa-xmark"></i>
      </a>
    <?php endif; ?>
    <?php if ($type): ?>
      <a href="<?= htmlspecialchars(buildUrl(['type' => '', 'page' => null])) ?>" class="filter-tag active">
        <i class="fa-solid fa-folder"></i> <?= htmlspecialchars($typeName) ?>
        <i class="fa-solid fa-xmark"></i>
      </a>
    <?php endif; ?>

    <!-- Sort -->
    <form method="GET" action="index.php" style="margin-left:auto">
      <?= $keyword ? '<input type="hidden" name="q"    value="' . htmlspecialchars($keyword) . '">' : '' ?>
      <?= $type    ? '<input type="hidden" name="type" value="' . htmlspecialchars($type)    . '">' : '' ?>
      <select class="sort-select" name="sort" onchange="this.form.submit()">
        <option value="" <?= $sort === ''           ? 'selected' : '' ?>>Mặc định</option>
        <option value="price_asc" <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Giá tăng dần</option>
        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
      </select>
    </form>
  </div>

  <!-- PRODUCTS -->
  <section class="featured">
    <?php if (empty($products)): ?>
      <div class="empty-state">
        <div class="empty-icon"><i class="fa-solid fa-box-open"></i></div>
        <h3>Không tìm thấy sản phẩm</h3>
        <p>Thử từ khóa khác hoặc xem tất cả sản phẩm.</p>
        <a href="index.php" class="btn-blue"><i class="fa-solid fa-arrow-left"></i> Xem tất cả</a>
      </div>
    <?php else: ?>
      <div class="product-grid">
        <?php foreach ($products as $i => $p):
          $delay    = ($i % 4) * 0.07;
          $dispName = htmlspecialchars($p['name']);
          if ($keyword) {
            $dispName = preg_replace(
              '/(' . preg_quote(htmlspecialchars($keyword), '/') . ')/i',
              '<mark>$1</mark>',
              $dispName
            );
          }
          $displayType = $p['type_name'] ?? $p['id_type'];
        ?>
          <div class="product-card" style="animation-delay:<?= $delay ?>s">
            <div class="product-img-wrap" style="cursor:pointer" onclick="openModal(<?= (int)$p['id'] ?>)">
              <?php if (!empty($p['image'])): ?>
                <img src="images/<?= htmlspecialchars($p['image']) ?>"
                  alt="<?= htmlspecialchars($p['name']) ?>"
                  onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
                <i class="fa-solid fa-image icon-fallback" style="display:none;"></i>
              <?php else: ?>
                <i class="fa-solid fa-box icon-fallback"></i>
              <?php endif; ?>
              <?php if (isset($_SESSION['user'])): ?>
                <button class="btn-wish-card <?= in_array($p['id'], $wishlistIds) ? 'active' : '' ?>"
                  onclick="event.stopPropagation(); toggleWishlist(<?= $p['id'] ?>, this)"
                  title="Yêu thích">
                  <i class="<?= in_array($p['id'], $wishlistIds) ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                </button>
              <?php endif; ?>
            </div>
            <div class="product-info">
              <span class="product-type"><?= htmlspecialchars($displayType) ?></span>
              <div class="product-name" onclick="openModal(<?= (int)$p['id'] ?>)"><?= $dispName ?></div>
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

  <!-- PAGINATION -->
  <?php if ($totalPages > 1): ?>
    <div class="pagination">
      <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>"
        href="<?= htmlspecialchars(buildUrl(['page' => $page - 1])) ?>">
        <i class="fa-solid fa-chevron-left"></i>
      </a>
      <?php
      $start = max(1, $page - 2);
      $end   = min($totalPages, $page + 2);
      if ($start > 1): ?>
        <a class="page-btn" href="<?= htmlspecialchars(buildUrl(['page' => 1])) ?>">1</a>
        <?php if ($start > 2): ?><span class="page-dots">…</span><?php endif;
                                                              endif;
                                                              for ($i = $start; $i <= $end; $i++): ?>
        <a class="page-btn <?= $i === $page ? 'active' : '' ?>"
          href="<?= htmlspecialchars(buildUrl(['page' => $i])) ?>"><?= $i ?></a>
        <?php endfor;
                                                              if ($end < $totalPages):
                                                                if ($end < $totalPages - 1): ?><span class="page-dots">…</span><?php endif; ?>
        <a class="page-btn" href="<?= htmlspecialchars(buildUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
      <?php endif; ?>
      <a class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>"
        href="<?= htmlspecialchars(buildUrl(['page' => $page + 1])) ?>">
        <i class="fa-solid fa-chevron-right"></i>
      </a>
    </div>
  <?php endif; ?>

  <?php if (!$keyword && !$type): ?>
    <section class="promo-banner">
      <div class="promo-inner">
        <h2><i class="fa-solid fa-fire"></i> Sale Công nghệ</h2>
        <p>Giảm đến 40% cho toàn bộ phụ kiện và thiết bị</p>
        <button class="btn-promo"><i class="fa-solid fa-tag"></i> Xem ngay</button>
      </div>
    </section>
  <?php endif; ?>

  <footer>
    <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
    <p>&copy; 2026 TechStore. All rights reserved.</p>
    <div class="foot-links">
      <a href="#"><i class="fa-solid fa-shield-halved"></i> Bảo mật</a>
      <a href="#"><i class="fa-solid fa-file-lines"></i> Điều khoản</a>
      <a href="#"><i class="fa-solid fa-headset"></i> Hỗ trợ</a>
      <a href="#"><i class="fa-solid fa-envelope"></i> Liên hệ</a>
    </div>
  </footer>

  <!-- MODAL CHI TIẾT SẢN PHẨM -->
  <div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
    <div class="modal" id="modal">
      <button class="modal-close" onclick="closeModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="modal-body">
        <div class="modal-img-side" id="modalImgSide"></div>
        <div class="modal-info">
          <span class="modal-type" id="modalType"></span>
          <div class="modal-name" id="modalName"></div>
          <div class="modal-price" id="modalPrice"></div>
          <hr class="modal-divider">
          <div class="modal-section-label">
            <i class="fa-solid fa-align-left"></i> Mô tả sản phẩm
          </div>
          <div class="modal-desc" id="modalDesc"></div>
          <div class="modal-meta" id="modalMeta"></div>
          <div class="modal-actions">
            <button class="modal-btn-cart" id="modalBtnCart" onclick="handleModalCart()">
              <i class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng
            </button>
            <button class="modal-btn-wish" title="Yêu thích">
              <i class="fa-regular fa-heart"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- LOGIN REQUIRED POPUP -->
  <div class="login-popup-overlay" id="loginPopupOverlay" onclick="handlePopupOverlay(event)">
    <div class="login-popup" id="loginPopup">
      <div class="login-popup-header">
        <button class="login-popup-close" onclick="closeLoginPopup()">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="popup-icon"><i class="fa-solid fa-lock"></i></div>
        <h3>Bạn chưa đăng nhập</h3>
        <p>Vui lòng đăng nhập để mua sản phẩm</p>
      </div>
      <div class="login-popup-body">
        <div class="popup-product-name" id="popupProductName">
          <i class="fa-solid fa-box"></i>
          <span id="popupProductNameText"></span>
        </div>
        <a href="login_form.php" class="btn-popup-login">
          <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập ngay
        </a>
        <button class="btn-popup-cancel" onclick="closeLoginPopup()">Để sau</button>
      </div>
    </div>
  </div>

  <script>
    const isLoggedIn = <?= isset($_SESSION['user']) ? 'true' : 'false' ?>;

    const products = <?= $productsJson ?>;
    const productMap = {};
    products.forEach(p => {
      productMap[p.id] = p;
    });

    const typeIconMap = {
      apple: 'fa-brands fa-apple',
      samsung: 'fa-solid fa-mobile-screen',
      laptop: 'fa-solid fa-laptop',
      gaming: 'fa-solid fa-gamepad',
      audio: 'fa-solid fa-headphones',
      smart: 'fa-solid fa-clock',
      phone: 'fa-solid fa-mobile-screen',
      tablet: 'fa-solid fa-tablet-screen-button',
    };

    function getTypeIcon(typeCode) {
      const t = (typeCode || '').toLowerCase();
      for (const [k, v] of Object.entries(typeIconMap)) {
        if (t.includes(k)) return v;
      }
      return 'fa-solid fa-box';
    }

    function formatPrice(price) {
      return Number(price).toLocaleString('vi-VN') + '₫';
    }

    function escHtml(str) {
      return String(str)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;')
        .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function openModal(id) {
      const p = productMap[id];
      if (!p) return;

      const imgSide = document.getElementById('modalImgSide');
      imgSide.innerHTML = p.image ?
        `<img src="images/${escHtml(p.image)}" alt="${escHtml(p.name)}"
               onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
           <i class="fa-solid fa-image icon-fallback" style="display:none;font-size:6rem;"></i>` :
        `<i class="fa-solid fa-box icon-fallback" style="font-size:6rem;"></i>`;

      const typeName = p.type_name || p.id_type;
      document.getElementById('modalType').innerHTML =
        `<i class="${getTypeIcon(p.id_type)}"></i> ${escHtml(typeName)}`;

      document.getElementById('modalName').textContent = p.name;
      document.getElementById('modalPrice').innerHTML = formatPrice(p.price);
      document.getElementById('modalDesc').textContent = p.description;

      document.getElementById('modalMeta').innerHTML = `
        <div class="modal-meta-row">
          <i class="fa-solid fa-tag"></i>
          Danh mục: <span>${escHtml(typeName)}</span>
        </div>
        <div class="modal-meta-row">
          <i class="fa-solid fa-hashtag"></i>
          Mã sản phẩm: <span>#${escHtml(String(p.id))}</span>
        </div>
        <div class="modal-meta-row">
          <i class="fa-solid fa-circle-check" style="color:#10b981"></i>
          Tình trạng: <span style="color:#10b981">Còn hàng</span>
        </div>
      `;

      document.getElementById('modalOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
      document.getElementById('modalBtnCart').dataset.name = p.name;
    }

    function closeModal() {
      document.getElementById('modalOverlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    function handleOverlayClick(e) {
      if (e.target === document.getElementById('modalOverlay')) closeModal();
    }

    function requireLogin(productName) {
      if (isLoggedIn) return true;
      document.getElementById('popupProductNameText').textContent = productName;
      document.getElementById('loginPopupOverlay').classList.add('open');
      return false;
    }

    function closeLoginPopup() {
      document.getElementById('loginPopupOverlay').classList.remove('open');
    }

    function handlePopupOverlay(e) {
      if (e.target === document.getElementById('loginPopupOverlay')) closeLoginPopup();
    }

    function handleModalCart() {
      const name = document.getElementById('modalBtnCart').dataset.name || '';
      requireLogin(name);
    }

    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        closeModal();
        closeLoginPopup();
      }
    });


    // ── CART & WISHLIST ──────────────────────────────────
    function updateCartBadge(count) {
      const badge = document.getElementById('cartBadge');
      if (!badge) return;
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }

    function updateWishlistBadge(count) {
      const badge = document.getElementById('wishlistBadge');
      if (!badge) return;
      badge.textContent = count;
      badge.style.display = count > 0 ? 'flex' : 'none';
    }

    // Toast thông báo
    function showToast(msg, type = 'success') {
      let t = document.getElementById('toast');
      if (!t) {
        t = document.createElement('div');
        t.id = 'toast';
        t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#111827;color:white;padding:12px 24px;border-radius:50px;font-size:.9rem;font-weight:600;z-index:9999;transition:transform .3s;display:flex;align-items:center;gap:8px;box-shadow:0 8px 24px rgba(0,0,0,.2);';
        document.body.appendChild(t);
      }
      t.innerHTML = (type === 'success' ? '<i class="fa-solid fa-check" style="color:#10b981"></i> ' : '<i class="fa-solid fa-xmark" style="color:#ef4444"></i> ') + msg;
      t.style.transform = 'translateX(-50%) translateY(0)';
      clearTimeout(t._timer);
      t._timer = setTimeout(() => {
        t.style.transform = 'translateX(-50%) translateY(80px)';
      }, 2500);
    }

    function addToCart(productId, productName) {
      if (!isLoggedIn) {
        requireLogin(productName);
        return;
      }
      fetch('cart_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=add&product_id=${productId}&qty=1`
        })
        .then(r => r.json())
        .then(d => {
          if (d.ok) {
            updateCartBadge(d.count);
            showToast('Đã thêm vào giỏ hàng!');
          }
        });
    }

    function buyNow(productId, productName) {
      if (!isLoggedIn) {
        requireLogin(productName);
        return;
      }
      fetch('cart_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=add&product_id=${productId}&qty=1`
        })
        .then(r => r.json())
        .then(d => {
          if (d.ok) window.location.href = 'cart.php';
        });
    }

    function toggleWishlist(productId, btn) {
      if (!isLoggedIn) {
        requireLogin('');
        return;
      }
      fetch('wishlist_action.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: `action=toggle&product_id=${productId}`
        })
        .then(r => r.json())
        .then(d => {
          if (d.ok) {
            const isAdded = d.result === 'added';
            btn.classList.toggle('active', isAdded);
            btn.querySelector('i').className = (isAdded ? 'fa-solid' : 'fa-regular') + ' fa-heart';
            updateWishlistBadge(d.count);
            showToast(isAdded ? 'Đã thêm vào yêu thích!' : 'Đã xóa khỏi yêu thích');
          }
        });
    }
    // ─────────────────────────────────────────────────────

    // ── BANNER SLIDESHOW ──────────────────────────────────
    (function() {
      const DURATION = 10000;
      let current = 0;
      const total = document.querySelectorAll('.banner-slide').length;
      let timer = null;

      const track = document.getElementById('bannerTrack');
      const dots = document.querySelectorAll('.banner-dot');
      const progress = document.getElementById('bannerProgress');

      function goTo(idx) {
        current = (idx + total) % total;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
        dots.forEach(function(d, i) {
          d.classList.toggle('active', i === current);
        });
        startProgress();
        clearTimeout(timer);
        timer = setTimeout(function() {
          goTo(current + 1);
        }, DURATION);
      }

      function startProgress() {
        progress.style.transition = 'none';
        progress.style.width = '0%';
        void progress.offsetWidth;
        progress.style.transition = 'width ' + DURATION + 'ms linear';
        progress.style.width = '100%';
      }

      function stopProgress() {
        var w = getComputedStyle(progress).width;
        progress.style.transition = 'none';
        progress.style.width = w;
      }

      window.bannerNext = function() {
        goTo(current + 1);
      };
      window.bannerPrev = function() {
        goTo(current - 1);
      };
      window.bannerGoTo = function(i) {
        goTo(i);
      };

      var slider = document.getElementById('bannerSlider');
      slider.addEventListener('mouseenter', function() {
        clearTimeout(timer);
        stopProgress();
      });
      slider.addEventListener('mouseleave', function() {
        startProgress();
        timer = setTimeout(function() {
          goTo(current + 1);
        }, DURATION);
      });

      goTo(0);
    })();
    // ─────────────────────────────────────────────────────
  </script>
</body>

</html>