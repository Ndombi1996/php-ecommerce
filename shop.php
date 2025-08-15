<?php
$page_title = 'Shop';
$page_description = 'Browse our complete collection of household items including trash bags, detergents, and cleaning supplies.';

require_once 'inc/header.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'name';
$price_min = floatval($_GET['price_min'] ?? 0);
$price_max = floatval($_GET['price_max'] ?? 1000);

// Get products and categories
$products = JsonStore::read_json('products.json');
$categories = JsonStore::read_json('categories.json');

// Filter products
$filtered_products = array_filter($products, function($product) use ($category_filter, $search_query, $price_min, $price_max) {
    // Status filter
    if ($product['status'] !== 'active') return false;
    
    // Category filter
    if ($category_filter) {
        $category = array_filter($categories, function($cat) use ($category_filter) {
            return $cat['slug'] === $category_filter;
        });
        $category = reset($category);
        if (!$category || $product['category_id'] !== $category['id']) return false;
    }
    
    // Search filter
    if ($search_query) {
        $search_fields = strtolower($product['title'] . ' ' . $product['short_description'] . ' ' . $product['long_description']);
        if (strpos($search_fields, strtolower($search_query)) === false) return false;
    }
    
    // Price filter
    if ($product['price'] < $price_min || $product['price'] > $price_max) return false;
    
    return true;
});

// Sort products
usort($filtered_products, function($a, $b) use ($sort_by) {
    switch ($sort_by) {
        case 'price_low':
            return $a['price'] <=> $b['price'];
        case 'price_high':
            return $b['price'] <=> $a['price'];
        case 'newest':
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        default:
            return strcasecmp($a['title'], $b['title']);
    }
});

$current_category = null;
if ($category_filter) {
    $current_category = array_filter($categories, function($cat) use ($category_filter) {
        return $cat['slug'] === $category_filter;
    });
    $current_category = reset($current_category);
}
?>

<div class="shop-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a>
            <span>/</span>
            <span>Shop</span>
            <?php if ($current_category): ?>
                <span>/</span>
                <span><?php echo htmlspecialchars($current_category['name']); ?></span>
            <?php endif; ?>
        </div>
        
        <h1 class="page-title">
            <?php if ($current_category): ?>
                <?php echo htmlspecialchars($current_category['name']); ?>
            <?php elseif ($search_query): ?>
                Search Results for "<?php echo htmlspecialchars($search_query); ?>"
            <?php else: ?>
                All Products
            <?php endif; ?>
        </h1>
        
        <p class="results-count"><?php echo count($filtered_products); ?> products found</p>
    </div>
</div>

<div class="shop-content">
    <div class="container">
        <div class="shop-layout">
            <aside class="shop-sidebar">
                <div class="filter-section">
                    <h3 class="filter-title">Categories</h3>
                    <ul class="filter-list">
                        <li>
                            <a href="/shop.php" class="filter-link <?php echo !$category_filter ? 'active' : ''; ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <?php if ($category['visible'] && (!isset($category['status']) || $category['status'] !== 'coming_soon')): ?>
                                <li>
                                    <a href="/shop.php?category=<?php echo urlencode($category['slug']); ?>" 
                                       class="filter-link <?php echo $category_filter === $category['slug'] ? 'active' : ''; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="filter-section">
                    <h3 class="filter-title">Price Range</h3>
                    <form class="price-filter" method="GET">
                        <?php if ($category_filter): ?>
                            <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                        <?php endif; ?>
                        <?php if ($search_query): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                        
                        <div class="price-inputs">
                            <input type="number" name="price_min" placeholder="Min" value="<?php echo $price_min; ?>" step="0.01" min="0">
                            <span>to</span>
                            <input type="number" name="price_max" placeholder="Max" value="<?php echo $price_max; ?>" step="0.01" min="0">
                        </div>
                        <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                    </form>
                </div>
            </aside>
            
            <div class="shop-main">
                <div class="shop-toolbar">
                    <div class="search-form">
                        <form method="GET">
                            <?php if ($category_filter): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                            <input type="hidden" name="price_min" value="<?php echo $price_min; ?>">
                            <input type="hidden" name="price_max" value="<?php echo $price_max; ?>">
                            
                            <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>" class="search-input">
                            <button type="submit" class="btn btn-primary btn-sm">Search</button>
                        </form>
                    </div>
                    
                    <div class="sort-form">
                        <form method="GET" id="sortForm">
                            <?php if ($category_filter): ?>
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category_filter); ?>">
                            <?php endif; ?>
                            <?php if ($search_query): ?>
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                            <?php endif; ?>
                            <input type="hidden" name="price_min" value="<?php echo $price_min; ?>">
                            <input type="hidden" name="price_max" value="<?php echo $price_max; ?>">
                            
                            <select name="sort" onchange="document.getElementById('sortForm').submit();" class="sort-select">
                                <option value="name" <?php echo $sort_by === 'name' ? 'selected' : ''; ?>>Name A-Z</option>
                                <option value="price_low" <?php echo $sort_by === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="newest" <?php echo $sort_by === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </form>
                    </div>
                </div>
                
                <?php if (empty($filtered_products)): ?>
                    <div class="no-products">
                        <h3>No products found</h3>
                        <p>Try adjusting your filters or search terms.</p>
                        <a href="/shop.php" class="btn btn-primary">View All Products</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($filtered_products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <a href="/product.php?id=<?php echo urlencode($product['id']); ?>">
                                        <img src="/placeholder.svg?height=250&width=300" alt="<?php echo htmlspecialchars($product['title']); ?>" class="product-img">
                                    </a>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
