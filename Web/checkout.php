<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$db = Database::getInstance();
$username = $_SESSION['user'];
$cart = $db->getCart($username);

if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$total = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

function formatPrice(float $p): string
{
    return number_format($p, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán – TechStore</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet">
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
            --blue: #2563eb;
            --blue-dark: #1d4ed8;
            --cyan: #06b6d4;
            --red: #ef4444;
            --green: #10b981;
            --navy: #0d1b4b;
            --navy-deep: #080f2d;
            --text: #111827;
            --muted: #6b7280;
            --bg: #f8fafc;
            --white: #fff;
            --border: #e5e7eb;
            --shadow: 0 4px 24px rgba(13, 27, 75, .10);
            --shadow-lg: 0 8px 40px rgba(13, 27, 75, .18);
            --r: 14px;
            --r-sm: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* NAV */
        nav {
            background: var(--white);
            border-bottom: 1.5px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
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
            letter-spacing: -.5px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo i {
            color: var(--cyan);
        }

        .nav-back {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .88rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            margin-left: auto;
            transition: color .2s;
        }

        .nav-back:hover {
            color: var(--blue);
        }

        /* LAYOUT */
        .page {
            max-width: 1100px;
            margin: 32px auto 60px;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 24px;
            align-items: start;
        }

        @media(max-width:900px) {
            .page {
                grid-template-columns: 1fr;
            }
        }

        /* MAIN */
        .main {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            padding: 24px;
        }

        .main h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--navy);
        }

        /* CART ITEMS */
        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid var(--border);
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--r-sm);
        }

        .cart-item-info {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .cart-item-type {
            font-size: .85rem;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .cart-item-price {
            font-weight: 600;
            color: var(--blue);
        }

        .cart-item-qty {
            font-size: .9rem;
            color: var(--muted);
        }

        /* SIDEBAR */
        .sidebar {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            padding: 24px;
            position: sticky;
            top: 100px;
        }

        .sidebar h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--navy);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: .9rem;
        }

        .summary-total {
            border-top: 2px solid var(--border);
            padding-top: 12px;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--navy);
        }

        /* PAYMENT FORM */
        .payment-form {
            margin-top: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            font-size: .9rem;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
        }

        .btn-pay {
            width: 100%;
            background: var(--blue);
            color: var(--white);
            border: none;
            padding: 14px;
            border-radius: var(--r-sm);
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-pay:hover {
            background: var(--blue-dark);
        }

        .btn-pay:disabled {
            background: var(--muted);
            cursor: not-allowed;
        }

        /* ALERT */
        .alert {
            padding: 12px 16px;
            border-radius: var(--r-sm);
            margin-bottom: 16px;
            font-weight: 500;
        }

        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a href="index.php" class="logo"><i class="fas fa-mobile-alt"></i> TechStore</a>
            <a href="cart.php" class="nav-back"><i class="fas fa-arrow-left"></i> Quay lại giỏ hàng</a>
        </div>
    </nav>

    <div class="page">
        <div class="main">
            <h1>Thanh toán</h1>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i> Thanh toán thất bại. Vui lòng thử lại.
                </div>
            <?php endif; ?>

            <div class="cart-items">
                <?php foreach ($cart as $item): ?>
                    <div class="cart-item">
                        <img src="images/<?= htmlspecialchars($item['image']) ?>"
                            alt="<?= htmlspecialchars($item['name']) ?>">
                        <div class="cart-item-info">
                            <div class="cart-item-name"><?= htmlspecialchars($item['name']) ?></div>
                            <div class="cart-item-type"><?= htmlspecialchars($item['type_name']) ?></div>
                            <div class="cart-item-price"><?= formatPrice($item['price']) ?> × <?= $item['quantity'] ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sidebar">
            <h2>Tóm tắt đơn hàng</h2>

            <div class="summary">
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-total">
                    <span>Tổng cộng</span>
                    <span><?= formatPrice($total) ?></span>
                </div>
            </div>

            <form class="payment-form" action="checkout_action.php" method="POST">
                <h3 style="font-size: 1.1rem; font-weight: 700; margin-bottom: 16px; color: var(--navy);">Thông tin giao
                    hàng</h3>

                <div class="form-group">
                    <label for="shipping_address">Địa chỉ giao hàng</label>
                    <textarea id="shipping_address" name="shipping_address"
                        placeholder="Nhập địa chỉ giao hàng đầy đủ..." required rows="3"
                        style="width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: var(--r-sm); font-size: .9rem; resize: vertical;"></textarea>
                </div>

                <h3 style="font-size: 1.1rem; font-weight: 700; margin: 24px 0 16px; color: var(--navy);">Thông tin
                    thanh toán</h3>

                <div class="form-group">
                    <label for="card_number">Số thẻ tín dụng</label>
                    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" required
                        pattern="\d{4} \d{4} \d{4} \d{4}">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="form-group">
                        <label for="expiry">Ngày hết hạn</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required pattern="\d{2}/\d{2}">
                    </div>
                    <div class="form-group">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required pattern="\d{3}">
                    </div>
                </div>

                <div class="form-group">
                    <label for="card_name">Tên trên thẻ</label>
                    <input type="text" id="card_name" name="card_name" placeholder="NGUYEN VAN A" required>
                </div>

                <button type="submit" class="btn-pay">
                    <i class="fas fa-credit-card"></i> Thanh toán <?= formatPrice($total) ?>
                </button>
            </form>
        </div>
    </div>

    <script>
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            e.target.value = value.substring(0, 19);
        });

        // Format expiry input
        document.getElementById('expiry').addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value.substring(0, 5);
        });

        // Format CVV input
        document.getElementById('cvv').addEventListener('input', function (e) {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 3);
        });
    </script>
</body>

</html>