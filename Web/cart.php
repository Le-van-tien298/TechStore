<?php
session_start();
require_once __DIR__ . '/Database.php';

if (!isset($_SESSION['user'])) {
    header('Location: login_form.php');
    exit;
}

$db       = Database::getInstance();
$username = $_SESSION['user'];
$cart     = $db->getCart($username);
$total    = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart));

function formatPrice(float $p): string {
    return number_format($p, 0, ',', '.') . '₫';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Giỏ hàng – TechStore</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --blue: #2563eb; --blue-dark: #1d4ed8; --cyan: #06b6d4;
      --red: #ef4444; --green: #10b981; --navy: #0d1b4b; --navy-deep: #080f2d;
      --text: #111827; --muted: #6b7280; --bg: #f8fafc;
      --white: #fff; --border: #e5e7eb;
      --shadow: 0 4px 24px rgba(13,27,75,.10);
      --shadow-lg: 0 8px 40px rgba(13,27,75,.18);
      --r: 14px; --r-sm: 8px;
    }
    body { font-family: 'Inter', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* NAV */
    nav { background: var(--white); border-bottom: 1.5px solid var(--border); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 12px rgba(0,0,0,.06); }
    .nav-inner { max-width: 1100px; margin: 0 auto; display: flex; align-items: center; gap: 24px; padding: 14px 24px; }
    .logo { font-weight: 800; font-size: 1.35rem; color: var(--blue); letter-spacing: -.5px; text-decoration: none; display: flex; align-items: center; gap: 8px; }
    .logo i { color: var(--cyan); }
    .nav-back { display: flex; align-items: center; gap: 6px; font-size: .88rem; font-weight: 600; color: var(--muted); text-decoration: none; margin-left: auto; transition: color .2s; }
    .nav-back:hover { color: var(--blue); }

    /* LAYOUT */
    .page { max-width: 1100px; margin: 32px auto 60px; padding: 0 24px; display: grid; grid-template-columns: 1fr 340px; gap: 24px; align-items: start; }
    @media(max-width:900px) { .page { grid-template-columns: 1fr; } }

    /* CART TABLE */
    .cart-card { background: var(--white); border-radius: var(--r); border: 1.5px solid var(--border); box-shadow: var(--shadow); overflow: hidden; }

    .cart-header { padding: 20px 24px; border-bottom: 1.5px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .cart-header h2 { font-size: 1.15rem; font-weight: 800; display: flex; align-items: center; gap: 8px; }
    .cart-header h2 i { color: var(--blue); }

    /* Toolbar */
    .cart-toolbar { padding: 12px 24px; border-bottom: 1px solid var(--border); display: flex; align-items: center; gap: 12px; background: #fafafa; }
    .toolbar-check { display: flex; align-items: center; gap: 8px; font-size: .88rem; font-weight: 600; cursor: pointer; user-select: none; }
    .toolbar-check input { width: 16px; height: 16px; accent-color: var(--blue); cursor: pointer; }
    .toolbar-sep { width: 1px; height: 18px; background: var(--border); }
    .toolbar-btn { display: flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 50px; font-family: 'Inter', sans-serif; font-size: .82rem; font-weight: 700; cursor: pointer; border: 1.5px solid; transition: all .18s; }
    .toolbar-btn-delete { color: var(--red); border-color: #fecaca; background: #fef2f2; }
    .toolbar-btn-delete:hover { background: var(--red); color: white; border-color: var(--red); }
    .toolbar-btn-edit { color: var(--blue); border-color: #bfdbfe; background: #eff6ff; }
    .toolbar-btn-edit:hover { background: var(--blue); color: white; border-color: var(--blue); }
    .toolbar-selected { font-size: .82rem; color: var(--muted); margin-left: auto; }

    /* Cart items */
    .cart-empty { text-align: center; padding: 64px 24px; }
    .cart-empty i { font-size: 3.5rem; color: #cbd5e1; margin-bottom: 16px; display: block; }
    .cart-empty h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 8px; }
    .cart-empty p { color: var(--muted); margin-bottom: 20px; font-size: .9rem; }

    .cart-item { display: flex; align-items: center; gap: 16px; padding: 18px 24px; border-bottom: 1px solid var(--border); transition: background .15s; }
    .cart-item:last-child { border-bottom: none; }
    .cart-item:hover { background: #fafafa; }
    .cart-item.selected { background: #eff6ff; }

    .item-check { flex-shrink: 0; }
    .item-check input { width: 17px; height: 17px; accent-color: var(--blue); cursor: pointer; }

    .item-img { width: 80px; height: 80px; border-radius: var(--r-sm); background: #f1f5f9; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0; }
    .item-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .item-img i { font-size: 2rem; color: #cbd5e1; }

    .item-info { flex: 1; min-width: 0; }
    .item-type { font-size: .7rem; font-weight: 700; color: var(--blue); background: #eff6ff; padding: 2px 8px; border-radius: 20px; text-transform: uppercase; letter-spacing: .5px; display: inline-block; margin-bottom: 4px; }
    .item-name { font-size: .95rem; font-weight: 700; line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-price { font-size: .88rem; color: var(--blue); font-weight: 700; margin-top: 4px; }

    /* Quantity control */
    .qty-control { display: flex; align-items: center; gap: 0; border: 1.5px solid var(--border); border-radius: var(--r-sm); overflow: hidden; flex-shrink: 0; }
    .qty-btn { width: 32px; height: 32px; border: none; background: #f8fafc; cursor: pointer; font-size: .9rem; color: var(--text); transition: background .15s; display: flex; align-items: center; justify-content: center; }
    .qty-btn:hover { background: var(--blue); color: white; }
    .qty-input { width: 44px; height: 32px; border: none; border-left: 1px solid var(--border); border-right: 1px solid var(--border); text-align: center; font-family: 'Inter', sans-serif; font-size: .88rem; font-weight: 700; outline: none; background: white; }

    .item-subtotal { font-size: 1rem; font-weight: 800; color: var(--text); flex-shrink: 0; min-width: 90px; text-align: right; }

    /* SUMMARY */
    .summary-card { background: var(--white); border-radius: var(--r); border: 1.5px solid var(--border); box-shadow: var(--shadow); padding: 24px; position: sticky; top: 90px; }
    .summary-card h3 { font-size: 1rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .summary-card h3 i { color: var(--blue); }

    .summary-row { display: flex; justify-content: space-between; align-items: center; font-size: .9rem; margin-bottom: 12px; color: var(--muted); }
    .summary-row.total { font-size: 1.1rem; font-weight: 800; color: var(--text); border-top: 1.5px solid var(--border); padding-top: 14px; margin-top: 4px; }
    .summary-row.total span:last-child { color: var(--blue); }

    .btn-checkout { width: 100%; padding: 13px; background: var(--blue); color: white; border: none; border-radius: var(--r-sm); font-family: 'Inter', sans-serif; font-size: .97rem; font-weight: 700; cursor: pointer; transition: background .2s, transform .15s, box-shadow .2s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 16px; }
    .btn-checkout:hover { background: var(--blue-dark); box-shadow: 0 4px 16px rgba(37,99,235,.35); transform: translateY(-1px); }
    .btn-checkout:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

    .summary-note { font-size: .78rem; color: var(--muted); text-align: center; margin-top: 12px; display: flex; align-items: center; justify-content: center; gap: 5px; }

    /* Edit modal */
    .edit-overlay { position: fixed; inset: 0; z-index: 999; background: rgba(8,15,45,.6); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; padding: 20px; opacity: 0; visibility: hidden; transition: opacity .25s, visibility .25s; }
    .edit-overlay.open { opacity: 1; visibility: visible; }
    .edit-modal { background: white; border-radius: 16px; max-width: 420px; width: 100%; padding: 28px; box-shadow: 0 24px 80px rgba(13,27,75,.28); transform: translateY(20px) scale(.97); transition: transform .25s cubic-bezier(.4,0,.2,1); }
    .edit-overlay.open .edit-modal { transform: translateY(0) scale(1); }
    .edit-modal h3 { font-size: 1rem; font-weight: 800; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .edit-modal h3 i { color: var(--blue); }
    .edit-product-name { font-size: .9rem; color: var(--muted); margin-bottom: 20px; padding: 10px 14px; background: var(--bg); border-radius: var(--r-sm); border: 1.5px solid var(--border); }
    .edit-label { font-size: .82rem; font-weight: 600; margin-bottom: 8px; display: block; }
    .edit-qty-wrap { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
    .edit-qty-input { width: 80px; height: 42px; border: 1.5px solid var(--border); border-radius: var(--r-sm); text-align: center; font-family: 'Inter', sans-serif; font-size: 1.1rem; font-weight: 700; outline: none; transition: border-color .2s; }
    .edit-qty-input:focus { border-color: var(--blue); }
    .edit-actions { display: flex; gap: 10px; }
    .btn-edit-save { flex: 1; padding: 11px; background: var(--blue); color: white; border: none; border-radius: var(--r-sm); font-family: 'Inter', sans-serif; font-size: .93rem; font-weight: 700; cursor: pointer; transition: background .2s; }
    .btn-edit-save:hover { background: var(--blue-dark); }
    .btn-edit-cancel { padding: 11px 18px; background: var(--bg); color: var(--muted); border: 1.5px solid var(--border); border-radius: var(--r-sm); font-family: 'Inter', sans-serif; font-size: .93rem; font-weight: 600; cursor: pointer; transition: all .2s; }
    .btn-edit-cancel:hover { background: var(--border); }

    .btn-continue { display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; background: var(--bg); color: var(--blue); border: 1.5px solid #bfdbfe; border-radius: 50px; font-family: 'Inter', sans-serif; font-size: .88rem; font-weight: 700; text-decoration: none; transition: all .2s; }
    .btn-continue:hover { background: var(--blue); color: white; border-color: var(--blue); }
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a class="logo" href="index.php"><i class="fa-solid fa-bolt"></i> TechStore</a>
    <a class="nav-back" href="index.php"><i class="fa-solid fa-arrow-left"></i> Tiếp tục mua sắm</a>
  </div>
</nav>

<div class="page">

  <!-- GIỎ HÀNG -->
  <div class="cart-card">
    <div class="cart-header">
      <h2><i class="fa-solid fa-bag-shopping"></i> Giỏ hàng của bạn</h2>
      <a href="index.php" class="btn-continue"><i class="fa-solid fa-plus"></i> Thêm sản phẩm</a>
    </div>

    <?php if (empty($cart)): ?>
      <div class="cart-empty">
        <i class="fa-solid fa-bag-shopping"></i>
        <h3>Giỏ hàng trống</h3>
        <p>Bạn chưa có sản phẩm nào trong giỏ hàng.</p>
        <a href="index.php" class="btn-continue"><i class="fa-solid fa-store"></i> Khám phá sản phẩm</a>
      </div>
    <?php else: ?>
      <!-- Toolbar -->
      <div class="cart-toolbar">
        <label class="toolbar-check">
          <input type="checkbox" id="checkAll" onchange="toggleAll(this)"> Chọn tất cả
        </label>
        <div class="toolbar-sep"></div>
        <button class="toolbar-btn toolbar-btn-delete" onclick="deleteSelected()">
          <i class="fa-solid fa-trash"></i> Xóa
        </button>
        <button class="toolbar-btn toolbar-btn-edit" onclick="editSelected()">
          <i class="fa-solid fa-pen"></i> Sửa số lượng
        </button>
        <span class="toolbar-selected" id="selectedCount">0 đã chọn</span>
      </div>

      <!-- Items -->
      <?php foreach ($cart as $item): ?>
        <div class="cart-item" id="item-<?= $item['id'] ?>">
          <div class="item-check">
            <input type="checkbox" class="item-checkbox" value="<?= $item['id'] ?>"
                   data-price="<?= $item['price'] ?>" data-qty="<?= $item['quantity'] ?>"
                   onchange="onCheckChange()">
          </div>
          <div class="item-img">
            <?php if ($item['image']): ?>
              <img src="images/<?= htmlspecialchars($item['image']) ?>"
                   alt="<?= htmlspecialchars($item['name']) ?>"
                   onerror="this.style.display='none';this.nextElementSibling.style.display='block'">
              <i class="fa-solid fa-image" style="display:none;font-size:2rem;color:#cbd5e1"></i>
            <?php else: ?>
              <i class="fa-solid fa-box"></i>
            <?php endif; ?>
          </div>
          <div class="item-info">
            <span class="item-type"><?= htmlspecialchars($item['type_name'] ?? '') ?></span>
            <div class="item-name" title="<?= htmlspecialchars($item['name']) ?>"><?= htmlspecialchars($item['name']) ?></div>
            <div class="item-price"><?= formatPrice((float)$item['price']) ?></div>
          </div>
          <div class="qty-control">
            <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)"><i class="fa-solid fa-minus"></i></button>
            <input class="qty-input" type="number" min="1" value="<?= $item['quantity'] ?>"
                   id="qty-<?= $item['id'] ?>" onchange="setQty(<?= $item['id'] ?>, this.value)">
            <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)"><i class="fa-solid fa-plus"></i></button>
          </div>
          <div class="item-subtotal" id="sub-<?= $item['id'] ?>">
            <?= formatPrice((float)$item['price'] * $item['quantity']) ?>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- SUMMARY -->
  <div class="summary-card">
    <h3><i class="fa-solid fa-receipt"></i> Tóm tắt đơn hàng</h3>
    <div class="summary-row">
      <span>Số sản phẩm</span>
      <span id="summaryCount"><?= count($cart) ?> sản phẩm</span>
    </div>
    <div class="summary-row">
      <span>Tạm tính</span>
      <span id="summarySubtotal"><?= formatPrice($total) ?></span>
    </div>
    <div class="summary-row">
      <span>Phí vận chuyển</span>
      <span style="color:var(--green);font-weight:700">Miễn phí</span>
    </div>
    <div class="summary-row total">
      <span>Tổng cộng</span>
      <span id="summaryTotal"><?= formatPrice($total) ?></span>
    </div>
    <button class="btn-checkout" id="btnCheckout" <?= empty($cart) ? 'disabled' : '' ?>>
      <i class="fa-solid fa-bolt"></i> Đặt hàng ngay
    </button>
    <p class="summary-note"><i class="fa-solid fa-shield-halved"></i> Thanh toán an toàn & bảo mật</p>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="edit-overlay" id="editOverlay" onclick="handleEditOverlay(event)">
  <div class="edit-modal">
    <h3><i class="fa-solid fa-pen"></i> Sửa số lượng</h3>
    <div class="edit-product-name" id="editProductName"></div>
    <label class="edit-label">Số lượng mới</label>
    <div class="edit-qty-wrap">
      <input type="number" class="edit-qty-input" id="editQtyInput" min="1" value="1">
    </div>
    <div class="edit-actions">
      <button class="btn-edit-save" onclick="saveEdit()"><i class="fa-solid fa-check"></i> Lưu</button>
      <button class="btn-edit-cancel" onclick="closeEdit()">Hủy</button>
    </div>
  </div>
</div>

<script>
let editCartId = null;

// ── Format giá ──
function fmt(n) { return Number(n).toLocaleString('vi-VN') + '₫'; }

// ── Checkbox logic ──
function onCheckChange() {
  const all   = document.querySelectorAll('.item-checkbox');
  const checked = document.querySelectorAll('.item-checkbox:checked');
  document.getElementById('checkAll').checked = checked.length === all.length && all.length > 0;
  document.getElementById('checkAll').indeterminate = checked.length > 0 && checked.length < all.length;
  document.getElementById('selectedCount').textContent = checked.length + ' đã chọn';
  // Highlight row
  all.forEach(cb => {
    document.getElementById('item-' + cb.value).classList.toggle('selected', cb.checked);
  });
  updateSummary();
}

function toggleAll(cb) {
  document.querySelectorAll('.item-checkbox').forEach(c => { c.checked = cb.checked; });
  document.querySelectorAll('.cart-item').forEach(row => row.classList.toggle('selected', cb.checked));
  const count = cb.checked ? document.querySelectorAll('.item-checkbox').length : 0;
  document.getElementById('selectedCount').textContent = count + ' đã chọn';
  updateSummary();
}

// ── Thay đổi số lượng trực tiếp ──
function changeQty(cartId, delta) {
  const input = document.getElementById('qty-' + cartId);
  const newQty = Math.max(1, parseInt(input.value) + delta);
  input.value = newQty;
  saveQty(cartId, newQty);
}

function setQty(cartId, val) {
  const newQty = Math.max(1, parseInt(val) || 1);
  document.getElementById('qty-' + cartId).value = newQty;
  saveQty(cartId, newQty);
}

function saveQty(cartId, qty) {
  fetch('cart_action.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `action=update&cart_id=${cartId}&qty=${qty}`
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      // Cập nhật subtotal của item
      const cb = document.querySelector(`.item-checkbox[value="${cartId}"]`);
      if (cb) {
        cb.dataset.qty = qty;
        const price = parseFloat(cb.dataset.price);
        document.getElementById('sub-' + cartId).textContent = fmt(price * qty);
      }
      updateBadge(d.count);
      updateSummary();
    }
  });
}

// ── Cập nhật tổng tiền ──
function updateSummary() {
  let total = 0, count = 0;
  document.querySelectorAll('.item-checkbox').forEach(cb => {
    const qty   = parseInt(document.getElementById('qty-' + cb.value)?.value || cb.dataset.qty || 0);
    const price = parseFloat(cb.dataset.price || 0);
    total += qty * price;
    count++;
  });
  document.getElementById('summarySubtotal').textContent = fmt(total);
  document.getElementById('summaryTotal').textContent    = fmt(total);
  document.getElementById('summaryCount').textContent    = count + ' sản phẩm';
}

// ── Xóa đã chọn ──
function deleteSelected() {
  const ids = [...document.querySelectorAll('.item-checkbox:checked')].map(cb => cb.value);
  if (!ids.length) { alert('Vui lòng chọn ít nhất 1 sản phẩm để xóa.'); return; }
  if (!confirm(`Xóa ${ids.length} sản phẩm đã chọn?`)) return;

  const body = ids.map(id => `ids[]=${id}`).join('&') + '&action=remove';
  fetch('cart_action.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body
  })
  .then(r => r.json())
  .then(d => {
    if (d.ok) {
      ids.forEach(id => document.getElementById('item-' + id)?.remove());
      updateBadge(d.count);
      updateSummary();
      document.getElementById('selectedCount').textContent = '0 đã chọn';
      document.getElementById('checkAll').checked = false;
      if (!document.querySelectorAll('.item-checkbox').length) location.reload();
    }
  });
}

// ── Sửa số lượng (chỉ 1 item) ──
function editSelected() {
  const checked = document.querySelectorAll('.item-checkbox:checked');
  if (checked.length === 0) { alert('Vui lòng chọn 1 sản phẩm để sửa.'); return; }
  if (checked.length > 1)   { alert('Chỉ chọn 1 sản phẩm để sửa số lượng.'); return; }
  const cb     = checked[0];
  const cartId = cb.value;
  const row    = document.getElementById('item-' + cartId);
  const name   = row.querySelector('.item-name').textContent;
  const curQty = parseInt(document.getElementById('qty-' + cartId).value);
  editCartId = cartId;
  document.getElementById('editProductName').textContent = name;
  document.getElementById('editQtyInput').value = curQty;
  document.getElementById('editOverlay').classList.add('open');
}

function saveEdit() {
  const qty = Math.max(1, parseInt(document.getElementById('editQtyInput').value) || 1);
  document.getElementById('qty-' + editCartId).value = qty;
  saveQty(editCartId, qty);
  closeEdit();
}

function closeEdit() {
  document.getElementById('editOverlay').classList.remove('open');
  editCartId = null;
}

function handleEditOverlay(e) {
  if (e.target === document.getElementById('editOverlay')) closeEdit();
}

// ── Cập nhật badge navbar ──
function updateBadge(count) {
  // Gửi message cho parent window nếu có (index.php)
  if (window.opener) window.opener.postMessage({cartCount: count}, '*');
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeEdit(); });
</script>
</body>
</html>