<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$message = '';
$total = 0;
$shipping_fee = 0;

$shippingStmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = 'shipping_fee' LIMIT 1");
$shippingStmt->execute();
$shipping_fee = max(0, (float)$shippingStmt->fetchColumn());

// Display a fresh total. The final amount is recalculated again inside the order transaction.
foreach ($_SESSION['cart'] as $key => $qty) {
    if (!preg_match('/^(\d+)_(\d+)$/', (string)$key, $matches)) {
        continue;
    }
    $productId = (int)$matches[1];
    $stmt = $pdo->prepare("SELECT price, sale_price FROM products WHERE id = ? AND status = 'active'");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if ($product && (int)$qty > 0) {
        $price = ($product['sale_price'] !== null && (float)$product['sale_price'] > 0) ? (float)$product['sale_price'] : (float)$product['price'];
        $total += $price * (int)$qty;
    }
}
$total += $shipping_fee;

if (isset($_POST['place_order'])) {
    if (!verify_csrf_token()) {
        $message = 'Your session has expired. Please refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $zip = trim($_POST['zip'] ?? '');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $address === '') {
            $message = 'Please enter your name, a valid email address and shipping address.';
        } else {
            try {
                $pdo->beginTransaction();
                $orderItems = [];
                $subtotal = 0;

                foreach ($_SESSION['cart'] as $key => $qty) {
                    if (!preg_match('/^(\d+)_(\d+)$/', (string)$key, $matches)) {
                        throw new RuntimeException('Your cart contains an invalid item. Please review your cart.');
                    }
                    $productId = (int)$matches[1];
                    $variantId = (int)$matches[2];
                    $qty = filter_var($qty, FILTER_VALIDATE_INT);
                    if ($qty === false || $qty < 1) {
                        throw new RuntimeException('One of the quantities in your cart is invalid.');
                    }

                    $productStmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active' FOR UPDATE");
                    $productStmt->execute([$productId]);
                    $product = $productStmt->fetch();
                    if (!$product) {
                        throw new RuntimeException('A product in your cart is no longer available.');
                    }

                    $variant = null;
                    if ($variantId > 0) {
                        $variantStmt = $pdo->prepare("SELECT id, size, color, stock FROM product_variants WHERE id = ? AND product_id = ? FOR UPDATE");
                        $variantStmt->execute([$variantId, $productId]);
                        $variant = $variantStmt->fetch();
                        if (!$variant || (int)$variant['stock'] < $qty || (int)$product['stock'] < $qty) {
                            throw new RuntimeException('The selected size or color does not have enough stock. Please update your cart.');
                        }
                        $stockStmt = $pdo->prepare("UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $stockStmt->execute([$qty, $variantId, $qty]);
                        if ($stockStmt->rowCount() !== 1) {
                            throw new RuntimeException('The selected variant is no longer available in that quantity.');
                        }
                        $productStockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $productStockStmt->execute([$qty, $productId, $qty]);
                        if ($productStockStmt->rowCount() !== 1) {
                            throw new RuntimeException('Product stock changed. Please review your cart.');
                        }
                    } else {
                        $variantCountStmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
                        $variantCountStmt->execute([$productId]);
                        if ((int)$variantCountStmt->fetchColumn() > 0 || (int)$product['stock'] < $qty) {
                            throw new RuntimeException('This product requires a valid size and color selection or has insufficient stock.');
                        }
                        $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                        $stockStmt->execute([$qty, $productId, $qty]);
                        if ($stockStmt->rowCount() !== 1) {
                            throw new RuntimeException('Product stock changed. Please review your cart.');
                        }
                    }

                    $price = ($product['sale_price'] !== null && (float)$product['sale_price'] > 0) ? (float)$product['sale_price'] : (float)$product['price'];
                    $lineTotal = $price * $qty;
                    $subtotal += $lineTotal;
                    $orderItems[] = [
                        'product_id' => $productId,
                        'variant_id' => $variantId > 0 ? $variantId : null,
                        'variant_size' => $variant['size'] ?? null,
                        'variant_color' => $variant['color'] ?? null,
                        'product_name' => $product['name'],
                        'product_sku' => $product['sku'],
                        'product_image' => $product['image'],
                        'unit_price' => $price,
                        'quantity' => $qty,
                        'line_total' => $lineTotal
                    ];
                }

                if (!$orderItems) {
                    throw new RuntimeException('Your cart is empty.');
                }

                $shippingStmt = $pdo->prepare("SELECT value FROM settings WHERE key_name = 'shipping_fee' LIMIT 1");
                $shippingStmt->execute();
                $shipping_fee = max(0, (float)$shippingStmt->fetchColumn());
                $grandTotal = $subtotal + $shipping_fee;

                $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
                $orderStmt = $pdo->prepare("INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, shipping_city, shipping_zip, subtotal, shipping_fee, total, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'cod', 'pending')");
                $orderStmt->execute([$orderNumber, $name, $email, $phone, $address, $city, $zip, $subtotal, $shipping_fee, $grandTotal]);
                $orderId = (int)$pdo->lastInsertId();

                $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, variant_id, variant_size, variant_color, product_name, product_sku, product_image, unit_price, quantity, line_total) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                foreach ($orderItems as $item) {
                    $itemStmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['variant_id'],
                        $item['variant_size'],
                        $item['variant_color'],
                        $item['product_name'],
                        $item['product_sku'],
                        $item['product_image'],
                        $item['unit_price'],
                        $item['quantity'],
                        $item['line_total']
                    ]);
                }

                $pdo->commit();
                unset($_SESSION['cart']);
                $total = $grandTotal;
                $message = 'Order placed successfully! Your order number is: ' . $orderNumber;
            } catch (Throwable $error) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = $error instanceof RuntimeException ? $error->getMessage() : 'We could not place your order. Please try again.';
            }
        }
    }
}

include 'header.php';
?>
<main>
    <div class="store-container">
        <h2 class="page-title">Checkout</h2>

        <?php if ($message != ""): ?>
            <div style="background: #d4edda; color: #155724; padding: 20px; text-align: center; border-radius: 8px; margin-bottom: 30px; font-size: 18px;">
                <?php echo $message; ?><br>
                <a href="index.php" style="color: #155724; text-decoration: underline;">Go back to Home</a>
            </div>
        <?php else: ?>

            <div class="checkout-layout">
                <div class="checkout-form-box">
                    <form method="POST">
                        <?php csrf_field(); ?>
                        <h3>Shipping Information</h3>

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required>
                        </div>

                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" required>
                        </div>

                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone">
                        </div>

                        <div class="form-group">
                            <label>Address *</label>
                            <textarea name="address" rows="3" required></textarea>
                        </div>

                        <div style="display: flex; gap: 15px;">
                            <div class="form-group" style="flex: 1;">
                                <label>City</label>
                                <input type="text" name="city">
                            </div>
                            <div class="form-group" style="flex: 1;">
                                <label>Zip Code</label>
                                <input type="text" name="zip">
                            </div>
                        </div>

                        <hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

                        <h3>Payment Method</h3>
                        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                            <strong>Cash on Delivery (COD)</strong>
                            <p style="margin: 5px 0 0 0; font-size: 13px; color: #666;">Pay with cash when your order is delivered to your doorstep.</p>
                        </div>

                        <button type="submit" name="place_order" class="place-order-btn">Place Order ($<?php echo number_format($total, 2); ?>)</button>
                    </form>
                </div>
            </div>

        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>