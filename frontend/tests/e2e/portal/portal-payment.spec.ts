import { test, expect } from "@playwright/test";
import { portalResponse, seedPortalSession } from "../helpers/session";

test.describe("Portal Payment Flow", () => {
  test("user can complete a payment workflow", async ({ page }) => {
    await seedPortalSession(page);

    let paymentIntentCreated = false;
    let paymentConfirmed = false;

    // Mock all necessary API endpoints
    await page.route("**/api/v1/portal/invoices/inv_1/pay", async (route) => {
      expect(route.request().method()).toBe("POST");
      paymentIntentCreated = true;
      await route.fulfill({
        status: 201,
        contentType: "application/json",
        body: portalResponse({
          client_secret: "pi_123_secret_456",
        }),
      });
    });

    await page.route(
      "https://api.stripe.com/v1/payment_intents/**/confirm",
      async (route) => {
        paymentConfirmed = true;
        await route.fulfill({
          status: 200,
          contentType: "application/json",
          body: JSON.stringify({
            id: "pi_123",
            object: "payment_intent",
            status: "succeeded",
          }),
        });
      }
    );

    // Mock the invoice detail endpoint to show it as unpaid initially
    await page.route("**/api/v1/portal/invoices/inv_1", (route) =>
      route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({
          id: "inv_1",
          number: "INV-001",
          total: 120,
          currency: "EUR",
          status: "sent",
        }),
      })
    );

    await page.goto("/portal/invoices/inv_1/pay");

    await expect(
      page.getByRole("heading", { name: "Pay Invoice INV-001" })
    ).toBeVisible();

    // The component should display a "loading" or "missing config" state
    // if Stripe keys aren't loaded. We'll assume for the test they are.
    await expect(
      page.getByText("Stripe publishable key is missing")
    ).not.toBeVisible();

    // Playwright cannot directly interact with the Stripe iframe,
    // so we mock the Stripe.js confirmPayment call or the API call it makes.
    // The test will succeed if the frontend code attempts to confirm the payment.

    // Simulate clicking the pay button. The test will pass if the confirm endpoint is called.
    await page.getByRole("button", { name: "Pay €120.00" }).click();

    // Wait for a navigation or a confirmation message.
    // Since we mocked the confirm call, we'll check if it was called.
    await page.waitForResponse(
      "https://api.stripe.com/v1/payment_intents/**/confirm"
    );

    expect(paymentIntentCreated).toBe(true);
    expect(paymentConfirmed).toBe(true);

    // After payment, the user should be redirected to a success page or back to the invoice
    await expect(page).toHaveURL(/.*\/portal\/invoices\/inv_1/);
    await expect(page.getByText("Payment Successful")).toBeVisible();
    await expect(page.getByText("paid")).toBeVisible();
  });
});
