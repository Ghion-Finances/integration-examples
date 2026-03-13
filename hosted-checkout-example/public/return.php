<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ghion\HostedCheckout\Gateway\Client;
use Ghion\HostedCheckout\Gateway\GatewayException;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..', ['.env', '../.env']);
$dotenv->load();

// Setup logger
$logger = new Logger('return');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::INFO));

// Get return parameters
$sessionId = $_GET['session_id'] ?? null;
$status = $_GET['status'] ?? 'unknown';

$logger->info('Customer returned from checkout', [
    'session_id' => $sessionId,
    'status' => $status,
]);

// Initialize Ghion client
$gateway = new Client(
    $_ENV['GHION_API_KEY'],
    $_ENV['GHION_API_SECRET'],
    $_ENV['GHION_API_PASSPHRASE'] ?? null,
    $_ENV['GHION_BASE_URL'],
    $logger
);

// Verify session status server-side
$sessionData = null;
if ($sessionId) {
    try {
        $sessionData = $gateway->getCheckoutSession($sessionId);
        $status = $sessionData['status'];
    } catch (GatewayException $e) {
        $logger->error('Failed to fetch session status', [
            'session_id' => $sessionId,
            'error' => $e->getMessage(),
        ]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment <?= ucfirst($status) ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="container">
        <?php if ($status === 'completed'): ?>
            <div class="success-box">
                <div class="icon">✅</div>
                <h1>Payment Successful!</h1>
                <p>Thank you for your purchase. Your order has been confirmed.</p>
                
                <?php if ($sessionData): ?>
                    <div class="order-details">
                        <h3>Order Details</h3>
                        <table>
                            <tr>
                                <td><strong>Reference:</strong></td>
                                <td><?= htmlspecialchars($sessionData['reference']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Amount:</strong></td>
                                <td><?= number_format($sessionData['amount'], 2) ?> <?= htmlspecialchars($sessionData['currency']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Description:</strong></td>
                                <td><?= htmlspecialchars($sessionData['description'] ?? 'N/A') ?></td>
                            </tr>
                            <tr>
                                <td><strong>Session ID:</strong></td>
                                <td><code><?= htmlspecialchars($sessionData['id']) ?></code></td>
                            </tr>
                        </table>
                    </div>
                <?php endif; ?>

                <p class="note">
                    📧 A confirmation email has been sent to your email address.
                </p>
                
                <a href="/" class="btn btn-primary">Continue Shopping</a>
            </div>

        <?php elseif ($status === 'pending' || $status === 'processing'): ?>
            <div class="pending-box">
                <div class="icon">⏳</div>
                <h1>Payment Processing</h1>
                <p>Your payment is being processed. This may take a few moments.</p>
                
                <?php if ($sessionId): ?>
                    <p class="note">
                        Session ID: <code><?= htmlspecialchars($sessionId) ?></code>
                    </p>
                <?php endif; ?>

                <p>
                    We'll send you a confirmation email once the payment is complete.
                    You can safely close this page.
                </p>
                
                <a href="/" class="btn btn-secondary">Back to Home</a>
            </div>

        <?php else: ?>
            <div class="error-box">
                <div class="icon">❌</div>
                <h1>Payment <?= $status === 'cancelled' ? 'Cancelled' : 'Failed' ?></h1>
                
                <?php if ($status === 'cancelled'): ?>
                    <p>You cancelled the payment. No charges were made.</p>
                <?php else: ?>
                    <p>We couldn't process your payment. Please try again.</p>
                <?php endif; ?>

                <?php if ($sessionId): ?>
                    <p class="note">
                        Session ID: <code><?= htmlspecialchars($sessionId) ?></code>
                    </p>
                <?php endif; ?>

                <div class="actions">
                    <a href="/" class="btn btn-primary">Back to Products</a>
                    <?php if ($sessionData && isset($sessionData['metadata']['product_id'])): ?>
                        <a href="/checkout.php?product_id=<?= urlencode($sessionData['metadata']['product_id']) ?>" 
                           class="btn btn-secondary">
                            Try Again
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="info-box">
            <h3>Important Notes</h3>
            <ul>
                <li>
                    <strong>Security:</strong> Always verify payment status server-side via webhook.
                    The return URL parameters can be manipulated by users.
                </li>
                <li>
                    <strong>Webhooks:</strong> Your server will receive a webhook notification
                    confirming the final payment status.
                </li>
                <li>
                    <strong>Test Mode:</strong> This example runs in test mode. No real payments
                    are processed.
                </li>
            </ul>
        </div>
    </div>

    <footer>
        <div class="container">
            <p>Ghion Finances - Hosted Checkout Example</p>
        </div>
    </footer>
</body>
</html>
