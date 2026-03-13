<?php

declare(strict_types=1);

namespace Ghion\HostedCheckout\Gateway;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Ghion Finances API Client
 */
class Client
{
    private HttpClient $httpClient;
    private string $apiKey;
    private string $apiSecret;
    private ?string $passphrase;
    private LoggerInterface $logger;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        ?string $passphrase = null,
        string $baseUrl = 'https://ghion.financial/api/v1',
        ?LoggerInterface $logger = null
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->passphrase = $passphrase;
        $this->logger = $logger ?? new NullLogger();

        $this->httpClient = new HttpClient([
            'base_uri' => rtrim($baseUrl, '/') . '/',
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Create a checkout session
     *
     * @param array<string, mixed> $data Session data
     * @return array<string, mixed> Session response
     * @throws GatewayException
     */
    public function createCheckoutSession(array $data): array
    {
        return $this->request('POST', 'checkout/initialize', $data);
    }

    /**
     * Get checkout session status
     *
     * @param string $sessionId Session ID
     * @return array<string, mixed> Session data
     * @throws GatewayException
     */
    public function getCheckoutSession(string $sessionId): array
    {
        return $this->request('GET', "checkout/{$sessionId}");
    }

    /**
     * Make an authenticated API request
     *
     * @param string $method HTTP method
     * @param string $path API endpoint path
     * @param array<string, mixed>|null $data Request body data
     * @return array<string, mixed> Response data
     * @throws GatewayException
     */
    private function request(string $method, string $path, ?array $data = null): array
    {
        $body = $data !== null ? json_encode($data) : '';

        // Server signs using getPathInfo() which returns the full path (e.g. /api/v1/checkout/initialize)
        $baseUri = (string) $this->httpClient->getConfig('base_uri');
        $basePath = parse_url($baseUri, PHP_URL_PATH) ?? '/';
        $signaturePath = rtrim($basePath, '/') . '/' . ltrim($path, '/');

        $authHeaders = Auth::generateAuthHeaders(
            $this->apiKey,
            $this->apiSecret,
            $method,
            $signaturePath,
            $body,
            $this->passphrase
        );

        try {
            $this->logger->info("Gateway API request", [
                'method' => $method,
                'path' => $path,
            ]);

            $options = ['headers' => $authHeaders];
            if ($body !== '') {
                $options['body'] = $body;
            }

            $response = $this->httpClient->request($method, $path, $options);
            $responseBody = (string)$response->getBody();
            $decoded = json_decode($responseBody, true);

            if (!is_array($decoded)) {
                throw new GatewayException('Invalid JSON response from API');
            }

            if (isset($decoded['data'])) {
                return $decoded['data'];
            }

            return $decoded;
        } catch (GuzzleException $e) {
            $this->logger->error("Gateway API error", [
                'method' => $method,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            throw new GatewayException(
                "API request failed: {$e->getMessage()}",
                (int)$e->getCode(),
                $e
            );
        }
    }
}
