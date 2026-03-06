import { expect, test } from "@playwright/test";
import { mockProtectedApi, seedAuthenticatedSession } from "../helpers/session";

test.describe("Warm-up plan flow", () => {
  test("creates and displays the active warm-up plan", async ({ page }) => {
    await seedAuthenticatedSession(page);
    await mockProtectedApi(page);

    await page.goto("/settings/warmup");

    await expect(page.getByText("Warm-up IP")).toBeVisible();
    await expect(page.getByText("Current plan")).toBeVisible();
    await expect(
      page
        .locator("div")
        .filter({ hasText: /^NameIP warm-up$/ })
        .first()
    ).toBeVisible();
    await expect(
      page
        .locator("div")
        .filter({ hasText: /^Limit today42$/ })
        .first()
    ).toBeVisible();

    await page.getByLabel("Name").fill("IP warm-up");
    await page.getByLabel("Start volume").fill("25");
    await page.getByLabel("Max volume").fill("500");
    await page.getByLabel("Increment %").fill("30");
    await page.getByRole("button", { name: "Save" }).click();

    await expect(page.getByText("All plans")).toBeVisible();
    await expect(page.getByRole("button", { name: "Pause" })).toBeVisible();
  });
});
