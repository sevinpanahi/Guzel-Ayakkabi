    <footer>
        <div class="store-container">
            <div class="footer-top">

                <div class="footer-col">
                    <h3>Guzel Ayakkabi</h3>
                    <p>Discover thoughtfully designed shoes for every occasion. Step into your style with our curated collection.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>

                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>My Account</h4>
                    <ul>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="profile.php">My Profile</a></li>
                            <li><a href="cart.php">My Cart</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                            <li><a href="cart.php">My Cart</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <div class="footer-col">
                    <h4>Contact Info</h4>
                    <ul>
                        <li><i class="fas fa-map-marker-alt"></i> Istanbul, Turkey</li>
                        <li><i class="fas fa-envelope"></i> contact@guzelayakkabi.example</li>
                        <li><i class="fas fa-phone-alt"></i> +90 555 123 4567</li>
                    </ul>
                </div>

            </div>

            <div class="footer-bottom">
                <p>Copyright &copy; Guzel Ayakkabi. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        function confirmLogout(event) {
            event.preventDefault();
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = "logout.php";
            }
        }
    </script>

    </body>

    </html>