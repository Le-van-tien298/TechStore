<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$db = Database::getInstance();
$user = $db->getUserByUsername($_SESSION['user']);

if ($user['role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

$orders = $db->getAllOrders();

function formatPrice(float $p): string
{
    return number_format($p, 0, ',', '.') . '₫';
}

function getStatusText(string $status): string
{
    return match ($status) {
        'pending' => 'Chờ xử lý',
        'processing' => 'Đang xử lý',
        'shipped' => 'Đang giao hàng',
        'delivered' => 'Đã giao',
        'cancelled' => 'Đã hủy',
        default => 'Không xác định'
    };
}

function getStatusColor(string $status): string
{
    return match ($status) {
        'pending' => '#f59e0b',
        'processing' => '#3b82f6',
        'shipped' => '#8b5cf6',
        'delivered' => '#10b981',
        'cancelled' => '#ef4444',
        default => '#6b7280'
    };
}

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = (int) $_POST['order_id'];
    $status = $_POST['status'];
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if (in_array($status, $validStatuses)) {
        $db->updateOrderStatus($orderId, $status);
        header('Location: admin_orders.php?updated=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng – Admin</title>
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
            max-width: 1200px;
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

        .nav-links {
            display: flex;
            gap: 20px;
            margin-left: auto;
        }

        .nav-link {
            font-size: .9rem;
            font-weight: 600;
            color: var(--muted);
            text-decoration: none;
            padding: 8px 12px;
            border-radius: var(--r-sm);
            transition: all .2s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--blue);
            background: var(--blue)10;
        }

        /* LAYOUT */
        .page {
            max-width: 1200px;
            margin: 32px auto 60px;
            padding: 0 24px;
        }

        /* HEADER */
        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .page-subtitle {
            color: var(--muted);
            font-size: 1rem;
        }

        /* ALERT */
        .alert {
            padding: 12px 16px;
            border-radius: var(--r-sm);
            margin-bottom: 16px;
            font-weight: 500;
        }

        .alert-success {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        /* ORDERS TABLE */
        .orders-table {
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .table-header {
            background: var(--navy);
            color: var(--white);
            padding: 16px 24px;
            font-weight: 600;
        }

        .table-row {
            display: grid;
            grid-template-columns: 100px 1fr 150px 120px 140px 120px;
            gap: 16px;
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: var(--bg);
        }

        .order-id {
            font-weight: 700;
            color: var(--blue);
        }

        .order-customer {
            font-weight: 600;
        }

        .order-total {
            font-weight: 700;
            color: var(--navy);
        }

        .order-date {
            font-size: .9rem;
            color: var(--muted);
        }

        .status-select {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: var(--r-sm);
            font-size: .85rem;
            background: var(--white);
        }

        .status-select option {
            padding: 4px;
        }

        .btn-update {
            background: var(--green);
            color: var(--white);
            border: none;
            padding: 6px 12px;
            border-radius: var(--r-sm);
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s;
        }

        .btn-update:hover {
            background: #059669;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: var(--white);
            border-radius: var(--r);
            box-shadow: var(--shadow);
        }

        .empty-icon {
            font-size: 4rem;
            color: var(--muted);
            margin-bottom: 24px;
        }

        .empty-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 8px;
        }

        .empty-text {
            color: var(--muted);
        }
    </style>
</head>

<body>
    <nav>
        <div class="nav-inner">
            <a href="index.php" class="logo"><i class="fas fa-mobile-alt"></i> TechStore Admin</a>
            <div class="nav-links">
                <a href="admin_page.php" class="nav-link">Dashboard</a>
                <a href="admin_orders.php" class="nav-link active">Đơn hàng</a>
                <a href="logout.php" class="nav-link">Đăng xuất</a>
            </div>
        </div>
    </nav>

    <div class="page">
        <div class="page-header">
            <h1 class="page-title">Quản lý đơn hàng</h1>
            <p class="page-subtitle">Xem và cập nhật trạng thái các đơn hàng</p>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Cập nhật trạng thái đơn hàng thành công!
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 class="empty-title">Chưa có đơn hàng nào</h2>
                <p class="empty-text">Chưa có đơn hàng nào được đặt.</p>
            </div>
        <?php else: ?>
            <div class="orders-table">
                <div class="table-header">Danh sách đơn hàng</div>

                <div class="table-row"
                    style="background: var(--bg); font-weight: 600; border-bottom: 2px solid var(--border);">
                    <div>ID</div>
                    <div>Khách hàng</div>
                    <div>Tổng tiền</div>
                    <div>Ngày đặt</div>
                    <div>Trạng thái</div>
                    <div>Thao tác</div>
                </div>

                <?php foreach ($orders as $order): ?>
                    <div class="table-row">
                        <div class="order-id">#<?= $order['id'] ?></div>
                        <div class="order-customer"><?= htmlspecialchars($order['user_name']) ?></div>
                        <div class="order-total"><?= formatPrice($order['total_amount']) ?></div>
                        <div class="order-date"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Chờ xử lý</option>
                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Đang xử lý
                                </option>
                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Đang giao hàng
                                </option>
                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Đã giao
                                </option>
                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                            </select>
                        </form>
                        <div>
                            <a href="order_status.php?id=<?= $order['id'] ?>" class="btn-update"
                                style="background: var(--blue); text-decoration: none;">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>