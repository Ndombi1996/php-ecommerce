<?php
$page_title = 'Shopping Cart';
require_once 'inc/header.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!Security::verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    switch ($_POST['action']) {
        case 'update_quantity':
            $product_id = $_POST['product_id'] ?? '';
            $quantity = max(0, intval($_POST['quantity'] ?? 0));
            
            if ($quantity === 0) {
                unset($_SESSION['cart'][$product_id]);
            } else {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id]['quantity'] = $quantity;
                }
            }
            break;
            
        case 'remove_item':
            $product_id = $_POST['product_id'] ?? '';
            unset($_SESSION['cart'][$product_id]);
            break;
            
        case 'apply_coupon':
            $coupon_code = trim($_POST['coupon_code'] ?? '');
            $coupons = JsonStore::read_json('coupons.json');
            $valid_coupon = null;
            
            foreach ($coupons as $coupon) {
                if (strtoupper($coupon['code']) === strtoupper($coupon_code)) {
                    $today = date('Y-m-d');
                    if ($today >= $coupon['valid_from'] && $today <= $coupon['valid_to'] && 
                        $coupon['uses'] < $coupon['max_uses']) {
                        $valid_coupon = $coupon;
                        break;
                    }
                }
            }
            
            if ($valid_coupon) {
                $_SESSION['coupon'] = $valid_coupon;
                $success_message = "Coupon applied successfully!";
            } else {
                $error_message = "Invalid or expired coupon code.";
            }
            break;
            
        case 'remove_coupon':
            unset($_SESSION['coupon']);
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: /cart.php');
    exit;
}

// Get cart items with product details
$cart_items = [];
$products = JsonStore::read_json('products.json');

foreach ($_SESSION['cart'] as $product_id => $cart_item) {
    $product = JsonStore::find_by_id('products.json', $product_id);
    if ($product) {
        $cart_items[] = [
            'product' => $product,
            'quantity' => $cart_item['quantity'],
            'subtotal' => $product['price'] * $cart_item['quantity']
        ];
    }
}

// Calculate totals
$subtotal = array_sum(array_column($cart_items, 'subtotal'));
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : STANDARD_SHIPPING_RATE;
$discount = 0;

// Apply coupon discount
if (isset($_SESSION['coupon'])) {
    $coupon = $_SESSION['coupon'];
    if ($subtotal >= $coupon['min_cart_value']) {
        switch ($coupon['type']) {
            case 'percent':
                $discount = $subtotal * ($coupon['value'] / 100);
                break;
            case 'fixed':
                $discount = min($coupon['value'], $subtotal);
                break;
            case 'free_shipping':
                $shipping = 0;
                break;
        }
    }
}

$tax = ($subtotal - $discount) * TAX_RATE;
$total = $subtotal + $shipping + $tax - $discount;
?>

<div class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Shopping Cart</h1>
            <div class="breadcrumb">
                <a href="/">Home</a>
                <span>/</span>
                <span>Cart</span>
            </div>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 6M7 13l-1.5 6m0 0h9"></path>
                    </svg>
                </div>
                <h2>Your cart is empty</h2>
                <p>Add some products to get started!</p>
                <a href="/shop.php" class="btn btn-primary">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-items">
                    <div class="cart-header">
                        <h2>Cart Items (<?php echo count($cart_items); ?>)</h2>
                    </div>
                    
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <img src="/placeholder.svg?height=100&width=100" 
                                     alt="<?php echo htmlspecialchars($item['product']['title']); ?>" 
                                     class="item-img">
                            </div>
                            
                            <div class="item-details">
                                <h3 class="item-title">
                                    <a href="/product.php?id=<?php echo urlencode($item['product']['id']); ?>">
                                        <?php echo htmlspecialchars($item['product']['title']); ?>
                                    </a>
                                </h3>
                                <p class="item-description"><?php echo htmlspecialchars($item['product']['short_description']); ?></p>
                                <div class="item-price">
                                    <?php echo CURRENCY_SYMBOL . number_format($item['product']['price'], 2); ?> each
                                </div>
                            </div>
                            
                            <div class="item-quantity">
                                <form method="POST" class="quantity-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_quantity">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                    
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn" data-action="decrease">-</button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['product']['stock']; ?>" 
                                               class="qty-input" onchange="this.form.submit()">
                                        <button type="button" class="qty-btn" data-action="increase">+</button>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="item-total">
                                <div class="item-subtotal">
                                    <?php echo CURRENCY_SYMBOL . number_format($item['subtotal'], 2); ?>
                                </div>
                                <form method="POST" class="remove-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="remove_item">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product']['id']; ?>">
                                    <button type="submit" class="btn btn-text btn-sm remove-btn">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3 class="summary-title">Order Summary</h3>
                        
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="summary-line">
                            <span>Shipping:</span>
                            <span>
                                <?php if ($shipping === 0): ?>
                                    <span class="free-shipping">FREE</span>
                                <?php else: ?>
                                    <?php echo CURRENCY_SYMBOL . number_format($shipping, 2); ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        
                        <?php if ($discount > 0): ?>
                            <div class="summary-line discount">
                                <span>Discount:</span>
                                <span>-<?php echo CURRENCY_SYMBOL . number_format($discount, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-line">
                            <span>Tax:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="summary-line total">
                            <span>Total:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($total, 2); ?></span>
                        </div>
                        
                        <div class="coupon-section">
                            <?php if (isset($_SESSION['coupon'])): ?>
                                <div class="applied-coupon">
                                    <span>Coupon: <?php echo htmlspecialchars($_SESSION['coupon']['code']); ?></span>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="remove_coupon">
                                        <button type="submit" class="btn btn-text btn-sm">Remove</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="coupon-form">
                                    <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="apply_coupon">
                                    <input type="text" name="coupon_code" placeholder="Coupon code" class="coupon-input">
                                    <button type="submit" class="btn btn-outline btn-sm">Apply</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <div class="checkout-actions">
                            <a href="/checkout.php" class="btn btn-primary btn-large">Proceed to Checkout</a>
                            <a href="/shop.php" class="btn btn-outline">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
