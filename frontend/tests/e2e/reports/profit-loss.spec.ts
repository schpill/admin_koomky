import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Profit & loss report", () => {
  test("p&l page is reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/reports/profit-loss");

    await expect(
      page.getByRole("heading", { name: "Profit & loss report", exact: true })
    ).toBeVisible();
  });
});
