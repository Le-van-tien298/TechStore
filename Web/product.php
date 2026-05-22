<?php
session_start();
require_once __DIR__ . '/Database.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

$productId = max(0, (int) ($_GET['id'] ?? 0));
if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$product = $db->getProductById($productId);
if (!$product) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['user'] ?? null;
$cartCount = $username ? $db->getCartCount($username) : 0;
$wishlistCount = $username ? $db->getWishlistCount($username) : 0;
$inWishlist = $username ? $db->inWishlist($username, $productId) : false;
$reviews = $db->getProductReviews($productId);
$userReview = null;
if ($username) {
    foreach ($reviews as $review) {
        if ($review['username'] === $username) {
            $userReview = $review;
            break;
        }
    }
}

function formatPrice(float $price): string
{
    return number_format($price, 0, ',', '.') . '&#8363;';
}

function typeIcon(string $typeCode): string
{
    $map = [
        'apple' => 'fa-solid fa-mobile-screen-button',
        'samsung' => 'fa-solid fa-mobile-screen',
        'laptop' => 'fa-solid fa-laptop',
        'gaming' => 'fa-solid fa-gamepad',
        'audio' => 'fa-solid fa-headphones',
        'smart' => 'fa-solid fa-clock',
        'phone' => 'fa-solid fa-mobile-screen',
        'tablet' => 'fa-solid fa-tablet-screen-button',
        'xiaomi' => 'fa-solid fa-mobile-screen',
        'redmi' => 'fa-solid fa-mobile-screen',
    ];
    foreach ($map as $k => $v) {
        if (stripos($typeCode, $k) !== false)
            return $v;
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

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$reviewsJson = json_encode($reviews, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechStore - <?= escape($product['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/index.css?v=1.1">
    <style>
        .detail-page {
            max-width: 1100px;
            margin: 38px auto 60px;
            padding: 0 24px;
        }

        .detail-breadcrumb {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: var(--muted);
            font-size: .92rem;
            margin-bottom: 18px;
        }

        .detail-breadcrumb a {
            color: var(--muted);
            text-decoration: none;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 28px;
        }

        .detail-image {
            background: #f8fafc;
            border-radius: 24px;
            padding: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 520px;
        }

        .detail-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 20px;
        }

        .detail-summary {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .detail-type {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #eff6ff;
            color: var(--blue);
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 999px;
            width: fit-content;
        }

        .detail-name {
            font-size: 2rem;
            font-weight: 800;
            line-height: 1.15;
            color: var(--text);
        }

        .detail-rating {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }

        .detail-rating .rating-stars {
            display: inline-flex;
            gap: 4px;
            color: #f59e0b;
            font-size: 1rem;
        }

        .detail-rating .rating-stars i {
            color: #f59e0b;
        }

        .detail-rating .review-count {
            color: var(--muted);
            font-weight: 700;
        }

        .detail-price {
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--blue);
        }

        .detail-price-old {
            font-size: 1.05rem;
            color: #64748b;
            text-decoration: line-through;
            margin-right: 14px;
        }

        .detail-desc {
            font-size: .96rem;
            color: #334155;
            line-height: 1.8;
        }

        .detail-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 10px;
        }

        .detail-meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            border-radius: 14px;
            padding: 14px 16px;
            color: var(--muted);
        }

        .detail-meta-row i {
            color: var(--blue);
            width: 18px;
            text-align: center;
        }

        .detail-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
        }

        .detail-actions button {
            min-width: 160px;
        }

        .detail-actions .btn-add-cart {
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: white;
        }

        .detail-actions .btn-add-cart:hover {
            background: linear-gradient(135deg, #1d4ed8, #2563eb);
        }

        .detail-actions .btn-wish-page {
            background: #fff5f5;
            color: var(--red);
            border: 1.5px solid #fecaca;
        }

        .detail-actions .btn-wish-page.active {
            background: #ef4444;
            color: white;
            border-color: #ef4444;
        }

        .detail-actions .btn-wish-page i {
            margin-right: 8px;
        }

        .product-reviews {
            margin-top: 42px;
            display: grid;
            gap: 28px;
        }

        .reviews-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }

        .reviews-title {
            font-size: 1.2rem;
            font-weight: 800;
        }

        .review-summary-large {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 18px;
            background: #ffffff;
            border: 1.5px solid var(--border);
            border-radius: 24px;
            padding: 24px;
            align-items: center;
        }

        .review-score-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #eff6ff;
            border-radius: 20px;
            padding: 18px 22px;
        }

        .review-score-box strong {
            font-size: 2.5rem;
            color: var(--blue);
        }

        .review-score-box span {
            color: var(--muted);
            margin-top: 6px;
        }

        .review-list {
            display: grid;
            gap: 14px;
        }

        .review-card {
            background: #f8fafc;
            border: 1.5px solid var(--border);
            border-radius: var(--r-sm);
            padding: 18px;
        }

        .review-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }

        .review-user {
            font-weight: 700;
            color: var(--text);
        }

        .review-date {
            color: var(--muted);
            font-size: .86rem;
        }

        .review-stars-large {
            color: #f59e0b;
            font-size: 1rem;
            display: inline-flex;
            gap: 4px;
        }

        .review-comment {
            margin-top: 12px;
            color: #334155;
            line-height: 1.75;
            white-space: pre-line;
        }

        .review-box {
            background: white;
            border: 1.5px solid var(--border);
            border-radius: var(--r-sm);
            padding: 20px;
            display: grid;
            gap: 14px;
        }

        .review-box h3 {
            font-size: 1rem;
            font-weight: 800;
        }

        .review-form {
            display: grid;
            gap: 12px;
        }

        .review-stars {
            display: inline-flex;
            gap: 6px;
        }

        .review-stars button {
            background: transparent;
            border: none;
            cursor: pointer;
            color: #cbd5e1;
            font-size: 1.35rem;
            transition: color .2s, transform .15s;
        }

        .review-stars button.active,
        .review-stars button:hover {
            color: #f59e0b;
            transform: scale(1.05);
        }

        .review-stars button:focus {
            outline: none;
        }

        .review-textarea {
            width: 100%;
            min-height: 130px;
            resize: vertical;
            border: 1.5px solid var(--border);
            border-radius: var(--r-sm);
            padding: 14px;
            font-family: 'Inter', sans-serif;
            font-size: .95rem;
            color: var(--text);
            background: #f8fafc;
        }

        .btn-submit-review {
            align-self: flex-start;
            padding: 12px 22px;
            border: none;
            border-radius: 14px;
            background: #10b981;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .15s;
        }

        .btn-submit-review:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .review-empty {
            padding: 20px;
            border: 1.5px dashed var(--border);
            border-radius: var(--r-sm);
            background: #ffffff;
            color: var(--muted);
        }

        @media(max-width:900px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .review-summary-large {
                grid-template-columns: 1fr;
            }

            .detail-price {
                font-size: 1.75rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
            <div class="nav-icons">
                <a class="nav-icon" href="wishlist.php" title="Yêu thích" style="text-decoration:none;color:inherit">
                    <i class="fa-regular fa-heart"></i>
                    <div class="badge" id="wishlistBadge" style="<?= $wishlistCount > 0 ? '' : 'display:none' ?>">
                        <?= $wishlistCount ?>
                    </div>
                </a>
                <a class="nav-icon" href="cart.php" style="text-decoration:none;color:inherit">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <div class="badge" id="cartBadge" style="<?= $cartCount > 0 ? '' : 'display:none' ?>">
                        <?= $cartCount ?>
                    </div>
                </a>
                <div class="nav-icon user-dropdown-wrap" onclick="this.classList.toggle('open')">
                    <i class="fa-regular fa-user"></i>
                    <div class="user-dropdown">
                        <div class="user-dropdown-header">
                            <div class="greeting">Xin chào,</div>
                            <div class="uname"><?= escape($_SESSION['name'] ?? $_SESSION['user'] ?? 'Khách') ?></div>
                        </div>
                        <?php if (isset($_SESSION['role']) && strtolower($_SESSION['role']) === 'admin'): ?>
                            <a href="admin_page.php" class="user-dropdown-item admin"><i
                                    class="fa-solid fa-screwdriver-wrench"></i> Quản lí</a>
                        <?php endif; ?>
                        <?php if ($username): ?>
                            <a href="orders.php" class="user-dropdown-item"><i class="fa-solid fa-shopping-bag"></i> Đơn
                                hàng của tôi</a>
                            <a href="logout.php" class="user-dropdown-item logout"><i
                                    class="fa-solid fa-right-from-bracket"></i> Đăng xuất</a>
                        <?php else: ?>
                            <a href="login_form.php?redirect=product.php?id=<?= $productId ?>" class="user-dropdown-item"><i
                                    class="fa-solid fa-right-to-bracket"></i> Đăng nhập</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="detail-page">
        <div class="detail-breadcrumb">
            <a href="index.php">Trang chủ</a>
            <span>›</span>
            <a
                href="index.php?type=<?= urlencode($product['id_type']) ?>"><?= escape($product['type_name'] ?? $product['id_type']) ?></a>
            <span>›</span>
            <span><?= escape($product['name']) ?></span>
        </div>

        <div class="detail-grid">
            <div class="detail-image">
                <?php if (!empty($product['image'])): ?>
                    <img src="images/<?= escape($product['image']) ?>" alt="<?= escape($product['name']) ?>"
                        onerror="this.style.display='none';">
                <?php else: ?>
                    <i class="fa-solid fa-box icon-fallback" style="font-size:5rem;color:#cbd5e1;"></i>
                <?php endif; ?>
            </div>
            <div class="detail-summary">
                <div class="detail-type"><i class="<?= escape(typeIcon($product['id_type'])) ?>"></i>
                    <?= escape($product['type_name'] ?? $product['id_type']) ?></div>
                <h1 class="detail-name"><?= escape($product['name']) ?></h1>

                <div class="detail-rating">
                    <div class="rating-stars"><?= renderStarRating((float) ($product['avg_rating'] ?? 0)) ?></div>
                    <div class="review-count">
                        <strong><?= number_format((float) ($product['avg_rating'] ?? 0), 1) ?></strong> / 5 ·
                        <?= (int) ($product['review_count'] ?? 0) ?> đánh giá
                    </div>
                </div>

                <div>
                    <?php if (!empty($product['flash_sale_active']) && (float) $product['flash_sale_price'] > 0 && (float) $product['flash_sale_price'] < (float) $product['price']): ?>
                        <div class="detail-price"><span
                                class="detail-price-old"><?= formatPrice((float) $product['price']) ?></span>
                            <?= formatPrice((float) $product['flash_sale_price']) ?></div>
                    <?php else: ?>
                        <div class="detail-price"><?= formatPrice((float) $product['price']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="detail-desc"><?= nl2br(escape($product['description'])) ?></div>

                <div class="detail-meta">
                    <div class="detail-meta-row"><i class="fa-solid fa-hashtag"></i> Mã sản phẩm:
                        <span><?= escape((string) $product['id']) ?></span>
                    </div>
                    <div class="detail-meta-row"><i class="fa-solid fa-circle-check"></i> Tình trạng: <span
                            style="color:#10b981; font-weight:700;">Còn hàng</span></div>
                </div>

                <div class="detail-actions">
                    <button class="btn-add-cart" type="button"
                        onclick="addToCart(<?= $productId ?>, '<?= escape(addslashes($product['name'])) ?>')"><i
                            class="fa-solid fa-cart-plus"></i> Thêm vào giỏ hàng</button>
                    <button id="wishlistActionBtn" class="btn-wish-page <?= $inWishlist ? 'active' : '' ?>"
                        type="button" onclick="toggleWishlist(<?= $productId ?>)">
                        <i class="fa-solid fa-heart"></i> <span
                            id="wishlistActionText"><?= $inWishlist ? 'Đã yêu thích' : 'Thêm vào yêu thích' ?></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="product-reviews">
            <div class="reviews-header">
                <div class="reviews-title">Đánh giá sản phẩm</div>
            </div>

            <div class="review-summary-large">
                <div class="review-score-box">
                    <strong><?= number_format((float) ($product['avg_rating'] ?? 0), 1) ?></strong>
                    <span>Trung bình trên <?= (int) ($product['review_count'] ?? 0) ?> đánh giá</span>
                </div>
                <div>
                    <div class="rating-stars" style="font-size:1.25rem;">
                        <?= renderStarRating((float) ($product['avg_rating'] ?? 0)) ?>
                    </div>
                    <p style="margin-top:12px;color:#475569;">Xem nhận xét chi tiết bên dưới hoặc gửi đánh giá của bạn
                        để giúp người khác lựa chọn.</p>
                </div>
            </div>

            <?php if ($username): ?>
                <div class="review-box">
                    <h3><?= $userReview ? 'Cập nhật đánh giá của bạn' : 'Viết đánh giá của bạn' ?></h3>
                    <div class="review-form">
                        <div class="review-stars" id="reviewStars"></div>
                        <textarea id="reviewComment" class="review-textarea" placeholder="Viết nhận xét của bạn..."
                            rows="5"><?= escape($userReview['comment'] ?? '') ?></textarea>
                        <button class="btn-submit-review" type="button" onclick="submitReview()">Gửi đánh giá</button>
                    </div>
                </div>
            <?php else: ?>
                <div class="review-box">
                    <h3>Đăng nhập để đánh giá</h3>
                    <p>Vui lòng đăng nhập để gửi đánh giá và bình luận về sản phẩm.</p>
                    <a href="login_form.php?redirect=product.php?id=<?= $productId ?>" class="btn-blue"><i
                            class="fa-solid fa-right-to-bracket"></i> Đăng nhập</a>
                </div>
            <?php endif; ?>

            <div class="review-list" id="reviewList">
                <?php if (empty($reviews)): ?>
                    <div class="review-empty">Chưa có đánh giá nào. Hãy là người đầu tiên nhận xét sản phẩm này!</div>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-card-header">
                                <div class="review-user"><?= escape($review['user_name'] ?? 'Khách') ?></div>
                                <div class="review-date"><?= escape($review['created_at']) ?></div>
                            </div>
                            <div class="review-stars-large">
                                <?= str_replace('fa-regular', 'fa-solid', renderStarRating((float) $review['rating'])) ?>
                            </div>
                            <div class="review-comment"><?= nl2br(escape($review['comment'] ?: 'Không có nội dung.')) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
        <p>&copy; 2026 TechStore. All rights reserved.</p>
        <div class="foot-links">
            <a href="#"><i class="fa-solid fa-shield-halved"></i> Bảo mật</a>
            <a href="#"><i class="fa-solid fa-file-lines"></i> Điều khoản</a>
            <a href="#"><i class="fa-solid fa-headset"></i> Hỗ trợ</a>
        </div>
    </footer>

    <script>
        const isLoggedIn = <?= $username ? 'true' : 'false' ?>;
        const productId = <?= $productId ?>;
        let currentReviewRating = <?= $userReview ? (int) $userReview['rating'] : 0 ?>;

        function escHtml(str) {
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
        function formatPrice(price) { return Number(price).toLocaleString('vi-VN') + '₫'; }

        function renderStarButtons(selected = 0) {
            const container = document.getElementById('reviewStars');
            if (!container) return;
            container.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerHTML = '<i class="fa-solid fa-star"></i>';
                btn.className = i <= selected ? 'active' : '';
                btn.title = i + ' sao';
                btn.addEventListener('click', () => setReviewRating(i));
                container.appendChild(btn);
            }
        }
        function setReviewRating(value) {
            currentReviewRating = value;
            renderStarButtons(value);
        }

        function showToast(message, type = 'success') {
            let toast = document.getElementById('toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'toast';
                toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(80px);background:#111827;color:white;padding:14px 22px;border-radius:50px;font-size:.95rem;z-index:9999;transition:transform .3s,opacity .3s;display:flex;align-items:center;gap:10px;box-shadow:0 12px 28px rgba(0,0,0,.22);';
                document.body.appendChild(toast);
            }
            toast.innerHTML = (type === 'success' ? '<i class="fa-solid fa-check" style="color:#10b981"></i>' : '<i class="fa-solid fa-xmark" style="color:#ef4444"></i>') + ' ' + message;
            toast.style.transform = 'translateX(-50%) translateY(0)';
            toast.style.opacity = '1';
            clearTimeout(toast._timer);
            toast._timer = setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(80px)'; toast.style.opacity = '0'; }, 2800);
        }

        function addToCart(productId, name) {
            if (!isLoggedIn) {
                window.location.href = 'login_form.php?redirect=product.php?id=' + productId;
                return;
            }
            fetch('cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&product_id=${productId}&qty=1`
            })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        const badge = document.getElementById('cartBadge');
                        if (badge) { badge.textContent = data.count; badge.style.display = data.count > 0 ? 'flex' : 'none'; }
                        showToast('Đã thêm vào giỏ hàng.', 'success');
                    } else {
                        showToast(data.msg || 'Không thêm được vào giỏ.', 'error');
                    }
                })
                .catch(() => showToast('Lỗi kết nối. Vui lòng thử lại.', 'error'));
        }

        function toggleWishlist(productId) {
            if (!isLoggedIn) {
                window.location.href = 'login_form.php?redirect=product.php?id=' + productId;
                return;
            }
            fetch('wishlist_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=toggle&product_id=${productId}`
            })
                .then(r => r.json())
                .then(data => {
                    if (!data.ok) {
                        showToast(data.msg || 'Không cập nhật wishlist.', 'error');
                        return;
                    }
                    const btn = document.getElementById('wishlistActionBtn');
                    const text = document.getElementById('wishlistActionText');
                    if (btn && text) {
                        const added = data.result === 'added';
                        btn.classList.toggle('active', added);
                        text.textContent = added ? 'Đã yêu thích' : 'Thêm vào yêu thích';
                    }
                    const badge = document.getElementById('wishlistBadge');
                    if (badge) { badge.textContent = data.count; badge.style.display = data.count > 0 ? 'flex' : 'none'; }
                    showToast(data.result === 'added' ? 'Đã thêm vào wishlist.' : 'Đã xóa khỏi wishlist.', 'success');
                })
                .catch(() => showToast('Lỗi kết nối. Vui lòng thử lại.', 'error'));
        }

        function renderReviewList(reviews) {
            const list = document.getElementById('reviewList');
            if (!list) return;
            if (!Array.isArray(reviews) || reviews.length === 0) {
                list.innerHTML = '<div class="review-empty">Chưa có đánh giá nào. Hãy là người đầu tiên nhận xét sản phẩm này!</div>';
                return;
            }
            list.innerHTML = reviews.map(review => {
                const comment = escHtml(review.comment || 'Không có nội dung.');
                const created = escHtml(review.created_at || '');
                const user = escHtml(review.user_name || 'Người dùng');
                const stars = renderRatingStars(Number(review.rating || 0));
                return `
          <div class="review-card">
            <div class="review-card-header">
              <div class="review-user">${user}</div>
              <div class="review-date">${created}</div>
            </div>
            <div class="review-stars-large">${stars}</div>
            <div class="review-comment">${comment}</div>
          </div>
        `;
            }).join('');
        }

        function loadReviews() {
            fetch(`reviews_action.php?action=list&product_id=${productId}`)
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        renderReviewList(data.reviews || []);
                    }
                });
        }

        function renderRatingStars(rating) {
            const full = Math.floor(rating);
            const half = rating - full >= 0.5;
            const parts = [];
            for (let i = 0; i < full; i++) parts.push('<i class="fa-solid fa-star"></i>');
            if (half) parts.push('<i class="fa-solid fa-star-half-stroke"></i>');
            while (parts.length < 5) parts.push('<i class="fa-regular fa-star"></i>');
            return parts.join('');
        }

        function submitReview() {
            if (currentReviewRating <= 0) {
                showToast('Vui lòng chọn số sao.', 'error');
                return;
            }
            const comment = document.getElementById('reviewComment')?.value.trim() || '';
            const btn = document.querySelector('.btn-submit-review');
            btn.disabled = true;
            fetch('reviews_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=submit&product_id=${productId}&rating=${currentReviewRating}&comment=${encodeURIComponent(comment)}`
            })
                .then(r => r.json())
                .then(data => {
                    btn.disabled = false;
                    if (data.ok) {
                        showToast('Cám ơn bạn đã đánh giá!', 'success');
                        loadReviews();
                    } else {
                        showToast(data.message || 'Đã có lỗi xảy ra.', 'error');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderStarButtons(currentReviewRating);
        });
    </script>
</body>

</html>