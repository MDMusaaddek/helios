
<?php
require_once 'config/db.php';

$products = [];

$sql = "SELECT * FROM products
        WHERE status IN ('Active', 'Featured')
        ORDER BY
        CASE WHEN status = 'Featured' THEN 0 ELSE 1 END,
        created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Explore timeless fashion for men and women at HELIOS.">

    <title>HELIOS | Timeless Elegance</title>

    <link rel="stylesheet" href="/helios/assets/css/style.css?v=4">
</head>
<body>

<header class="site-header">
    <a href="#home" class="logo">HELIOS</a>

    <nav class="main-nav">
        <a href="#home">Home</a>
        <a href="#products" data-nav-category="Men">Men</a>
        <a href="#products" data-nav-category="Women">Women</a>
        <a href="#about">About</a>
        <a href="#contact">Contact</a>
    </nav>

    <a class="whatsapp-header"
       href="https://wa.me/8801351248758"
       target="_blank" rel="noopener noreferrer">
        WhatsApp
    </a>
</header>

<main>

    <!-- HERO -->
    <section id="home" class="hero">
        <div class="hero-content">
            <p class="collection-text">NEW COLLECTION 2026</p>

            <h1>TIMELESS ELEGANCE.<br>WEAR YOUR LIGHT.</h1>

            <p class="hero-description">
                Contemporary fashion for men and women,
                designed for effortless style.
            </p>

            <div class="hero-buttons">
                <a href="#products" class="btn"
                   data-nav-category="Men">SHOP MEN</a>

                <a href="#products" class="btn btn-outline"
                   data-nav-category="Women">SHOP WOMEN</a>
            </div>
        </div>

        <div class="hero-image"
             role="img"
             aria-label="HELIOS fashion collection"></div>
    </section>

    <!-- PRODUCTS -->
    <section id="products" class="products-section">
        <p class="section-eyebrow">THE HELIOS COLLECTION</p>
        <h2>SHOP HELIOS</h2>

        <p class="section-subtitle">
            Discover timeless styles for every occasion.
        </p>

        <div class="product-tabs">
            <button type="button" class="tab-btn active"
                    data-category="Men">MEN</button>

            <button type="button" class="tab-btn"
                    data-category="Women">WOMEN</button>
        </div>

        <div class="product-grid" id="productGrid">

            <?php foreach ($products as $product): ?>

                <article class="product-card"
                         data-category="<?= h($product['category']) ?>">

                    <!-- CLICKABLE PRODUCT IMAGE -->
                    <a href="/helios/product.php?id=<?= (int)$product['id'] ?>"
                       class="product-details-link"
                       aria-label="View <?= h($product['name']) ?> details">

                        <div class="product-image">
                            <img
                                src="/helios/assets/images/products/<?= rawurlencode(basename($product['image'])) ?>"
                                alt="<?= h($product['name']) ?>"
                                loading="lazy">
                        </div>
                    </a>

                    <div class="product-info">

                        <p class="product-category">
                            <?= h($product['category']) ?>
                        </p>

                        <!-- CLICKABLE PRODUCT NAME -->
                        <h3>
                            <a href="/helios/product.php?id=<?= (int)$product['id'] ?>"
                               class="product-name-link">
                                <?= h($product['name']) ?>
                            </a>
                        </h3>

                        <div class="product-price">
                            <span class="current-price">
                                ৳<?= number_format((float)$product['price'], 0) ?>
                            </span>

                            <?php if (
                                !empty($product['old_price']) &&
                                (float)$product['old_price'] > (float)$product['price']
                            ): ?>
                                <span class="old-price">
                                    ৳<?= number_format((float)$product['old_price'], 0) ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <p class="product-description">
                            <?= h($product['short_description']) ?>
                        </p>

                        <!-- VIEW DETAILS BUTTON -->
                        <a href="/helios/product.php?id=<?= (int)$product['id'] ?>"
                           class="view-details-btn">
                            VIEW DETAILS
                        </a>

                        <?php
                        $message = 'Hello HELIOS, I want to order ' .
                            $product['name'] . '. Price: Tk ' .
                            $product['price'] . '. ' .
                            $product['short_description'];
                        ?>

                        <!-- EXISTING WHATSAPP ORDER BUTTON -->
                        <a class="order-btn"
                           href="https://wa.me/8801351248758?text=<?= rawurlencode($message) ?>"
                           target="_blank" rel="noopener noreferrer">
                            ORDER NOW
                        </a>

                    </div>
                </article>

            <?php endforeach; ?>

        </div>

        <p id="noProducts" class="no-products" hidden>
            No products in this category yet. Please check back soon.
        </p>

        <button type="button" id="viewMoreBtn"
                class="view-more-btn" hidden>
            VIEW MORE
        </button>
    </section>

 <!-- ABOUT -->
<section id="about" class="about-section">

    <div class="about-image-box">
       <img src="/helios/assets/images/about/about-helios.png" alt="HELIOS Brand Story">
        <div class="about-overlay">
            <p class="about-overlay-label">BRAND HISTORY</p>

            <h3>Timeless Style, Modern Confidence</h3>

            <p>
                HELIOS was created to bring timeless elegance into everyday fashion.
                We focus on stylish, comfortable, and versatile clothing for men and women
                who want confidence in every look.
            </p>
        </div>
    </div>

    <div class="about-content">
        <p class="section-eyebrow">OUR STORY</p>

        <h2>ABOUT HELIOS</h2>

        <p>
            HELIOS is a contemporary fashion brand for men and women, built around timeless style,
            confidence, and everyday elegance.
        </p>

        <p>
            We create refined and wearable fashion pieces that combine comfort, quality,
            and modern design for daily life.
        </p>

        <p class="about-tagline">
            Timeless Elegance • Wear Your Light
        </p>
    </div>

</section>

    <!-- CONTACT -->
    <section id="contact" class="contact-section">
        <p class="section-eyebrow">WE'RE HERE TO HELP</p>
        <h2>CONTACT HELIOS</h2>

        <p>
            Need help choosing a product or want to place an order?
        </p>

        <a href="https://wa.me/8801351248758"
           target="_blank" rel="noopener noreferrer"
           class="contact-whatsapp">
            ORDER / CONTACT ON WHATSAPP
        </a>

        <div class="social-links">
            <a href="https://www.facebook.com/profile.php?id=61595611768284"
               target="_blank" rel="noopener noreferrer">Facebook</a>

            <a href="https://www.instagram.com/mdmusaaddekshahriar/"
               target="_blank" rel="noopener noreferrer">Instagram</a>

            <a href="mailto:contracthelios@gmail.com">Email</a>
        </div>
    </section>

</main>

<footer class="site-footer">
    <h3>HELIOS</h3>
    <p>Timeless Elegance • Wear Your Light</p>
    <p>&copy; <?= date('Y') ?> HELIOS. All rights reserved.</p>
</footer>

<a href="https://wa.me/8801351248758"
   target="_blank" rel="noopener noreferrer"
   class="floating-whatsapp">
    WhatsApp
</a>

<script src="/helios/assets/js/script.js?v=4"></script>
</body>
</html>
