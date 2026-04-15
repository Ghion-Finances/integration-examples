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
     * @param string $passphrase API passphrase (use the RAW value shown when generated, do NOT base64-encode)
     * @return array<string, string> Headers array
     */
    public static function generateAuthHeaders(
        string $apiKey,
        string $apiSecret,
        string $method,
        string $path,
        string $body = '',
        string $passphrase = ''
    ): array {
        $timestamp = (int)time();
        $signature = self::generateSignature($timestamp, $method, $path, $body, $apiSecret);

        return [
            'X-Ghion-Key' => $apiKey,
            'X-Ghion-Timestamp' => (string)$timestamp,
            'X-Ghion-Signature' => $signature,
            'X-Ghion-Passphrase' => $passphrase,
        ];
    }

    /**
     * Verify webhook signature
     *
     * The signature is computed on the raw request body (industry-standard approach).
     * Always verify BEFORE parsing JSON to ensure byte-exact comparison.
     *
     * @param string $rawBody Raw webhook payload (before JSON parsing)
     * @param string $signature Signature from X-Ghion-Signature header
     * @param string $secret API secret
     * @return bool True if signature is valid
     */
    public static function verifyWebhookSignature(
        string $rawBody,
        string $signature,
        string $secret
    ): bool {
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));

        return hash_equals($expected, $signature);
    }
}
