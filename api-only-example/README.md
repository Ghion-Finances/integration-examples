# API-Only Integration Example (Node.js)

Full control over the payment UI. You build the form, Ghion processes the payment.

## Setup

```bash
npm install
cp .env.example .env   # fill in your API credentials
npm start
```

Visit http://localhost:3003

## Flow

1. **Initialize** — `POST /api/initialize` creates a payment, returns available channels
2. **Present channels** — your UI shows Telebirr, CBE Birr, etc. with required input fields
3. **Submit** — `POST /api/pay/:id` submits the chosen channel + phone/account number
4. **Confirm** — `GET /api/status/:id` polls until `completed` or `failed`
5. **Webhook** — `POST /webhook` receives server-side confirmation

## Key Files

| File | Purpose |
|------|---------|
| `server.js` | Express server — all API proxy routes + webhook |
| `gateway.js` | HMAC auth + Ghion API client (init, submit, status) |
| `public/index.html` | Custom payment UI — channel picker, status polling |

## Environment Variables

```env
GHION_API_KEY=your_api_key
GHION_API_SECRET=your_api_secret
GHION_API_PASSPHRASE=your_passphrase
GHION_BASE_URL=https://ghion.financial/api/v1
GHION_MODE=test
PORT=3003
```
