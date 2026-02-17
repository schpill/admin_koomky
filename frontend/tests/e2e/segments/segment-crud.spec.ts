import { test, expect } from "@playwright/test";
import {
  mockProtectedApi,
  seedAuthenticatedSession,
} from "../helpers/session";

test.describe("Segment CRUD", () => {
  test("create segment and redirect to edit page", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/campaigns/segments/create");

    await page.fill("#segment-name", "VIP clients");
    await page.click('button:has-text("Save segment")');

    await expect(page).toHaveURL(/\/campaigns\/segments\/seg_1\/edit/);
    await expect(
      page.getByRole("heading", { name: "Edit segment" })
    ).toBeVisible();
  });
});
