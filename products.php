<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

$where_clauses = ["status = 'active'"];
$params = [];
$page_title = "All Products";

if (isset($_GET['cat']) && $_GET['cat'] != "") {
    $cat_slug = $_GET['cat'];
    
    $stmt = $pdo->prepare("SELECT id, name FROM categories WHERE slug = ?");
    $stmt->execute([$cat_slug]);
    $category = $stmt->fetch();
    
    if ($category) {
        $where_clauses[] = "category_id = ?";
        $params[] = $category['id'];
        $page_title = $category['name'];
    }
}

if (isset($_GET['search']) && trim($_GET['search']) != "") {
    $search_term = trim($_GET['search']);
    $where_clauses[] = "(name LIKE ? OR description LIKE ? OR short_description LIKE ?)";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $params[] = "%" . $search_term . "%";
    $page_title = "Search results for: " . htmlspecialchars($search_term);
}

$sql = "SELECT * FROM products WHERE " . implode(" AND ", $where_clauses) . " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'header.php';
?>

<main>
    <div class="store-container">
        <h2 class="page-title"><?php echo htmlspecialchars($page_title); ?></h2>

        <div class="product-grid">
            <?php foreach ($products as $prod):
                $discount = 0;
                if ($prod['sale_price'] > 0 && $prod['price'] > 0) {
                    $discount = round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100);
                }
            ?>
                <a href="/guzel_ayakkabi/product.php?id=<?php echo (int)$prod['id']; ?>" class="product-card">

                    <div class="image-box">
                        <?php if ($discount > 0): ?>
                            <span class="discount-tag">-<?php echo $discount; ?>%</span>
                        <?php endif; ?>
                        <img src="/guzel_ayakkabi/imgs/<?php echo e(basename($prod['image'])); ?>" alt="<?php echo e($prod['name']); ?>">
                    </div>

                    <div class="card-info">
                        <span class="category-label">Product</span>

                        <span class="card-name"><?php echo htmlspecialchars($prod['name']); ?></span>

                        <div class="card-price">
                            <?php if ($prod['sale_price'] > 0): ?>
                                <span class="new-price">$<?php echo e($prod['sale_price']); ?></span>
                                <span class="old-price">$<?php echo e($prod['price']); ?></span>
                            <?php else: ?>
                                <span class="new-price">$<?php echo e($prod['price']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                </a>
            <?php endforeach; ?>

            <?php if (count($products) == 0): ?>
                <p style="text-align: center; width: 100%; grid-column: 1 / -1;">No products found.</p>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>