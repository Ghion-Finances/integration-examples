# Ghion Finances — Integration Examples

Three ways to accept payments with Ghion Finances. Each example is self-contained and runnable.

## Examples

| Example | Tech | Port | Approach |
|---------|------|------|----------|
| **[Hosted Checkout](./hosted-checkout-example/)** | PHP | 3001 | Redirect to a hosted payment page (easiest) |
| **[Embedded Checkout](./embedded-checkout-example/)** | Node.js | 3002 | Modal on your site via Ghion JS SDK |
| **[API-Only](./api-only-example/)** | Node.js | 3003 | Full control — you build the UI, we process |

## Quick Start (Docker)

Run all three examples with one command:

```bash
# 1. Configure credentials for each example
for d in hosted-checkout-example embedded-checkout-example api-only-example; do
  cp $d/.env.example $d/.env
done
# Edit each .env with your Ghion API credentials

# 2. Start all examples
docker compose up --build

# 3. Visit
# Hosted Checkout:  http://localhost:3001
# Embedded Checkout: http://localhost:3002
# API-Only:          http://localhost:3003
```

Run a single example:

```bash
docker compose up hosted-checkout      # just PHP example
docker compose up embedded-checkout    # just embedded modal
docker compose up api-only             # just API-only
```

### Without Docker

```bash
cd hosted-checkout-example && composer install && php -S localhost:3001 -t public
cd embedded-checkout-example && npm install && npm start
cd api-only-example && npm install && npm start
```

## Prerequisites

- Docker (recommended) or PHP 8.3+ / Node.js 18+ for local dev
- API credentials from your Ghion Finances dashboard at `https://ghion.financial`

## Project Structure

```
integration-examples/
├── hosted-checkout-example/      # PHP — redirect flow
│   ├── public/                   #   index, checkout, return, webhook
│   ├── src/Gateway/              #   Auth.php, Client.php
│   └── .env.example
├── embedded-checkout-example/    # Node.js — Ghion SDK modal
│   ├── public/index.html         #   loads Ghion SDK, opens modal
│   ├── server.js                 #   session creation + webhook
│   ├── gateway.js                #   HMAC auth + API client
│   └── .env.example
├── api-only-example/             # Node.js — custom payment UI
│   ├── public/index.html         #   channel picker, status polling
│   ├── server.js                 #   init, pay, status + webhook
│   ├── gateway.js                #   HMAC auth + API client
│   └── .env.example
├── LICENSE
└── README.md
```

## Authentication

All examples implement HMAC-SHA256 request signing:

```
signature = base64( hmac_sha256( timestamp + METHOD + path + body, api_secret ) )
```

Headers sent with every API request:

| Header | Value |
|--------|-------|
| `X-Ghion-Key` | Your API key |
| `X-Ghion-Timestamp` | Unix timestamp (seconds) |
| `X-Ghion-Signature` | HMAC-SHA256 signature |
| `X-Ghion-Passphrase` | Your API passphrase |

**Note:** The timestamp must be within **30 seconds** of the server time. Ensure your system clock is synchronized via NTP.

## API Endpoints Used

| Endpoint | Method | Used By |
|----------|--------|---------|
| `/checkout/initialize` | POST | All three examples |
| `/checkout/{id}` | GET | Hosted + API-only |
| `/checkout/{id}/pay` | POST | API-only |

## Environment Variables

Same across all examples:

```env
GHION_API_KEY=your_api_key
GHION_API_SECRET=your_api_secret
GHION_API_PASSPHRASE=your_passphrase
GHION_BASE_URL=https://ghion.financial/api/v1
GHION_MODE=test
```

## License

MIT
