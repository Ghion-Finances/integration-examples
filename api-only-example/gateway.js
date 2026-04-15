const crypto = require("crypto");

const API_KEY = process.env.GHION_API_KEY;
const API_SECRET = process.env.GHION_API_SECRET;
const PASSPHRASE = process.env.GHION_API_PASSPHRASE;
const BASE_URL = process.env.GHION_BASE_URL || "https://ghion.financial/api/v1";

function generateSignature(timestamp, method, path, body) {
  const message = `${timestamp}${method.toUpperCase()}${path}${body}`;
  return crypto
    .createHmac("sha256", API_SECRET)
    .update(message)
    .digest("base64");
}

function authHeaders(method, path, body = "") {
  const timestamp = Math.floor(Date.now() / 1000); // Convert to seconds
  const signature = generateSignature(timestamp, method, path, body);

  return {
    "Content-Type": "application/json",
    "X-Ghion-Key": API_KEY,
    "X-Ghion-Timestamp": String(timestamp),
    "X-Ghion-Signature": signature,
    "X-Ghion-Passphrase": PASSPHRASE,
  };
}

async function apiRequest(method, path, data) {
  const body = data ? JSON.stringify(data) : "";
  const fullPath = `/api/v1${path}`;
  console.log(`API Request: ${method} ${BASE_URL}${path}`);
  console.log(`Full path for signature: ${fullPath}`);
  console.log(`Body: ${body}`);
  const res = await fetch(`${BASE_URL}${path}`, {
    method,
    headers: authHeaders(method, fullPath, body),
    body: body || undefined,
  });
  
  // Get response as text first to handle potential HTML errors
  const text = await res.text();
  console.log(`Response status: ${res.status}`);
  console.log(`Response body: ${text.substring(0, 200)}...`);
  
  // Try to parse as JSON, fallback to error message
  let json;
  try {
    json = JSON.parse(text);
  } catch (e) {
    // If it's HTML, extract a meaningful error message
    if (text.includes('<')) {
      throw new Error(`API returned HTML error page (status ${res.status}). This usually means the payment session has expired or is invalid. Please create a new payment session.`);
    }
    throw new Error(`Invalid response from API: ${text.substring(0, 100)}`);
  }
  
  if (!res.ok) throw new Error(json.error?.message || `API ${res.status}`);
  return json;
}

// Step 1: Initialize a payment — returns available channels
async function initializePayment({ amount, currency, reference, description, webhookUrl }) {
  return apiRequest("POST", "/checkout/initialize", {
    amount,
    currency,
    reference,
    description,
    webhook_url: webhookUrl,
  });
}

// Step 2: Submit payment with chosen channel and customer details
async function submitPayment(paymentId, { channel, phoneNumber, accountNumber }) {
  const body = {};
  if (phoneNumber) body.phone_number = phoneNumber;
  if (accountNumber) body.account_number = accountNumber;
  return apiRequest("POST", `/checkout/${paymentId}/pay/${channel}`, body);
}

// Step 3: Poll payment status
async function getPaymentStatus(paymentId) {
  return apiRequest("GET", `/checkout/${paymentId}`);
}

function verifyWebhookSignature(payload, signature) {
  const expected = crypto
    .createHmac("sha256", API_SECRET)
    .update(payload)
    .digest("base64");
  return crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature));
}

module.exports = {
  initializePayment,
  submitPayment,
  getPaymentStatus,
  verifyWebhookSignature,
  BASE_URL,
};
