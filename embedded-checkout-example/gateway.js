const crypto = require("crypto");
require("dotenv").config();

const API_KEY = process.env.GHION_API_KEY;
const API_SECRET = process.env.GHION_API_SECRET;
// PASSPHRASE: Use the raw value shown when you generated credentials.
// Do NOT base64-encode it - the API expects the raw passphrase.
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

async function createCheckoutSession({ amount, currency, description, reference, returnUrl, webhookUrl }) {
  const data = JSON.stringify({
    amount,
    currency,
    description,
    reference,
    return_url: returnUrl,
    webhook_url: webhookUrl,
  });

  const fullPath = "/checkout/initialize";
  const res = await fetch(`${BASE_URL}${fullPath}`, {
    method: "POST",
    headers: authHeaders("POST", `/api/v1${fullPath}`, data),
    body: data,
  });

  const json = await res.json();
  if (!res.ok) {
    throw new Error(json.error?.message || "Failed to create session");
  }
  return json;
}

/**
 * Verify webhook signature
 * The signature is computed on the raw request body (industry-standard approach).
 * Always verify BEFORE parsing JSON to ensure byte-exact comparison.
 *
 * @param {string|Buffer} rawBody - Raw request body from webhook
 * @param {string} signature - Signature from X-Ghion-Signature header
 * @returns {boolean} True if signature is valid
 */
function verifyWebhookSignature(rawBody, signature) {
  const expected = crypto
    .createHmac("sha256", API_SECRET)
    .update(rawBody)
    .digest("base64");

  return crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature));
}

module.exports = { createCheckoutSession, verifyWebhookSignature, BASE_URL };
