import { expect, test } from "@playwright/test";

function portalResponse(data: unknown, message = "OK") {
  return JSON.stringify({ status: "Success", message, data });
}

const portalSession = {
  portal_token: "portal-token-e2e",
  expires_at: Date.now() + 60 * 60 * 1000,
  client: { id: "cli-rag-1", name: "Client RAG", email: "ragclient@test.test" },
};

test.describe("RAG portal chat widget", () => {
  test.beforeEach(async ({ page }) => {
    // Inject portal session so layout doesn't redirect to /portal/auth
    await page.addInitScript((session) => {
      window.localStorage.setItem(
        "koomky-portal-session",
        JSON.stringify(session)
      );
    }, portalSession);

    await page.route("**/api/v1/portal/**", async (route) => {
      const url = new URL(route.request().url());
      const path = url.pathname;

      if (path.endsWith("/portal/dashboard")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            client: portalSession.client,
            branding: { custom_logo: null, custom_color: null },
            outstanding_invoices: { count: 0, total: 0, currency: "EUR" },
            recent_invoices: [],
            recent_quotes: [],
            recent_payments: [],
          }),
        });
      }

      if (path.endsWith("/portal/rag/status")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({ available: true, indexed_documents: 3 }),
        });
      }

      if (path.endsWith("/portal/rag/ask")) {
        return route.fulfill({
          status: 200,
          contentType: "application/json",
          body: portalResponse({
            answer:
              "Le délai de paiement standard est de 30 jours à compter de la date de facturation.",
            sources: [
              {
                document_id: "doc-1",
                chunk_index: 0,
                score: 0.92,
                title: "CGV",
              },
            ],
            tokens_used: 120,
            latency_ms: 450,
          }),
        });
      }

      return route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({}),
      });
    });
  });

  test("chat widget button is visible when RAG is available", async ({
    page,
  }) => {
    await page.goto("/portal/invoices");

    // Wait for the RAG status call to resolve and widget to appear
    const chatButton = page
      .locator("button")
      .filter({ has: page.locator("svg") })
      .last();
    await expect(chatButton).toBeVisible({ timeout: 5000 });
  });

  test("opens chat panel and sends a question", async ({ page }) => {
    let askCalled = false;

    await page.route("**/api/v1/portal/rag/ask", async (route) => {
      askCalled = true;
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({
          answer: "Le délai de paiement standard est de 30 jours.",
          sources: [
            { document_id: "doc-1", chunk_index: 0, score: 0.92, title: "CGV" },
          ],
          tokens_used: 100,
          latency_ms: 300,
        }),
      });
    });

    await page.goto("/portal/invoices");

    // Open the chat widget (the floating MessageCircle button)
    const openButton = page.locator('[class*="rounded-full"]').last();
    await openButton.click();

    await expect(page.getByText("Assistant documentaire")).toBeVisible();

    // Type and submit a question
    const input = page.getByPlaceholder("Posez votre question...");
    await input.fill("Quel est le délai de paiement ?");
    await page.getByRole("button", { name: "" }).last().click(); // Send button

    await expect.poll(() => askCalled, { timeout: 5000 }).toBe(true);

    // Answer should appear in the chat
    await expect(
      page.getByText("Le délai de paiement standard est de 30 jours.")
    ).toBeVisible({ timeout: 5000 });
  });

  test("chat widget is hidden when RAG is unavailable", async ({ page }) => {
    await page.route("**/api/v1/portal/rag/status", async (route) => {
      await route.fulfill({
        status: 200,
        contentType: "application/json",
        body: portalResponse({ available: false, indexed_documents: 0 }),
      });
    });

    await page.goto("/portal/invoices");

    // The MessageCircle toggle button should NOT be present
    await expect(page.getByText("Assistant documentaire")).not.toBeVisible();
  });
});
