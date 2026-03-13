<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Ghion\HostedCheckout\Gateway\Auth;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..', ['.env', '../.env']);
$dotenv->load();

// Setup logger
$logger = new Logger('webhook');
$logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/webhook.log', Logger::INFO));

// Get raw POST body
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_GHION_SIGNATURE'] ?? '';

$logger->info('Webhook received', [
    'signature_present' => !empty($signature),
    'payload_length' => strlen($payload),
]);

// Verify webhook signature
if (!Auth::verifyWebhookSignature($payload, $signature, $_ENV['GHION_API_SECRET'])) {
    $logger->error('Invalid webhook signature', [
        'signature' => $signature,
    ]);
    
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Parse webhook data
$data = json_decode($payload, true);
if (!is_array($data)) {
    $logger->error('Invalid webhook payload', [
        'payload' => $payload,
    ]);
    
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$logger->info('Webhook verified', [
    'event_type' => $data['type'] ?? 'unknown',
    'event_id' => $data['id'] ?? null,
]);

// Process webhook event
try {
    $eventType = $data['type'] ?? null;
    $transaction = $data['data']['transaction'] ?? null;

    if (!$transaction) {
        throw new Exception('Transaction data missing from webhook');
    }

    $logger->info('Processing webhook event', [
        'event_type' => $eventType,
        'transaction_id' => $transaction['id'] ?? null,
        'status' => $transaction['status'] ?? null,
        'reference' => $transaction['reference'] ?? null,
    ]);

    // Handle different event types
    switch ($eventType) {
        case 'transaction.completed':
            handleTransactionCompleted($transaction, $logger);
            break;

        case 'transaction.failed':
            handleTransactionFailed($transaction, $logger);
            break;

        case 'transaction.pending':
            handleTransactionPending($transaction, $logger);
            break;

        default:
            $logger->warning('Unhandled webhook event type', [
                'event_type' => $eventType,
            ]);
    }

    // Respond with success
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $logger->error('Webhook processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    http_response_code(500);
    echo json_encode(['error' => 'Processing failed']);
}

/**
 * Handle completed transaction
 */
function handleTransactionCompleted(array $transaction, Logger $logger): void
{
    $logger->info('Transaction completed', [
        'transaction_id' => $transaction['id'],
        'reference' => $transaction['reference'],
        'amount' => $transaction['amount'],
    ]);

    // TODO: In production, you would:
    // 1. Update order status in database
    // 2. Send confirmation email to customer
    // 3. Trigger fulfillment process
    // 4. Update inventory
    // 5. Generate invoice/receipt

    // Example: Save to database
    // $db->query('UPDATE orders SET status = ? WHERE reference = ?', [
    //     'completed',
    //     $transaction['reference']
    // ]);

    // Example: Send email
    // $mailer->send([
    //     'to' => $customer['email'],
    //     'subject' => 'Payment Confirmed',
    //     'template' => 'payment-success',
    //     'data' => $transaction,
    // ]);
}

/**
 * Handle failed transaction
 */
function handleTransactionFailed(array $transaction, Logger $logger): void
{
    $logger->warning('Transaction failed', [
        'transaction_id' => $transaction['id'],
        'reference' => $transaction['reference'],
    ]);

    // TODO: In production, you would:
    // 1. Update order status to failed
    // 2. Notify customer of failure
    // 3. Optionally send retry link
}

/**
 * Handle pending transaction
 */
function handleTransactionPending(array $transaction, Logger $logger): void
{
    $logger->info('Transaction pending', [
        'transaction_id' => $transaction['id'],
        'reference' => $transaction['reference'],
    ]);

    // TODO: In production, you would:
    // 1. Update order status to pending
    // 2. Optionally notify customer to complete payment
}
