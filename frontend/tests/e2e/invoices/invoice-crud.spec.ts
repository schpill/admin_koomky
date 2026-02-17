import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Invoices CRUD", () => {
  test("invoice list page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices");

    await expect(
      page.getByRole("heading", { name: "Invoices", exact: true })
    ).toBeVisible();
  });
});
