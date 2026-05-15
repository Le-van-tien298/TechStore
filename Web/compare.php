<?php
session_start();
require_once __DIR__ . '/Database.php';
$db = Database::getInstance();
$pdo = $db->getConnection();

$idsRaw = trim($_GET['ids'] ?? '');
$ids = [];
if ($idsRaw !== '') {
    $parsed = array_filter(array_map('intval', explode(',', $idsRaw)), fn($v) => $v > 0);
    $ids = array_values(array_unique($parsed));
}
$products = [];
if (!empty($ids)) {
    $products = $db->getProductsByIds($ids);
}

function formatPrice(float $price): string
{
    return number_format($price, 0, ',', '.') . '₫';
}
function hasFlashSale(array $product): bool
{
    return !empty($product['flash_sale_active'])
        && isset($product['flash_sale_price'])
        && (float) $product['flash_sale_price'] > 0
        && (float) $product['flash_sale_price'] < (float) $product['price'];
}
function getDiscountPercent(array $product): int
{
    if (!hasFlashSale($product)) {
        return 0;
    }
    return (int) round((1 - (float) $product['flash_sale_price'] / (float) $product['price']) * 100);
}
function getPriceHtml(array $product): string
{
    $effectivePrice = hasFlashSale($product) ? (float) $product['flash_sale_price'] : (float) $product['price'];
    $price = formatPrice($effectivePrice);
    if (hasFlashSale($product)) {
        $oldPrice = formatPrice((float) $product['price']);
        $discount = getDiscountPercent($product);
        return '<div class="price-sale"><span class="price-old">' . $oldPrice . '</span><span class="price">' . $price . '</span></div>'
            . '<div class="sale-badge">Flash Sale -' . $discount . '%</div>';
    }
    return '<div class="price">' . $price . '</div>';
}
function typeIcon(string $typeCode): string
{
    $map = [
        'apple' => 'fa-brands fa-apple',
        'samsung' => 'fa-solid fa-mobile-screen',
        'laptop' => 'fa-solid fa-laptop',
        'gaming' => 'fa-solid fa-gamepad',
        'audio' => 'fa-solid fa-headphones',
        'smart' => 'fa-solid fa-clock',
        'phone' => 'fa-solid fa-mobile-screen',
        'tablet' => 'fa-solid fa-tablet-screen-button',
    ];
    $typeCode = strtolower($typeCode);
    foreach ($map as $k => $v) {
        if (stripos($typeCode, $k) !== false) {
            return $v;
        }
    }
    return 'fa-solid fa-box';
}

$compareCount = count($products);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>So sánh sản phẩm</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="public/css/index.css?v=1.1">
    <style>
        body {
            background: #f8fafc;
        }

        nav {
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .compare-page {
            max-width: 1140px;
            margin: 30px auto 60px;
            padding: 0 24px;
        }

        .compare-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .compare-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .compare-title i {
            color: #2563eb;
            font-size: 1.35rem;
        }

        .compare-meta {
            color: #475569;
        }

        .compare-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .compare-actions .btn-compare-action,
        .compare-card .btn-compare-action,
        .compare-card .btn-clear-compare {
            min-width: 140px;
            padding: 10px 18px;
            border-radius: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background .2s, transform .15s, box-shadow .2s;
        }

        .compare-actions .btn-compare-action,
        .compare-card .btn-compare-action {
            background: #2563eb;
            color: white;
            border: none;
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.16);
        }

        .compare-actions .btn-compare-action:hover,
        .compare-card .btn-compare-action:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .compare-card .btn-clear-compare,
        .compare-actions .btn-clear-compare {
            background: #f8fafc;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .compare-card .btn-clear-compare:hover,
        .compare-actions .btn-clear-compare:hover {
            background: #e2e8f0;
            transform: translateY(-1px);
        }

        .compare-board {
            display: grid;
            gap: 18px;
        }

        @media(min-width: 900px) {
            .compare-board {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
        }

        .compare-card {
            background: white;
            border: 1.5px solid var(--border);
            border-radius: var(--r);
            padding: 18px;
            box-shadow: var(--shadow);
        }

        .compare-card img {
            width: 100%;
            height: 220px;
            object-fit: contain;
            border-radius: 14px;
            background: #f8fafc;
        }

        .compare-card h3 {
            font-size: 1.05rem;
            margin: 12px 0 6px;
        }

        .compare-card .price {
            color: #2563eb;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .compare-card .type {
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: .95rem;
        }

        .compare-card .desc {
            color: #475569;
            font-size: .95rem;
            line-height: 1.6;
            min-height: 84px;
            margin-bottom: 14px;
        }

        .compare-card .compare-actions-card {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .compare-card .btn-clear-compare {
            min-width: auto;
        }

        .compare-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 26px;
        }

        .compare-table th,
        .compare-table td {
            border: 1px solid #e2e8f0;
            padding: 14px 12px;
            text-align: left;
        }

        .compare-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
        }

        .compare-table td {
            background: white;
        }

        .compare-table td span {
            display: block;
            color: #334155;
        }

        .empty-state {
            text-align: center;
            padding: 60px 24px;
            background: white;
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .empty-state h3 {
            margin: 18px 0 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 18px;
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
            <div class="nav-icons" style="margin-left:auto; gap:18px;">
                <a class="btn-compare-action" href="index.php"><i class="fa-solid fa-arrow-left"></i> Quay lại</a>
            </div>
        </div>
    </nav>

    <main class="compare-page">
        <div class="compare-header">
            <div class="compare-title">
                <i class="fa-solid fa-scale-balanced"></i>
                <div>
                    <h1>So sánh sản phẩm</h1>
                    <div class="compare-meta"><?= $compareCount ?> sản phẩm được chọn</div>
                </div>
            </div>
            <div class="compare-actions">
                <button class="btn-compare-action" type="button" onclick="clearCompareSelection()">Xóa toàn bộ</button>
                <button class="btn-compare-action" type="button" onclick="window.location.href='index.php'">Tiếp tục
                    chọn</button>
            </div>
        </div>

        <?php if ($compareCount < 2): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-scale-balanced" style="font-size:2.5rem;color:#2563eb"></i>
                </div>
                <h3>Chọn ít nhất 2 sản phẩm để so sánh</h3>
                <p>Hãy quay lại trang sản phẩm và nhấn "So sánh" cho các mặt hàng bạn muốn so sánh.</p>
                <a class="btn-compare-action" href="index.php">Quay về trang sản phẩm</a>
            </div>
        <?php else: ?>
            <div class="compare-board">
                <?php foreach ($products as $product): ?>
                    <div class="compare-card" id="compare-card-<?= $product['id'] ?>">
                        <?php if (!empty($product['image'])): ?>
                            <img src="images/<?= htmlspecialchars($product['image']) ?>"
                                alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.style.display='none'">
                        <?php else: ?>
                            <div
                                style="height:220px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;border-radius:14px;">
                                <i class="fa-solid fa-box" style="font-size:3rem;color:#94a3b8"></i>
                            </div>
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($product['name']) ?></h3>
                        <?= getPriceHtml($product) ?>
                        <div class="type"><i class="<?= typeIcon($product['id_type'] ?? $product['type'] ?? '') ?>"></i>
                            <?= htmlspecialchars($product['type_name'] ?? $product['id_type']) ?></div>
                        <div class="desc"><?= nl2br(htmlspecialchars($product['description'])) ?></div>
                        <div class="compare-actions-card">
                            <button class="btn-compare-action" type="button"
                                onclick="addToCart(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['name']), ENT_QUOTES) ?>')"><i
                                    class="fa-solid fa-cart-plus"></i> Thêm giỏ</button>
                            <button class="btn-clear-compare" type="button" onclick="removeCompare(<?= $product['id'] ?>)"><i
                                    class="fa-solid fa-trash"></i> Xóa</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <table class="compare-table">
                <tr>
                    <th>Thuộc tính</th>
                    <?php foreach ($products as $product): ?>
                        <th><?= htmlspecialchars($product['name']) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Giá</th>
                    <?php foreach ($products as $product): ?>
                        <td><?= getPriceHtml($product) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Flash Sale</th>
                    <?php foreach ($products as $product): ?>
                        <td><?= hasFlashSale($product) ? 'Có (-' . getDiscountPercent($product) . '%)' : 'Không' ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Loại</th>
                    <?php foreach ($products as $product): ?>
                        <td><?= htmlspecialchars($product['type_name'] ?? $product['id_type']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Mã sản phẩm</th>
                    <?php foreach ($products as $product): ?>
                        <td>#<?= htmlspecialchars($product['id']) ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <th>Mô tả</th>
                    <?php foreach ($products as $product): ?>
                        <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                    <?php endforeach; ?>
                </tr>
            </table>
        <?php endif; ?>
    </main>

    <script>
        const compareStorageKey = 'compareProducts';
        function loadCompareSelection() {
            try {
                const stored = JSON.parse(localStorage.getItem(compareStorageKey) || '[]');
                return Array.isArray(stored) ? stored.map(Number).filter(n => n > 0) : [];
            } catch {
                return [];
            }
        }
        function saveCompareSelection(ids) {
            localStorage.setItem(compareStorageKey, JSON.stringify(ids));
        }
        function removeCompare(productId) {
            const ids = loadCompareSelection().filter(id => id !== Number(productId));
            saveCompareSelection(ids);
            window.location.href = ids.length > 1 ? 'compare.php?ids=' + ids.join(',') : 'index.php';
        }
        function clearCompareSelection() {
            localStorage.removeItem(compareStorageKey);
            window.location.href = 'index.php';
        }
        function addToCart(productId, productName) {
            fetch('cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=add&product_id=${productId}&qty=1`
            }).then(r => r.json()).then(d => {
                if (d.ok) {
                    location.href = 'cart.php';
            }
     });
        }
    </script>
</body>

</html>