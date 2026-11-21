<?php
session_start();
include 'functions.php';
$pdo = pdo_connect_mysql();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = "";

$stmt_user = $pdo->prepare("SELECT email FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user_data = $stmt_user->fetch();
if ($user_data) {
    $user_email = $user_data['email'];
}

$stmt_orders = $pdo->prepare("SELECT * FROM orders WHERE customer_email = ? ORDER BY created_at DESC");
$stmt_orders->execute([$user_email]);
$my_orders = $stmt_orders->fetchAll();

include 'header.php';
?>

<main>
    <div class="store-container">
        <div class="profile-header">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>
            <p>Email: <?php echo htmlspecialchars($user_email); ?></p>
        </div>

        <h3 class="section-title">Your Order History</h3>

        <?php if (count($my_orders) == 0): ?>
            <div class="empty-orders">
                <p>You haven't placed any orders yet.</p>
                <a href="products.php" class="shop-btn">Start Shopping</a>
            </div>
        <?php else: ?>
            
            <div class="profile-orders-list">
                <?php foreach ($my_orders as $order): 
                    $stmt_items = $pdo->prepare("SELECT SUM(quantity) as total_qty FROM order_items WHERE order_id = ?");
                    $stmt_items->execute([$order['id']]);
                    $item_count = $stmt_items->fetchColumn();
                    
                    $status_color = "#f39c12";
                    if ($order['status'] == 'shipped') $status_color = "#3498db";
                    if ($order['status'] == 'completed') $status_color = "#2ecc71";
                    if ($order['status'] == 'cancelled') $status_color = "#e74c3c";
                ?>
                    <div class="order-card">
                        <div class="order-card-header">
                            <div>
                                <strong><?php echo e($order['order_number']); ?></strong><br>
                                <span style="font-size: 13px; color: #999;"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div>
                                <span style="background: <?php echo $status_color; ?>; color: white; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold;">
                                    <?php echo strtoupper($order['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-card-body">
                            <p><strong>Items:</strong> <?php echo $item_count; ?> product(s)</p>
                            <p><strong>Total:</strong> $<?php echo number_format($order['total'], 2); ?></p>
                            <p><strong>Payment:</strong> Cash on Delivery</p>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>
</main>

<?php include 'footer.php'; ?>