<?php

declare(strict_types=1);

namespace Ghion\HostedCheckout\Gateway;

/**
 * Authentication utilities for Ghion Finances
 * Implements HMAC-SHA256 signature generation
 */
class Auth
{
    /**
     * Generate HMAC-SHA256 signature for API requests
     *
     * @param int $timestamp Unix timestamp in milliseconds
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $path API endpoint path
     * @param string $body Request body (JSON string)
     * @param string $secret API secret key
     * @return string Base64-encoded signature
     */
    private static function generateSignature(
        int $timestamp,
        string $method,
        string $path,
        string $body,
        string $secret
    ): string {
        $message = $timestamp . $method . $path . $body;
        return base64_encode(hash_hmac('sha256', $message, $secret, true));
    }

    /**
     * Generate authentication headers for API requests
     *
     * @param string $apiKey API key
     * @param string $apiSecret API secret
     * @param string $method HTTP method
     * @param string $path API endpoint path
     * @param string $body Request body
     * @param string|null $passphrase Optional passphrase
     * @return array<string, string> Headers array
     */
    public static function generateAuthHeaders(
        string $apiKey,
        string $apiSecret,
        string $method,
        string $path,
        string $body = '',
        ?string $passphrase = null
    ): array {
        $timestamp = (int)time();
        $signature = self::generateSignature($timestamp, $method, $path, $body, $apiSecret);

        $headers = [
            'X-Ghion-Key' => $apiKey,
            'X-Ghion-Timestamp' => (string)$timestamp,
            'X-Ghion-Signature' => $signature,
        ];

        if ($passphrase !== null) {
            $headers['X-Ghion-Passphrase'] = $passphrase;
        }

        return $headers;
    }

    /**
     * Verify webhook signature
     *
     * @param string $payload Webhook payload (raw JSON string)
     * @param string $signature Signature from X-Ghion-Signature header
     * @param string $secret API secret
     * @return bool True if signature is valid
     */
    public static function verifyWebhookSignature(
        string $payload,
        string $signature,
        string $secret
    ): bool {
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $payload, $secret, true)
        );

        return hash_equals($expectedSignature, $signature);
    }
}
