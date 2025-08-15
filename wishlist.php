<?php
session_start();
require_once 'inc/json_store.php';
require_once 'inc/security.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$store = new JsonStore();
$products = $store->read('products');
$users = $store->read('users');

// Get current user
$current_user = null;
foreach ($users as $user) {
    if ($user['id'] === $_SESSION['user_id']) {
        $current_user = $user;
        break;
    }
}

$wishlist_items = $current_user['wishlist'] ?? [];

// Get wishlist products
$wishlist_products = array_filter($products, function($product) use ($wishlist_items) {
    return in_array($product['id'], $wishlist_items);
});

include 'inc/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>My Wishlist</h1>
            <p>Items you've saved for later</p>
        </div>

        <?php if (empty($wishlist_products)): ?>
        <div class="empty-wishlist">
            <div class="empty-state">
                <h3>Your wishlist is empty</h3>
                <p>Start adding products you love to your wishlist</p>
                <a href="shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        </div>
        <?php else: ?>
        <div class="wishlist-grid">
            <?php foreach ($wishlist_products as $product): ?>
            <div class="wishlist-item">
                <div class="product-image">
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                </div>
                <div class="product-info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                    <div class="product-actions">
                        <button class="btn btn-primary" onclick="addToCart('<?php echo $product['id']; ?>')">
                            Add to Cart
                        </button>
                        <button class="btn btn-secondary" onclick="removeFromWishlist('<?php echo $product['id']; ?>')">
                            Remove
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
