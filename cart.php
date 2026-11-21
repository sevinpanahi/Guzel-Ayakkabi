<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        header('Location: cart.php?error=session');
        exit;
    }

    if (isset($_POST['add_to_cart'])) {
        $product_id = (int)($_POST['product_id'] ?? 0);
        $variant_id = (int)($_POST['variant_id'] ?? 0);
        $qty = filter_var($_POST['quantity'] ?? 1, FILTER_VALIDATE_INT);

        if ($product_id > 0 && $qty !== false && $qty > 0 && $qty <= 20) {
            $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();

            if ($product) {
                if ($variant_id > 0) {
                    $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ?");
                    $stmt->execute([$variant_id, $product_id]);
                    $variant = $stmt->fetch();
                    $available = $variant ? (int)$variant['stock'] : 0;
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    $hasVariants = (int)$stmt->fetchColumn() > 0;
                    $available = $hasVariants ? 0 : (int)$product['stock'];
                }

                $cart_key = $product_id . '_' . $variant_id;
                $currentQty = (int)($_SESSION['cart'][$cart_key] ?? 0);
                if ($available > 0 && $currentQty + $qty <= $available) {
                    $_SESSION['cart'][$cart_key] = $currentQty + $qty;
                }
            }
        }

        header('Location: cart.php');
        exit;
    }

    if (isset($_POST['remove'])) {
        $key = (string)$_POST['remove'];
        if (preg_match('/^\d+_\d+$/', $key)) {
            unset($_SESSION['cart'][$key]);
        }
        header('Location: cart.php');
        exit;
    }

    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['qty'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $key => $new_qty) {
                if (!preg_match('/^\d+_\d+$/', (string)$key)) {
                    continue;
                }
                $new_qty = filter_var($new_qty, FILTER_VALIDATE_INT);
                if ($new_qty === false || $new_qty <= 0) {
                    unset($_SESSION['cart'][$key]);
                    continue;
                }

                [$productId, $variantId] = array_map('intval', explode('_', $key));
                if ($variantId > 0) {
                    $stmt = $pdo->prepare("SELECT stock FROM product_variants WHERE id = ? AND product_id = ?");
                    $stmt->execute([$variantId, $productId]);
                    $available = (int)$stmt->fetchColumn();
                } else {
                    $stmt = $pdo->prepare("SELECT p.stock FROM products p WHERE p.id = ? AND NOT EXISTS (SELECT 1 FROM product_variants v WHERE v.product_id = p.id)");
                    $stmt->execute([$productId]);
                    $available = (int)$stmt->fetchColumn();
                }

                if ($available <= 0) {
                    unset($_SESSION['cart'][$key]);
                } else {
                    $_SESSION['cart'][$key] = min($new_qty, $available);
                }
            }
        }
        header('Location: cart.php');
        exit;
    }
}

$cart_items = [];
$total = 0;

foreach ($_SESSION['cart'] as $key => $qty) {
    if (!preg_match('/^(\d+)_(\d+)$/', (string)$key, $matches)) {
        unset($_SESSION['cart'][$key]);
        continue;
    }
    $pid = (int)$matches[1];
    $vid = (int)$matches[2];
    $qty = (int)$qty;

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$pid]);
    $prod = $stmt->fetch();
    if (!$prod || $qty < 1) {
        unset($_SESSION['cart'][$key]);
        continue;
    }

    $variant_text = '';
    if ($vid > 0) {
        $stmt_v = $pdo->prepare("SELECT size, color, stock FROM product_variants WHERE id = ? AND product_id = ?");
        $stmt_v->execute([$vid, $pid]);
        $var = $stmt_v->fetch();
        if (!$var || (int)$var['stock'] < $qty) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $variant_text = 'Size: ' . $var['size'] . ' | Color: ' . $var['color'];
    } else {
        $stmt_v = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
        $stmt_v->execute([$pid]);
        if ((int)$stmt_v->fetchColumn() > 0 || (int)$prod['stock'] < $qty) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
    }

    $price = ($prod['sale_price'] !== null && (float)$prod['sale_price'] > 0) ? (float)$prod['sale_price'] : (float)$prod['price'];
    $line_total = $price * $qty;
    $total += $line_total;

    $cart_items[] = [
        'key' => $key,
        'name' => $prod['name'],
        'image' => $prod['image'],
        'variant' => $variant_text,
        'price' => $price,
        'qty' => $qty,
        'line_total' => $line_total
    ];
}

include 'header.php';
?>
<main>
    <div class="store-container">
        <h2 class="page-title">Your Shopping Cart</h2>

        <?php if (count($cart_items) == 0): ?>
            <div style="text-align: center; padding: 50px 0;">
                <p style="font-size: 18px; color: #999;">Your cart is empty.</p>
                <a href="products.php" style="display: inline-block; margin-top: 20px; background: #151429; color: white; padding: 12px 30px; border-radius: 5px; text-decoration: none;">Go to Products</a>
            </div>
        <?php else: ?>

            <form method="POST">
                <?php csrf_field(); ?>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><img src="imgs/<?php echo e($item['image']); ?>" style="width: 80px; border-radius: 5px;"></td>
                                <td>
                                    <strong><?php echo e($item['name']); ?></strong><br>
                                    <span style="font-size: 12px; color: #999;"><?php echo e($item['variant']); ?></span>
                                </td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" name="qty[<?php echo $item['key']; ?>]" value="<?php echo $item['qty']; ?>" min="0" style="width: 60px; padding: 5px;">
                                </td>
                                <td>$<?php echo number_format($item['line_total'], 2); ?></td>
                                <td><button type="submit" name="remove" value="<?php echo e($item['key']); ?>" style="color: #e74c3c; background: none; border: 0; cursor: pointer;">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-bottom">
                    <button type="submit" name="update_cart" class="update-btn">Update Cart</button>

                    <div class="cart-total-box">
                        <span>Total: <strong>$<?php echo number_format($total, 2); ?></strong></span>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            </form>

        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>