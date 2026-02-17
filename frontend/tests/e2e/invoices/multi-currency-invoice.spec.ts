import { test, expect } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Multi-currency invoices", () => {
  test("invoice create page exposes currency selector and conversion hint", async ({
    page,
  }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/invoices/create");

    await expect(
      page.getByRole("heading", { name: "Create invoice", exact: true })
    ).toBeVisible();
    await expect(page.getByLabel("Currency", { exact: true })).toBeVisible();
    await expect(page.getByText(/estimated in .*: /i).first()).toBeVisible();
  });
});
