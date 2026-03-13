<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ghion\HostedCheckout\Model\Product;

$products = Product::getSampleProducts();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ethiopian Artisan Market - Hosted Checkout Example</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>🇪🇹 Ethiopian Artisan Market</h1>
            <p class="subtitle">Hosted Checkout Integration Example</p>
        </div>
    </header>

    <main class="container">
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product->imageUrl): ?>
                        <img src="<?= htmlspecialchars($product->imageUrl) ?>" 
                             alt="<?= htmlspecialchars($product->name) ?>"
                             class="product-image">
                    <?php endif; ?>
                    
                    <div class="product-info">
                        <h3><?= htmlspecialchars($product->name) ?></h3>
                        <p class="product-description">
                            <?= htmlspecialchars($product->description) ?>
                        </p>
                        <div class="product-footer">
                            <span class="price"><?= htmlspecialchars($product->getPriceFormatted()) ?></span>
                            <a href="/checkout.php?product_id=<?= urlencode($product->id) ?>" 
                               class="btn btn-primary">
                                Buy Now
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info-box">
            <h2>About This Example</h2>
            <p>
                This is a production-ready example of <strong>Hosted Checkout</strong> integration 
                with Ghion Finances. When you click "Buy Now", you'll be redirected to a 
                secure hosted payment page.
            </p>
            <h3>Integration Flow:</h3>
            <ol>
                <li>Customer selects a product</li>
                <li>Server creates a checkout session via API</li>
                <li>Customer is redirected to hosted checkout page</li>
                <li>After payment, customer returns to your site</li>
                <li>Webhook confirms payment status</li>
            </ol>
            <p class="note">
                💡 <strong>Note:</strong> This example uses test mode. No real payments will be processed.
            </p>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>Ghion Finances - Hosted Checkout Example</p>
            <p>
                <a href="https://github.com/your-org/gateway/tree/main/integration-examples/hosted-checkout-example">
                    View Source Code
                </a>
            </p>
        </div>
    </footer>
</body>
</html>
