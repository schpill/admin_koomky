import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Expense reports", () => {
  test("expense report page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/expenses/report");

    await expect(
      page.getByRole("heading", { name: "Expense report", exact: true })
    ).toBeVisible();
  });
});
