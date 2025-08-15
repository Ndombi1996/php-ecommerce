<?php
session_start();
require_once 'inc/json_store.php';
require_once 'inc/security.php';

$store = new JsonStore();

// Handle form submission
if ($_POST) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    $ticket = [
        'id' => uniqid(),
        'user_id' => $_SESSION['user_id'] ?? null,
        'name' => sanitize_input($_POST['name']),
        'email' => sanitize_input($_POST['email']),
        'subject' => sanitize_input($_POST['subject']),
        'message' => sanitize_input($_POST['message']),
        'status' => 'open',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Read existing tickets
    $tickets = [];
    if (file_exists('data/tickets.json')) {
        $tickets = $store->read('tickets');
    }
    
    $tickets[] = $ticket;
    $store->write('tickets', $tickets);
    
    $success_message = "Your support ticket has been submitted. We'll get back to you soon!";
}

include 'inc/header.php';
?>

<main class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Customer Support</h1>
            <p>Need help? We're here to assist you</p>
        </div>

        <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <div class="support-layout">
            <div class="support-form">
                <div class="card">
                    <h2>Submit a Support Ticket</h2>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required 
                                       value="<?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : ''; ?>">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <select id="subject" name="subject" required>
                                <option value="">Select a topic</option>
                                <option value="Order Issue">Order Issue</option>
                                <option value="Product Question">Product Question</option>
                                <option value="Shipping Inquiry">Shipping Inquiry</option>
                                <option value="Return/Refund">Return/Refund</option>
                                <option value="Technical Issue">Technical Issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="6" required 
                                      placeholder="Please describe your issue in detail..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Ticket</button>
                    </form>
                </div>
            </div>
            
            <div class="support-info">
                <div class="card">
                    <h3>Other Ways to Reach Us</h3>
                    <div class="contact-methods">
                        <div class="contact-item">
                            <strong>Email:</strong>
                            <p>support@yourstore.com</p>
                        </div>
                        <div class="contact-item">
                            <strong>Phone:</strong>
                            <p>1-800-123-4567</p>
                        </div>
                        <div class="contact-item">
                            <strong>Hours:</strong>
                            <p>Mon-Fri: 9AM-6PM EST</p>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Frequently Asked Questions</h3>
                    <div class="faq-list">
                        <div class="faq-item">
                            <strong>How long does shipping take?</strong>
                            <p>Standard shipping takes 3-5 business days.</p>
                        </div>
                        <div class="faq-item">
                            <strong>What's your return policy?</strong>
                            <p>We accept returns within 30 days of purchase.</p>
                        </div>
                        <div class="faq-item">
                            <strong>Do you offer international shipping?</strong>
                            <p>Currently we only ship within the United States.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
