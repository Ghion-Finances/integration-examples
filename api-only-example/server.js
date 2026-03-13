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

// Webhook handler
app.post("/webhook", express.raw({ type: "application/json" }), (req, res) => {
  const signature = req.headers["x-ghion-signature"];
  const payload = typeof req.body === "string" ? req.body : JSON.stringify(req.body);

  if (!signature) return res.status(400).json({ error: "Missing signature" });

  try {
    if (!verifyWebhookSignature(payload, signature)) {
      return res.status(401).json({ error: "Invalid signature" });
    }
  } catch {
    return res.status(401).json({ error: "Signature verification failed" });
  }

  const event = JSON.parse(payload);
  res.json({ received: true });
});

// Static files last
app.use(express.static(path.join(__dirname, "public")));

app.listen(PORT, () => {
  console.log(`API-only example running at http://localhost:${PORT}`);
});
