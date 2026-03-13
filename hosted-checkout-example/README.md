# Hosted Checkout Example (PHP)

Redirect customers to a secure hosted payment page. Simplest integration method.

## Setup

```bash
composer install
cp .env.example .env   # fill in your API credentials
php -S localhost:3001 -t public
```

Visit http://localhost:3001

## Flow

1. Customer clicks "Buy Now" → `checkout.php` creates a session via API
2. Customer is redirected to the hosted checkout page
3. After payment, customer returns to `return.php`
4. `webhook.php` receives payment confirmation

## Key Files

| File | Purpose |
|------|---------|
| `public/index.php` | Product catalog |
| `public/checkout.php` | Creates session, redirects to checkout |
| `public/return.php` | Handles customer return |
| `public/webhook.php` | Verifies and processes webhooks |
| `src/Gateway/Auth.php` | HMAC signature generation |
| `src/Gateway/Client.php` | API client |

## Environment Variables

```env
GHION_API_KEY=your_api_key
GHION_API_SECRET=your_api_secret
GHION_API_PASSPHRASE=your_passphrase
GHION_BASE_URL=https://ghion.financial/api/v1
GHION_MODE=test
```
