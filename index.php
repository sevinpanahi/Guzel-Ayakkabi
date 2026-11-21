<?php
session_start();

include 'functions.php';

try {
    $pdo = pdo_connect_mysql();
} catch (Exception $e) {
    die('Database connection failed.');
}

$categories = [];

$sql = "SELECT categories.slug, categories.name, COUNT(products.id) as total 
        FROM categories 
        LEFT JOIN products ON categories.id = products.category_id AND products.status = 'active' 
        GROUP BY categories.id";

$stmt = $pdo->query($sql);
$all_categories = $stmt->fetchAll();

foreach ($all_categories as $cat) {
    $categories[$cat['slug']] = [
        'name' => $cat['name'],
        'count' => $cat['total']
    ];
}

$stmt = $pdo->query("SELECT id, name, image, price FROM products WHERE status = 'active' ORDER BY created_at DESC LIMIT 2");
$recent_products = $stmt->fetchAll();
?>

<?php include 'header.php'; ?>

<main>

    <div class="slider">

        <div class="slide active">
            <img src="imgs/shoes3.png" alt="Shoes from the Guzel Ayakkabi collection">
            <div class="slide-caption">
                <h2>Guzel Ayakkabi</h2>
                <p>Experience Timeless Elegance with Our Shoes</p>
                <a href="products.php">Shop Now</a>
            </div>
        </div>

        <div class="slide">
            <img src="imgs/shoes1.png" alt="Everyday shoe styles">
            <div class="slide-caption">
                <h2>Everyday Style</h2>
                <p>The Latest Shoe Styles for Every Taste</p>
                <a href="products.php">Explore Our Collection</a>
            </div>
        </div>

        <div class="slide">
            <img src="imgs/shoes2.png" alt="Comfortable and stylish shoes">
            <div class="slide-caption">
                <h2>Comfort Meets Quality</h2>
                <p>Stylish Shoes Designed for Exceptional Comfort</p>
                <a href="products.php">Explore Our Collection</a>
            </div>
        </div>

        <button type="button" class="prev" aria-label="Previous slide">&#10094;</button>
        <button type="button" class="next" aria-label="Next slide">&#10095;</button>

    </div>

    <div class="store-highlights">
        <div class="store-feature">
            <div class="icon-box"><i class="fas fa-truck"></i></div>
            <h3>Fast delivery</h3>
            <p>Ships within 24 hours</p>
        </div>
        <div class="store-feature">
            <div class="icon-box"><i class="fas fa-shield-alt"></i></div>
            <h3>Secure checkout</h3>
            <p>Your data is protected</p>
        </div>
        <div class="store-feature">
            <div class="icon-box"><i class="fas fa-sync-alt"></i></div>
            <h3>Easy returns</h3>
            <p>30-day return policy</p>
        </div>
        <div class="store-feature">
            <div class="icon-box"><i class="fas fa-headset"></i></div>
            <h3>24/7 support</h3>
            <p>We're here to help</p>
        </div>
    </div>

    <div class="shoe-categories">
        <h2>Shop by category</h2>
        <p class="category-intro">Find exactly what you're looking for in our curated collections.</p>

        <div class="category-grid">

            <a href="products.php?cat=sneakers" class="category-card pink">
                <div class="category-image"><img src="imgs/sneakers.png" alt="Sneakers"></div>
                <h3>Sneakers</h3>
                <span><?php echo $categories['sneakers']['count'] ?? 0; ?> products</span>
            </a>

            <a href="products.php?cat=boots" class="category-card green">
                <div class="category-image"><img src="imgs/boots.png" alt="Boots"></div>
                <h3>Boots</h3>
                <span><?php echo $categories['boots']['count'] ?? 0; ?> products</span>
            </a>

            <a href="products.php?cat=heels" class="category-card yellow">
                <div class="category-image"><img src="imgs/heels.png" alt="Heels"></div>
                <h3>Heels</h3>
                <span><?php echo $categories['heels']['count'] ?? 0; ?> products</span>
            </a>

            <a href="products.php?cat=flats" class="category-card blue">
                <div class="category-image"><img src="imgs/flats.png" alt="Flats"></div>
                <h3>Flats</h3>
                <span><?php echo $categories['flats']['count'] ?? 0; ?> products</span>
            </a>

            <a href="products.php?cat=sandals" class="category-card purple">
                <div class="category-image"><img src="imgs/sandals.png" alt="Sandals"></div>
                <h3>Sandals</h3>
                <span><?php echo $categories['sandals']['count'] ?? 0; ?> products</span>
            </a>

            <a href="products.php?cat=formal-shoes" class="category-card red">
                <div class="category-image"><img src="imgs/formal-shoes.png" alt="Formal Shoes"></div>
                <h3>Formal Shoes</h3>
                <span><?php echo $categories['formal-shoes']['count'] ?? 0; ?> products</span>
            </a>

        </div>
    </div>

    <div class="home-categories-wrapper store-container">

        <?php
        $cat_slugs = ['sneakers', 'boots', 'heels', 'flats', 'sandals', 'formal-shoes'];

        foreach ($cat_slugs as $slug) {
            $stmt_cat = $pdo->prepare("SELECT id, name FROM categories WHERE slug = ?");
            $stmt_cat->execute([$slug]);
            $current_cat = $stmt_cat->fetch();

            if ($current_cat) {
                $stmt_prod = $pdo->prepare("SELECT id, name, image, price, sale_price FROM products WHERE category_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 10");
                $stmt_prod->execute([$current_cat['id']]);
                $cat_products = $stmt_prod->fetchAll();

                if (count($cat_products) > 0) {
        ?>

                    <div class="home-cat-section">
                        <div class="cat-header">
                            <div>
                                <span style="color: #8a6248; font-size: 12px; font-weight: bold;">EXPLORE COLLECTION</span>
                                <h2><?php echo e($current_cat['name']); ?></h2>
                            </div>
                            <a href="products.php?cat=<?php echo $slug; ?>" class="view-all-btn">View All</a>
                        </div>

                        <div class="horizontal-scroll-container">
                            <?php foreach ($cat_products as $prod): ?>
                                <?php
                                $discount_percent = 0;

                                if ($prod['sale_price'] > 0 && $prod['sale_price'] < $prod['price']) {
                                    $discount_percent = round(
                                        (($prod['price'] - $prod['sale_price']) / $prod['price']) * 100
                                    );
                                }
                                ?>

                                <a href="product.php?id=<?php echo (int)$prod['id']; ?>" class="horizontal-product-card">

                                    <?php if ($discount_percent > 0): ?>
                                        <span class="discount-tag">
                                            -<?php echo $discount_percent; ?>%
                                        </span>
                                    <?php endif; ?>
                                    <img src="imgs/<?php echo e(basename($prod['image'])); ?>" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                    <span class="name"><?php echo htmlspecialchars($prod['name']); ?></span>
                                    <div class="price-box">
                                        <?php if ($prod['sale_price'] > 0): ?>
                                            <span class="old-price">$<?php echo e($prod['price']); ?></span>
                                            <span class="new-price">$<?php echo e($prod['sale_price']); ?></span>
                                        <?php else: ?>
                                            <span class="new-price">$<?php echo e($prod['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

        <?php
                }
            }
        }
        ?>

    </div>
</main>

<?php include 'footer.php'; ?>

<script>
    var slides = document.querySelectorAll('main .slider .slide');
    var sliderBox = document.querySelector('main .slider');
    var current = 0;
    var timer;

    function showSlide(index) {
        if (!slides.length) {
            return;
        }

        slides.forEach(function(slide) {
            slide.classList.remove('active');
            slide.classList.remove('caption-visible');
        });

        slides[index].classList.add('active');
        current = index;
    }

    function startTimer() {
        if (slides.length < 2) {
            return;
        }

        stopTimer();
        timer = setInterval(function() {
            showSlide((current + 1) % slides.length);
        }, 5000);
    }

    function stopTimer() {
        clearInterval(timer);
    }

    if (sliderBox && slides.length) {
        var nextButton = sliderBox.querySelector('.next');
        var prevButton = sliderBox.querySelector('.prev');

        nextButton.onclick = function() {
            showSlide((current + 1) % slides.length);
        };

        prevButton.onclick = function() {
            showSlide((current - 1 + slides.length) % slides.length);
        };

        sliderBox.onmouseenter = stopTimer;
        sliderBox.onmouseleave = startTimer;

        slides.forEach(function(slide) {
            slide.addEventListener('click', function(event) {
                if (event.target.closest('a, button')) {
                    return;
                }
                slide.classList.toggle('caption-visible');
            });
        });

        startTimer();
    }
</script>

</body>

</html>