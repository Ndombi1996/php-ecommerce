<?php
$page_title = 'Checkout';
require_once 'inc/header.php';

// Redirect if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: /cart.php');
    exit;
}

// Get current step
$step = intval($_GET['step'] ?? 1);
$step = max(1, min(4, $step)); // Ensure step is between 1-4

// Calculate cart totals (same logic as cart.php)
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

$subtotal = array_sum(array_column($cart_items, 'subtotal'));
$shipping = $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : STANDARD_SHIPPING_RATE;
$discount = 0;

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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('Invalid CSRF token');
    }
    
    switch ($_POST['action']) {
        case 'save_shipping':
            $_SESSION['checkout_data']['shipping'] = [
                'name' => Security::sanitize_input($_POST['name'] ?? ''),
                'email' => Security::sanitize_input($_POST['email'] ?? ''),
                'phone' => Security::sanitize_input($_POST['phone'] ?? ''),
                'address' => Security::sanitize_input($_POST['address'] ?? ''),
                'city' => Security::sanitize_input($_POST['city'] ?? ''),
                'state' => Security::sanitize_input($_POST['state'] ?? ''),
                'zip' => Security::sanitize_input($_POST['zip'] ?? ''),
                'country' => Security::sanitize_input($_POST['country'] ?? 'USA')
            ];
            header('Location: /checkout.php?step=3');
            exit;
            
        case 'save_payment':
            $_SESSION['checkout_data']['payment'] = [
                'method' => Security::sanitize_input($_POST['payment_method'] ?? '')
            ];
            header('Location: /checkout.php?step=4');
            exit;
            
        case 'place_order':
            // Create order
            $order_id = JsonStore::generate_id('order_');
            $order_data = [
                'id' => $order_id,
                'user_id' => $_SESSION['user_id'] ?? 'guest',
                'items' => array_map(function($item) {
                    return [
                        'product_id' => $item['product']['id'],
                        'title' => $item['product']['title'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['product']['price']
                    ];
                }, $cart_items),
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'status' => 'pending',
                'created_at' => date('c'),
                'shipping_address' => $_SESSION['checkout_data']['shipping'] ?? [],
                'payment' => [
                    'method' => $_SESSION['checkout_data']['payment']['method'] ?? 'pending',
                    'status' => 'pending'
                ]
            ];
            
            // Save order
            $orders = JsonStore::read_json('orders.json');
            $orders[] = $order_data;
            JsonStore::write_json('orders.json', $orders);
            
            // Clear cart and checkout data
            unset($_SESSION['cart']);
            unset($_SESSION['checkout_data']);
            unset($_SESSION['coupon']);
            
            // Redirect to success page
            header('Location: /checkout-success.php?order=' . $order_id);
            exit;
    }
}
?>

<div class="checkout-page">
    <div class="container">
        <div class="checkout-header">
            <h1 class="page-title">Checkout</h1>
            
            <div class="checkout-steps">
                <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                    <span class="step-number">1</span>
                    <span class="step-label">Cart Review</span>
                </div>
                <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                    <span class="step-number">2</span>
                    <span class="step-label">Shipping</span>
                </div>
                <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                    <span class="step-number">3</span>
                    <span class="step-label">Payment</span>
                </div>
                <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                    <span class="step-number">4</span>
                    <span class="step-label">Review</span>
                </div>
            </div>
        </div>
        
        <div class="checkout-content">
            <div class="checkout-main">
                <?php if ($step === 1): ?>
                    <!-- Step 1: Cart Review -->
                    <div class="checkout-section">
                        <h2>Review Your Order</h2>
                        <div class="order-items">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="order-item">
                                    <img src="/placeholder.svg?height=60&width=60" 
                                         alt="<?php echo htmlspecialchars($item['product']['title']); ?>" 
                                         class="item-image">
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($item['product']['title']); ?></h4>
                                        <p>Quantity: <?php echo $item['quantity']; ?></p>
                                    </div>
                                    <div class="item-price">
                                        <?php echo CURRENCY_SYMBOL . number_format($item['subtotal'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="step-actions">
                            <a href="/cart.php" class="btn btn-outline">Edit Cart</a>
                            <a href="/checkout.php?step=2" class="btn btn-primary">Continue to Shipping</a>
                        </div>
                    </div>
                    
                <?php elseif ($step === 2): ?>
                    <!-- Step 2: Shipping Information -->
                    <div class="checkout-section">
                        <h2>Shipping Information</h2>
                        <form method="POST" class="shipping-form">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="save_shipping">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Full Name *</label>
                                    <input type="text" id="name" name="name" required 
                                           value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['name'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email">Email *</label>
                                    <input type="email" id="email" name="email" required 
                                           value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['email'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" required 
                                       value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['address'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" required 
                                           value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['city'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <input type="text" id="state" name="state" required 
                                           value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['state'] ?? ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label for="zip">ZIP Code *</label>
                                    <input type="text" id="zip" name="zip" required 
                                           value="<?php echo htmlspecialchars($_SESSION['checkout_data']['shipping']['zip'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="step-actions">
                                <a href="/checkout.php?step=1" class="btn btn-outline">Back to Cart</a>
                                <button type="submit" class="btn btn-primary">Continue to Payment</button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($step === 3): ?>
                    <!-- Step 3: Payment Method -->
                    <div class="checkout-section">
                        <h2>Payment Method</h2>
                        <form method="POST" class="payment-form">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="save_payment">
                            
                            <div class="payment-methods">
                                <div class="payment-option">
                                    <input type="radio" id="paypal" name="payment_method" value="paypal" 
                                           <?php echo ($_SESSION['checkout_data']['payment']['method'] ?? '') === 'paypal' ? 'checked' : ''; ?>>
                                    <label for="paypal" class="payment-label">
                                        <div class="payment-info">
                                            <strong>PayPal</strong>
                                            <p>Pay securely with your PayPal account</p>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="payment-option">
                                    <input type="radio" id="card" name="payment_method" value="card" 
                                           <?php echo ($_SESSION['checkout_data']['payment']['method'] ?? '') === 'card' ? 'checked' : ''; ?>>
                                    <label for="card" class="payment-label">
                                        <div class="payment-info">
                                            <strong>Credit/Debit Card</strong>
                                            <p>Visa, Mastercard, American Express</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="step-actions">
                                <a href="/checkout.php?step=2" class="btn btn-outline">Back to Shipping</a>
                                <button type="submit" class="btn btn-primary">Review Order</button>
                            </div>
                        </form>
                    </div>
                    
                <?php elseif ($step === 4): ?>
                    <!-- Step 4: Order Review -->
                    <div class="checkout-section">
                        <h2>Review & Place Order</h2>
                        
                        <div class="order-review">
                            <div class="review-section">
                                <h3>Shipping Address</h3>
                                <?php $shipping = $_SESSION['checkout_data']['shipping'] ?? []; ?>
                                <div class="address-display">
                                    <p><strong><?php echo htmlspecialchars($shipping['name'] ?? ''); ?></strong></p>
                                    <p><?php echo htmlspecialchars($shipping['address'] ?? ''); ?></p>
                                    <p><?php echo htmlspecialchars($shipping['city'] ?? ''); ?>, <?php echo htmlspecialchars($shipping['state'] ?? ''); ?> <?php echo htmlspecialchars($shipping['zip'] ?? ''); ?></p>
                                    <p><?php echo htmlspecialchars($shipping['email'] ?? ''); ?></p>
                                </div>
                                <a href="/checkout.php?step=2" class="btn btn-text btn-sm">Edit</a>
                            </div>
                            
                            <div class="review-section">
                                <h3>Payment Method</h3>
                                <?php $payment_method = $_SESSION['checkout_data']['payment']['method'] ?? ''; ?>
                                <p><?php echo $payment_method === 'paypal' ? 'PayPal' : 'Credit/Debit Card'; ?></p>
                                <a href="/checkout.php?step=3" class="btn btn-text btn-sm">Edit</a>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo Security::generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="place_order">
                            
                            <div class="step-actions">
                                <a href="/checkout.php?step=3" class="btn btn-outline">Back to Payment</a>
                                <button type="submit" class="btn btn-primary btn-large">Place Order</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary Sidebar -->
            <div class="checkout-sidebar">
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    
                    <div class="summary-items">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="summary-item">
                                <span class="item-name"><?php echo htmlspecialchars($item['product']['title']); ?> × <?php echo $item['quantity']; ?></span>
                                <span class="item-total"><?php echo CURRENCY_SYMBOL . number_format($item['subtotal'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-line">
                            <span>Subtotal:</span>
                            <span><?php echo CURRENCY_SYMBOL . number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-line">
                            <span>Shipping:</span>
                            <span><?php echo $shipping === 0 ? 'FREE' : CURRENCY_SYMBOL . number_format($shipping, 2); ?></span>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
