import { test, expect } from "@playwright/test";
import {
  mockProtectedApi,
  seedAuthenticatedSession,
} from "../helpers/session";

test.describe("Invoice PDF", () => {
  test("invoice page exposes pdf preview section", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices");

    await expect(page.getByText("PDF preview")).toBeVisible();
  });
});
