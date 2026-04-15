const express = require("express");
const path = require("path");
const {
  initializePayment,
  submitPayment,
  getPaymentStatus,
  verifyWebhookSignature,
  BASE_URL,
} = require("./gateway");

const app = express();
const PORT = process.env.PORT || 3003;

app.use(express.json());

// API routes first
// Step 1: Initialize payment — returns available channels
app.post("/api/initialize", async (req, res) => {
  try {
    const { amount, currency, description } = req.body;
    const payment = await initializePayment({
      amount,
      currency: currency || "ETB",
      description: description || "Payment",
      reference: `order_${Date.now()}`,
      webhookUrl: `http://localhost:${PORT}/webhook`,
    });
    res.json(payment);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Step 2: Submit payment with chosen channel
app.post("/api/pay/:paymentId", async (req, res) => {
  try {
    console.log("Payment submission request body:", req.body);
    console.log("Payment ID:", req.params.paymentId);
    const { channel, phoneNumber, accountNumber } = req.body;
    const result = await submitPayment(req.params.paymentId, {
      channel,
      phoneNumber,
      accountNumber,
    });
    res.json(result);
  } catch (err) {
    console.error("Payment submission error:", err);
    res.status(500).json({ error: err.message });
  }
});

// Step 3: Poll payment status
app.get("/api/status/:paymentId", async (req, res) => {
  try {
    const status = await getPaymentStatus(req.params.paymentId);
    res.json(status);
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// Webhook handler - use raw body for signature verification
app.post("/webhook", express.raw({ type: "application/json" }), (req, res) => {
  const signature = req.headers["x-ghion-signature"];
  if (!signature) {
    return res.status(400).json({ error: "Missing signature" });
  }

  // req.body is a Buffer when using express.raw()
  const rawBody = req.body;

  try {
    if (!verifyWebhookSignature(rawBody, signature)) {
      return res.status(401).json({ error: "Invalid signature" });
    }
  } catch (err) {
    console.error("Signature verification error:", err);
    return res.status(401).json({ error: "Signature verification failed" });
  }

  // Parse after verification
  const event = JSON.parse(rawBody.toString());
  console.log("Webhook received:", event.event, event.data?.transaction_id);

  res.json({ received: true });
});

// Static files last
app.use(express.static(path.join(__dirname, "public")));

app.listen(PORT, () => {
  console.log(`API-only example running at http://localhost:${PORT}`);
});
