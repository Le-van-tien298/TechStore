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
$baseSelect = "SELECT p.*, t.name AS type_name,
               COALESCE(r.avg_rating, 0) AS avg_rating,
               COALESCE(r.review_count, 0) AS review_count
               FROM p_product p
               LEFT JOIN p_type t ON p.id_type = t.type
               LEFT JOIN (
                 SELECT product_id, ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS review_count
                 FROM p_product_reviews
                 GROUP BY product_id
               ) r ON p.id = r.product_id";

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

function renderStarRating(float $rating): string
{
  $full = floor($rating);
  $half = ($rating - $full) >= 0.5 ? 1 : 0;
  $empty = 5 - $full - $half;
  $output = '';
  for ($i = 0; $i < $full; $i++) {
    $output .= '<i class="fa-solid fa-star"></i>';
  }
  if ($half) {
    $output .= '<i class="fa-solid fa-star-half-stroke"></i>';
  }
  for ($i = 0; $i < $empty; $i++) {
    $output .= '<i class="fa-regular fa-star"></i>';
  }
  return $output;
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
  <link rel="stylesheet" href="public/css/index.css?v=1.1">
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
              <a href="orders.php" class="user-dropdown-item">
                <i class="fa-solid fa-shopping-bag"></i> Đơn hàng của tôi
              </a>
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

    <div class="compare-bar" id="compareBar">
      <div><i class="fa-solid fa-scale-balanced"></i> Đã chọn <span id="compareCount">0</span> sản phẩm để so sánh</div>
      <div class="compare-actions">
        <button class="btn-compare-action" type="button" onclick="openComparePage()">Xem so sánh</button>
        <button class="btn-clear-compare" type="button" onclick="clearCompareSelection()">Xóa chọn</button>
      </div>
    </div>

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
          $hasFlashSale = !empty($p['flash_sale_active']) && (float) $p['flash_sale_price'] > 0 && (float) $p['flash_sale_price'] < (float) $p['price'];
          $salePrice = $hasFlashSale ? (float) $p['flash_sale_price'] : 0;
          $origPrice = (float) $p['price'];
          $discount = $hasFlashSale ? round((($origPrice - $salePrice) / $origPrice) * 100) : 0;
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
              <?php if ($hasFlashSale): ?>
                <div class="sale-badge"><i class="fa-solid fa-fire"></i> Flash Sale -<?= $discount ?>%</div>
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
              <div class="rating-summary">
                <div class="rating-stars"><?= renderStarRating((float) ($p['avg_rating'] ?? 0)) ?></div>
                <span class="review-count"><?= (int) ($p['review_count'] ?? 0) ?> đánh giá</span>
              </div>
              <?php if ($hasFlashSale): ?>
                <div class="price-sale">
                  <span class="price-old"><?= formatPrice($origPrice) ?></span>
                  <span class="price"><?= formatPrice($salePrice) ?></span>
                </div>
              <?php else: ?>
                <div class="price"><?= formatPrice($origPrice) ?></div>
              <?php endif; ?>
              <div class="btn-action-wrap">
                <button class="btn-cart-sm" onclick="event.stopPropagation(); addToCart(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p["name"]), ENT_QUOTES) ?>')"
                  title="Thêm vào giỏ">
                  <i class="fa-solid fa-cart-plus"></i>
                </button>
                <button class="btn-compare" type="button" data-product-id="<?= $p['id'] ?>"
                  onclick="event.stopPropagation(); toggleCompare(<?= $p['id'] ?>, this)">So sánh</button>
                <button class="btn-buynow"
                  onclick="event.stopPropagation(); buyNow(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p["name"]), ENT_QUOTES) ?>')">
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
      if (!id) return;
      window.location.href = 'product.php?id=' + encodeURIComponent(id);
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

    const compareStorageKey = 'compareProducts';
    let compareIds = [];

    function loadCompareSelection() {
      try {
        const stored = JSON.parse(localStorage.getItem(compareStorageKey) || '[]');
        return Array.isArray(stored) ? stored.map(Number).filter(id => id > 0) : [];
      } catch {
        return [];
      }
    }

    function saveCompareSelection() {
      localStorage.setItem(compareStorageKey, JSON.stringify(compareIds));
    }

    function syncCompareButtons() {
      document.querySelectorAll('.btn-compare').forEach(btn => {
        const id = Number(btn.dataset.productId);
        const active = compareIds.includes(id);
        btn.classList.toggle('selected', active);
        btn.textContent = active ? 'Đã chọn' : 'So sánh';
      });
    }

    function updateCompareBar() {
      const bar = document.getElementById('compareBar');
      const count = document.getElementById('compareCount');
      if (!bar || !count) return;
      if (compareIds.length > 0) {
        bar.classList.add('open');
      } else {
        bar.classList.remove('open');
      }
      count.textContent = compareIds.length;
    }

    function toggleCompare(productId, btn) {
      const id = Number(productId);
      if (compareIds.includes(id)) {
        compareIds = compareIds.filter(x => x !== id);
      } else {
        if (compareIds.length >= 3) {
          showToast('Bạn chỉ có thể so sánh tối đa 3 sản phẩm.', 'error');
          return;
        }
        compareIds.push(id);
      }
      saveCompareSelection();
      syncCompareButtons();
      updateCompareBar();
    }

    function openComparePage() {
      if (compareIds.length < 2) {
        showToast('Chọn ít nhất 2 sản phẩm để so sánh.', 'error');
        return;
      }
      window.location.href = 'compare.php?ids=' + compareIds.join(',');
    }

    function clearCompareSelection() {
      compareIds = [];
      saveCompareSelection();
      syncCompareButtons();
      updateCompareBar();
    }

    compareIds = loadCompareSelection();
    syncCompareButtons();
    updateCompareBar();
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