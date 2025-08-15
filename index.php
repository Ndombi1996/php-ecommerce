<?php
$page_title = 'Home';
$page_description = 'Quality household items including trash bags, detergents, and more. Shop now for great deals!';

require_once 'inc/header.php';

// Get featured products
$products = JsonStore::read_json('products.json');
$featured_products = array_filter($products, function($product) {
    return $product['status'] === 'active';
});
$featured_products = array_slice($featured_products, 0, 6);

// Get categories
$categories = JsonStore::read_json('categories.json');
$visible_categories = array_filter($categories, function($cat) {
    return $cat['visible'];
});
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">Quality Household Items for Every Need</h1>
                <p class="hero-subtitle">Discover our premium collection of trash bags, cleaning supplies, and more. Built for durability, priced for value.</p>
                <div class="hero-actions">
                    <a href="/shop.php" class="btn btn-primary">Shop Now</a>
                    <a href="/shop.php?category=trash-bags" class="btn btn-secondary">View Trash Bags</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="/placeholder.svg?height=400&width=500" alt="Quality household items" class="hero-img">
            </div>
        </div>
    </div>
</section>

<section class="featured-categories">
    <div class="container">
        <h2 class="section-title">Shop by Category</h2>
        <div class="categories-grid">
            <?php foreach ($visible_categories as $category): ?>
                <div class="category-card <?php echo isset($category['status']) && $category['status'] === 'coming_soon' ? 'coming-soon' : ''; ?>">
                    <div class="category-image">
                        <img src="/placeholder.svg?height=200&width=300" alt="<?php echo htmlspecialchars($category['name']); ?>" class="category-img">
                        <?php if (isset($category['status']) && $category['status'] === 'coming_soon'): ?>
                            <div class="coming-soon-badge">Coming Soon</div>
                        <?php endif; ?>
                    </div>
                    <div class="category-info">
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-description"><?php echo htmlspecialchars($category['description']); ?></p>
                        <?php if (!isset($category['status']) || $category['status'] !== 'coming_soon'): ?>
                            <a href="/shop.php?category=<?php echo urlencode($category['slug']); ?>" class="btn btn-outline">Browse <?php echo htmlspecialchars($category['name']); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="featured-products">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Featured Products</h2>
            <a href="/shop.php" class="section-link">View All Products</a>
        </div>
        <div class="products-grid">
            <?php foreach ($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image">
                        <img src="/placeholder.svg?height=250&width=300" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-img">
                        <?php if ($product['stock'] <= 10 && $product['stock'] > 0): ?>
                            <div class="stock-badge low-stock">Low Stock</div>
                        <?php elseif ($product['stock'] === 0): ?>
                            <div class="stock-badge out-of-stock">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="/product.php?id=<?php echo urlencode($product['id']); ?>">
                                <?php echo htmlspecialchars($product['title']); ?>
                            </a>
                        </h3>
                        <p class="product-description"><?php echo htmlspecialchars($product['short_description']); ?></p>
                        <div class="product-price">
                            <span class="price"><?php echo CURRENCY_SYMBOL . number_format($product['price'], 2); ?></span>
                        </div>
                        <div class="product-actions">
                            <?php if ($product['stock'] > 0): ?>
                                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>Out of Stock</button>
                            <?php endif; ?>
                            <a href="/product.php?id=<?php echo urlencode($product['id']); ?>" class="btn btn-outline">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <div class="features-grid">
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
                <h3 class="feature-title">Free Shipping</h3>
                <p class="feature-text">Free shipping on orders over <?php echo CURRENCY_SYMBOL . FREE_SHIPPING_THRESHOLD; ?></p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="feature-title">Quality Guaranteed</h3>
                <p class="feature-text">Premium products with satisfaction guarantee</p>
            </div>
            <div class="feature">
                <div class="feature-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                    </svg>
                </div>
                <h3 class="feature-title">24/7 Support</h3>
                <p class="feature-text">Round-the-clock customer support</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'inc/footer.php'; ?>
