import { expect, test } from "@playwright/test";
import { seedAuthenticatedSession } from "../helpers/session";

test("drip sequence index is reachable", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.route("**/api/v1/drip-sequences**", async (route) => {
    await route.fulfill({
      status: 200,
      contentType: "application/json",
      body: JSON.stringify({
        status: "Success",
        data: [],
      }),
    });
  });

  await page.goto("/campaigns/drip");
  await expect(
    page.getByRole("heading", { name: "Drip Sequences" })
  ).toBeVisible();
});

test("drip sequence create page is reachable", async ({ page }) => {
  await seedAuthenticatedSession(page);

  await page.goto("/campaigns/drip/create");
  await expect(
    page.getByRole("heading", { name: "Create Drip Sequence" })
  ).toBeVisible();
});
