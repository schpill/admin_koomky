import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Recurring invoices", () => {
  test("recurring invoice list page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices/recurring");

    await expect(
      page.getByRole("heading", { name: "Recurring invoices", exact: true })
    ).toBeVisible();
  });
});
