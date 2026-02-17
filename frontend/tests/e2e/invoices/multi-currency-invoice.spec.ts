import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Multi-currency invoices", () => {
  test("submits invoice payload in selected currency with converted estimate hint", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices/create");

    await expect(
      page.getByRole("heading", { name: "Create invoice", exact: true })
    ).toBeVisible();

    await page.getByLabel("Client", { exact: true }).selectOption({
      label: "Acme",
    });
    await expect(page.getByLabel("Currency", { exact: true })).toHaveValue(
      "USD"
    );
    await expect(page.getByLabel("Currency", { exact: true })).toBeVisible();
    await expect(page.getByText(/estimated in .*: /i).first()).toBeVisible();

    await page.locator("#line-description-0").fill("  Consulting session  ");
    await page.locator("#line-unit-price-0").fill("110");

    const createRequestPromise = page.waitForRequest(
      (request) =>
        request.method() === "POST" &&
        request.url().includes("/api/v1/invoices")
    );

    await page.getByRole("button", { name: "Save draft", exact: true }).click();

    const createRequest = await createRequestPromise;
    const payload = createRequest.postDataJSON() as {
      currency: string;
      line_items: Array<{ description: string }>;
    };

    expect(payload.currency).toBe("USD");
    expect(payload.line_items[0]?.description).toBe("Consulting session");
    await expect(page).toHaveURL(/\/invoices$/);
  });
});
