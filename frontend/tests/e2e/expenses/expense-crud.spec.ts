import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Expenses CRUD", () => {
  test("expenses list and create pages are reachable", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/expenses");

    await expect(
      page.getByRole("heading", { name: "Expenses", exact: true })
    ).toBeVisible();

    await page.getByRole("link", { name: "Quick add" }).click();

    await expect(
      page.getByRole("heading", { name: "Create expense", exact: true })
    ).toBeVisible();
  });
});
