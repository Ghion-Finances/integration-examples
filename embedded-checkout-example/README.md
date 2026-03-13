# Embedded Checkout Example (Node.js)

Keep customers on your site with an embedded checkout modal using the Ghion JS SDK.

## Setup

```bash
npm install
cp .env.example .env   # fill in your API credentials
npm start
```

Visit http://localhost:3002

## Flow

1. Customer clicks "Pay Now" → frontend calls your server (`POST /api/create-session`)
2. Server creates a checkout session via Ghion API (with HMAC auth)
3. Frontend opens `Ghion.checkout({ sessionId })` modal — no redirect
4. SDK fires `onSuccess` / `onFailure` / `onClose` callbacks
5. `POST /webhook` receives payment confirmation server-side

## Key Files

| File | Purpose |
|------|---------|
| `server.js` | Express server — session creation + webhook |
| `gateway.js` | HMAC auth + Ghion API client |
| `public/index.html` | Frontend — loads Ghion SDK, opens modal |

## Environment Variables

```env
GHION_API_KEY=your_api_key
GHION_API_SECRET=your_api_secret
GHION_API_PASSPHRASE=your_passphrase
GHION_BASE_URL=https://ghion.financial/api/v1
GHION_MODE=test
PORT=3002
```
