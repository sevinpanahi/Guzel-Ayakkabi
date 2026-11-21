<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header('Location: products.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND status = 'active'");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "Product not found.";
    exit;
}

$stmt_v = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock > 0");
$stmt_v->execute([$id]);
$variants = $stmt_v->fetchAll();

$stmt_c = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
$stmt_c->execute([$product['category_id']]);
$category = $stmt_c->fetch();

include 'header.php';
?>

<main>
    <div class="store-container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> /
            <a href="products.php">Shop</a> /
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-details">
            <div class="product-image-col">
                <img src="imgs/<?php echo e(basename($product['image'])); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>

            <div class="product-info-col">
                <span class="cat-name"><?php echo htmlspecialchars($product['sku']); ?></span>
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="short-desc"><?php echo htmlspecialchars($product['short_description']); ?></p>

                <div class="price-box">
                    <?php if ($product['sale_price'] > 0): ?>
                        <span class="new-price">$<?php echo e($product['sale_price']); ?></span>
                        <span class="old-price">$<?php echo e($product['price']); ?></span>
                        <span class="discount-badge">SALE</span>
                    <?php else: ?>
                        <span class="new-price">$<?php echo e($product['price']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="stock-status">
                    <span style="color: #28a745;">● In stock</span> - <?php echo e($product['stock']); ?> available
                </div>

                <form class="add-form" action="cart.php" method="post">
                    <?php csrf_field(); ?>
                    <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>">

                    <label>Choose size and color</label>
                    <select name="variant_id">
                        <option value="">Select a size / color</option>
                        <?php foreach ($variants as $v): ?>
                            <option value="<?php echo (int)$v['id']; ?>">Size: <?php echo e($v['size']); ?> - Color: <?php echo e($v['color']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <div class="action-row">
                        <input type="number" name="quantity" value="1" min="1">
                        <button type="submit" name="add_to_cart" class="btn-cart">Add to cart</button>
                        <button type="button" class="btn-buy">Buy now</button>
                    </div>
                </form>

                <div class="description-box">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>