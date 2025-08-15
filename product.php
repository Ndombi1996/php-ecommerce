<?php
require_once 'inc/header.php';

$product_id = $_GET['id'] ?? '';
if (!$product_id) {
    header('Location: /shop.php');
    exit;
}

$product = JsonStore::find_by_id('products.json', $product_id);
if (!$product) {
    header('Location: /shop.php');
    exit;
}

// Get category info
$categories = JsonStore::read_json('categories.json');
$category = array_filter($categories, function($cat) use ($product) {
    return $cat['id'] === $product['category_id'];
});
$category = reset($category);

// Get related products
$products = JsonStore::read_json('products.json');
$related_products = array_filter($products, function($p) use ($product) {
    return $p['category_id'] === $product['category_id'] && 
           $p['id'] !== $product['id'] && 
           $p['status'] === 'active';
});
$related_products = array_slice($related_products, 0, 4);

$page_title = $product['title'];
$page_description = $product['short_description'];
?>

<div class="product-detail">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a>
            <span>/</span>
            <a href="/shop.php">Shop</a>
            <?php if ($category): ?>
                <span>/</span>
                <a href="/shop.php?category=<?php echo urlencode($category['slug']); ?>">
                    <?php echo htmlspecialchars($category['name']); ?>
                </a>
            <?php endif; ?>
            <span>/</span>
            <span><?php echo htmlspecialchars($product['title']); ?></span>
        </div>
        
        <div class="product-layout">
            <div class="product-images">
                <div class="main-image">
                    <img src="/placeholder.svg?height=500&width=500" 
                         alt="<?php echo htmlspecialchars($product['title']); ?>" 
                         class="product-main-img">
                    <?php if ($product['stock'] <= 10 && $product['stock'] > 0): ?>
                        <div class="stock-badge low-stock">Only <?php echo $product['stock']; ?> left</div>
                    <?php elseif ($product['stock'] === 0): ?>
                        <div class="stock-badge out-of-stock">Out of Stock</div>
                    <?php elseif ($product['status'] === 'coming_soon'): ?>
                        <div class="stock-badge coming-soon">Coming Soon</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-details">
                <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>
                <p class="product-short-desc"><?php echo htmlspecialchars($product['short_description']); ?></p>
                
                <div class="product-price">
                    <span class="price"><?php echo CURRENCY_SYMBOL . number_format($product['price'], 2); ?></span>
                    <span class="currency"><?php echo $product['currency']; ?></span>
                </div>
                
                <?php if (!empty($product['attributes'])): ?>
                    <div class="product-attributes">
                        <h3>Product Details</h3>
                        <ul class="attributes-list">
                            <?php foreach ($product['attributes'] as $key => $value): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars(ucfirst($key)); ?>:</strong>
                                    <?php echo htmlspecialchars($value); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="product-actions">
                    <?php if ($product['status'] === 'active' && $product['stock'] > 0): ?>
                        <div class="quantity-selector">
                            <label for="quantity">Quantity:</label>
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn" data-action="decrease">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="qty-input">
                                <button type="button" class="qty-btn" data-action="increase">+</button>
                            </div>
                        </div>
                        
                        <button class="btn btn-primary btn-large add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="btn-icon">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5 6m0 0h9"></path>
                            </svg>
                            Add to Cart
                        </button>
                        
                        <button class="btn btn-outline btn-large" id="buyNowBtn">
                            Buy Now
                        </button>
                    <?php elseif ($product['status'] === 'coming_soon'): ?>
                        <button class="btn btn-disabled btn-large" disabled>Coming Soon</button>
                        <p class="stock-info">This product will be available soon. Check back later!</p>
                    <?php else: ?>
                        <button class="btn btn-disabled btn-large" disabled>Out of Stock</button>
                        <p class="stock-info">This product is currently out of stock.</p>
                    <?php endif; ?>
                </div>
                
                <div class="product-info-tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" data-tab="description">Description</button>
                        <button class="tab-btn" data-tab="shipping">Shipping</button>
                        <button class="tab-btn" data-tab="returns">Returns</button>
                    </div>
                    
                    <div class="tab-content">
                        <div class="tab-panel active" id="description">
                            <p><?php echo nl2br(htmlspecialchars($product['long_description'])); ?></p>
                        </div>
                        <div class="tab-panel" id="shipping">
                            <p>Free shipping on orders over <?php echo CURRENCY_SYMBOL . FREE_SHIPPING_THRESHOLD; ?>.</p>
                            <p>Standard shipping: <?php echo CURRENCY_SYMBOL . STANDARD_SHIPPING_RATE; ?> (3-5 business days)</p>
                            <p>Express shipping: <?php echo CURRENCY_SYMBOL . EXPRESS_SHIPPING_RATE; ?> (1-2 business days)</p>
                        </div>
                        <div class="tab-panel" id="returns">
                            <p>30-day return policy. Items must be in original condition.</p>
                            <p>Return shipping costs may apply unless the item is defective.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($related_products)): ?>
<section class="related-products">
    <div class="container">
        <h2 class="section-title">Related Products</h2>
        <div class="products-grid">
            <?php foreach ($related_products as $related): ?>
                <div class="product-card">
                    <div class="product-image">
                        <a href="/product.php?id=<?php echo urlencode($related['id']); ?>">
                            <img src="/placeholder.svg?height=250&width=300" 
                                 alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                 class="product-img">
                        </a>
                        <?php if ($related['stock'] <= 10 && $related['stock'] > 0): ?>
                            <div class="stock-badge low-stock">Low Stock</div>
                        <?php elseif ($related['stock'] === 0): ?>
                            <div class="stock-badge out-of-stock">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h3 class="product-title">
                            <a href="/product.php?id=<?php echo urlencode($related['id']); ?>">
                                <?php echo htmlspecialchars($related['title']); ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <span class="price"><?php echo CURRENCY_SYMBOL . number_format($related['price'], 2); ?></span>
                        </div>
                        <div class="product-actions">
                            <?php if ($related['stock'] > 0): ?>
                                <button class="btn btn-primary add-to-cart" data-product-id="<?php echo $related['id']; ?>">
                                    Add to Cart
                                </button>
                            <?php else: ?>
                                <button class="btn btn-disabled" disabled>Out of Stock</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require_once 'inc/footer.php'; ?>
