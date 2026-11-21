<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Guzel Ayakkabi</title>
    <link rel="stylesheet" href="/guzel_ayakkabi/style.css?v=3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>

    <header>
        <div class="store-container">

            <form class="search" action="products.php" method="get">
                <input type="text" name="search" placeholder="Search...">
                <button type="submit">Search</button>
            </form>

            <div class="brand">
                <h1>Guzel Ayakkabi</h1>
                <p>Step Into Your Style</p>
            </div>

            <nav>
                <a href="index.php">Home</a>
                <a href="products.php">Products</a>
                <a href="contact.php">Contact</a>
                <a href="cart.php" class="header-cart-link">Cart <span><?php echo isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0; ?></span></a>
            </nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-menu">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['user_name']); ?> &#9662;</span>
                    <div class="dropdown-content">
                        <a href="profile.php" style="color: #151429; padding: 12px 16px; text-decoration: none; display: block;">My Profile</a>
                        <a href="#" onclick="confirmLogout(event)" style="color: #e74c3c; font-weight: bold;">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>

        </div>
    </header>