const express = require("express");
const path = require("path");
const { createCheckoutSession, verifyWebhookSignature, BASE_URL } = require("./gateway");

const app = express();
const PORT = process.env.PORT || 3002;

app.use(express.json());
app.use(express.static(path.join(__dirname, "public")));

// POST /api/create-session — called by the frontend to get a session ID
app.post("/api/create-session", async (req, res) => {
  try {
    const { amount, currency, description } = req.body;

    const session = await createCheckoutSession({
      amount,
      currency: currency || "ETB",
      description: description || "Payment",
      reference: `order_${Date.now()}`,
      returnUrl: `http://localhost:${PORT}`,
      webhookUrl: `http://localhost:${PORT}/webhook`,
    });

    res.json({ 
      sessionId: session.id, 
      checkoutUrl: session.checkout_url,
    });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

// POST /webhook — receives payment confirmations from Ghion
app.post("/webhook", express.raw({ type: "application/json" }), (req, res) => {
  const signature = req.headers["x-ghion-signature"];
  const payload = typeof req.body === "string" ? req.body : JSON.stringify(req.body);

  if (!signature) {
    return res.status(400).json({ error: "Missing signature" });
  }

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

app.listen(PORT, () => {
  console.log(`Embedded checkout example running at http://localhost:${PORT}`);
});
