<?php
$page_title = 'Order Confirmation';
require_once 'inc/header.php';

$order_id = $_GET['order'] ?? '';
if (!$order_id) {
    header('Location: /');
    exit;
}

$order = JsonStore::find_by_id('orders.json', $order_id);
if (!$order) {
    header('Location: /');
    exit;
}
?>

<div class="success-page">
    <div class="container">
        <div class="success-content">
            <div class="success-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h1 class="success-title">Order Confirmed!</h1>
            <p class="success-message">Thank you for your order. We've received your payment and will process your order shortly.</p>
            
            <div class="order-details">
                <h2>Order Details</h2>
                <div class="order-info">
                    <div class="info-row">
                        <span>Order Number:</span>
                        <strong><?php echo htmlspecialchars($order['id']); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Order Date:</span>
                        <strong><?php echo date('F j, Y', strtotime($order['created_at'])); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Total Amount:</span>
                        <strong><?php echo CURRENCY_SYMBOL . number_format($order['total'], 2); ?></strong>
                    </div>
                    <div class="info-row">
                        <span>Payment Method:</span>
                        <strong><?php echo ucfirst($order['payment']['method'] ?? 'N/A'); ?></strong>
                    </div>
                </div>
                
                <div class="order-items">
                    <h3>Items Ordered</h3>
                    <?php foreach ($order['items'] as $item): ?>
                        <div class="order-item">
                            <span class="item-name"><?php echo htmlspecialchars($item['title']); ?></span>
                            <span class="item-quantity">Qty: <?php echo $item['quantity']; ?></span>
                            <span class="item-price"><?php echo CURRENCY_SYMBOL . number_format($item['unit_price'] * $item['quantity'], 2); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($order['shipping_address'])): ?>
                    <div class="shipping-info">
                        <h3>Shipping Address</h3>
                        <div class="address">
                            <p><strong><?php echo htmlspecialchars($order['shipping_address']['name'] ?? ''); ?></strong></p>
                            <p><?php echo htmlspecialchars($order['shipping_address']['address'] ?? ''); ?></p>
                            <p><?php echo htmlspecialchars($order['shipping_address']['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['shipping_address']['state'] ?? ''); ?> <?php echo htmlspecialchars($order['shipping_address']['zip'] ?? ''); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="success-actions">
                <a href="/shop.php" class="btn btn-primary">Continue Shopping</a>
                <?php if (Security::is_logged_in()): ?>
                    <a href="/account/dashboard.php" class="btn btn-outline">View Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'inc/footer.php'; ?>
