<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ghion\HostedCheckout\Gateway\Client;
use Ghion\HostedCheckout\Gateway\GatewayException;
use Ghion\HostedCheckout\Model\Product;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..', ['.env', '../.env']);
$dotenv->load();

// Setup logger
$logger = new Logger('checkout');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::INFO));

// Get product
$productId = $_GET['product_id'] ?? null;
if (!$productId) {
    http_response_code(400);
    die('Product ID is required');
}

$product = Product::findById($productId);
if (!$product) {
    http_response_code(404);
    die('Product not found');
}

// Initialize Ghion client
$gateway = new Client(
    $_ENV['GHION_API_KEY'],
    $_ENV['GHION_API_SECRET'],
    $_ENV['GHION_API_PASSPHRASE'] ?? null,
    $_ENV['GHION_BASE_URL'],
    $logger
);

try {
    // Create checkout session
    $appUrl = rtrim($_ENV['APP_URL'], '/');
    $orderReference = 'order_' . uniqid();
    
    $logger->info('Creating checkout session', [
        'product_id' => $product->id,
        'reference' => $orderReference,
    ]);

    $session = $gateway->createCheckoutSession([
        'amount' => $product->priceInCents,
        'currency' => $product->currency,
        'description' => $product->name,
        'reference' => $orderReference,
        'return_url' => $appUrl . '/return.php',
        'cancel_url' => $appUrl . '/',
        'webhook_url' => $appUrl . '/webhook.php',
        'metadata' => [
            'product_id' => $product->id,
            'product_name' => $product->name,
        ],
    ]);

    $logger->info('Checkout session created', [
        'session_id' => $session['id'],
        'checkout_url' => $session['checkout_url'],
    ]);

    // Redirect to hosted checkout page
    header('Location: ' . $session['checkout_url']);
    exit;

} catch (GatewayException $e) {
    $logger->error('Checkout session creation failed', [
        'error' => $e->getMessage(),
        'product_id' => $product->id,
    ]);

    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Checkout Error</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-box">
                <h1>⚠️ Checkout Error</h1>
                <p>We encountered an error while creating your checkout session.</p>
                <p class="error-message"><?= htmlspecialchars($e->getMessage()) ?></p>
                <a href="/" class="btn btn-primary">Back to Products</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
